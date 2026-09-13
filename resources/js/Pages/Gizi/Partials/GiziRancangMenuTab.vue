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
    HelpCircle,
    ExternalLink,
} from "lucide-vue-next";
import {
    ALERGI_OPTIONS,
    ALLERGEN_KEYWORDS,
    REKOMENDASI_SUBSTITUSI,
    matchWordBoundary,
    checkTextMatchesAllergen,
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
        step === "formula_makanan" ||
        step === "formula-makanan" ||
        step === "formula_gizi" ||
        step === "formula-gizi" ||
        step === "formula" ||
        step === "pre_order"
    )
        return "bahan_pangan";
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
        label: "2. Formula Makanan",
        icon: Package,
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
    sub_menu_1: "",
    sub_menu_2: "",
    sub_menu_3: "",
    sub_menu_4: "",
    sub_menu_5: "",
});

// Opsi Menu Pengganti Alergi per masing-masing Sub Menu (Opsional & Dinamis)
const subMenuAlergi = ref({
    sub_menu_1: [],
    sub_menu_2: [],
    sub_menu_3: [],
    sub_menu_4: [],
    sub_menu_5: [],
});

// Sumber kelompok sasaran yang aktif & konsisten (mencegah duplikasi data PM)
const activeKelompoksSource = computed(() => {
    if (
        Array.isArray(woKelompokList.value) &&
        woKelompokList.value.length > 0
    ) {
        return woKelompokList.value;
    }
    return props.kelompokList || [];
});

// Opsi Alergi khusus yang benar-benar ada/tercatat pada data Penerima Manfaat (PM)
const availableAlergiOptions = computed(() => {
    const set = new Set();
    activeKelompoksSource.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const j = typeof item === "string" ? item : item?.jenis_alergi;
                const pk = Number(item?.porsi_kecil) || 0;
                const pb = Number(item?.porsi_besar) || 0;
                const total = typeof item === "string" ? 1 : pk + pb;
                if (j && typeof j === "string" && j.trim() && total > 0) {
                    set.add(j.trim());
                }
            });
        }
    });
    return Array.from(set);
});

// Rekapitulasi porsi & jumlah PM terdampak per jenis alergi
const pmAlergiStats = computed(() => {
    const map = {};
    activeKelompoksSource.value.forEach((k) => {
        if (Array.isArray(k.keterangan_alergi)) {
            k.keterangan_alergi.forEach((item) => {
                const jenis =
                    typeof item === "string" ? item : item?.jenis_alergi;
                if (!jenis || typeof jenis !== "string" || !jenis.trim())
                    return;
                const cleanJenis = jenis.trim();
                const pk = Number(item?.porsi_kecil) || 0;
                const pb = Number(item?.porsi_besar) || 0;
                const totalPorsi = typeof item === "string" ? 1 : pk + pb;

                if (!map[cleanJenis]) {
                    map[cleanJenis] = {
                        jenis: cleanJenis,
                        total_pm: 0,
                        pk: 0,
                        pb: 0,
                        kelompokNames: [],
                    };
                }
                map[cleanJenis].total_pm += totalPorsi;
                map[cleanJenis].pk += pk;
                map[cleanJenis].pb += pb;
                if (
                    k.nama_kelompok &&
                    !map[cleanJenis].kelompokNames.includes(k.nama_kelompok)
                ) {
                    map[cleanJenis].kelompokNames.push(k.nama_kelompok);
                }
            });
        }
    });
    return map;
});

// Cek apakah string input cocok dengan jenis alergi tertentu menggunakan word-boundary ketat
function checkTextContainsAllergen(text, allergenName) {
    return checkTextMatchesAllergen(text, allergenName);
}

// Deteksi seluruh alergen yang cocok pada sebuah teks
function detectAllergensInText(text) {
    if (!text || !text.trim()) return [];
    const detected = [];
    const stats = pmAlergiStats.value;
    for (const [jenis, data] of Object.entries(stats)) {
        if (checkTextContainsAllergen(text, jenis)) {
            detected.push(data);
        }
    }
    return detected;
}

// Deteksi Realtime pada Nama Menu Utama
const detectedAllergensMenuUtama = computed(() => {
    return detectAllergensInText(namaMenuAktif.value);
});

// Deteksi Realtime per Sub Menu 1..5
const detectedAllergensPerSubMenu = computed(() => ({
    sub_menu_1: detectAllergensInText(subMenuKomponen.value.sub_menu_1),
    sub_menu_2: detectAllergensInText(subMenuKomponen.value.sub_menu_2),
    sub_menu_3: detectAllergensInText(subMenuKomponen.value.sub_menu_3),
    sub_menu_4: detectAllergensInText(subMenuKomponen.value.sub_menu_4),
    sub_menu_5: detectAllergensInText(subMenuKomponen.value.sub_menu_5),
}));

// Tambahkan Pengganti Alergi dengan Preset Otomatis
function addPenggantiAlergiWithPreset(subKey, jenisAlergiDefault = "") {
    if (!subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey] = [];
    }
    const exists = subMenuAlergi.value[subKey].some(
        (p) => p.jenis_alergi === jenisAlergiDefault,
    );
    if (!exists) {
        subMenuAlergi.value[subKey].push({
            jenis_alergi: jenisAlergiDefault,
            menu_pengganti: "",
        });
    }
}

// Ringkasan semua alergi yang terdeteksi secara real-time di Step 1
const realTimeAllergyAlerts = computed(() => {
    const alerts = [];
    const subMenus = [
        {
            key: "sub_menu_1",
            label: "Sub Menu 1",
            val: subMenuKomponen.value.sub_menu_1,
        },
        {
            key: "sub_menu_2",
            label: "Sub Menu 2",
            val: subMenuKomponen.value.sub_menu_2,
        },
        {
            key: "sub_menu_3",
            label: "Sub Menu 3",
            val: subMenuKomponen.value.sub_menu_3,
        },
        {
            key: "sub_menu_4",
            label: "Sub Menu 4",
            val: subMenuKomponen.value.sub_menu_4,
        },
        {
            key: "sub_menu_5",
            label: "Sub Menu 5",
            val: subMenuKomponen.value.sub_menu_5,
        },
    ];

    subMenus.forEach((sm) => {
        if (sm.val && sm.val.trim()) {
            const detected = detectAllergensInText(sm.val);
            detected.forEach((al) => {
                const existing = (subMenuAlergi.value[sm.key] || []).find(
                    (p) =>
                        p.jenis_alergi === al.jenis ||
                        (p.jenis_alergi &&
                            al.jenis
                                .toLowerCase()
                                .includes(p.jenis_alergi.toLowerCase())),
                );
                alerts.push({
                    subKey: sm.key,
                    subLabel: sm.label,
                    menuName: sm.val,
                    allergen: al.jenis,
                    totalPm: al.total_pm,
                    pk: al.pk,
                    pb: al.pb,
                    hasReplacement: !!(
                        existing &&
                        existing.menu_pengganti &&
                        existing.menu_pengganti.trim()
                    ),
                    replacementName: existing?.menu_pengganti || "",
                });
            });
        }
    });
    return alerts;
});

function addPenggantiAlergi(subKey) {
    if (!subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey] = [];
    }
    // Default dikosongkan terlebih dahulu saat tombol pengganti diklik
    subMenuAlergi.value[subKey].push({
        jenis_alergi: "",
        menu_pengganti: "",
    });
}

function removePenggantiAlergi(subKey, index) {
    if (subMenuAlergi.value[subKey]) {
        subMenuAlergi.value[subKey].splice(index, 1);
        clearError(`alergi_${subKey}_${index}_jenis`);
        clearError(`alergi_${subKey}_${index}_menu`);
    }
}

// Format tampilan sub menu dengan isi dalam kurung jika ada menu pengganti alergi
function formatSubMenuDisplay(subKey) {
    const mainText = subMenuKomponen.value[subKey];
    if (!mainText) return "";
    const alergiList = subMenuAlergi.value[subKey];
    if (!Array.isArray(alergiList) || alergiList.length === 0) {
        return mainText;
    }
    const penggantiItems = alergiList
        .map((item) => {
            if (item.menu_pengganti && item.menu_pengganti.trim()) {
                return item.menu_pengganti.trim();
            }
            if (item.jenis_alergi && item.jenis_alergi.trim()) {
                return item.jenis_alergi.trim();
            }
            return null;
        })
        .filter(Boolean);

    if (penggantiItems.length === 0) return mainText;
    return `${mainText} (Pengganti: ${penggantiItems.join(", ")})`;
}

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

// Daftar Kelompok Penerima Manfaat Terjadwal untuk Work Order Ini
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
const tkpiItems = computed(() => {
    if (
        props.tkpiDatasets &&
        props.tkpiDatasets[props.selectedSource] &&
        props.tkpiDatasets[props.selectedSource].length > 0
    ) {
        return props.tkpiDatasets[props.selectedSource];
    }
    return props.tkpiList || [];
});

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

function formatGram(gram) {
    if (
        gram === null ||
        gram === undefined ||
        gram === "" ||
        isNaN(Number(gram))
    )
        return "0 g";
    const num = Number(gram);
    if (num <= 0) return "0 g";
    return `${parseFloat(num.toFixed(1))} g`;
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
    return Number(cost) || 0;
}

// ==========================================
// 2. ARSITEKTUR BLOK SUB MENU (LANGKAH 2: PEMILIHAN BAHAN PANGAN)
// ==========================================
// Daftar blok Sub Menu dinamis dari Step 1 (Sub Menu 1..5 + Varian Pengganti Alergi)
const step2SubMenuBlocks = computed(() => {
    const blocks = [];
    const keys = [
        {
            key: "sub_menu_1",
            label: "Sub Menu 1",
            color: "amber",
            defaultName: "Makanan Pokok",
            dotColor: "bg-amber-500",
            borderCard: "border-amber-200/90",
            bgHeader: "bg-amber-50/50",
        },
        {
            key: "sub_menu_2",
            label: "Sub Menu 2",
            color: "rose",
            defaultName: "Protein Hewani",
            dotColor: "bg-rose-500",
            borderCard: "border-rose-200/90",
            bgHeader: "bg-rose-50/50",
        },
        {
            key: "sub_menu_3",
            label: "Sub Menu 3",
            color: "yellow",
            defaultName: "Protein Nabati",
            dotColor: "bg-yellow-500",
            borderCard: "border-yellow-200/90",
            bgHeader: "bg-yellow-50/50",
        },
        {
            key: "sub_menu_4",
            label: "Sub Menu 4",
            color: "blue",
            defaultName: "Sayuran",
            dotColor: "bg-blue-500",
            borderCard: "border-blue-200/90",
            bgHeader: "bg-blue-50/50",
        },
        {
            key: "sub_menu_5",
            label: "Sub Menu 5",
            color: "emerald",
            defaultName: "Buah",
            dotColor: "bg-emerald-500",
            borderCard: "border-emerald-200/90",
            bgHeader: "bg-emerald-50/50",
        },
    ];

    keys.forEach((sm, idx) => {
        const menuName =
            (subMenuKomponen.value[sm.key] || "").trim() || sm.defaultName;
        // 1. Blok Utama (Normal)
        blocks.push({
            id: `${sm.key}_normal`,
            subKey: sm.key,
            subIndex: idx + 1,
            subLabel: sm.label,
            namaMenu: menuName,
            isAlergi: false,
            jenisAlergi: "",
            alergiIndex: null,
            themeColor: sm.color,
            dotColor: sm.dotColor,
            borderCard: sm.borderCard,
            bgHeader: sm.bgHeader,
        });

        // 2. Blok Pengganti Alergi Khusus (jika ditambahkan di Step 1)
        const alergiList = subMenuAlergi.value[sm.key];
        if (Array.isArray(alergiList) && alergiList.length > 0) {
            alergiList.forEach((al, alIdx) => {
                const penggantiName =
                    (al.menu_pengganti || "").trim() ||
                    `Pengganti ${al.jenis_alergi || "Alergi"}`;
                blocks.push({
                    id: `${sm.key}_alergi_${alIdx}`,
                    subKey: sm.key,
                    subIndex: idx + 1,
                    subLabel: `${sm.label} • Pengganti Alergi`,
                    namaMenu: penggantiName,
                    isAlergi: true,
                    jenisAlergi: al.jenis_alergi || "",
                    alergiIndex: alIdx,
                    themeColor: "rose",
                    dotColor: "bg-rose-500",
                    borderCard: "border-rose-300 ring-1 ring-rose-200/60",
                    bgHeader: "bg-rose-50/80",
                });
            });
        }
    });

    return blocks;
});

// State Pencarian Combobox per Blok Sub Menu
const activeComboboxBlockId = ref(null);
const searchTkpiQueryPerBlock = ref({});

function getSearchQueryForBlock(blockId) {
    return searchTkpiQueryPerBlock.value[blockId] || "";
}

function setSearchQueryForBlock(blockId, val) {
    searchTkpiQueryPerBlock.value[blockId] = val;
}

function toggleComboboxForBlock(blockId) {
    if (activeComboboxBlockId.value === blockId) {
        activeComboboxBlockId.value = null;
    } else {
        activeComboboxBlockId.value = blockId;
    }
}

function getFilteredTkpiListForBlock(blockId) {
    const list = tkpiItems.value || [];
    const q = (searchTkpiQueryPerBlock.value[blockId] || "")
        .toLowerCase()
        .trim();
    if (!q) {
        return list;
    }
    const searchTerms = q.split(/\s+/).filter(Boolean);
    return list.filter((item) => {
        const itemStr =
            `${item.nama || ""} ${item.kategori || ""} ${item.kategori_raw || ""} ${item.id || ""} ${item.code || ""}`.toLowerCase();
        return searchTerms.every((term) => itemStr.includes(term));
    });
}

function selectTkpiItemForBlock(master, block) {
    if (!master || !block) return;
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
        sub_menu_block_id: block.id,
        sub_menu_key: block.subKey,
        nama_sub_menu: block.namaMenu,
        kategori: master.kategori || "Lainnya",
        nama: master.nama,
        nama_po: "",
        tipe_porsi: block.isAlergi ? "alergi" : "normal",
        jenis_alergi: block.isAlergi ? block.jenisAlergi || "" : "",
        gram_pk: 0,
        gram_pb: 0,
        bdd: bddValue,
        buffer: 0,
        harga_master: null,
        harga_aktual: null,
        alergen: master.alergen || "",
        keterangan: "",
        tkpi: master,
    });

    activeComboboxBlockId.value = null;
    searchTkpiQueryPerBlock.value[block.id] = "";
    if (validationErrors.value.selectedBahan) {
        delete validationErrors.value.selectedBahan;
    }
}

// Navigasi & Highlight Baris Bahan Pangan dari Tabel Rekapitulasi
function scrollToSubMenuBahan(item) {
    if (!item) return;

    if (buatMenuSubTab.value !== "bahan_pangan") {
        buatMenuSubTab.value = "bahan_pangan";
        scrollToTopSection();
    }

    nextTick(() => {
        const targetRowId = `row-bahan-${item.originalIndex}`;
        const targetBlockId = item.sub_menu_block_id
            ? `card-block-${item.sub_menu_block_id}`
            : null;

        let el = document.getElementById(targetRowId);
        if (!el && targetBlockId) {
            el = document.getElementById(targetBlockId);
        }

        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "center" });
            el.classList.add(
                "ring-4",
                "ring-primary",
                "bg-amber-100/70",
                "transition-all",
                "duration-500",
            );
            const inputPo = el.querySelector('input[type="text"]');
            if (inputPo) {
                inputPo.focus();
            }
            setTimeout(() => {
                el.classList.remove(
                    "ring-4",
                    "ring-primary",
                    "bg-amber-100/70",
                );
            }, 2500);
        }
    });
}

// Opsi Alergi yang tersedia di data Penerima Manfaat untuk dropdown Peruntukan Porsi
const availableAlergiPmOptions = computed(() => {
    const list =
        rekapAlergiDetailPm.value.length > 0
            ? rekapAlergiDetailPm.value
            : rekapAlergiMasterPm.value;

    const options = [];
    const addedSet = new Set();

    // Hanya masukkan alergi yang benar-benar ada siswanya pada data Penerima Manfaat (total > 0)
    list.forEach((a) => {
        const total = Number(a.total || 0);
        if (a.jenis_alergi && total > 0 && !addedSet.has(a.jenis_alergi)) {
            addedSet.add(a.jenis_alergi);
            options.push({
                jenis_alergi: a.jenis_alergi,
                total: total,
                pk: Number(a.porsi_kecil || 0),
                pb: Number(a.porsi_besar || 0),
                hasPm: true,
            });
        }
    });

    // Pertahankan alergi yang sudah terpilih pada bahan yang sedang diedit (agar tidak hilang)
    selectedBahanList.value.forEach((b) => {
        if (
            b.tipe_porsi === "alergi" &&
            b.jenis_alergi &&
            !addedSet.has(b.jenis_alergi)
        ) {
            addedSet.add(b.jenis_alergi);
            options.push({
                jenis_alergi: b.jenis_alergi,
                total: b.totalTargetCount || 0,
                pk: b.targetPKCount || 0,
                pb: b.targetPBCount || 0,
                hasPm: (b.totalTargetCount || 0) > 0,
            });
        }
    });

    return options;
});

function getPeruntukanPorsiValue(bahan) {
    if (!bahan) return "normal";
    if (bahan.tipe_porsi === "alergi") {
        return `alergi:${bahan.jenis_alergi || ""}`;
    }
    return "normal";
}

function handlePeruntukanPorsiChange(originalIndex, eventVal) {
    const b = selectedBahanList.value[originalIndex];
    if (!b) return;
    if (eventVal === "normal") {
        b.tipe_porsi = "normal";
        b.jenis_alergi = "";
    } else if (eventVal && eventVal.startsWith("alergi:")) {
        b.tipe_porsi = "alergi";
        b.jenis_alergi = eventVal.replace("alergi:", "");
    }
}

// Helper filter bahan calculations untuk satu blok tertentu
function getBahanForBlock(blockId) {
    return bahanCalculations.value.filter((b) => {
        if (b.sub_menu_block_id) {
            return b.sub_menu_block_id === blockId;
        }
        // Fallback backward compatibility: masukkan ke blok normal pertama jika belum ada block_id
        const firstBlock = step2SubMenuBlocks.value[0];
        return firstBlock && firstBlock.id === blockId;
    });
}

// Helper kalkulasi ringkasan subtotal per blok
function getBlockSummary(blockId) {
    const items = getBahanForBlock(blockId);
    const totalNetKg = items.reduce((sum, it) => sum + (it.totalNetKg || 0), 0);
    const totalGrossKg = items.reduce(
        (sum, it) => sum + (it.totalGrossKg || 0),
        0,
    );
    const totalCostMaster = items.reduce(
        (sum, it) => sum + (it.subtotalMaster || 0),
        0,
    );
    const totalCostAktual = items.reduce(
        (sum, it) => sum + (it.subtotalAktual || 0),
        0,
    );
    return {
        count: items.length,
        totalNetKg,
        totalGrossKg,
        totalCostMaster,
        totalCostAktual,
    };
}

// Helper untuk mendapatkan nama teks Sub Menu dari objek bahan
function getSubMenuNameFromBahan(it) {
    const info = getSubMenuLabelForBahan(it);
    return info.label || "Sub Menu";
}

// Helper untuk identifikasi nama Sub Menu asal pada Rekapitulasi Bahan Pangan
function getSubMenuLabelForBahan(it) {
    if (it.sub_menu_block_id) {
        const found = step2SubMenuBlocks.value.find(
            (b) => b.id === it.sub_menu_block_id,
        );
        if (found) {
            return {
                label: found.subLabel,
                namaMenu: found.namaMenu,
                isAlergi: found.isAlergi,
                jenisAlergi: found.jenisAlergi,
                badgeClass: found.isAlergi
                    ? "bg-rose-100 text-rose-800 border-rose-300"
                    : "bg-slate-100 text-slate-800 border-slate-300",
            };
        }
    }
    const keyMap = {
        sub_menu_1: "Sub Menu 1",
        sub_menu_2: "Sub Menu 2",
        sub_menu_3: "Sub Menu 3",
        sub_menu_4: "Sub Menu 4",
        sub_menu_5: "Sub Menu 5",
    };
    const label = keyMap[it.sub_menu_key] || "Sub Menu 1";
    const namaMenu =
        it.nama_sub_menu || subMenuKomponen.value[it.sub_menu_key] || "";
    const isAlergi = it.tipe_porsi === "alergi";
    return {
        label: isAlergi ? `${label} • Alergi` : label,
        namaMenu: namaMenu || "-",
        isAlergi,
        jenisAlergi: it.jenis_alergi || "",
        badgeClass: isAlergi
            ? "bg-rose-100 text-rose-800 border-rose-300"
            : "bg-slate-100 text-slate-800 border-slate-300",
    };
}

function scrollToRekapitulasi() {
    nextTick(() => {
        const el = document.getElementById("tabel-rekapitulasi-bahan-pangan");
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });
}

function onGlobalWindowClick() {
    activeComboboxBlockId.value = null;
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

function scrollToTopSection() {
    nextTick(() => {
        const topEl =
            document.getElementById("rancang-menu-top-anchor") ||
            document.getElementById("gizi-rancang-menu-container");
        if (topEl) {
            topEl.scrollIntoView({ behavior: "smooth", block: "start" });
        }

        const scrollableContainers =
            document.querySelectorAll(".overflow-y-auto");
        scrollableContainers.forEach((el) => {
            el.scrollTo({ top: 0, behavior: "smooth" });
        });

        window.scrollTo({ top: 0, behavior: "smooth" });
        if (document.documentElement) {
            document.documentElement.scrollTo({ top: 0, behavior: "smooth" });
        }
        if (document.body) {
            document.body.scrollTo({ top: 0, behavior: "smooth" });
        }
    });
}

watch(buatMenuSubTab, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        scrollToTopSection();
    }
});

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

    // Validasi menu pengganti alergi (jika ditambahkan, jenis dan nama menu pengganti TIDAK BOLEH KOSONG)
    [
        "sub_menu_1",
        "sub_menu_2",
        "sub_menu_3",
        "sub_menu_4",
        "sub_menu_5",
    ].forEach((subKey, idx) => {
        const list = subMenuAlergi.value[subKey];
        if (Array.isArray(list) && list.length > 0) {
            list.forEach((item, itemIdx) => {
                const subLabel = `Sub Menu ${idx + 1}`;
                if (!item.jenis_alergi || !item.jenis_alergi.trim()) {
                    errs[`alergi_${subKey}_${itemIdx}_jenis`] =
                        `Pilih jenis alergi untuk ${subLabel}.`;
                }
                if (!item.menu_pengganti || !item.menu_pengganti.trim()) {
                    errs[`alergi_${subKey}_${itemIdx}_menu`] =
                        `Menu pengganti ${subLabel} wajib diisi.`;
                }
            });
        }
    });

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
            "Wajib memilih dan menambahkan minimal 1 bahan pangan dari Database.";
    }

    selectedBahanList.value.forEach((b, i) => {
        if (!b.nama_po || !b.nama_po.trim()) {
            errs["bahan_" + i + "_nama_po"] = "Nama di PO wajib diisi.";
        }
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
                "Gramasi (PK atau PB) wajib diisi > 0";
        } else if (pkVal < 0 || pbVal < 0) {
            errs["bahan_" + i + "_gram"] = "Gramasi tidak boleh kurang dari 0";
        }

        const hargaVal = Number(b.harga_master) || 0;
        if (
            b.harga_master === null ||
            b.harga_master === undefined ||
            b.harga_master === "" ||
            hargaVal <= 0
        ) {
            errs["bahan_" + i + "_harga"] = "Harga per Kg wajib diisi > 0";
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
        scrollToTopSection();
        return;
    }

    // Step 1 WAJIB VALID untuk dapat berpindah ke Step 2 atau Step 3
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        scrollToTopSection();
        return;
    }

    if (
        targetTab === "bahan_pangan" ||
        targetTab === "pre_order" ||
        targetTab === "formula-gizi" ||
        targetTab === "formula_gizi"
    ) {
        buatMenuSubTab.value = "bahan_pangan";
        scrollToTopSection();
        return;
    }

    if (
        targetTab === "order" ||
        targetTab === "review" ||
        targetTab === "review_pengajuan" ||
        targetTab === "pembelian_bahan" ||
        targetTab === "pembelian-bahan"
    ) {
        if (!validateStep2()) {
            buatMenuSubTab.value = "bahan_pangan";
            scrollToTopSection();
            return;
        }
        buatMenuSubTab.value = "order";
        scrollToTopSection();
        return;
    }

    buatMenuSubTab.value = normalizeStep(targetTab);
}

function handleMulaiFormulasiWo() {
    if (!validateStep1()) {
        return;
    }
    buatMenuSubTab.value = "bahan_pangan";
    scrollToTopSection();
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
        scrollToTopSection();
        return;
    }
    if (!validateStep2()) {
        return;
    }
    buatMenuSubTab.value = "order";
    scrollToTopSection();
}

function handleLanjutStep4() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        scrollToTopSection();
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        scrollToTopSection();
        return;
    }
    if (!validateStep3()) {
        return;
    }
    buatMenuSubTab.value = "order";
    scrollToTopSection();
}

function simpanDraftStep3() {
    if (!validateStep1()) {
        buatMenuSubTab.value = "work_order";
        scrollToTopSection();
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        scrollToTopSection();
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
        scrollToTopSection();
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

// Total siswa yang BENAR-BENAR terdampak alergi di menu ini (berdasarkan jenis alergi aktif di bahan)
const totalTerdampakAlergi = computed(() => {
    return activeAlergiFoodCostList.value.reduce(
        (acc, al) => acc + (Number(al.total_siswa) || 0),
        0,
    );
});
const totalTerdampakPK = computed(() =>
    activeAlergiFoodCostList.value.reduce((acc, al) => acc + (Number(al.siswa_pk) || 0), 0)
);
const totalTerdampakPB = computed(() =>
    activeAlergiFoodCostList.value.reduce((acc, al) => acc + (Number(al.siswa_pb) || 0), 0)
);

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

    const activeAlergi = rekapAlergiDetailPm.value;
    const conflicts = [];

    activeAlergi.forEach((al) => {
        if (checkTextMatchesAllergen(combinedText, al.jenis_alergi)) {
            const rekomendasiBahan =
                REKOMENDASI_SUBSTITUSI[al.jenis_alergi] ||
                "Bahan pangan sumber protein/karbohidrat alternatif non-alergen";

            conflicts.push({
                ...al,
                keyword: al.jenis_alergi,
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
    if (!b || !jenisAlergi) return false;
    const namaBahan = (b.nama || (b.tkpi && b.tkpi.nama) || "").trim();
    const alergenField = (b.alergen || (b.tkpi && b.tkpi.alergen) || "").trim();
    const kategori = (b.kategori || (b.tkpi && b.tkpi.kategori) || "").trim();
    const combinedText = `${namaBahan} ${alergenField} ${kategori}`.trim();

    return checkTextMatchesAllergen(combinedText, jenisAlergi);
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
                matchWordBoundary(cleanNoPrefix, rCleanNoPrefix) ||
                matchWordBoundary(rCleanNoPrefix, cleanNoPrefix)
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

function getSubMenuSortWeight(b) {
    const keyMap = {
        sub_menu_1: 1,
        sub_menu_2: 2,
        sub_menu_3: 3,
        sub_menu_4: 4,
        sub_menu_5: 5,
    };
    let weight = 99;
    if (b.sub_menu_key && keyMap[b.sub_menu_key]) {
        weight = keyMap[b.sub_menu_key];
    } else if (b.sub_menu_block_id) {
        for (const [k, v] of Object.entries(keyMap)) {
            if (b.sub_menu_block_id.startsWith(k)) {
                weight = v;
                break;
            }
        }
    }
    const isAlergi =
        b.tipe_porsi === "alergi" ||
        (b.sub_menu_block_id && b.sub_menu_block_id.includes("_alergi_"));
    return weight * 10 + (isAlergi ? 1 : 0);
}

// Kalkulasi Detail per Bahan (Gross Weight, Biaya Draft Master, Biaya Aktual Akuntan)
const bahanCalculations = computed(() => {
    return selectedBahanList.value
        .map((b, idx) => {
            const tkpi =
                b.tkpi && b.tkpi.energi !== undefined
                    ? b.tkpi
                    : tkpiItems.value.find(
                          (i) =>
                              (b.tkpi_id &&
                                  (i.id === b.tkpi_id ||
                                      i.code === b.tkpi_id)) ||
                              (b.id && (i.id === b.id || i.code === b.id)) ||
                              (b.code &&
                                  (i.id === b.code || i.code === b.code)) ||
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

            // Berat Kotor per Porsi (Gross Gram) berdasarkan BDD
            const bddFactor = (bdd || 100) / 100;
            const grossGramPK =
                bddFactor > 0 ? (Number(b.gram_pk) || 0) / bddFactor : 0;
            const grossGramPB =
                bddFactor > 0 ? (Number(b.gram_pb) || 0) / bddFactor : 0;

            const netKgPK = ((Number(b.gram_pk) || 0) * targetPKCount) / 1000;
            const netKgPB = ((Number(b.gram_pb) || 0) * targetPBCount) / 1000;
            const totalNetKg = netKgPK + netKgPB;

            // Biaya PO
            let subtotalMaster = Math.round(
                totalGrossKg * (b.harga_master || 0),
            );
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
                originalIndex: idx,
                nama_po: b.nama_po || "",
                tkpi,
                isAlergi,
                targetPKCount,
                targetPBCount,
                totalTargetCount: targetPKCount + targetPBCount,
                alergiDampakList,
                grossGramPK,
                grossGramPB,
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
        })
        .sort((a, b) => {
            const orderA = getSubMenuSortWeight(a);
            const orderB = getSubMenuSortWeight(b);
            if (orderA !== orderB) {
                return orderA - orderB;
            }
            return (a.originalIndex ?? 0) - (b.originalIndex ?? 0);
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
    const list = tkpiItems.value || [];
    if (!searchTkpiGiziQuery.value) {
        return list;
    }
    const q = searchTkpiGiziQuery.value.toLowerCase().trim();
    const searchTerms = q.split(/\s+/).filter(Boolean);
    return list.filter((it) => {
        const itemStr =
            `${it.nama || ""} ${it.kategori || ""} ${it.code || ""}`.toLowerCase();
        return searchTerms.every((term) => itemStr.includes(term));
    });
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
        keterangan: b.keterangan || "",
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

// Active Bahan List for Nutrition (Supports Step 2 raw materials calculation and Step 3 cooked formula calculation)
const activeBahanListForNutrisi = computed(() => {
    return bahanCalculations.value;
});

// Helper Status Badge AKG Real-Time
function getAkgStatusBadge(nutritionObj, isPB = false) {
    if (!nutritionObj) {
        return {
            label: "Belum Ada Formula",
            badgeClass:
                "bg-slate-100 text-slate-600 border-slate-200 font-extrabold text-[10px]",
        };
    }
    const energi = Number(nutritionObj.energi) || 0;
    if (energi === 0) {
        return {
            label: "Belum Ada Formula",
            badgeClass:
                "bg-slate-100 text-slate-600 border-slate-200 font-extrabold text-[10px]",
        };
    }
    const minTarget = isPB ? 650 : 450;
    const maxTarget = isPB ? 800 : 550;

    if (energi >= minTarget && energi <= maxTarget) {
        return {
            label: "✓ MEMENUHI STANDAR AKG BGN",
            badgeClass:
                "bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]",
        };
    } else if (energi < minTarget) {
        return {
            label: "⚠ DI BAWAH STANDAR AKG",
            badgeClass:
                "bg-amber-50 text-amber-800 border-amber-300 font-extrabold text-[10px]",
        };
    } else {
        return {
            label: "⚡ MELEBIHI STANDAR AKG",
            badgeClass:
                "bg-teal-50 text-teal-800 border-teal-300 font-extrabold text-[10px]",
        };
    }
}

// ==========================================
// 3. KALKULASI HASIL AKG
// ==========================================
const akgResultPKNormal = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    activeBahanListForNutrisi.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            if (b.nutrisiPK) {
                res.energi += b.nutrisiPK.energi || 0;
                res.protein += b.nutrisiPK.protein || 0;
                res.lemak += b.nutrisiPK.lemak || 0;
                res.karbohidrat += b.nutrisiPK.karbohidrat || 0;
                res.serat += b.nutrisiPK.serat || 0;
            }
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
    activeBahanListForNutrisi.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            if (b.nutrisiPB) {
                res.energi += b.nutrisiPB.energi || 0;
                res.protein += b.nutrisiPB.protein || 0;
                res.lemak += b.nutrisiPB.lemak || 0;
                res.karbohidrat += b.nutrisiPB.karbohidrat || 0;
                res.serat += b.nutrisiPB.serat || 0;
            }
        });
    return {
        energi: Number(res.energi.toFixed(1)),
        protein: Number(res.protein.toFixed(1)),
        lemak: Number(res.lemak.toFixed(1)),
        karbohidrat: Number(res.karbohidrat.toFixed(1)),
        serat: Number(res.serat.toFixed(1)),
    };
});

// Seluruh Jenis Alergi yang Telah Ditentukan / Aktif (dari Blok Sub Menu Pengganti Alergi atau Bahan Alergi)
const determinedActiveAlergiTypes = computed(() => {
    const types = new Set();

    // 1. Dari Blok Sub Menu di Step 2
    if (step2SubMenuBlocks.value && Array.isArray(step2SubMenuBlocks.value)) {
        step2SubMenuBlocks.value.forEach((b) => {
            if (b.isAlergi && b.jenisAlergi && b.jenisAlergi.trim()) {
                types.add(b.jenisAlergi.trim());
            }
        });
    }

    // 2. Dari bahan yang sudah ber-tipe alergi
    if (selectedBahanList.value && Array.isArray(selectedBahanList.value)) {
        selectedBahanList.value.forEach((b) => {
            if (
                b.tipe_porsi === "alergi" &&
                b.jenis_alergi &&
                b.jenis_alergi.trim()
            ) {
                types.add(b.jenis_alergi.trim());
            }
        });
    }

    return Array.from(types);
});

// AKG Varian Khusus Alergi (Dikelompokkan Spesifik per Jenis Alergi yang Telah Ditentukan / Dipilih)
const activeAlergiAkgList = computed(() => {
    const activeTypes = determinedActiveAlergiTypes.value;
    if (activeTypes.length === 0) {
        return [];
    }

    const currentList = activeBahanListForNutrisi.value;
    const result = [];
    activeTypes.forEach((jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : 0;

        // Jika jenis alergi ini tidak ada siswa di PM (0 siswa), jangan tampilkan card evaluasinya
        if (jmlTotal === 0) {
            return;
        }

        // Bahan substitusi khusus alergi ini
        const bahans = currentList.filter(
            (b) => b.tipe_porsi === "alergi" && b.jenis_alergi === jenis,
        );

        // Ambil bahan normal yang aman (tidak mengandung alergen terkait)
        const bahanNormalSafe = currentList.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
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
const targetSasaranNormal = computed(() => {
    let pk = totalPK.value || 0;
    let pb = totalPB.value || 0;

    const activeAlergiTypes = determinedActiveAlergiTypes.value;

    activeAlergiTypes.forEach((jenis) => {
        const detailPm = findAlergiDetail(jenis);
        if (detailPm) {
            pk = Math.max(0, pk - (Number(detailPm.porsi_kecil) || 0));
            pb = Math.max(0, pb - (Number(detailPm.porsi_besar) || 0));
        }
    });

    return {
        pk,
        pb,
        total: pk + pb,
    };
});

const totalFoodCostPKNormal = computed(() => {
    return bahanCalculations.value
        .filter((item) => item.tipe_porsi !== "alergi")
        .reduce((acc, item) => acc + (Number(item.costPK) || 0), 0);
});

const totalFoodCostPBNormal = computed(() => {
    return bahanCalculations.value
        .filter((item) => item.tipe_porsi !== "alergi")
        .reduce((acc, item) => acc + (Number(item.costPB) || 0), 0);
});

// Food Cost per Porsi Khusus Varian Alergi (Disesuaikan dengan bahan aman + substitusi, otomatis tampil jika varian alergi ditentukan)
const activeAlergiFoodCostList = computed(() => {
    const activeTypes = determinedActiveAlergiTypes.value;
    if (activeTypes.length === 0) return [];

    const result = [];
    activeTypes.forEach((jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : 0;

        // Ambil bahan substitusi khusus alergi ini jika ada
        const substitusiBahans = bahanCalculations.value.filter(
            (b) => b.tipe_porsi === "alergi" && b.jenis_alergi === jenis,
        );

        // Ambil bahan normal yang aman (tidak mengandung alergen terkait)
        const bahanNormalSafe = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            return !isBahanContainsAlergen(b, jenis);
        });

        // Ambil bahan normal yang dieliminasi karena mengandung alergen
        const bahanNormalDikeluarkan = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            return isBahanContainsAlergen(b, jenis);
        });

        const allItems = [...bahanNormalSafe, ...substitusiBahans];
        const costPK = allItems.reduce(
            (acc, it) => acc + (Number(it.costPK) || 0),
            0,
        );
        const costPB = allItems.reduce(
            (acc, it) => acc + (Number(it.costPB) || 0),
            0,
        );

        const statusPK = getFoodCostStatusInfo(costPK, 8000);
        const statusPB = getFoodCostStatusInfo(costPB, 10000);

        result.push({
            jenis_alergi: jenis,
            siswa_pk: jmlPk,
            siswa_pb: jmlPb,
            total_siswa: jmlTotal,
            cost_pk: costPK,
            cost_pb: costPB,
            status_pk: statusPK,
            status_pb: statusPB,
            total_biaya: jmlPk * costPK + jmlPb * costPB,
            bahan_normal_aman: bahanNormalSafe,
            bahan_substitusi: substitusiBahans,
            bahan_dikeluarkan: bahanNormalDikeluarkan,
            all_included_items: allItems,
        });
    });

    return result;
});

// Config Sub Menu untuk Kalkulasi Breakdown Food Cost & AKG
const subMenuKeysConfig = [
    {
        key: "sub_menu_1",
        label: "Sub Menu 1",
        defaultName: "Makanan Pokok",
        dotColor: "bg-amber-500",
        badgeColor: "bg-amber-50 text-amber-900 border-amber-200",
    },
    {
        key: "sub_menu_2",
        label: "Sub Menu 2",
        defaultName: "Protein Hewani",
        dotColor: "bg-rose-500",
        badgeColor: "bg-rose-50 text-rose-900 border-rose-200",
    },
    {
        key: "sub_menu_3",
        label: "Sub Menu 3",
        defaultName: "Protein Nabati",
        dotColor: "bg-yellow-500",
        badgeColor: "bg-yellow-50 text-yellow-900 border-yellow-200",
    },
    {
        key: "sub_menu_4",
        label: "Sub Menu 4",
        defaultName: "Sayuran",
        dotColor: "bg-blue-500",
        badgeColor: "bg-blue-50 text-blue-900 border-blue-200",
    },
    {
        key: "sub_menu_5",
        label: "Sub Menu 5",
        defaultName: "Buah",
        dotColor: "bg-emerald-500",
        badgeColor: "bg-emerald-50 text-emerald-900 border-emerald-200",
    },
];

// Rincian Food Cost per Sub Menu untuk Porsi Normal
const foodCostSubMenuNormal = computed(() => {
    const totalPK = totalFoodCostPKNormal.value || 0;
    const totalPB = totalFoodCostPBNormal.value || 0;

    return subMenuKeysConfig.map((sm) => {
        const menuName =
            (subMenuKomponen.value[sm.key] || "").trim() || sm.defaultName;
        const items = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            if (b.sub_menu_key) return b.sub_menu_key === sm.key;
            if (b.sub_menu_block_id)
                return b.sub_menu_block_id.startsWith(sm.key);
            return false;
        });

        const costPK = items.reduce(
            (sum, it) => sum + (Number(it.costPK) || 0),
            0,
        );
        const costPB = items.reduce(
            (sum, it) => sum + (Number(it.costPB) || 0),
            0,
        );

        const percentPK = totalPK > 0 ? (costPK / totalPK) * 100 : 0;
        const percentPB = totalPB > 0 ? (costPB / totalPB) * 100 : 0;

        return {
            key: sm.key,
            label: sm.label,
            nama_menu: menuName,
            dotColor: sm.dotColor,
            badgeColor: sm.badgeColor,
            items_count: items.length,
            cost_pk: costPK,
            cost_pb: costPB,
            percent_pk: percentPK,
            percent_pb: percentPB,
        };
    });
});

// Helper untuk mendapatkan Rincian Food Cost per Sub Menu pada Varian Alergi tertentu
function getFoodCostSubMenuForAlergi(jenisAlergi) {
    const detail = activeAlergiFoodCostList.value.find(
        (a) => a.jenis_alergi === jenisAlergi,
    );
    const totalPK = detail ? detail.cost_pk : 0;
    const totalPB = detail ? detail.cost_pb : 0;

    return subMenuKeysConfig.map((sm) => {
        const normalMenuName =
            (subMenuKomponen.value[sm.key] || "").trim() || sm.defaultName;

        // Ambil bahan substitusi khusus jenis alergi ini di sub menu ini
        const substitusiBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi !== "alergi" || b.jenis_alergi !== jenisAlergi)
                return false;
            if (b.sub_menu_key) return b.sub_menu_key === sm.key;
            if (b.sub_menu_block_id)
                return b.sub_menu_block_id.startsWith(sm.key);
            return false;
        });

        // Ambil bahan normal yang aman di sub menu ini
        const normalSafeBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            const matchKey = b.sub_menu_key
                ? b.sub_menu_key === sm.key
                : b.sub_menu_block_id && b.sub_menu_block_id.startsWith(sm.key);
            if (!matchKey) return false;
            return !isBahanContainsAlergen(b, jenisAlergi);
        });

        // Ambil bahan normal yang dieliminasi karena alergi
        const normalDikeluarkanBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            const matchKey = b.sub_menu_key
                ? b.sub_menu_key === sm.key
                : b.sub_menu_block_id && b.sub_menu_block_id.startsWith(sm.key);
            if (!matchKey) return false;
            return isBahanContainsAlergen(b, jenisAlergi);
        });

        const allItems = [...normalSafeBahans, ...substitusiBahans];
        const costPK = allItems.reduce(
            (sum, it) => sum + (Number(it.costPK) || 0),
            0,
        );
        const costPB = allItems.reduce(
            (sum, it) => sum + (Number(it.costPB) || 0),
            0,
        );

        const isSubstituted = substitusiBahans.length > 0;
        const isEliminated = normalDikeluarkanBahans.length > 0;

        let displayName = normalMenuName;
        if (isSubstituted) {
            const foundBlock = step2SubMenuBlocks.value.find(
                (b) =>
                    b.isAlergi &&
                    b.jenisAlergi === jenisAlergi &&
                    b.subKey === sm.key,
            );
            if (foundBlock && foundBlock.namaMenu) {
                displayName = foundBlock.namaMenu;
            }
        }

        const percentPK = totalPK > 0 ? (costPK / totalPK) * 100 : 0;
        const percentPB = totalPB > 0 ? (costPB / totalPB) * 100 : 0;

        return {
            key: sm.key,
            label: sm.label,
            nama_menu: displayName,
            normal_menu_name: normalMenuName,
            is_substituted: isSubstituted,
            is_eliminated: isEliminated,
            dotColor: sm.dotColor,
            badgeColor: sm.badgeColor,
            items_count: allItems.length,
            substitusi_count: substitusiBahans.length,
            dikeluarkan_names: normalDikeluarkanBahans.map((b) => b.nama),
            cost_pk: costPK,
            cost_pb: costPB,
            percent_pk: percentPK,
            percent_pb: percentPB,
        };
    });
}

function formatRupiah(num) {
    const val = Number(num);
    if (!val || isNaN(val) || val <= 0) return "Rp 0";

    // Jika integer / bilangan bulat
    if (Number.isInteger(val)) {
        return "Rp " + val.toLocaleString("id-ID");
    }

    // Jika bernilai pecahan sangat kecil (< 1)
    if (val < 1) {
        const decimals = val < 0.01 ? 3 : 2;
        return (
            "Rp " +
            val.toLocaleString("id-ID", {
                minimumFractionDigits: 1,
                maximumFractionDigits: decimals,
            })
        );
    }

    // Jika bernilai >= 1 dengan pecahan desimal
    return (
        "Rp " +
        val.toLocaleString("id-ID", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })
    );
}

function formatHargaInput(val) {
    if (val === null || val === undefined || val === "") return "";
    const num = Number(val);
    if (isNaN(num)) return "";
    return "Rp " + num.toLocaleString("id-ID");
}

function handleHargaMasterInput(index, event) {
    const rawVal = event.target.value || "";
    const digits = rawVal.replace(/\D/g, "");
    if (!digits) {
        if (selectedBahanList.value[index]) {
            selectedBahanList.value[index].harga_master = null;
        }
        event.target.value = "";
        return;
    }
    const num = parseInt(digits, 10);
    if (selectedBahanList.value[index]) {
        selectedBahanList.value[index].harga_master = num;
    }
    event.target.value = "Rp " + num.toLocaleString("id-ID");
}

function getFoodCostStatusInfo(cost, pagu) {
    const val = Number(cost) || 0;
    const maxPagu = Number(pagu) || 0;

    if (val === 0) {
        return {
            label: "Belum Ada Bahan",
            badgeClass: "bg-slate-100 text-slate-600 border-slate-200",
            cardClass: "border-slate-200 bg-slate-50/20",
            barClass: "bg-slate-200",
            percentage: "0%",
        };
    }

    if (val > maxPagu) {
        return {
            label: "⚠ Melebihi Pagu",
            badgeClass: "bg-rose-100 text-rose-800 border-rose-300",
            cardClass: "border-rose-200 bg-rose-50/20",
            barClass: "bg-rose-500",
            percentage: "100%",
        };
    }

    if (val === maxPagu) {
        return {
            label: "✓ Sesuai Pagu",
            badgeClass: "bg-emerald-100 text-emerald-800 border-emerald-300",
            cardClass: "border-emerald-200 bg-emerald-50/20",
            barClass: "bg-emerald-500",
            percentage: "100%",
        };
    }

    const thresholdMendekati = maxPagu * 0.85;
    const pct = Math.min((val / maxPagu) * 100, 100).toFixed(1) + "%";

    if (val >= thresholdMendekati) {
        return {
            label: "⚡ Mendekati Pagu",
            badgeClass: "bg-teal-100 text-teal-800 border-teal-300",
            cardClass: "border-teal-200 bg-teal-50/20",
            barClass: "bg-teal-500",
            percentage: pct,
        };
    }

    return {
        label: "ℹ Kurang dari Pagu",
        badgeClass: "bg-amber-100 text-amber-800 border-amber-300",
        cardClass: "border-amber-200 bg-amber-50/20",
        barClass: "bg-amber-500",
        percentage: pct,
    };
}

const foodCostPKStatus = computed(() =>
    getFoodCostStatusInfo(totalFoodCostPKNormal.value, 8000),
);
const foodCostPBStatus = computed(() =>
    getFoodCostStatusInfo(totalFoodCostPBNormal.value, 10000),
);

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
        sub_menu_alergi: subMenuAlergi.value,
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
            sub_menu_key: b.sub_menu_key || null,
            sub_menu_block_id: b.sub_menu_block_id || null,
            nama_sub_menu: b.nama_sub_menu || null,
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
            keterangan: b.keterangan || null,
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
        scrollToTopSection();
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
        scrollToTopSection();
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
        scrollToTopSection();
        return;
    }
    if (!validateStep2()) {
        buatMenuSubTab.value = "bahan_pangan";
        scrollToTopSection();
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

            if (wo.sub_menu_alergi && typeof wo.sub_menu_alergi === "object") {
                subMenuAlergi.value = {
                    sub_menu_1: Array.isArray(wo.sub_menu_alergi.sub_menu_1)
                        ? wo.sub_menu_alergi.sub_menu_1
                        : [],
                    sub_menu_2: Array.isArray(wo.sub_menu_alergi.sub_menu_2)
                        ? wo.sub_menu_alergi.sub_menu_2
                        : [],
                    sub_menu_3: Array.isArray(wo.sub_menu_alergi.sub_menu_3)
                        ? wo.sub_menu_alergi.sub_menu_3
                        : [],
                    sub_menu_4: Array.isArray(wo.sub_menu_alergi.sub_menu_4)
                        ? wo.sub_menu_alergi.sub_menu_4
                        : [],
                    sub_menu_5: Array.isArray(wo.sub_menu_alergi.sub_menu_5)
                        ? wo.sub_menu_alergi.sub_menu_5
                        : [],
                };
            } else {
                subMenuAlergi.value = {
                    sub_menu_1: [],
                    sub_menu_2: [],
                    sub_menu_3: [],
                    sub_menu_4: [],
                    sub_menu_5: [],
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
                        sub_menu_key: it.sub_menu_key || null,
                        sub_menu_block_id: it.sub_menu_block_id || null,
                        nama_sub_menu: it.nama_sub_menu || null,
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
                            it.harga_master !== undefined &&
                            it.harga_master !== null &&
                            it.harga_master !== ""
                                ? Number(it.harga_master)
                                : null,
                        harga_aktual:
                            it.harga_aktual !== undefined &&
                            it.harga_aktual !== null &&
                            it.harga_aktual !== ""
                                ? Number(it.harga_aktual)
                                : it.harga_master !== undefined &&
                                    it.harga_master !== null &&
                                    it.harga_master !== ""
                                  ? Number(it.harga_master)
                                  : null,
                        keterangan: it.keterangan || "",
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
    <div id="rancang-menu-top-anchor" class="space-y-6 scroll-mt-24">
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
                        {{ formatSubMenuDisplay("sub_menu_1") }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_2"
                        class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-200 border border-rose-400/30"
                    >
                        {{ formatSubMenuDisplay("sub_menu_2") }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_3"
                        class="px-2 py-0.5 rounded-md bg-yellow-500/20 text-yellow-200 border border-yellow-400/30"
                    >
                        {{ formatSubMenuDisplay("sub_menu_3") }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_4"
                        class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-200 border border-blue-400/30"
                    >
                        {{ formatSubMenuDisplay("sub_menu_4") }}
                    </span>
                    <span
                        v-if="subMenuKomponen.sub_menu_5"
                        class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-200 border border-emerald-400/30"
                    >
                        {{ formatSubMenuDisplay("sub_menu_5") }}
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
                    <span
                        v-if="activeAlergiFoodCostList.length > 0"
                        class="inline-flex items-center gap-1.5 flex-wrap"
                        >•
                        <strong class="text-rose-300"
                            >Alergi Terdampak ({{
                                activeAlergiFoodCostList.reduce(
                                    (s, a) => s + (a.total_siswa || 0),
                                    0,
                                )
                            }}
                            Porsi):</strong
                        >
                        <span
                            v-for="al in activeAlergiFoodCostList"
                            :key="al.jenis_alergi"
                            class="px-2 py-0.5 rounded-md bg-rose-500/25 text-rose-200 border border-rose-400/40 text-[11px] font-bold"
                        >
                            {{ al.jenis_alergi }}: {{ al.total_siswa }} Porsi
                            (PK: {{ al.siswa_pk }}, PB: {{ al.siswa_pb }})
                        </span>
                    </span>
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
                                    <span>Menyusun Perencanaan Produksi</span>
                                </CardTitle>
                                <!-- <Badge
                                    variant="outline"
                                    class="bg-blue-50 text-blue-700 border-blue-300 font-extrabold text-xs"
                                >
                                    Langkah 1 dari 3
                                </Badge> -->
                            </div>
                            <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Penetapan jadwal distribusi menu, penamaan menu,
                                dan menentukan Penerima Manfaat (PM).
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
                            <!-- Real-time Warning Alergi pada Nama Menu Utama -->
                            <div
                                v-if="detectedAllergensMenuUtama.length > 0"
                                class="flex flex-wrap items-center gap-1.5 pt-1"
                            >
                                <span
                                    class="text-[10.5px] font-extrabold text-amber-800 flex items-center gap-1"
                                >
                                    <AlertTriangle
                                        class="h-3.5 w-3.5 text-amber-600 shrink-0"
                                    />
                                    <span>Alergen PM Terdeteksi:</span>
                                </span>
                                <span
                                    v-for="al in detectedAllergensMenuUtama"
                                    :key="al.jenis"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-100/90 text-amber-900 border border-amber-300 text-[10.5px] font-bold shadow-2xs"
                                >
                                    ⚠️ {{ al.jenis }} ({{ al.total_pm }} Siswa
                                    PM)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Sub Menu Komponen Gizi (Sub Menu 1 s.d. Sub Menu 5) -->
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

                        <!-- Banner Real-time Deteksi Alergi PM (Muncul otomatis saat ada kata kunci alergen terketik) -->
                        <div
                            v-if="realTimeAllergyAlerts.length > 0"
                            class="p-3 rounded-xl bg-amber-50 border border-amber-300/90 text-amber-900 space-y-2 text-xs shadow-2xs transition-all duration-300"
                        >
                            <div
                                class="flex items-center justify-between flex-wrap gap-1"
                            >
                                <div
                                    class="flex items-center gap-1.5 font-black text-amber-900 text-xs"
                                >
                                    <ShieldAlert
                                        class="h-4 w-4 text-amber-600 shrink-0"
                                    />
                                    <span
                                        >Peringatan: Terdeteksi
                                        {{ realTimeAllergyAlerts.length }} Sub
                                        Menu Mengandung Bahan Alergi PM</span
                                    >
                                </div>
                                <span
                                    class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-200"
                                >
                                    ⚡ Realtime Deteksi Alergi
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[11px]">
                                <div
                                    v-for="(al, alIdx) in realTimeAllergyAlerts"
                                    :key="alIdx"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border bg-white shadow-2xs"
                                    :class="
                                        al.hasReplacement
                                            ? 'border-emerald-300 text-emerald-900'
                                            : 'border-rose-300 text-rose-900'
                                    "
                                >
                                    <span class="font-extrabold text-slate-900"
                                        >{{ al.subLabel }}:</span
                                    >
                                    <span class="font-bold text-slate-700"
                                        >"{{ al.menuName }}"</span
                                    >
                                    <span class="text-slate-500">➔</span>
                                    <span class="font-bold text-rose-700"
                                        >Alergi {{ al.allergen }} ({{
                                            al.totalPm
                                        }}
                                        Siswa)</span
                                    >
                                    <span
                                        v-if="al.hasReplacement"
                                        class="inline-flex items-center gap-0.5 text-emerald-700 font-extrabold text-[10px] bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200"
                                    >
                                        ✓ Pengganti: {{ al.replacementName }}
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="
                                            addPenggantiAlergiWithPreset(
                                                al.subKey,
                                                al.allergen,
                                            )
                                        "
                                        class="inline-flex items-center gap-0.5 text-rose-700 hover:text-rose-900 font-extrabold text-[10px] bg-rose-50 hover:bg-rose-100 px-1.5 py-0.5 rounded border border-rose-300 cursor-pointer"
                                        title="Tambahkan menu pengganti sekarang"
                                    >
                                        + Pengganti
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-start"
                        >
                            <!-- Sub Menu 1 -->
                            <div
                                class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit"
                                :class="
                                    validationErrors.sub_menu_1
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-amber-200/90'
                                "
                            >
                                <div class="space-y-1">
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

                                    <!-- Warning Realtime Alergi Sub Menu 1 -->
                                    <div
                                        v-if="
                                            detectedAllergensPerSubMenu
                                                .sub_menu_1.length > 0
                                        "
                                        class="p-1.5 rounded-lg bg-amber-50/90 border border-amber-300 text-amber-900 space-y-1 text-[10px]"
                                    >
                                        <div
                                            class="flex items-center gap-1 font-extrabold text-amber-900"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 text-amber-600 shrink-0"
                                            />
                                            <span>Alergi PM Terdeteksi:</span>
                                        </div>
                                        <div
                                            v-for="al in detectedAllergensPerSubMenu.sub_menu_1"
                                            :key="al.jenis"
                                            class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                        >
                                            <span
                                                >⚠️
                                                <strong
                                                    >{{
                                                        al.total_pm
                                                    }}
                                                    PM</strong
                                                >
                                                alergi
                                                <strong>{{
                                                    al.jenis
                                                }}</strong></span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        'sub_menu_1',
                                                        al.jenis,
                                                    )
                                                "
                                                class="text-amber-800 font-bold hover:underline cursor-pointer shrink-0"
                                                title="Tambah opsi pengganti untuk alergi ini"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opsi Menu Pengganti Alergi -->
                                <div
                                    class="pt-2 border-t border-slate-100 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                        >
                                            <span>🛡️ Alergi:</span>
                                            <span
                                                v-if="
                                                    subMenuAlergi.sub_menu_1 &&
                                                    subMenuAlergi.sub_menu_1
                                                        .length > 0
                                                "
                                                class="text-rose-600 font-extrabold"
                                                >({{
                                                    subMenuAlergi.sub_menu_1
                                                        .length
                                                }})</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                addPenggantiAlergi('sub_menu_1')
                                            "
                                            class="text-[10px] font-bold text-amber-700 hover:text-amber-800 hover:underline flex items-center gap-0.5 cursor-pointer"
                                            title="Tambah menu pengganti jika ada siswa alergi"
                                        >
                                            <Plus class="h-3 w-3" />
                                            <span>Pengganti</span>
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            alItem, alIdx
                                        ) in subMenuAlergi.sub_menu_1"
                                        :key="alIdx"
                                        class="p-1.5 rounded-lg border space-y-1 transition-all"
                                        :class="
                                            validationErrors[
                                                'alergi_sub_menu_1_' +
                                                    alIdx +
                                                    '_jenis'
                                            ] ||
                                            validationErrors[
                                                'alergi_sub_menu_1_' +
                                                    alIdx +
                                                    '_menu'
                                            ]
                                                ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                : 'bg-rose-50/60 border-rose-200'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-model="alItem.jenis_alergi"
                                                @change="
                                                    clearError(
                                                        'alergi_sub_menu_1_' +
                                                            alIdx +
                                                            '_jenis',
                                                    )
                                                "
                                                :class="[
                                                    'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                    validationErrors[
                                                        'alergi_sub_menu_1_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                        ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                        : 'border border-rose-200 text-rose-900 focus:ring-rose-400 focus:border-rose-400',
                                                ]"
                                            >
                                                <option value="" disabled>
                                                    {{
                                                        availableAlergiOptions.length >
                                                        0
                                                            ? "-- Pilih Alergi PM (Wajib) --"
                                                            : "-- Belum ada data alergi di PM --"
                                                    }}
                                                </option>
                                                <option
                                                    v-for="opt in availableAlergiOptions"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    ⚠️ {{ opt }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="
                                                    removePenggantiAlergi(
                                                        'sub_menu_1',
                                                        alIdx,
                                                    )
                                                "
                                                class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                title="Hapus opsi ini"
                                            >
                                                <Trash2 class="h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_1_' +
                                                        alIdx +
                                                        '_jenis'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_1_" +
                                                        alIdx +
                                                        "_jenis"
                                                ]
                                            }}</span>
                                        </p>

                                        <input
                                            type="text"
                                            v-model="alItem.menu_pengganti"
                                            @input="
                                                clearError(
                                                    'alergi_sub_menu_1_' +
                                                        alIdx +
                                                        '_menu',
                                                )
                                            "
                                            placeholder="Menu pengganti (wajib)..."
                                            :class="[
                                                'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                validationErrors[
                                                    'alergi_sub_menu_1_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'border border-rose-400 focus:ring-rose-400'
                                                    : 'border border-rose-200 focus:ring-rose-400 focus:border-rose-400',
                                            ]"
                                        />
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_1_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_1_" +
                                                        alIdx +
                                                        "_menu"
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub Menu 2 -->
                            <div
                                class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit"
                                :class="
                                    validationErrors.sub_menu_2
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-rose-200/90'
                                "
                            >
                                <div class="space-y-1">
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

                                    <!-- Warning Realtime Alergi Sub Menu 2 -->
                                    <div
                                        v-if="
                                            detectedAllergensPerSubMenu
                                                .sub_menu_2.length > 0
                                        "
                                        class="p-1.5 rounded-lg bg-amber-50/90 border border-amber-300 text-amber-900 space-y-1 text-[10px]"
                                    >
                                        <div
                                            class="flex items-center gap-1 font-extrabold text-amber-900"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 text-amber-600 shrink-0"
                                            />
                                            <span>Alergi PM Terdeteksi:</span>
                                        </div>
                                        <div
                                            v-for="al in detectedAllergensPerSubMenu.sub_menu_2"
                                            :key="al.jenis"
                                            class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                        >
                                            <span
                                                >⚠️
                                                <strong
                                                    >{{
                                                        al.total_pm
                                                    }}
                                                    PM</strong
                                                >
                                                alergi
                                                <strong>{{
                                                    al.jenis
                                                }}</strong></span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        'sub_menu_2',
                                                        al.jenis,
                                                    )
                                                "
                                                class="text-amber-800 font-bold hover:underline cursor-pointer shrink-0"
                                                title="Tambah opsi pengganti untuk alergi ini"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opsi Menu Pengganti Alergi -->
                                <div
                                    class="pt-2 border-t border-slate-100 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                        >
                                            <span>🛡️ Alergi:</span>
                                            <span
                                                v-if="
                                                    subMenuAlergi.sub_menu_2 &&
                                                    subMenuAlergi.sub_menu_2
                                                        .length > 0
                                                "
                                                class="text-rose-600 font-extrabold"
                                                >({{
                                                    subMenuAlergi.sub_menu_2
                                                        .length
                                                }})</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                addPenggantiAlergi('sub_menu_2')
                                            "
                                            class="text-[10px] font-bold text-rose-700 hover:text-rose-800 hover:underline flex items-center gap-0.5 cursor-pointer"
                                            title="Tambah menu pengganti jika ada siswa alergi"
                                        >
                                            <Plus class="h-3 w-3" />
                                            <span>Pengganti</span>
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            alItem, alIdx
                                        ) in subMenuAlergi.sub_menu_2"
                                        :key="alIdx"
                                        class="p-1.5 rounded-lg border space-y-1 transition-all"
                                        :class="
                                            validationErrors[
                                                'alergi_sub_menu_2_' +
                                                    alIdx +
                                                    '_jenis'
                                            ] ||
                                            validationErrors[
                                                'alergi_sub_menu_2_' +
                                                    alIdx +
                                                    '_menu'
                                            ]
                                                ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                : 'bg-rose-50/60 border-rose-200'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-model="alItem.jenis_alergi"
                                                @change="
                                                    clearError(
                                                        'alergi_sub_menu_2_' +
                                                            alIdx +
                                                            '_jenis',
                                                    )
                                                "
                                                :class="[
                                                    'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                    validationErrors[
                                                        'alergi_sub_menu_2_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                        ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                        : 'border border-rose-200 text-rose-900 focus:ring-rose-400 focus:border-rose-400',
                                                ]"
                                            >
                                                <option value="" disabled>
                                                    {{
                                                        availableAlergiOptions.length >
                                                        0
                                                            ? "-- Pilih Alergi PM (Wajib) --"
                                                            : "-- Belum ada data alergi di PM --"
                                                    }}
                                                </option>
                                                <option
                                                    v-for="opt in availableAlergiOptions"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    ⚠️ {{ opt }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="
                                                    removePenggantiAlergi(
                                                        'sub_menu_2',
                                                        alIdx,
                                                    )
                                                "
                                                class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                title="Hapus opsi ini"
                                            >
                                                <Trash2 class="h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_2_' +
                                                        alIdx +
                                                        '_jenis'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_2_" +
                                                        alIdx +
                                                        "_jenis"
                                                ]
                                            }}</span>
                                        </p>

                                        <input
                                            type="text"
                                            v-model="alItem.menu_pengganti"
                                            @input="
                                                clearError(
                                                    'alergi_sub_menu_2_' +
                                                        alIdx +
                                                        '_menu',
                                                )
                                            "
                                            placeholder="Menu pengganti (wajib)..."
                                            :class="[
                                                'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                validationErrors[
                                                    'alergi_sub_menu_2_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'border border-rose-400 focus:ring-rose-400'
                                                    : 'border border-rose-200 focus:ring-rose-400 focus:border-rose-400',
                                            ]"
                                        />
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_2_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_2_" +
                                                        alIdx +
                                                        "_menu"
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub Menu 3 -->
                            <div
                                class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit"
                                :class="
                                    validationErrors.sub_menu_3
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-yellow-200/90'
                                "
                            >
                                <div class="space-y-1">
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

                                    <!-- Warning Realtime Alergi Sub Menu 3 -->
                                    <div
                                        v-if="
                                            detectedAllergensPerSubMenu
                                                .sub_menu_3.length > 0
                                        "
                                        class="p-1.5 rounded-lg bg-amber-50/90 border border-amber-300 text-amber-900 space-y-1 text-[10px]"
                                    >
                                        <div
                                            class="flex items-center gap-1 font-extrabold text-amber-900"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 text-amber-600 shrink-0"
                                            />
                                            <span>Alergi PM Terdeteksi:</span>
                                        </div>
                                        <div
                                            v-for="al in detectedAllergensPerSubMenu.sub_menu_3"
                                            :key="al.jenis"
                                            class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                        >
                                            <span
                                                >⚠️
                                                <strong
                                                    >{{
                                                        al.total_pm
                                                    }}
                                                    PM</strong
                                                >
                                                alergi
                                                <strong>{{
                                                    al.jenis
                                                }}</strong></span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        'sub_menu_3',
                                                        al.jenis,
                                                    )
                                                "
                                                class="text-amber-800 font-bold hover:underline cursor-pointer shrink-0"
                                                title="Tambah opsi pengganti untuk alergi ini"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opsi Menu Pengganti Alergi -->
                                <div
                                    class="pt-2 border-t border-slate-100 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                        >
                                            <span>🛡️ Alergi:</span>
                                            <span
                                                v-if="
                                                    subMenuAlergi.sub_menu_3 &&
                                                    subMenuAlergi.sub_menu_3
                                                        .length > 0
                                                "
                                                class="text-rose-600 font-extrabold"
                                                >({{
                                                    subMenuAlergi.sub_menu_3
                                                        .length
                                                }})</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                addPenggantiAlergi('sub_menu_3')
                                            "
                                            class="text-[10px] font-bold text-yellow-700 hover:text-yellow-800 hover:underline flex items-center gap-0.5 cursor-pointer"
                                            title="Tambah menu pengganti jika ada siswa alergi"
                                        >
                                            <Plus class="h-3 w-3" />
                                            <span>Pengganti</span>
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            alItem, alIdx
                                        ) in subMenuAlergi.sub_menu_3"
                                        :key="alIdx"
                                        class="p-1.5 rounded-lg border space-y-1 transition-all"
                                        :class="
                                            validationErrors[
                                                'alergi_sub_menu_3_' +
                                                    alIdx +
                                                    '_jenis'
                                            ] ||
                                            validationErrors[
                                                'alergi_sub_menu_3_' +
                                                    alIdx +
                                                    '_menu'
                                            ]
                                                ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                : 'bg-rose-50/60 border-rose-200'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-model="alItem.jenis_alergi"
                                                @change="
                                                    clearError(
                                                        'alergi_sub_menu_3_' +
                                                            alIdx +
                                                            '_jenis',
                                                    )
                                                "
                                                :class="[
                                                    'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                    validationErrors[
                                                        'alergi_sub_menu_3_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                        ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                        : 'border border-rose-200 text-rose-900 focus:ring-rose-400 focus:border-rose-400',
                                                ]"
                                            >
                                                <option value="" disabled>
                                                    {{
                                                        availableAlergiOptions.length >
                                                        0
                                                            ? "-- Pilih Alergi PM (Wajib) --"
                                                            : "-- Belum ada data alergi di PM --"
                                                    }}
                                                </option>
                                                <option
                                                    v-for="opt in availableAlergiOptions"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    ⚠️ {{ opt }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="
                                                    removePenggantiAlergi(
                                                        'sub_menu_3',
                                                        alIdx,
                                                    )
                                                "
                                                class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                title="Hapus opsi ini"
                                            >
                                                <Trash2 class="h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_3_' +
                                                        alIdx +
                                                        '_jenis'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_3_" +
                                                        alIdx +
                                                        "_jenis"
                                                ]
                                            }}</span>
                                        </p>

                                        <input
                                            type="text"
                                            v-model="alItem.menu_pengganti"
                                            @input="
                                                clearError(
                                                    'alergi_sub_menu_3_' +
                                                        alIdx +
                                                        '_menu',
                                                )
                                            "
                                            placeholder="Menu pengganti (wajib)..."
                                            :class="[
                                                'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                validationErrors[
                                                    'alergi_sub_menu_3_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'border border-rose-400 focus:ring-rose-400'
                                                    : 'border border-rose-200 focus:ring-rose-400 focus:border-rose-400',
                                            ]"
                                        />
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_3_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_3_" +
                                                        alIdx +
                                                        "_menu"
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub Menu 4 -->
                            <div
                                class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit"
                                :class="
                                    validationErrors.sub_menu_4
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-blue-200/90'
                                "
                            >
                                <div class="space-y-1">
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

                                    <!-- Warning Realtime Alergi Sub Menu 4 -->
                                    <div
                                        v-if="
                                            detectedAllergensPerSubMenu
                                                .sub_menu_4.length > 0
                                        "
                                        class="p-1.5 rounded-lg bg-amber-50/90 border border-amber-300 text-amber-900 space-y-1 text-[10px]"
                                    >
                                        <div
                                            class="flex items-center gap-1 font-extrabold text-amber-900"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 text-amber-600 shrink-0"
                                            />
                                            <span>Alergi PM Terdeteksi:</span>
                                        </div>
                                        <div
                                            v-for="al in detectedAllergensPerSubMenu.sub_menu_4"
                                            :key="al.jenis"
                                            class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                        >
                                            <span
                                                >⚠️
                                                <strong
                                                    >{{
                                                        al.total_pm
                                                    }}
                                                    PM</strong
                                                >
                                                alergi
                                                <strong>{{
                                                    al.jenis
                                                }}</strong></span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        'sub_menu_4',
                                                        al.jenis,
                                                    )
                                                "
                                                class="text-amber-800 font-bold hover:underline cursor-pointer shrink-0"
                                                title="Tambah opsi pengganti untuk alergi ini"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opsi Menu Pengganti Alergi -->
                                <div
                                    class="pt-2 border-t border-slate-100 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                        >
                                            <span>🛡️ Alergi:</span>
                                            <span
                                                v-if="
                                                    subMenuAlergi.sub_menu_4 &&
                                                    subMenuAlergi.sub_menu_4
                                                        .length > 0
                                                "
                                                class="text-rose-600 font-extrabold"
                                                >({{
                                                    subMenuAlergi.sub_menu_4
                                                        .length
                                                }})</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                addPenggantiAlergi('sub_menu_4')
                                            "
                                            class="text-[10px] font-bold text-blue-700 hover:text-blue-800 hover:underline flex items-center gap-0.5 cursor-pointer"
                                            title="Tambah menu pengganti jika ada siswa alergi"
                                        >
                                            <Plus class="h-3 w-3" />
                                            <span>Pengganti</span>
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            alItem, alIdx
                                        ) in subMenuAlergi.sub_menu_4"
                                        :key="alIdx"
                                        class="p-1.5 rounded-lg border space-y-1 transition-all"
                                        :class="
                                            validationErrors[
                                                'alergi_sub_menu_4_' +
                                                    alIdx +
                                                    '_jenis'
                                            ] ||
                                            validationErrors[
                                                'alergi_sub_menu_4_' +
                                                    alIdx +
                                                    '_menu'
                                            ]
                                                ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                : 'bg-rose-50/60 border-rose-200'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-model="alItem.jenis_alergi"
                                                @change="
                                                    clearError(
                                                        'alergi_sub_menu_4_' +
                                                            alIdx +
                                                            '_jenis',
                                                    )
                                                "
                                                :class="[
                                                    'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                    validationErrors[
                                                        'alergi_sub_menu_4_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                        ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                        : 'border border-rose-200 text-rose-900 focus:ring-rose-400 focus:border-rose-400',
                                                ]"
                                            >
                                                <option value="" disabled>
                                                    {{
                                                        availableAlergiOptions.length >
                                                        0
                                                            ? "-- Pilih Alergi PM (Wajib) --"
                                                            : "-- Belum ada data alergi di PM --"
                                                    }}
                                                </option>
                                                <option
                                                    v-for="opt in availableAlergiOptions"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    ⚠️ {{ opt }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="
                                                    removePenggantiAlergi(
                                                        'sub_menu_4',
                                                        alIdx,
                                                    )
                                                "
                                                class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                title="Hapus opsi ini"
                                            >
                                                <Trash2 class="h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_4_' +
                                                        alIdx +
                                                        '_jenis'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_4_" +
                                                        alIdx +
                                                        "_jenis"
                                                ]
                                            }}</span>
                                        </p>

                                        <input
                                            type="text"
                                            v-model="alItem.menu_pengganti"
                                            @input="
                                                clearError(
                                                    'alergi_sub_menu_4_' +
                                                        alIdx +
                                                        '_menu',
                                                )
                                            "
                                            placeholder="Menu pengganti (wajib)..."
                                            :class="[
                                                'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                validationErrors[
                                                    'alergi_sub_menu_4_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'border border-rose-400 focus:ring-rose-400'
                                                    : 'border border-rose-200 focus:ring-rose-400 focus:border-rose-400',
                                            ]"
                                        />
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_4_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_4_" +
                                                        alIdx +
                                                        "_menu"
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub Menu 5 -->
                            <div
                                class="space-y-2 bg-white p-2.5 rounded-xl border shadow-2xs flex flex-col h-fit"
                                :class="
                                    validationErrors.sub_menu_5
                                        ? 'border-rose-400 bg-rose-50/20'
                                        : 'border-emerald-200/90'
                                "
                            >
                                <div class="space-y-1">
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

                                    <!-- Warning Realtime Alergi Sub Menu 5 -->
                                    <div
                                        v-if="
                                            detectedAllergensPerSubMenu
                                                .sub_menu_5.length > 0
                                        "
                                        class="p-1.5 rounded-lg bg-amber-50/90 border border-amber-300 text-amber-900 space-y-1 text-[10px]"
                                    >
                                        <div
                                            class="flex items-center gap-1 font-extrabold text-amber-900"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 text-amber-600 shrink-0"
                                            />
                                            <span>Alergi PM Terdeteksi:</span>
                                        </div>
                                        <div
                                            v-for="al in detectedAllergensPerSubMenu.sub_menu_5"
                                            :key="al.jenis"
                                            class="flex items-center justify-between gap-1 text-[9.5px] leading-tight text-slate-800"
                                        >
                                            <span
                                                >⚠️
                                                <strong
                                                    >{{
                                                        al.total_pm
                                                    }}
                                                    PM</strong
                                                >
                                                alergi
                                                <strong>{{
                                                    al.jenis
                                                }}</strong></span
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    addPenggantiAlergiWithPreset(
                                                        'sub_menu_5',
                                                        al.jenis,
                                                    )
                                                "
                                                class="text-amber-800 font-bold hover:underline cursor-pointer shrink-0"
                                                title="Tambah opsi pengganti untuk alergi ini"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opsi Menu Pengganti Alergi -->
                                <div
                                    class="pt-2 border-t border-slate-100 space-y-1.5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-500 flex items-center gap-1"
                                        >
                                            <span>🛡️ Alergi:</span>
                                            <span
                                                v-if="
                                                    subMenuAlergi.sub_menu_5 &&
                                                    subMenuAlergi.sub_menu_5
                                                        .length > 0
                                                "
                                                class="text-rose-600 font-extrabold"
                                                >({{
                                                    subMenuAlergi.sub_menu_5
                                                        .length
                                                }})</span
                                            >
                                        </span>
                                        <button
                                            type="button"
                                            @click="
                                                addPenggantiAlergi('sub_menu_5')
                                            "
                                            class="text-[10px] font-bold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-0.5 cursor-pointer"
                                            title="Tambah menu pengganti jika ada siswa alergi"
                                        >
                                            <Plus class="h-3 w-3" />
                                            <span>Pengganti</span>
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            alItem, alIdx
                                        ) in subMenuAlergi.sub_menu_5"
                                        :key="alIdx"
                                        class="p-1.5 rounded-lg border space-y-1 transition-all"
                                        :class="
                                            validationErrors[
                                                'alergi_sub_menu_5_' +
                                                    alIdx +
                                                    '_jenis'
                                            ] ||
                                            validationErrors[
                                                'alergi_sub_menu_5_' +
                                                    alIdx +
                                                    '_menu'
                                            ]
                                                ? 'bg-rose-50 border-rose-400 ring-1 ring-rose-300'
                                                : 'bg-rose-50/60 border-rose-200'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <select
                                                v-model="alItem.jenis_alergi"
                                                @change="
                                                    clearError(
                                                        'alergi_sub_menu_5_' +
                                                            alIdx +
                                                            '_jenis',
                                                    )
                                                "
                                                :class="[
                                                    'w-full text-[10.5px] font-bold rounded py-0.5 px-1.5 focus:ring-1 bg-white',
                                                    validationErrors[
                                                        'alergi_sub_menu_5_' +
                                                            alIdx +
                                                            '_jenis'
                                                    ]
                                                        ? 'border border-rose-400 text-rose-900 focus:ring-rose-400'
                                                        : 'border border-rose-200 text-rose-900 focus:ring-rose-400 focus:border-rose-400',
                                                ]"
                                            >
                                                <option value="" disabled>
                                                    {{
                                                        availableAlergiOptions.length >
                                                        0
                                                            ? "-- Pilih Alergi PM (Wajib) --"
                                                            : "-- Belum ada data alergi di PM --"
                                                    }}
                                                </option>
                                                <option
                                                    v-for="opt in availableAlergiOptions"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    ⚠️ {{ opt }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="
                                                    removePenggantiAlergi(
                                                        'sub_menu_5',
                                                        alIdx,
                                                    )
                                                "
                                                class="h-5 w-5 shrink-0 rounded bg-white hover:bg-rose-100 text-rose-600 border border-rose-200 flex items-center justify-center cursor-pointer"
                                                title="Hapus opsi ini"
                                            >
                                                <Trash2 class="h-2.5 w-2.5" />
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_5_' +
                                                        alIdx +
                                                        '_jenis'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_5_" +
                                                        alIdx +
                                                        "_jenis"
                                                ]
                                            }}</span>
                                        </p>

                                        <input
                                            type="text"
                                            v-model="alItem.menu_pengganti"
                                            @input="
                                                clearError(
                                                    'alergi_sub_menu_5_' +
                                                        alIdx +
                                                        '_menu',
                                                )
                                            "
                                            placeholder="Menu pengganti (wajib)..."
                                            :class="[
                                                'w-full text-[10.5px] font-medium text-slate-800 bg-white rounded py-0.5 px-1.5 focus:ring-1 placeholder:text-slate-400',
                                                validationErrors[
                                                    'alergi_sub_menu_5_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                                    ? 'border border-rose-400 focus:ring-rose-400'
                                                    : 'border border-rose-200 focus:ring-rose-400 focus:border-rose-400',
                                            ]"
                                        />
                                        <p
                                            v-if="
                                                validationErrors[
                                                    'alergi_sub_menu_5_' +
                                                        alIdx +
                                                        '_menu'
                                                ]
                                            "
                                            class="text-[9.5px] text-rose-600 font-bold flex items-center gap-0.5"
                                        >
                                            <AlertCircle
                                                class="h-2.5 w-2.5 shrink-0"
                                            />
                                            <span>{{
                                                validationErrors[
                                                    "alergi_sub_menu_5_" +
                                                        alIdx +
                                                        "_menu"
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>
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

                    <!-- Tabel Rincian Kelompok Penerima Manfaat Terjadwal (Status Menerima & Edit Detail Sub-Kategori) -->
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
                                                Nama Kelompok Penerima Manfaat
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
                                <span
                                    >Lanjut ke Formula Makanan (Langkah 2)</span
                                >
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
                                    <span>Pemilihan Bahan Pangan</span>
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
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium"
                                >
                                    <span>Database:</span>
                                    <div
                                        class="inline-flex rounded-lg bg-slate-200/70 p-0.5 border border-slate-300/60"
                                        :title="
                                            selectedBahanList.length > 0
                                                ? 'Database terkunci karena sudah ada bahan pangan yang dipilih. Hapus/reset semua bahan terlebih dahulu jika ingin mengganti database.'
                                                : 'Pilih Database Acuan'
                                        "
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                selectedBahanList.length ===
                                                    0 &&
                                                emit('update-source', 'fta')
                                            "
                                            :disabled="
                                                selectedBahanList.length > 0
                                            "
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold transition-all"
                                            :class="[
                                                selectedSource === 'fta'
                                                    ? 'bg-white text-primary shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900',
                                                selectedBahanList.length > 0
                                                    ? 'opacity-80 cursor-not-allowed'
                                                    : 'cursor-pointer',
                                            ]"
                                        >
                                            Nutri Survey
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                selectedBahanList.length ===
                                                    0 &&
                                                emit('update-source', 'csv')
                                            "
                                            :disabled="
                                                selectedBahanList.length > 0
                                            "
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold transition-all"
                                            :class="[
                                                selectedSource === 'csv'
                                                    ? 'bg-white text-primary shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900',
                                                selectedBahanList.length > 0
                                                    ? 'opacity-80 cursor-not-allowed'
                                                    : 'cursor-pointer',
                                            ]"
                                        >
                                            TKPI 2020
                                        </button>
                                    </div>
                                    <span
                                        v-if="selectedBahanList.length > 0"
                                        class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 font-bold inline-flex items-center gap-1"
                                        title="Database terkunci karena sudah ada bahan dipilih"
                                    >
                                        🔒 Terkunci
                                    </span>
                                </div>
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

                    <!-- Error Alert jika belum ada bahan sama sekali -->
                    <div
                        v-if="validationErrors.selectedBahan"
                        class="p-3 rounded-xl bg-rose-50 border border-rose-300 text-rose-800 text-xs flex items-center gap-2 font-bold"
                    >
                        <AlertTriangle class="h-4 w-4 text-rose-600 shrink-0" />
                        <span>{{ validationErrors.selectedBahan }}</span>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- BLOK-BLOK SUB MENU & VARIAN PENGGANTI ALERGI (LANGKAH 2) -->
                    <!-- ========================================================================= -->
                    <div class="space-y-6">
                        <div
                            v-for="(block, bIdx) in step2SubMenuBlocks"
                            :key="block.id"
                            :id="'card-block-' + block.id"
                            class="border rounded-2xl bg-white shadow-xs transition-all duration-200"
                            :class="[
                                block.borderCard,
                                activeComboboxBlockId === block.id
                                    ? 'relative z-30'
                                    : 'relative z-10',
                            ]"
                        >
                            <!-- Header Blok Sub Menu -->
                            <div
                                class="p-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3 rounded-t-2xl"
                                :class="block.bgHeader"
                            >
                                <div class="flex items-center gap-3 flex-wrap">
                                    <div
                                        class="h-7 w-7 rounded-xl flex items-center justify-center font-black text-xs text-white shadow-2xs shrink-0"
                                        :class="
                                            block.isAlergi
                                                ? 'bg-rose-600'
                                                : 'bg-slate-800'
                                        "
                                    >
                                        {{
                                            block.isAlergi
                                                ? "🛡️"
                                                : block.subIndex
                                        }}
                                    </div>
                                    <div>
                                        <div
                                            class="flex items-center gap-2 flex-wrap"
                                        >
                                            <span
                                                class="text-xs font-black uppercase tracking-wider text-slate-800"
                                            >
                                                {{ block.subLabel }}:
                                            </span>
                                            <span
                                                class="text-sm font-extrabold"
                                                :class="
                                                    block.isAlergi
                                                        ? 'text-rose-900'
                                                        : 'text-slate-900'
                                                "
                                            >
                                                "{{ block.namaMenu }}"
                                            </span>
                                            <span
                                                v-if="block.isAlergi"
                                                class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs"
                                            >
                                                ⚠️ Varian Alergi:
                                                {{ block.jenisAlergi }}
                                            </span>
                                        </div>
                                        <!-- Info Sasaran Porsi -->
                                        <div
                                            class="text-[11px] text-slate-600 flex items-center gap-2 mt-0.5 flex-wrap"
                                        >
                                            <span
                                                v-if="!block.isAlergi"
                                                class="font-medium"
                                            >
                                                Target Porsi Normal:
                                                <strong class="text-slate-900"
                                                    >{{ totalPM }} Siswa</strong
                                                >
                                                (PK: {{ totalPK }}, PB:
                                                {{ totalPB }})
                                            </span>
                                            <span
                                                v-else
                                                class="font-bold text-rose-800 flex items-center gap-1"
                                            >
                                                <span
                                                    >Target Alergi
                                                    {{
                                                        block.jenisAlergi
                                                    }}:</span
                                                >
                                                <strong class="text-rose-950"
                                                    >{{
                                                        findAlergiDetail(
                                                            block.jenisAlergi,
                                                        )?.total || 0
                                                    }}
                                                    Siswa</strong
                                                >
                                                <span
                                                    >(PK:
                                                    {{
                                                        findAlergiDetail(
                                                            block.jenisAlergi,
                                                        )?.porsi_kecil || 0
                                                    }}, PB:
                                                    {{
                                                        findAlergiDetail(
                                                            block.jenisAlergi,
                                                        )?.porsi_besar || 0
                                                    }})</span
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ringkasan Blok & Tombol Cek Rekap Pojok Kanan -->
                                <div
                                    class="flex items-center gap-2 text-xs flex-wrap self-start md:self-auto"
                                >
                                    <!-- Tombol Cek Rekapitulasi -->
                                    <button
                                        type="button"
                                        @click="scrollToRekapitulasi"
                                        class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:text-indigo-900 border border-indigo-200 text-[11px] font-extrabold flex items-center gap-1.5 transition-all cursor-pointer shadow-2xs active:scale-95"
                                        title="Lihat Tabel Rekapitulasi Semua Bahan Pangan"
                                    >
                                        <ClipboardList
                                            class="h-3.5 w-3.5 text-indigo-600"
                                        />
                                        <span>Cek Rekap</span>
                                    </button>
                                    <span
                                        class="px-2.5 py-1 rounded-lg bg-white/90 border border-slate-200 text-slate-700 font-bold text-[11px] shadow-2xs"
                                    >
                                        {{ getBlockSummary(block.id).count }}
                                        Bahan
                                    </span>
                                    <span
                                        class="px-2.5 py-1 rounded-lg bg-blue-50/90 border border-blue-200 text-blue-900 font-black text-[11px] shadow-2xs"
                                    >
                                        {{
                                            formatGrossWeight(
                                                getBlockSummary(block.id)
                                                    .totalGrossKg,
                                            )
                                        }}
                                    </span>
                                    <span
                                        class="px-2.5 py-1 rounded-lg bg-emerald-50/90 border border-emerald-200 text-emerald-900 font-black text-[11px] shadow-2xs"
                                    >
                                        {{
                                            formatRupiah(
                                                getBlockSummary(block.id)
                                                    .totalCostMaster,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Selector Combobox Tambah Bahan Baku Khusus Blok Ini (Di Atas Tabel) -->
                            <div
                                class="p-3.5 bg-slate-50/70 border-b border-slate-200 relative z-30"
                            >
                                <div class="relative" @click.stop>
                                    <div
                                        @click="
                                            toggleComboboxForBlock(block.id)
                                        "
                                        class="w-full min-h-[42px] px-3.5 py-1.5 rounded-xl border border-slate-300 bg-white hover:border-primary/70 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all flex items-center justify-between gap-3 cursor-pointer shadow-2xs"
                                    >
                                        <div
                                            class="flex items-center gap-2.5 min-w-0 flex-1"
                                        >
                                            <div
                                                class="h-6 w-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                                            >
                                                <Plus class="h-3.5 w-3.5" />
                                            </div>
                                            <span
                                                class="text-xs text-slate-500 font-normal truncate"
                                            >
                                                Ketik nama bahan baku mentah
                                                untuk ditambahkan ke
                                                <strong
                                                    >{{ block.subLabel }} ({{
                                                        block.namaMenu
                                                    }})</strong
                                                >...
                                            </span>
                                        </div>
                                        <ChevronDown
                                            class="h-4 w-4 text-slate-400 shrink-0 transition-transform duration-150"
                                            :class="{
                                                'rotate-180 text-primary':
                                                    activeComboboxBlockId ===
                                                    block.id,
                                            }"
                                        />
                                    </div>

                                    <!-- Dropdown Panel Pencarian Bahan Pangan Master (Tidak Terpotong Tabel) -->
                                    <div
                                        v-if="
                                            activeComboboxBlockId === block.id
                                        "
                                        class="absolute z-50 left-0 right-0 top-full mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                                    >
                                        <div
                                            class="p-2.5 border-b border-slate-100 bg-slate-50/90 space-y-2"
                                        >
                                            <div class="relative">
                                                <Search
                                                    class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                                                />
                                                <input
                                                    type="text"
                                                    :value="
                                                        getSearchQueryForBlock(
                                                            block.id,
                                                        )
                                                    "
                                                    @input="
                                                        setSearchQueryForBlock(
                                                            block.id,
                                                            $event.target.value,
                                                        )
                                                    "
                                                    :placeholder="`Cari dari seluruh database bahan pangan untuk ${block.namaMenu}...`"
                                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-lg border border-slate-200 focus:outline-hidden focus:border-primary font-medium"
                                                    autofocus
                                                />
                                            </div>
                                            <div
                                                class="flex items-center justify-between text-[10px] text-slate-500 font-medium px-1"
                                            >
                                                <span>
                                                    Sumber:
                                                    <strong
                                                        class="text-slate-800"
                                                        >{{
                                                            selectedSource ===
                                                            "csv"
                                                                ? "TKPI 2020 (.csv)"
                                                                : "Nutri Survey (.fta)"
                                                        }}</strong
                                                    >
                                                </span>
                                                <span
                                                    class="text-primary font-bold"
                                                >
                                                    {{
                                                        getFilteredTkpiListForBlock(
                                                            block.id,
                                                        ).length
                                                    }}
                                                    Bahan Pangan Tersedia
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            class="max-h-80 overflow-y-auto divide-y divide-slate-100"
                                        >
                                            <div
                                                v-for="item in getFilteredTkpiListForBlock(
                                                    block.id,
                                                )"
                                                :key="item.id || item.code"
                                                @click="
                                                    selectTkpiItemForBlock(
                                                        item,
                                                        block,
                                                    )
                                                "
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
                                                        <span>{{
                                                            item.kategori
                                                        }}</span>
                                                        <span>•</span>
                                                        <span
                                                            >BDD:
                                                            {{
                                                                item.bdd || 100
                                                            }}%</span
                                                        >
                                                    </div>
                                                </div>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    className="bg-primary/10 text-primary hover:bg-primary hover:text-white h-7 px-2.5 text-[11px] font-bold rounded-lg shrink-0"
                                                >
                                                    + Pilih ke
                                                    {{ block.subLabel }}
                                                </Button>
                                            </div>
                                            <div
                                                v-if="
                                                    getFilteredTkpiListForBlock(
                                                        block.id,
                                                    ).length === 0
                                                "
                                                class="p-6 text-center text-xs text-slate-400"
                                            >
                                                Bahan baku pangan tidak
                                                ditemukan.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Bahan Baku Mentah Khusus Blok Ini -->
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-[1100px] text-left text-xs border-collapse"
                                >
                                    <thead>
                                        <tr
                                            class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                        >
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-center w-10 border-r border-slate-200/80"
                                            >
                                                No
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 min-w-[170px] w-auto border-r border-slate-200/80 whitespace-normal break-words leading-tight"
                                            >
                                                Bahan Pangan (Master)
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 min-w-[180px] border-r border-slate-200/80 whitespace-normal break-words leading-tight"
                                            >
                                                Nama di PO
                                                <span
                                                    class="text-rose-500 font-black"
                                                    >*</span
                                                >
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 min-w-[210px] text-center border-r border-slate-200/80 whitespace-normal break-words leading-tight"
                                            >
                                                Peruntukan Porsi
                                            </th>
                                            <th
                                                colspan="2"
                                                class="p-2 text-center bg-emerald-50/80 text-emerald-950 border-r border-slate-200/80 font-black"
                                            >
                                                Berat Bersih (g)
                                            </th>
                                            <th
                                                colspan="2"
                                                class="p-2 text-center bg-blue-50/80 text-blue-950 border-r border-slate-200/80 font-black"
                                            >
                                                Berat Kotor (g)
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-center min-w-[65px] border-r border-slate-200/80"
                                            >
                                                BDD (%)
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-center min-w-[70px] border-r border-slate-200/80"
                                            >
                                                Buffer (%)
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-right bg-blue-50/60 text-blue-950 min-w-[95px] border-r border-slate-200/80 font-black"
                                            >
                                                Kebutuhan (kg)
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-right min-w-[115px] border-r border-slate-200/80 whitespace-normal break-words leading-tight"
                                            >
                                                Harga / Kg
                                                <span
                                                    class="text-rose-500 font-black"
                                                    >*</span
                                                >
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-right min-w-[95px] border-r border-slate-200/80"
                                            >
                                                Subtotal
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2.5 text-left min-w-[150px] border-r border-slate-200/80 whitespace-normal break-words leading-tight"
                                            >
                                                Keterangan
                                            </th>
                                            <th
                                                rowspan="2"
                                                class="p-2 text-center w-14 min-w-[56px] max-w-[56px] shrink-0"
                                            >
                                                Aksi
                                            </th>
                                        </tr>
                                        <tr
                                            class="bg-slate-50/90 border-b border-slate-200 text-[10px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                        >
                                            <th
                                                class="p-1.5 text-center bg-emerald-50/50 text-emerald-900 border-r border-slate-200/60 min-w-[80px]"
                                            >
                                                PK
                                            </th>
                                            <th
                                                class="p-1.5 text-center bg-emerald-50/50 text-emerald-900 border-r border-slate-200/80 min-w-[80px]"
                                            >
                                                PB
                                            </th>
                                            <th
                                                class="p-1.5 text-center bg-blue-50/50 text-blue-900 border-r border-slate-200/60 min-w-[75px]"
                                            >
                                                PK
                                            </th>
                                            <th
                                                class="p-1.5 text-center bg-blue-50/50 text-blue-900 border-r border-slate-200/80 min-w-[75px]"
                                            >
                                                PB
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-if="
                                                getBahanForBlock(block.id)
                                                    .length === 0
                                            "
                                        >
                                            <td
                                                colspan="15"
                                                class="p-6 text-center text-slate-400 font-medium bg-slate-50/30"
                                            >
                                                <div
                                                    class="flex flex-col items-center justify-center gap-1"
                                                >
                                                    <Package
                                                        class="h-6 w-6 text-slate-300 stroke-[1.5]"
                                                    />
                                                    <span
                                                        >Belum ada bahan baku
                                                        pangan untuk
                                                        <strong>{{
                                                            block.namaMenu
                                                        }}</strong
                                                        >.</span
                                                    >
                                                    <span
                                                        class="text-[11px] text-slate-400"
                                                        >Gunakan kolom pencarian
                                                        di atas untuk
                                                        menambahkan bahan baku
                                                        mentah.</span
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(
                                                it, itemIdx
                                            ) in getBahanForBlock(block.id)"
                                            :key="it.originalIndex"
                                            :id="
                                                'row-bahan-' + it.originalIndex
                                            "
                                            class="hover:bg-slate-50/70 transition-colors"
                                        >
                                            <td
                                                class="p-3 text-center font-bold text-slate-400 align-top pt-4 border-r border-slate-100"
                                            >
                                                {{ itemIdx + 1 }}
                                            </td>
                                            <!-- 1. Bahan Pangan (Master) - Wrap & Break Words -->
                                            <td
                                                class="p-3 font-bold text-slate-900 align-top pt-4 border-r border-slate-100 min-w-[170px] whitespace-normal break-words leading-snug"
                                            >
                                                <div
                                                    class="whitespace-normal break-words"
                                                >
                                                    {{ it.nama }}
                                                </div>
                                                <span
                                                    v-if="it.alergen"
                                                    class="block text-[9.5px] text-amber-700 font-normal mt-0.5 whitespace-normal break-words"
                                                >
                                                    Alergen: {{ it.alergen }}
                                                </span>
                                            </td>
                                            <!-- 2. Nama di PO (Wajib diisi & Wrap) -->
                                            <td
                                                class="p-3 align-top pt-3 border-r border-slate-100 min-w-[180px]"
                                            >
                                                <textarea
                                                    rows="2"
                                                    v-model="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ].nama_po
                                                    "
                                                    placeholder="Nama di PO (Wajib diisi)..."
                                                    :class="[
                                                        'w-full px-2.5 py-1.5 text-xs border rounded-lg focus:outline-hidden font-medium transition-colors resize-y min-h-[42px] leading-snug break-words',
                                                        validationErrors[
                                                            'nama_po_' +
                                                                it.originalIndex
                                                        ] ||
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_nama_po'
                                                        ]
                                                            ? 'border-rose-400 focus:border-rose-500 bg-rose-50/40 text-rose-900 placeholder:text-rose-400'
                                                            : !selectedBahanList[
                                                                    it
                                                                        .originalIndex
                                                                ].nama_po
                                                              ? 'border-amber-300 focus:border-amber-400 bg-amber-50/20 text-slate-800 placeholder:text-amber-600/70'
                                                              : 'border-slate-200 focus:border-primary text-slate-900',
                                                    ]"
                                                    @input="
                                                        clearError(
                                                            'nama_po_' +
                                                                it.originalIndex,
                                                        );
                                                        clearError(
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_nama_po',
                                                        );
                                                    "
                                                ></textarea>
                                                <p
                                                    v-if="
                                                        validationErrors[
                                                            'nama_po_' +
                                                                it.originalIndex
                                                        ] ||
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_nama_po'
                                                        ]
                                                    "
                                                    class="text-[10px] text-rose-600 mt-1 font-bold flex items-center gap-1 whitespace-normal break-words leading-tight"
                                                >
                                                    <span>⚠️</span>
                                                    <span>{{
                                                        validationErrors[
                                                            "nama_po_" +
                                                                it.originalIndex
                                                        ] ||
                                                        validationErrors[
                                                            "bahan_" +
                                                                it.originalIndex +
                                                                "_nama_po"
                                                        ]
                                                    }}</span>
                                                </p>
                                                <p
                                                    v-else-if="
                                                        !selectedBahanList[
                                                            it.originalIndex
                                                        ].nama_po
                                                    "
                                                    class="text-[9.5px] text-amber-700 mt-1 font-semibold flex items-center gap-1 whitespace-normal break-words leading-tight"
                                                >
                                                    <span>⚠️</span>
                                                    <span
                                                        >Wajib diisi untuk
                                                        PO</span
                                                    >
                                                </p>
                                            </td>
                                            <!-- 3. Peruntukan Porsi (Wrap & Full Display) -->
                                            <td
                                                class="p-3 align-top pt-3 border-r border-slate-100 min-w-[210px]"
                                            >
                                                <select
                                                    :value="
                                                        getPeruntukanPorsiValue(
                                                            selectedBahanList[
                                                                it.originalIndex
                                                            ],
                                                        )
                                                    "
                                                    @change="
                                                        handlePeruntukanPorsiChange(
                                                            it.originalIndex,
                                                            $event.target.value,
                                                        )
                                                    "
                                                    class="w-full px-2 py-1.5 text-xs border rounded-lg focus:outline-hidden focus:border-primary font-bold transition-colors cursor-pointer whitespace-normal break-words"
                                                    :class="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ]?.tipe_porsi ===
                                                        'alergi'
                                                            ? 'bg-rose-50 text-rose-900 border-rose-300'
                                                            : 'bg-white text-slate-800 border-slate-200'
                                                    "
                                                >
                                                    <option value="normal">
                                                        ✓ Porsi Normal (Standar)
                                                    </option>
                                                    <optgroup
                                                        v-if="
                                                            availableAlergiPmOptions.length >
                                                            0
                                                        "
                                                        label="Porsi Varian Khusus Alergi"
                                                    >
                                                        <option
                                                            v-for="opt in availableAlergiPmOptions"
                                                            :key="
                                                                opt.jenis_alergi
                                                            "
                                                            :value="`alergi:${opt.jenis_alergi}`"
                                                        >
                                                            ⚠️ Alergi:
                                                            {{
                                                                opt.jenis_alergi
                                                            }}
                                                            ({{ opt.total }}
                                                            Porsi • PK:
                                                            {{ opt.pk }}, PB:
                                                            {{ opt.pb }})
                                                        </option>
                                                    </optgroup>
                                                </select>
                                            </td>
                                            <!-- 4. Berat Bersih (g) - PK -->
                                            <td
                                                class="p-2.5 align-top pt-3 text-center bg-emerald-50/15 border-r border-slate-100"
                                            >
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    v-model.number="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ].gram_pk
                                                    "
                                                    placeholder="0"
                                                    :class="[
                                                        'w-16 text-center px-1.5 py-1 text-xs font-bold border rounded-lg focus:outline-hidden mx-auto transition-colors',
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_gram'
                                                        ]
                                                            ? 'border-rose-400 bg-rose-50/40 text-rose-900'
                                                            : (Number(
                                                                    selectedBahanList[
                                                                        it
                                                                            .originalIndex
                                                                    ].gram_pk,
                                                                ) || 0) <= 0 &&
                                                                (Number(
                                                                    selectedBahanList[
                                                                        it
                                                                            .originalIndex
                                                                    ].gram_pb,
                                                                ) || 0) <= 0
                                                              ? 'border-amber-300 bg-amber-50/20'
                                                              : 'border-slate-200 focus:border-primary',
                                                    ]"
                                                    @input="
                                                        clearError(
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_gram',
                                                        )
                                                    "
                                                />
                                                <div
                                                    class="text-[9.5px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                                >
                                                    ×
                                                    {{ it.targetPKCount }} porsi
                                                </div>
                                                <p
                                                    v-if="
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_gram'
                                                        ]
                                                    "
                                                    class="text-[9px] text-rose-600 font-bold mt-1 leading-tight whitespace-normal break-words"
                                                >
                                                    {{
                                                        validationErrors[
                                                            "bahan_" +
                                                                it.originalIndex +
                                                                "_gram"
                                                        ]
                                                    }}
                                                </p>
                                            </td>
                                            <!-- 5. Berat Bersih (g) - PB -->
                                            <td
                                                class="p-2.5 align-top pt-3 text-center bg-emerald-50/15 border-r border-slate-100"
                                            >
                                                <input
                                                    type="number"
                                                    step="0.1"
                                                    v-model.number="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ].gram_pb
                                                    "
                                                    placeholder="0"
                                                    :class="[
                                                        'w-16 text-center px-1.5 py-1 text-xs font-bold border rounded-lg focus:outline-hidden mx-auto transition-colors',
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_gram'
                                                        ]
                                                            ? 'border-rose-400 bg-rose-50/40 text-rose-900'
                                                            : (Number(
                                                                    selectedBahanList[
                                                                        it
                                                                            .originalIndex
                                                                    ].gram_pk,
                                                                ) || 0) <= 0 &&
                                                                (Number(
                                                                    selectedBahanList[
                                                                        it
                                                                            .originalIndex
                                                                    ].gram_pb,
                                                                ) || 0) <= 0
                                                              ? 'border-amber-300 bg-amber-50/20'
                                                              : 'border-slate-200 focus:border-primary',
                                                    ]"
                                                    @input="
                                                        clearError(
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_gram',
                                                        )
                                                    "
                                                />
                                                <div
                                                    class="text-[9.5px] text-slate-500 font-medium mt-1 whitespace-nowrap bg-slate-50 py-0.5 rounded border border-slate-100"
                                                >
                                                    ×
                                                    {{ it.targetPBCount }} porsi
                                                </div>
                                            </td>
                                            <!-- 6. Berat Kotor (g) - PK -->
                                            <td
                                                class="p-2.5 align-top pt-3.5 text-center bg-blue-50/15 border-r border-slate-100 font-bold text-blue-950"
                                            >
                                                <div>
                                                    {{
                                                        formatGram(
                                                            it.grossGramPK,
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                                >
                                                    (Net ÷ {{ it.bdd || 100 }}%)
                                                </div>
                                            </td>
                                            <!-- 7. Berat Kotor (g) - PB -->
                                            <td
                                                class="p-2.5 align-top pt-3.5 text-center bg-blue-50/15 border-r border-slate-100 font-bold text-blue-950"
                                            >
                                                <div>
                                                    {{
                                                        formatGram(
                                                            it.grossGramPB,
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                                >
                                                    (Net ÷ {{ it.bdd || 100 }}%)
                                                </div>
                                            </td>
                                            <!-- 8. BDD (%) -->
                                            <td
                                                class="p-3 text-center align-top pt-4 text-slate-700 font-medium border-r border-slate-100"
                                            >
                                                {{ it.bdd || 100 }}%
                                            </td>
                                            <!-- 9. Buffer (%) -->
                                            <td
                                                class="p-3 align-top pt-3 border-r border-slate-100"
                                            >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ].buffer
                                                    "
                                                    class="w-14 text-center px-1.5 py-1 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary mx-auto"
                                                />
                                            </td>
                                            <!-- 10. Kebutuhan (kg) -->
                                            <td
                                                class="p-3 text-right font-bold text-blue-950 bg-blue-50/30 align-top pt-4 whitespace-nowrap border-r border-slate-100"
                                            >
                                                {{
                                                    formatGrossWeight(
                                                        it.totalGrossKg,
                                                    )
                                                }}
                                            </td>
                                            <!-- 11. Harga / Kg (Wajib diisi > 0) -->
                                            <td
                                                class="p-3 align-top pt-3 border-r border-slate-100 min-w-[115px]"
                                            >
                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    placeholder="Rp 0 *"
                                                    :value="
                                                        formatHargaInput(
                                                            selectedBahanList[
                                                                it.originalIndex
                                                            ]?.harga_master,
                                                        )
                                                    "
                                                    @input="
                                                        handleHargaMasterInput(
                                                            it.originalIndex,
                                                            $event,
                                                        );
                                                        clearError(
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_harga',
                                                        );
                                                    "
                                                    :class="[
                                                        'w-28 text-right px-2 py-1 text-xs border rounded-lg focus:outline-hidden font-medium transition-colors',
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_harga'
                                                        ]
                                                            ? 'border-rose-400 bg-rose-50/40 text-rose-900 font-bold'
                                                            : !selectedBahanList[
                                                                    it
                                                                        .originalIndex
                                                                ]
                                                                    ?.harga_master ||
                                                                Number(
                                                                    selectedBahanList[
                                                                        it
                                                                            .originalIndex
                                                                    ]
                                                                        ?.harga_master,
                                                                ) <= 0
                                                              ? 'border-amber-300 bg-amber-50/20 text-slate-800'
                                                              : 'border-slate-200 focus:border-primary text-slate-800',
                                                    ]"
                                                />
                                                <p
                                                    v-if="
                                                        validationErrors[
                                                            'bahan_' +
                                                                it.originalIndex +
                                                                '_harga'
                                                        ]
                                                    "
                                                    class="text-[9.5px] text-rose-600 font-bold mt-1 leading-tight whitespace-normal break-words"
                                                >
                                                    {{
                                                        validationErrors[
                                                            "bahan_" +
                                                                it.originalIndex +
                                                                "_harga"
                                                        ]
                                                    }}
                                                </p>
                                                <p
                                                    v-else-if="
                                                        !selectedBahanList[
                                                            it.originalIndex
                                                        ]?.harga_master ||
                                                        Number(
                                                            selectedBahanList[
                                                                it.originalIndex
                                                            ]?.harga_master,
                                                        ) <= 0
                                                    "
                                                    class="text-[9px] text-amber-700 font-semibold mt-1 leading-tight whitespace-normal break-words"
                                                >
                                                    ⚠️ Harga wajib diisi > 0
                                                </p>
                                            </td>
                                            <!-- 12. Subtotal -->
                                            <td
                                                class="p-3 text-right font-bold text-emerald-800 align-top pt-4 whitespace-nowrap border-r border-slate-100"
                                            >
                                                {{
                                                    formatRupiah(
                                                        it.subtotalMaster,
                                                    )
                                                }}
                                            </td>
                                            <!-- 13. Keterangan (Catatan Tambahan) -->
                                            <td
                                                class="p-2.5 align-top pt-3 border-r border-slate-100 min-w-[150px]"
                                            >
                                                <textarea
                                                    rows="2"
                                                    v-model="
                                                        selectedBahanList[
                                                            it.originalIndex
                                                        ].keterangan
                                                    "
                                                    placeholder="Catatan / spesifikasi..."
                                                    class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-hidden focus:border-primary text-slate-800 placeholder:text-slate-400 font-medium resize-y min-h-[42px] leading-snug break-words"
                                                ></textarea>
                                            </td>
                                            <!-- 14. Aksi -->
                                            <td
                                                class="p-2 text-center align-middle w-14 min-w-[56px] max-w-[56px] shrink-0"
                                            >
                                                <button
                                                    type="button"
                                                    @click="
                                                        handleRemoveBahan(
                                                            it.originalIndex,
                                                        )
                                                    "
                                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center mx-auto"
                                                    title="Hapus Bahan dari Sub Menu"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot
                                        v-if="
                                            getBahanForBlock(block.id).length >
                                            0
                                        "
                                        class="bg-slate-50/90 font-bold border-t border-slate-200 text-xs"
                                    >
                                        <tr>
                                            <td
                                                colspan="10"
                                                class="p-2.5 text-right uppercase text-[10.5px] text-slate-600 font-extrabold border-r border-slate-200/80"
                                            >
                                                Total Kebutuhan
                                                {{ block.subLabel }}:
                                            </td>
                                            <td
                                                class="p-2.5 text-right font-black text-blue-950 bg-blue-100/30 whitespace-nowrap border-r border-slate-200/80"
                                            >
                                                {{
                                                    formatGrossWeight(
                                                        getBlockSummary(
                                                            block.id,
                                                        ).totalGrossKg,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="border-r border-slate-200/80"
                                            ></td>
                                            <td
                                                class="p-2.5 text-right font-black text-emerald-900 whitespace-nowrap border-r border-slate-200/80"
                                            >
                                                {{
                                                    formatRupiah(
                                                        getBlockSummary(
                                                            block.id,
                                                        ).totalCostMaster,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="border-r border-slate-200/80 min-w-[150px]"
                                            ></td>
                                            <td
                                                class="w-14 min-w-[56px] max-w-[56px]"
                                            ></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- TABEL REKAPITULASI SEMUA BAHAN PANGAN DARI SELURUH SUB MENU (READ-ONLY) -->
                    <!-- ========================================================================= -->
                    <div
                        id="tabel-rekapitulasi-bahan-pangan"
                        class="border border-indigo-200 rounded-2xl overflow-hidden bg-white shadow-xs scroll-mt-20"
                    >
                        <div
                            class="p-4 bg-gradient-to-r from-indigo-50/90 via-slate-50 to-indigo-50/60 border-b border-indigo-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0"
                                >
                                    <ClipboardList class="h-4.5 w-4.5" />
                                </div>
                                <div>
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <h4
                                            class="text-sm font-black text-slate-900 tracking-tight"
                                        >
                                            Rekapitulasi Semua Bahan Pangan (PO
                                            Belanja)
                                        </h4>
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-indigo-100 text-indigo-800 border border-indigo-200 shadow-2xs"
                                        >
                                            Mode Tinjauan (Read-Only)
                                        </span>
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 mt-0.5"
                                    >
                                        Daftar gabungan seluruh bahan baku
                                        mentah dari masing-masing Sub Menu dan
                                        varian pengganti alergi.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-700 shadow-2xs"
                                >
                                    Total:
                                    <strong class="text-indigo-600">{{
                                        bahanCalculations.length
                                    }}</strong>
                                    Bahan Baku Mentah
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Rekapitulasi Read-Only -->
                        <div class="overflow-x-auto">
                            <table
                                class="w-full min-w-[1200px] text-left text-xs border-collapse"
                            >
                                <thead>
                                    <tr
                                        class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                    >
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-center w-10 border-r border-slate-200/80"
                                        >
                                            No
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 min-w-[160px] border-r border-slate-200/80"
                                        >
                                            Sub Menu
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 min-w-[160px] border-r border-slate-200/80"
                                        >
                                            Bahan Pangan
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 min-w-[130px] border-r border-slate-200/80"
                                        >
                                            Nama di PO
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 min-w-[150px] text-center border-r border-slate-200/80"
                                        >
                                            Peruntukan Porsi
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 min-w-[85px] border-r border-slate-200/80"
                                        >
                                            Kategori
                                        </th>
                                        <th
                                            colspan="2"
                                            class="p-2 text-center bg-emerald-50/80 text-emerald-950 border-r border-slate-200/80 font-black"
                                        >
                                            Berat Bersih (g)
                                        </th>
                                        <th
                                            colspan="2"
                                            class="p-2 text-center bg-blue-50/80 text-blue-950 border-r border-slate-200/80 font-black"
                                        >
                                            Berat Kotor (g)
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-center min-w-[65px] border-r border-slate-200/80"
                                        >
                                            BDD (%)
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-center min-w-[70px] border-r border-slate-200/80"
                                        >
                                            Buffer (%)
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-right bg-blue-50/60 text-blue-950 min-w-[95px] border-r border-slate-200/80 font-black"
                                        >
                                            Kebutuhan (kg)
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-right min-w-[105px] border-r border-slate-200/80"
                                        >
                                            Harga / Kg
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-right min-w-[100px] border-r border-slate-200/80"
                                        >
                                            Subtotal PO
                                        </th>
                                        <th
                                            rowspan="2"
                                            class="p-2.5 text-left min-w-[150px] whitespace-normal break-words"
                                        >
                                            Keterangan
                                        </th>
                                    </tr>
                                    <tr
                                        class="bg-slate-50/90 border-b border-slate-200 text-[10px] font-bold text-slate-700 uppercase tracking-wider select-none"
                                    >
                                        <th
                                            class="p-1.5 text-center bg-emerald-50/50 text-emerald-900 border-r border-slate-200/60 min-w-[75px]"
                                        >
                                            PK
                                        </th>
                                        <th
                                            class="p-1.5 text-center bg-emerald-50/50 text-emerald-900 border-r border-slate-200/80 min-w-[75px]"
                                        >
                                            PB
                                        </th>
                                        <th
                                            class="p-1.5 text-center bg-blue-50/50 text-blue-900 border-r border-slate-200/60 min-w-[75px]"
                                        >
                                            PK
                                        </th>
                                        <th
                                            class="p-1.5 text-center bg-blue-50/50 text-blue-900 border-r border-slate-200/80 min-w-[75px]"
                                        >
                                            PB
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 text-slate-800"
                                >
                                    <tr v-if="bahanCalculations.length === 0">
                                        <td
                                            colspan="16"
                                            class="p-8 text-center text-slate-400 font-medium bg-slate-50/30"
                                        >
                                            <div
                                                class="flex flex-col items-center justify-center gap-1.5"
                                            >
                                                <Package
                                                    class="h-7 w-7 text-slate-300 stroke-[1.5]"
                                                />
                                                <span
                                                    class="text-xs font-bold text-slate-600"
                                                    >Belum ada bahan baku pangan
                                                    yang dialokasikan ke Sub
                                                    Menu manapun.</span
                                                >
                                                <span
                                                    class="text-[11px] text-slate-400"
                                                    >Pilih dan alokasikan bahan
                                                    pangan pada blok Sub Menu di
                                                    atas untuk melihat
                                                    rekapitulasi.</span
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(it, idx) in bahanCalculations"
                                        :key="idx"
                                        class="hover:bg-slate-50/70 transition-colors"
                                    >
                                        <td
                                            class="p-3 text-center font-bold text-slate-400 align-middle border-r border-slate-100"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 align-middle border-r border-slate-100"
                                        >
                                            <div class="space-y-1">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black border shadow-2xs"
                                                    :class="
                                                        getSubMenuLabelForBahan(
                                                            it,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            it,
                                                        ).label
                                                    }}
                                                </span>
                                                <div
                                                    class="font-extrabold text-slate-900 text-xs truncate max-w-[200px]"
                                                    :title="
                                                        getSubMenuLabelForBahan(
                                                            it,
                                                        ).namaMenu
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            it,
                                                        ).namaMenu
                                                    }}
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 font-bold text-slate-900 align-middle border-r border-slate-100 cursor-pointer group hover:bg-primary/5 transition-colors"
                                            @click="scrollToSubMenuBahan(it)"
                                            title="Klik untuk menuju ke baris bahan pangan ini di tabel Sub Menu"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 group-hover:text-primary transition-colors"
                                            >
                                                <span
                                                    class="underline decoration-slate-300 group-hover:decoration-primary underline-offset-2"
                                                    >{{ it.nama }}</span
                                                >
                                                <ExternalLink
                                                    class="h-3 w-3 text-slate-400 group-hover:text-primary transition-colors shrink-0"
                                                />
                                            </div>
                                            <span
                                                v-if="it.alergen"
                                                class="block text-[9.5px] text-amber-700 font-normal mt-0.5"
                                            >
                                                Alergen: {{ it.alergen }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-slate-700 align-middle font-medium border-r border-slate-100"
                                        >
                                            <span
                                                v-if="
                                                    it.nama_po &&
                                                    it.nama_po.trim()
                                                "
                                                class="text-slate-900 font-semibold"
                                            >
                                                {{ it.nama_po }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-slate-400 font-bold"
                                            >
                                                -
                                            </span>
                                        </td>
                                        <!-- Peruntukan Porsi (Read-Only) -->
                                        <td
                                            class="p-2.5 text-center align-middle border-r border-slate-100"
                                        >
                                            <span
                                                :class="[
                                                    'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block',
                                                    it.tipe_porsi === 'alergi'
                                                        ? 'bg-rose-50 text-rose-800 border-rose-200 shadow-2xs'
                                                        : 'bg-slate-100 text-slate-700 border-slate-200',
                                                ]"
                                            >
                                                {{
                                                    it.tipe_porsi === "alergi"
                                                        ? "⚠️ Alergi: " +
                                                          (it.jenis_alergi ||
                                                              "Khusus")
                                                        : "✓ Porsi Normal"
                                                }}
                                            </span>
                                            <div
                                                v-if="
                                                    it.tipe_porsi === 'alergi'
                                                "
                                                class="text-[9.5px] text-rose-700 font-bold mt-1"
                                            >
                                                {{ it.totalTargetCount }} Siswa
                                                (PK: {{ it.targetPKCount }}, PB:
                                                {{ it.targetPBCount }})
                                            </div>
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-middle border-r border-slate-100"
                                        >
                                            {{ it.kategori }}
                                        </td>
                                        <!-- Berat Bersih PK (g) -->
                                        <td
                                            class="p-2.5 text-center align-middle font-bold text-slate-800 bg-emerald-50/15 border-r border-slate-100"
                                        >
                                            {{ it.gram_pk }} g
                                            <div
                                                class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                            >
                                                × {{ it.targetPKCount }} porsi
                                            </div>
                                        </td>
                                        <!-- Berat Bersih PB (g) -->
                                        <td
                                            class="p-2.5 text-center align-middle font-bold text-slate-800 bg-emerald-50/15 border-r border-slate-100"
                                        >
                                            {{ it.gram_pb }} g
                                            <div
                                                class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                            >
                                                × {{ it.targetPBCount }} porsi
                                            </div>
                                        </td>
                                        <!-- Berat Kotor PK (g) -->
                                        <td
                                            class="p-2.5 text-center align-middle font-bold text-blue-950 bg-blue-50/15 border-r border-slate-100"
                                        >
                                            <div>
                                                {{ formatGram(it.grossGramPK) }}
                                            </div>
                                            <div
                                                class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                            >
                                                (Net ÷ {{ it.bdd || 100 }}%)
                                            </div>
                                        </td>
                                        <!-- Berat Kotor PB (g) -->
                                        <td
                                            class="p-2.5 text-center align-middle font-bold text-blue-950 bg-blue-50/15 border-r border-slate-100"
                                        >
                                            <div>
                                                {{ formatGram(it.grossGramPB) }}
                                            </div>
                                            <div
                                                class="text-[9.5px] text-slate-400 font-normal mt-0.5"
                                            >
                                                (Net ÷ {{ it.bdd || 100 }}%)
                                            </div>
                                        </td>
                                        <!-- BDD (%) -->
                                        <td
                                            class="p-3 text-center align-middle text-slate-700 font-medium border-r border-slate-100"
                                        >
                                            {{ it.bdd || 100 }}%
                                        </td>
                                        <!-- Buffer (%) -->
                                        <td
                                            class="p-3 text-center align-middle text-slate-700 font-medium border-r border-slate-100"
                                        >
                                            {{ it.buffer || 0 }}%
                                        </td>
                                        <!-- Kebutuhan (kg) -->
                                        <td
                                            class="p-3 text-right font-bold text-blue-950 bg-blue-50/30 align-middle whitespace-nowrap border-r border-slate-100"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    it.totalGrossKg,
                                                )
                                            }}
                                        </td>
                                        <!-- Harga / Kg -->
                                        <td
                                            class="p-3 text-right text-slate-700 align-middle whitespace-nowrap font-medium border-r border-slate-100"
                                        >
                                            {{ formatRupiah(it.harga_master) }}
                                        </td>
                                        <!-- Subtotal PO -->
                                        <td
                                            class="p-3 text-right font-bold text-emerald-800 align-middle whitespace-nowrap"
                                        >
                                            {{
                                                formatRupiah(it.subtotalMaster)
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-middle text-xs min-w-[150px] whitespace-normal break-words"
                                        >
                                            {{ it.keterangan || "-" }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    v-if="bahanCalculations.length > 0"
                                    class="bg-slate-50/90 font-bold border-t border-slate-200 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="12"
                                            class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold"
                                        >
                                            Total Rekapitulasi Kebutuhan Bahan
                                            (PO):
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
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- RINGKASAN GRAND TOTAL KESELURUHAN PO BELANJA -->
                    <!-- ========================================================================= -->
                    <div
                        class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs p-4 sm:p-5 bg-gradient-to-br from-slate-50/80 to-white space-y-4"
                    >
                        <div
                            class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-200 pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <Coins class="h-5 w-5 text-emerald-600" />
                                <h4
                                    class="text-sm font-black text-slate-900 uppercase tracking-wider"
                                >
                                    Rekapitulasi Grand Total Estimasi Belanja
                                    (PO)
                                </h4>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="text-xs font-bold text-slate-600 bg-white px-3 py-1 rounded-lg border border-slate-200 shadow-2xs"
                                >
                                    Total:
                                    <strong class="text-slate-900">{{
                                        selectedBahanList.length
                                    }}</strong>
                                    Bahan Baku Mentah
                                </span>
                                <button
                                    type="button"
                                    @click="showRumusBahan = !showRumusBahan"
                                    class="text-[11px] text-primary font-bold hover:underline cursor-pointer flex items-center gap-1 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs transition-colors hover:bg-slate-50"
                                >
                                    <Info class="h-3.5 w-3.5 text-primary shrink-0" />
                                    <span>{{
                                        showRumusBahan
                                            ? "Sembunyikan Rumus"
                                            : "Penjelasan Rumus Rekapitulasi"
                                    }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Penjelasan Rumus Rekapitulasi Grand Total PO -->
                        <div
                            v-if="showRumusBahan"
                            class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 text-xs space-y-3 text-slate-700 shadow-2xs"
                        >
                            <div
                                class="flex items-center gap-2 text-emerald-950 font-bold border-b border-emerald-200/80 pb-2"
                            >
                                <Info class="h-4 w-4 text-emerald-600 shrink-0" />
                                <span
                                    >Metode & Rumus Perhitungan Rekapitulasi Pengadaan Bahan Baku (PO)</span
                                >
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-3 leading-relaxed"
                            >
                                <div
                                    class="p-3 bg-white rounded-xl border border-emerald-100 space-y-1.5 shadow-2xs"
                                >
                                    <strong class="text-emerald-950 font-bold block flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                                        1. Total Berat Bersih (Net Kg):
                                    </strong>
                                    <p
                                        class="text-[11px] text-slate-800 font-mono bg-amber-50/80 p-2 rounded-lg border border-amber-200 text-center font-bold"
                                    >
                                        Net (Kg) = &sum; [ (Gram Bersih &times; Sasaran PM) &divide; 1.000 ]
                                    </p>
                                    <p class="text-[11px] text-slate-500 leading-snug">
                                        Total berat porsi konsumsi bersih seluruh siswa (Porsi Kecil + Porsi Besar). Pada menu varian alergi, jumlah sasaran disesuaikan otomatis dengan data siswa alergi terdampak.
                                    </p>
                                </div>
                                <div
                                    class="p-3 bg-white rounded-xl border border-emerald-100 space-y-1.5 shadow-2xs"
                                >
                                    <strong class="text-emerald-950 font-bold block flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                        2. Berat Pengadaan Kotor (Gross Kg):
                                    </strong>
                                    <p
                                        class="text-[11px] text-slate-800 font-mono bg-blue-50/80 p-2 rounded-lg border border-blue-200 text-center font-bold"
                                    >
                                        Gross (Kg) = [ Net &divide; (BDD% &divide; 100) ] &times; (1 + Buffer%)
                                    </p>
                                    <p class="text-[11px] text-slate-500 leading-snug">
                                        Memperhitungkan faktor bagian yang dapat dimakan (<strong>BDD%</strong>) dari kulit/tulang terbuang serta persentase <strong>Buffer Margin</strong> untuk toleransi penyusutan/masak.
                                    </p>
                                </div>
                                <div
                                    class="p-3 bg-white rounded-xl border border-emerald-100 space-y-1.5 shadow-2xs"
                                >
                                    <strong class="text-emerald-950 font-bold block flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                        3. Grand Total Anggaran Belanja PO:
                                    </strong>
                                    <p
                                        class="text-[11px] text-slate-800 font-mono bg-emerald-50/80 p-2 rounded-lg border border-emerald-200 text-center font-bold"
                                    >
                                        Grand Total = &sum; [ Gross (Kg) &times; Harga Satuan per Kg ]
                                    </p>
                                    <p class="text-[11px] text-slate-500 leading-snug">
                                        Akumulasi subtotal estimasi biaya seluruh item bahan mentah berdasarkan harga pasar/katalog yang diajukan ke bagian logistik pengadaan SPPG.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/90 text-amber-950"
                            >
                                <span
                                    class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block mb-1"
                                >
                                    Total Berat Bersih (Net)
                                </span>
                                <div
                                    class="text-base sm:text-lg font-black text-amber-950"
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
                                </div>
                            </div>
                            <div
                                class="p-3 rounded-xl bg-blue-50/70 border border-blue-200/90 text-blue-950"
                            >
                                <span
                                    class="text-[11px] font-bold text-blue-800 uppercase tracking-wider block mb-1"
                                >
                                    Total Berat Pengadaan Kotor (Gross)
                                </span>
                                <div
                                    class="text-base sm:text-lg font-black text-blue-950"
                                >
                                    {{
                                        formatGrossWeight(
                                            bahanCalculations.reduce(
                                                (acc, it) =>
                                                    acc + it.totalGrossKg,
                                                0,
                                            ),
                                        )
                                    }}
                                </div>
                            </div>
                            <div
                                class="p-3 rounded-xl bg-emerald-50/80 border border-emerald-300 text-emerald-950 shadow-2xs"
                            >
                                <span
                                    class="text-[11px] font-extrabold text-emerald-800 uppercase tracking-wider block mb-1"
                                >
                                    Grand Total Anggaran Belanja PO
                                </span>
                                <div
                                    class="text-base sm:text-xl font-black text-emerald-900"
                                >
                                    {{ formatRupiah(grandTotalDraftMaster) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analisis Food Cost & Batas Pagu Anggaran Mentah -->
                    <div class="space-y-4 pt-2">
                        <div
                            class="flex items-center justify-between flex-wrap gap-2"
                        >
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
                                class="text-[11px] text-primary font-bold hover:underline cursor-pointer flex items-center gap-1"
                            >
                                <span>{{
                                    showRumusCost
                                        ? "Sembunyikan Penjelasan Rumus"
                                        : "Tampilkan Penjelasan Rumus & Metode"
                                }}</span>
                            </button>
                        </div>

                        <!-- Penjelasan Rumus Food Cost -->
                        <div
                            v-if="showRumusCost"
                            class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 text-xs space-y-3 text-slate-700 shadow-2xs"
                        >
                            <div
                                class="flex items-center gap-2 text-blue-900 font-bold border-b border-blue-200 pb-2"
                            >
                                <Info class="h-4 w-4 text-blue-600 shrink-0" />
                                <span
                                    >Metode Perhitungan Food Cost per Porsi
                                    (Normal & Varian Alergi)</span
                                >
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-3 leading-relaxed"
                            >
                                <div
                                    class="p-3 bg-white rounded-xl border border-blue-100 space-y-1.5"
                                >
                                    <strong
                                        class="text-blue-950 font-bold block"
                                        >1. Rumus Food Cost per Bahan:</strong
                                    >
                                    <p
                                        class="text-[11px] text-slate-600 font-mono bg-slate-50 p-1.5 rounded border border-slate-200"
                                    >
                                        (Berat Bersih ÷ BDD%) × (1 + Buffer%) ×
                                        (Harga/Kg ÷ 1000)
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Perhitungan memperhitungkan bagian yang
                                        dapat dimakan (BDD) serta buffer
                                        pengadaan secara presisi tanpa
                                        pembulatan prematur.
                                    </p>
                                </div>
                                <div
                                    class="p-3 bg-white rounded-xl border border-blue-100 space-y-1.5"
                                >
                                    <strong
                                        class="text-blue-950 font-bold block"
                                        >2. Klasifikasi Porsi Normal vs Varian
                                        Alergi:</strong
                                    >
                                    <ul
                                        class="text-[11px] text-slate-600 space-y-1 list-disc list-inside"
                                    >
                                        <li>
                                            <strong>Porsi Normal:</strong>
                                            Akumulasi seluruh bahan porsi normal
                                            untuk siswa tanpa alergi terdampak.
                                        </li>
                                        <li>
                                            <strong>Porsi Alergi:</strong>
                                            (Bahan Normal Aman / Bebas Alergen)
                                            + (Bahan Substitusi Alergi). Bahan
                                            normal yang mengandung alergen
                                            otomatis dikeluarkan dari varian
                                            ini.
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Evaluasi Food Cost Porsi Normal -->
                        <div
                            class="space-y-2 bg-slate-50/70 p-4 rounded-3xl border border-slate-200/80 shadow-2xs"
                        >
                            <div
                                class="flex items-center justify-between flex-wrap gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <Utensils
                                        class="h-4 w-4 text-slate-700 shrink-0"
                                    />
                                    <span
                                        class="text-xs font-black uppercase tracking-wider text-slate-800"
                                    >
                                        Menu Utama (Porsi Normal)
                                    </span>
                                </div>
                                <span
                                    class="px-2.5 py-0.5 rounded-lg bg-slate-200 text-slate-800 text-[11px] font-bold"
                                >
                                    Sasaran:
                                    {{ targetSasaranNormal.total }} Porsi (PK:
                                    {{ targetSasaranNormal.pk }}, PB:
                                    {{ targetSasaranNormal.pb }})
                                </span>
                            </div>

                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1"
                            >
                                <!-- Card Food Cost PK Normal -->
                                <div
                                    class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                    :class="foodCostPKStatus.cardClass"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                            >Food Cost Porsi Kecil (PK)</span
                                        >
                                        <span
                                            class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                            :class="foodCostPKStatus.badgeClass"
                                        >
                                            {{ foodCostPKStatus.label }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-baseline justify-between"
                                    >
                                        <div
                                            class="text-2xl font-black text-slate-900"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPKNormal,
                                                )
                                            }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Batas Pagu:
                                            <strong>Rp 8.000</strong>
                                        </div>
                                    </div>
                                    <div
                                        class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full transition-all"
                                            :class="foodCostPKStatus.barClass"
                                            :style="{
                                                width: foodCostPKStatus.percentage,
                                            }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Card Food Cost PB Normal -->
                                <div
                                    class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                    :class="foodCostPBStatus.cardClass"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                            >Food Cost Porsi Besar (PB)</span
                                        >
                                        <span
                                            class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                            :class="foodCostPBStatus.badgeClass"
                                        >
                                            {{ foodCostPBStatus.label }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-baseline justify-between"
                                    >
                                        <div
                                            class="text-2xl font-black text-slate-900"
                                        >
                                            {{
                                                formatRupiah(
                                                    totalFoodCostPBNormal,
                                                )
                                            }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            Batas Pagu:
                                            <strong>Rp 10.000</strong>
                                        </div>
                                    </div>
                                    <div
                                        class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full transition-all"
                                            :class="foodCostPBStatus.barClass"
                                            :style="{
                                                width: foodCostPBStatus.percentage,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rincian Food Cost per Sub Menu (Porsi Normal) -->
                            <div
                                class="mt-3 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs"
                            >
                                <div
                                    class="p-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2"
                                >
                                    <span
                                        class="text-xs font-black text-slate-800 flex items-center gap-1.5 uppercase tracking-wider"
                                    >
                                        <Layers
                                            class="h-3.5 w-3.5 text-slate-600"
                                        />
                                        Rincian Food Cost per Sub Menu (Porsi
                                        Normal)
                                    </span>
                                    <span
                                        class="text-[11px] text-slate-500 font-medium"
                                    >
                                        Alokasi biaya bahan baku per komponen
                                        hidangan
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table
                                        class="w-full text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] select-none"
                                        >
                                            <tr>
                                                <th
                                                    class="p-2.5 text-center w-10"
                                                >
                                                    No
                                                </th>
                                                <th class="p-2.5 min-w-[200px]">
                                                    Sub Menu
                                                </th>
                                                <th
                                                    class="p-2.5 text-center min-w-[100px]"
                                                >
                                                    Bahan Baku
                                                </th>
                                                <th
                                                    class="p-2.5 text-right min-w-[150px] bg-amber-50/50 text-amber-950 font-black"
                                                >
                                                    Food Cost PK
                                                </th>
                                                <th
                                                    class="p-2.5 text-right min-w-[150px] bg-blue-50/50 text-blue-950 font-black"
                                                >
                                                    Food Cost PB
                                                </th>
                                                <th
                                                    class="p-2.5 min-w-[140px] text-center"
                                                >
                                                    Porsi Biaya (PK / PB)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-100 text-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    smCost, idx
                                                ) in foodCostSubMenuNormal"
                                                :key="smCost.key"
                                                class="hover:bg-slate-50/70 transition-colors"
                                            >
                                                <td
                                                    class="p-2.5 text-center font-bold text-slate-400 align-middle"
                                                >
                                                    {{ idx + 1 }}
                                                </td>
                                                <td class="p-2.5 align-middle">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <!-- <span
                                                            class="h-2 w-2 rounded-full shrink-0"
                                                            :class="
                                                                smCost.dotColor
                                                            "
                                                        ></span> -->
                                                        <div>
                                                            <span
                                                                class="text-[10px] font-extrabold uppercase px-1.5 py-1 rounded border"
                                                                :class="
                                                                    smCost.badgeColor
                                                                "
                                                            >
                                                                {{
                                                                    smCost.label
                                                                }}
                                                            </span>
                                                            <div
                                                                class="font-bold text-slate-900 text-xs mt-1.5"
                                                            >
                                                                {{
                                                                    smCost.nama_menu
                                                                }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td
                                                    class="p-2.5 text-center align-middle font-medium text-slate-600"
                                                >
                                                    <span
                                                        v-if="
                                                            smCost.items_count >
                                                            0
                                                        "
                                                        class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700"
                                                    >
                                                        {{ smCost.items_count }}
                                                        Bahan
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-slate-400 italic text-[11px]"
                                                        >-</span
                                                    >
                                                </td>
                                                <td
                                                    class="p-2.5 text-right align-middle bg-amber-50/20"
                                                >
                                                    <div
                                                        class="font-black text-slate-900 text-xs"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                smCost.cost_pk,
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-[10px] text-amber-800 font-medium"
                                                    >
                                                        {{
                                                            smCost.percent_pk.toFixed(
                                                                1,
                                                            )
                                                        }}% dari total
                                                    </div>
                                                </td>
                                                <td
                                                    class="p-2.5 text-right align-middle bg-blue-50/20"
                                                >
                                                    <div
                                                        class="font-black text-slate-900 text-xs"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                smCost.cost_pb,
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-[10px] text-blue-800 font-medium"
                                                    >
                                                        {{
                                                            smCost.percent_pb.toFixed(
                                                                1,
                                                            )
                                                        }}% dari total
                                                    </div>
                                                </td>
                                                <td class="p-2.5 align-middle">
                                                    <div
                                                        class="space-y-1 w-28 mx-auto"
                                                    >
                                                        <div
                                                            class="flex items-center gap-1.5 text-[9.5px]"
                                                        >
                                                            <span
                                                                class="font-bold text-amber-800 w-5 shrink-0"
                                                                >PK</span
                                                            >
                                                            <div
                                                                class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                            >
                                                                <div
                                                                    class="bg-amber-500 h-full rounded-full transition-all"
                                                                    :style="{
                                                                        width:
                                                                            smCost.percent_pk +
                                                                            '%',
                                                                    }"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-1.5 text-[9.5px]"
                                                        >
                                                            <span
                                                                class="font-bold text-blue-800 w-5 shrink-0"
                                                                >PB</span
                                                            >
                                                            <div
                                                                class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                            >
                                                                <div
                                                                    class="bg-blue-600 h-full rounded-full transition-all"
                                                                    :style="{
                                                                        width:
                                                                            smCost.percent_pb +
                                                                            '%',
                                                                    }"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot
                                            class="bg-slate-50/90 font-black border-t border-slate-200 text-xs"
                                        >
                                            <tr>
                                                <td
                                                    colspan="3"
                                                    class="p-2.5 text-right uppercase tracking-wider text-slate-700"
                                                >
                                                    Total Food Cost Porsi
                                                    Normal:
                                                </td>
                                                <td
                                                    class="p-2.5 text-right text-emerald-900 bg-amber-100/40 text-sm"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPKNormal,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="p-2.5 text-right text-emerald-900 bg-blue-100/40 text-sm"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            totalFoodCostPBNormal,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="p-2.5 text-center text-[10px] text-slate-500"
                                                >
                                                    100% Total
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Evaluasi Food Cost Varian Khusus Alergi -->
                        <div
                            v-if="activeAlergiFoodCostList.length > 0"
                            class="space-y-4 pt-2"
                        >
                            <div
                                v-for="alCost in activeAlergiFoodCostList"
                                :key="alCost.jenis_alergi"
                                class="space-y-3 bg-amber-50/40 p-4 rounded-3xl border border-amber-200/80 shadow-2xs"
                            >
                                <div
                                    class="flex items-center justify-between flex-wrap gap-2"
                                >
                                    <h5
                                        class="text-xs font-black uppercase tracking-wider text-amber-950 flex items-center gap-2"
                                    >
                                        <ShieldAlert
                                            class="h-4 w-4 text-amber-600 shrink-0"
                                        />
                                        <span
                                            >Food Cost Varian Khusus Alergi:
                                            {{ alCost.jenis_alergi }}</span
                                        >
                                    </h5>
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <span
                                            class="px-2.5 py-0.5 rounded-lg bg-amber-100 text-amber-800 border border-amber-300 text-[11px] font-bold"
                                        >
                                            Sasaran:
                                            {{ alCost.total_siswa }} Porsi (PK:
                                            {{ alCost.siswa_pk }}, PB:
                                            {{ alCost.siswa_pb }})
                                        </span>
                                        <span
                                            class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] font-bold"
                                        >
                                            Subtotal PO:
                                            {{
                                                formatRupiah(alCost.total_biaya)
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Card Food Cost PK Alergi -->
                                    <div
                                        class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                        :class="alCost.status_pk.cardClass"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                                >Food Cost PK •
                                                {{ alCost.jenis_alergi }}</span
                                            >
                                            <span
                                                class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                                :class="
                                                    alCost.status_pk.badgeClass
                                                "
                                            >
                                                {{ alCost.status_pk.label }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-baseline justify-between"
                                        >
                                            <div
                                                class="text-2xl font-black text-slate-900"
                                            >
                                                {{
                                                    formatRupiah(alCost.cost_pk)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Batas Pagu:
                                                <strong>Rp 8.000</strong>
                                            </div>
                                        </div>
                                        <div
                                            class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                        >
                                            <div
                                                class="h-full transition-all"
                                                :class="
                                                    alCost.status_pk.barClass
                                                "
                                                :style="{
                                                    width: alCost.status_pk
                                                        .percentage,
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Card Food Cost PB Alergi -->
                                    <div
                                        class="p-4 rounded-2xl border bg-white shadow-2xs space-y-3"
                                        :class="alCost.status_pb.cardClass"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                                >Food Cost PB •
                                                {{ alCost.jenis_alergi }}</span
                                            >
                                            <span
                                                class="text-xs font-black px-2.5 py-0.5 rounded-lg border"
                                                :class="
                                                    alCost.status_pb.badgeClass
                                                "
                                            >
                                                {{ alCost.status_pb.label }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-baseline justify-between"
                                        >
                                            <div
                                                class="text-2xl font-black text-slate-900"
                                            >
                                                {{
                                                    formatRupiah(alCost.cost_pb)
                                                }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Batas Pagu:
                                                <strong>Rp 10.000</strong>
                                            </div>
                                        </div>
                                        <div
                                            class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"
                                        >
                                            <div
                                                class="h-full transition-all"
                                                :class="
                                                    alCost.status_pb.barClass
                                                "
                                                :style="{
                                                    width: alCost.status_pb
                                                        .percentage,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rincian Transparansi Bahan Masuk & Dikeluarkan -->
                                <div
                                    class="bg-white/90 p-3 rounded-xl border border-amber-200/70 text-[11px] space-y-1 text-slate-600"
                                >
                                    <div
                                        class="flex items-center justify-between flex-wrap gap-1 font-semibold text-amber-900"
                                    >
                                        <span>Komposisi Bahan Masuk:</span>
                                        <span class="text-slate-700">
                                            {{
                                                alCost.bahan_normal_aman.length
                                            }}
                                            Bahan Normal Bebas Alergen +
                                            {{ alCost.bahan_substitusi.length }}
                                            Bahan Substitusi
                                        </span>
                                    </div>
                                    <div
                                        v-if="
                                            alCost.bahan_dikeluarkan.length > 0
                                        "
                                        class="text-rose-700 text-[10.5px]"
                                    >
                                        <span class="font-bold"
                                            >Bahan Normal Dieliminasi:</span
                                        >
                                        {{
                                            alCost.bahan_dikeluarkan
                                                .map((b) => b.nama)
                                                .join(", ")
                                        }}
                                        (mengandung {{ alCost.jenis_alergi }})
                                    </div>
                                </div>

                                <!-- Rincian Food Cost per Sub Menu (Varian Alergi) -->
                                <div
                                    class="bg-white rounded-2xl border border-amber-200/90 overflow-hidden shadow-2xs mt-2"
                                >
                                    <div
                                        class="p-3 bg-amber-50/70 border-b border-amber-200 flex items-center justify-between flex-wrap gap-2"
                                    >
                                        <span
                                            class="text-xs font-black text-amber-950 flex items-center gap-1.5 uppercase tracking-wider"
                                        >
                                            <Layers
                                                class="h-3.5 w-3.5 text-amber-700"
                                            />
                                            Rincian Food Cost per Sub Menu
                                            (Varian {{ alCost.jenis_alergi }})
                                        </span>
                                        <span
                                            class="text-[11px] text-amber-800 font-medium"
                                        >
                                            Termasuk penyesuaian bahan
                                            substitusi & eliminasi alergen
                                        </span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table
                                            class="w-full text-left text-xs border-collapse"
                                        >
                                            <thead
                                                class="bg-amber-50/40 text-slate-700 font-bold border-b border-amber-200 uppercase text-[10px] select-none"
                                            >
                                                <tr>
                                                    <th
                                                        class="p-2.5 text-center w-10"
                                                    >
                                                        No
                                                    </th>
                                                    <th
                                                        class="p-2.5 min-w-[220px]"
                                                    >
                                                        Sub Menu
                                                    </th>
                                                    <th
                                                        class="p-2.5 text-center min-w-[100px]"
                                                    >
                                                        Bahan Baku
                                                    </th>
                                                    <th
                                                        class="p-2.5 text-right min-w-[150px] bg-amber-50/50 text-amber-950 font-black"
                                                    >
                                                        Food Cost PK
                                                    </th>
                                                    <th
                                                        class="p-2.5 text-right min-w-[150px] bg-blue-50/50 text-blue-950 font-black"
                                                    >
                                                        Food Cost PB
                                                    </th>
                                                    <th
                                                        class="p-2.5 min-w-[140px] text-center"
                                                    >
                                                        Porsi Biaya (PK / PB)
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-amber-100/60 text-slate-800"
                                            >
                                                <tr
                                                    v-for="(
                                                        smCost, idx
                                                    ) in getFoodCostSubMenuForAlergi(
                                                        alCost.jenis_alergi,
                                                    )"
                                                    :key="smCost.key"
                                                    class="hover:bg-amber-50/30 transition-colors"
                                                >
                                                    <td
                                                        class="p-2.5 text-center font-bold text-slate-400 align-middle"
                                                    >
                                                        {{ idx + 1 }}
                                                    </td>
                                                    <td
                                                        class="p-2.5 align-middle"
                                                    >
                                                        <div class="space-y-1">
                                                            <div
                                                                class="flex items-center gap-1.5 flex-wrap"
                                                            >
                                                                <span
                                                                    class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded border"
                                                                    :class="
                                                                        smCost.badgeColor
                                                                    "
                                                                >
                                                                    {{
                                                                        smCost.label
                                                                    }}
                                                                </span>
                                                                <span
                                                                    v-if="
                                                                        smCost.is_substituted
                                                                    "
                                                                    class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-300 font-black text-[9.5px]"
                                                                >
                                                                    ★ Substitusi
                                                                    Khusus
                                                                </span>
                                                                <span
                                                                    v-else-if="
                                                                        smCost.is_eliminated
                                                                    "
                                                                    class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300 font-black text-[9.5px]"
                                                                >
                                                                    ⚠ Alergen
                                                                    Dieliminasi
                                                                </span>
                                                            </div>
                                                            <div
                                                                class="font-bold text-slate-900 text-xs"
                                                            >
                                                                {{
                                                                    smCost.nama_menu
                                                                }}
                                                            </div>
                                                            <div
                                                                v-if="
                                                                    smCost.is_substituted &&
                                                                    smCost.normal_menu_name !==
                                                                        smCost.nama_menu
                                                                "
                                                                class="text-[10px] text-slate-400"
                                                            >
                                                                Menu Normal:
                                                                {{
                                                                    smCost.normal_menu_name
                                                                }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-center align-middle font-medium text-slate-600"
                                                    >
                                                        <span
                                                            v-if="
                                                                smCost.items_count >
                                                                0
                                                            "
                                                            class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700"
                                                        >
                                                            {{
                                                                smCost.items_count
                                                            }}
                                                            Bahan
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="text-slate-400 italic text-[11px]"
                                                            >-</span
                                                        >
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-right align-middle bg-amber-50/20"
                                                    >
                                                        <div
                                                            class="font-black text-slate-900 text-xs"
                                                        >
                                                            {{
                                                                formatRupiah(
                                                                    smCost.cost_pk,
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-[10px] text-amber-800 font-medium"
                                                        >
                                                            {{
                                                                smCost.percent_pk.toFixed(
                                                                    1,
                                                                )
                                                            }}% dari total
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-right align-middle bg-blue-50/20"
                                                    >
                                                        <div
                                                            class="font-black text-slate-900 text-xs"
                                                        >
                                                            {{
                                                                formatRupiah(
                                                                    smCost.cost_pb,
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-[10px] text-blue-800 font-medium"
                                                        >
                                                            {{
                                                                smCost.percent_pb.toFixed(
                                                                    1,
                                                                )
                                                            }}% dari total
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="p-2.5 align-middle"
                                                    >
                                                        <div
                                                            class="space-y-1 w-28 mx-auto"
                                                        >
                                                            <div
                                                                class="flex items-center gap-1.5 text-[9.5px]"
                                                            >
                                                                <span
                                                                    class="font-bold text-amber-800 w-5 shrink-0"
                                                                    >PK</span
                                                                >
                                                                <div
                                                                    class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                                >
                                                                    <div
                                                                        class="bg-amber-500 h-full rounded-full transition-all"
                                                                        :style="{
                                                                            width:
                                                                                smCost.percent_pk +
                                                                                '%',
                                                                        }"
                                                                    ></div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="flex items-center gap-1.5 text-[9.5px]"
                                                            >
                                                                <span
                                                                    class="font-bold text-blue-800 w-5 shrink-0"
                                                                    >PB</span
                                                                >
                                                                <div
                                                                    class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                                >
                                                                    <div
                                                                        class="bg-blue-600 h-full rounded-full transition-all"
                                                                        :style="{
                                                                            width:
                                                                                smCost.percent_pb +
                                                                                '%',
                                                                        }"
                                                                    ></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot
                                                class="bg-amber-50/70 font-black border-t border-amber-200 text-xs"
                                            >
                                                <tr>
                                                    <td
                                                        colspan="3"
                                                        class="p-2.5 text-right uppercase tracking-wider text-amber-950"
                                                    >
                                                        Total Food Cost Varian
                                                        {{
                                                            alCost.jenis_alergi
                                                        }}:
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-right text-emerald-900 bg-amber-100/60 text-sm"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                alCost.cost_pk,
                                                            )
                                                        }}
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-right text-emerald-900 bg-blue-100/60 text-sm"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                alCost.cost_pb,
                                                            )
                                                        }}
                                                    </td>
                                                    <td
                                                        class="p-2.5 text-center text-[10px] text-amber-800"
                                                    >
                                                        100% Total
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Evaluasi Standar Angka Kecukupan Gizi (AKG) BGN (Step 2) -->
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <div
                                class="flex items-center justify-between flex-wrap gap-2"
                            >
                                <h4
                                    class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2"
                                >
                                    <Activity
                                        class="h-4 w-4 text-emerald-600"
                                    />
                                    <span
                                        >Evaluasi Standar Angka Kecukupan Gizi
                                        (AKG) Badan Gizi Nasional (BGN)</span
                                    >
                                </h4>
                                <button
                                    type="button"
                                    @click="showRumusAkg = !showRumusAkg"
                                    class="text-[11px] text-primary font-bold hover:underline cursor-pointer flex items-center gap-1.5 bg-white px-3 py-1 rounded-lg border border-slate-200 shadow-2xs transition-colors hover:bg-slate-50"
                                >
                                    <Info class="h-3.5 w-3.5 text-primary shrink-0" />
                                    <span>{{
                                        showRumusAkg
                                            ? "Sembunyikan Penjelasan Rumus"
                                            : "Tampilkan Penjelasan Rumus & Standar AKG"
                                    }}</span>
                                </button>
                            </div>

                            <!-- Panel Penjelasan Rumus & Standar AKG BGN -->
                            <div
                                v-if="showRumusAkg"
                                class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 text-xs space-y-4 text-slate-700 shadow-2xs"
                            >
                                <div
                                    class="flex items-center gap-2 text-amber-950 font-bold border-b border-amber-200/80 pb-2"
                                >
                                    <Info class="h-4 w-4 text-amber-600 shrink-0" />
                                    <span
                                        >Metode Perhitungan Nilai Zat Gizi & Standar Acuan AKG BGN (Makan Siang MBG)</span
                                    >
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 leading-relaxed">
                                    <div
                                        class="p-3 bg-white rounded-xl border border-amber-100 space-y-2 shadow-2xs"
                                    >
                                        <strong class="text-amber-950 font-bold block flex items-center gap-1.5">
                                            <Calculator class="h-4 w-4 text-amber-600" />
                                            1. Rumus Perhitungan Nilai Gizi per Porsi:
                                        </strong>
                                        <p
                                            class="text-[11px] text-slate-800 font-mono bg-amber-50/70 p-2 rounded-lg border border-amber-200 text-center font-bold"
                                        >
                                            Zat Gizi = &sum; [ (Gram Bersih &divide; 100) &times; Nilai Gizi per 100g TKPI ]
                                        </p>
                                        <p class="text-[11px] text-slate-500 leading-snug">
                                            Kandungan zat gizi (Energi, Protein, Lemak, Karbohidrat, Serat) dihitung otomatis berdasarkan porsi berat bersih (<em>edible weight</em>) masing-masing bahan masakan terhadap basis data Tabel Komposisi Pangan Indonesia (TKPI).
                                        </p>
                                    </div>
                                    <div
                                        class="p-3 bg-white rounded-xl border border-amber-100 space-y-2 shadow-2xs"
                                    >
                                        <strong class="text-amber-950 font-bold block flex items-center gap-1.5">
                                            <HeartPulse class="h-4 w-4 text-rose-500" />
                                            2. Standar Acuan AKG Makan Siap Santap BGN:
                                        </strong>
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div class="p-2.5 rounded-lg bg-amber-50/80 border border-amber-200/70 space-y-1">
                                                <span class="font-extrabold text-amber-950 block border-b border-amber-200 pb-0.5 text-[11px]">Porsi Kecil (PK):</span>
                                                <div class="text-slate-600 space-y-0.5 text-[10.5px]">
                                                    <div>&bull; Energi: <strong class="text-slate-900">450 - 550 kkal</strong></div>
                                                    <div>&bull; Protein: <strong class="text-slate-900">15 - 22 g</strong></div>
                                                    <div>&bull; Lemak: <strong class="text-slate-900">15 - 20 g</strong></div>
                                                    <div>&bull; Karbo: <strong class="text-slate-900">60 - 85 g</strong></div>
                                                    <div>&bull; Serat: <strong class="text-slate-900">5 - 7 g</strong></div>
                                                </div>
                                            </div>
                                            <div class="p-2.5 rounded-lg bg-blue-50/80 border border-blue-200/70 space-y-1">
                                                <span class="font-extrabold text-blue-950 block border-b border-blue-200 pb-0.5 text-[11px]">Porsi Besar (PB):</span>
                                                <div class="text-slate-600 space-y-0.5 text-[10.5px]">
                                                    <div>&bull; Energi: <strong class="text-slate-900">650 - 800 kkal</strong></div>
                                                    <div>&bull; Protein: <strong class="text-slate-900">20 - 30 g</strong></div>
                                                    <div>&bull; Lemak: <strong class="text-slate-900">20 - 30 g</strong></div>
                                                    <div>&bull; Karbo: <strong class="text-slate-900">90 - 120 g</strong></div>
                                                    <div>&bull; Serat: <strong class="text-slate-900">7 - 10 g</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 1. Evaluasi AKG Menu Utama (Porsi Normal) -->
                            <div
                                class="space-y-3 bg-slate-50/70 p-4 rounded-3xl border border-slate-200/80 shadow-2xs"
                            >
                                <div
                                    class="flex items-center justify-between flex-wrap gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <Utensils
                                            class="h-4 w-4 text-slate-700 shrink-0"
                                        />
                                        <span
                                            class="text-xs font-black uppercase tracking-wider text-slate-800"
                                        >
                                            Menu Utama (Porsi Normal)
                                        </span>
                                    </div>
                                    <span
                                        class="px-2.5 py-0.5 rounded-lg bg-slate-200 text-slate-800 text-[11px] font-bold"
                                    >
                                        Sasaran:
                                        {{ targetSasaranNormal.total }} Porsi
                                        (PK: {{ targetSasaranNormal.pk }}, PB:
                                        {{ targetSasaranNormal.pb }})
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Card AKG Porsi Kecil (PK) Normal -->
                                    <div
                                        class="p-4 bg-amber-50/40 rounded-2xl border border-amber-200 space-y-3 shadow-2xs"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h5
                                                class="text-xs font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5"
                                            >
                                                <Activity
                                                    class="h-4 w-4 text-amber-600"
                                                />
                                                <span>Porsi Kecil (PK)</span>
                                            </h5>
                                            <Badge
                                                variant="outline"
                                                :class="
                                                    getAkgStatusBadge(
                                                        akgResultPKNormal,
                                                        false,
                                                    ).badgeClass
                                                "
                                            >
                                                {{
                                                    getAkgStatusBadge(
                                                        akgResultPKNormal,
                                                        false,
                                                    ).label
                                                }}
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
                                                    {{
                                                        akgResultPKNormal.energi
                                                    }}
                                                    kkal
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
                                                    {{
                                                        akgResultPKNormal.protein
                                                    }}
                                                    g
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
                                                    {{
                                                        akgResultPKNormal.lemak
                                                    }}
                                                    g
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
                                                    {{
                                                        akgResultPKNormal.karbohidrat
                                                    }}
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
                                                    {{
                                                        akgResultPKNormal.serat
                                                    }}
                                                    g
                                                </div>
                                                <span
                                                    class="text-[9.5px] text-slate-400"
                                                    >Target: Min 4.0 g</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card AKG Porsi Besar (PB) Normal -->
                                    <div
                                        class="p-4 bg-indigo-50/40 rounded-2xl border border-indigo-200 space-y-3 shadow-2xs"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h5
                                                class="text-xs font-black uppercase tracking-wider text-indigo-900 flex items-center gap-1.5"
                                            >
                                                <Activity
                                                    class="h-4 w-4 text-indigo-600"
                                                />
                                                <span>Porsi Besar (PB)</span>
                                            </h5>
                                            <Badge
                                                variant="outline"
                                                :class="
                                                    getAkgStatusBadge(
                                                        akgResultPBNormal,
                                                        true,
                                                    ).badgeClass
                                                "
                                            >
                                                {{
                                                    getAkgStatusBadge(
                                                        akgResultPBNormal,
                                                        true,
                                                    ).label
                                                }}
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
                                                    {{
                                                        akgResultPBNormal.energi
                                                    }}
                                                    kkal
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
                                                    {{
                                                        akgResultPBNormal.protein
                                                    }}
                                                    g
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
                                                    {{
                                                        akgResultPBNormal.lemak
                                                    }}
                                                    g
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
                                                    {{
                                                        akgResultPBNormal.karbohidrat
                                                    }}
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
                                                    {{
                                                        akgResultPBNormal.serat
                                                    }}
                                                    g
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

                            <!-- 2. Kartu Evaluasi AKG Varian Khusus Alergi -->
                            <div
                                v-if="activeAlergiAkgList.length > 0"
                                class="space-y-4 pt-2"
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
                                                >Evaluasi Standar AKG Varian
                                                Khusus Alergi:
                                                {{ alRes.jenis_alergi }}</span
                                            >
                                        </h5>
                                        <span
                                            class="px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-800 border border-rose-300 text-[11px] font-bold"
                                        >
                                            Sasaran:
                                            {{ alRes.total_siswa }} Porsi (PK:
                                            {{ alRes.jml_pk }}, PB:
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
                                                        >Porsi Kecil (PK) •
                                                        Varian
                                                        {{
                                                            alRes.jenis_alergi
                                                        }}</span
                                                    >
                                                </h6>
                                                <Badge
                                                    variant="outline"
                                                    :class="
                                                        getAkgStatusBadge(
                                                            alRes.pk,
                                                            false,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getAkgStatusBadge(
                                                            alRes.pk,
                                                            false,
                                                        ).label
                                                    }}
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
                                                        {{ alRes.pk.energi }}
                                                        kkal
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
                                                        {{
                                                            alRes.pk.karbohidrat
                                                        }}
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
                                                        >Porsi Besar (PB) •
                                                        Varian
                                                        {{
                                                            alRes.jenis_alergi
                                                        }}</span
                                                    >
                                                </h6>
                                                <Badge
                                                    variant="outline"
                                                    :class="
                                                        getAkgStatusBadge(
                                                            alRes.pb,
                                                            true,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getAkgStatusBadge(
                                                            alRes.pb,
                                                            true,
                                                        ).label
                                                    }}
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
                                                        {{ alRes.pb.energi }}
                                                        kkal
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
                                                        {{
                                                            alRes.pb.karbohidrat
                                                        }}
                                                        g
                                                    </div>
                                                    <span
                                                        class="text-[9.5px] text-slate-400"
                                                        >Target: 85 - 110
                                                        g</span
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
                        Lanjutkan ke Langkah 3 untuk mereview rangkuman dokumen
                        perencanaan, evaluasi gizi & biaya, serta mengajukan
                        Work Order.
                    </p>
                </div>
                <div
                    class="flex items-center gap-2.5 shrink-0 flex-wrap w-full sm:w-auto"
                >
                    <Button
                        type="button"
                        @click="handleSwitchSubTab('work_order')"
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
                        <Send class="h-4 w-4 shrink-0" />
                        <span>Lanjut ke Review & Pengajuan (Langkah 3)</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================================================================= -->
        <!-- Bagian 3: Review & Pengajuan Lengkap Work Order (Step 3) -->
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
                                class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                            >
                                <FileSpreadsheet class="h-5 w-5 text-primary" />
                                <span>Review Lengkap Rancangan Menu MBG</span>
                            </CardTitle>
                            <!-- <span
                                class="px-2.5 py-0.5 text-xs font-black bg-primary/10 text-primary rounded-full"
                            >
                                Langkah 3 dari 3
                            </span> -->
                        </div>
                        <CardDescription
                            class="text-xs sm:text-sm text-slate-500 mt-1"
                        >
                            Tinjauan menyeluruh hasil perencanaan produksi, 5
                            sub menu hidangan, kuota sasaran penerima manfaat,
                            formulasi bahan makanan & PO belanja, evaluasi
                            pemenuhan standar AKG BGN, serta kalkulasi food cost
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
                    <div
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4"
                    >
                        <!-- Card 1: No WO -->
                        <div
                            class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 col-span-2 sm:col-span-1"
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

                        <!-- Card 2: Sasaran PK Normal -->
                        <div
                            class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200/80 col-span-1"
                        >
                            <p
                                class="text-[10.5px] font-bold text-amber-800 uppercase tracking-wider"
                            >
                                Sasaran PK (Normal)
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-amber-950 mt-1"
                            >
                                {{
                                    targetSasaranNormal.pk.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                                Porsi
                            </p>
                            <p class="text-[11px] text-amber-800 mt-0.5">
                                Food Cost:
                                {{ formatRupiah(totalFoodCostPKNormal) }}
                            </p>
                        </div>

                        <!-- Card 3: Sasaran PB Normal -->
                        <div
                            class="p-3.5 bg-indigo-50/70 rounded-xl border border-indigo-200/80 col-span-1"
                        >
                            <p
                                class="text-[10.5px] font-bold text-indigo-800 uppercase tracking-wider"
                            >
                                Sasaran PB (Normal)
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-indigo-950 mt-1"
                            >
                                {{
                                    targetSasaranNormal.pb.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                                Porsi
                            </p>
                            <p class="text-[11px] text-indigo-800 mt-0.5">
                                Food Cost:
                                {{ formatRupiah(totalFoodCostPBNormal) }}
                            </p>
                        </div>

                        <!-- Card 4+: Kartu Sasaran Khusus Setiap Varian Alergi yang Ada -->
                        <div
                            v-for="al in activeAlergiFoodCostList"
                            :key="'header-al-card-' + al.jenis_alergi"
                            class="p-3.5 bg-rose-50/80 rounded-xl border border-rose-200/90 col-span-1 shadow-2xs space-y-0.5"
                        >
                            <p
                                class="text-[10.5px] font-black text-rose-800 uppercase tracking-wider truncate"
                                :title="'Sasaran Alergi: ' + al.jenis_alergi"
                            >
                                ⚠️ Alergi: {{ al.jenis_alergi }}
                            </p>
                            <p
                                class="text-sm sm:text-base font-black text-rose-950 mt-1"
                            >
                                {{ al.total_siswa.toLocaleString("id-ID") }}
                                Porsi
                            </p>
                            <p class="text-[10px] text-rose-700 font-bold">
                                PK: {{ al.siswa_pk }} • PB: {{ al.siswa_pb }}
                            </p>
                            <p class="text-[9.5px] text-rose-600 font-medium">
                                Cost: PK {{ formatRupiah(al.cost_pk) }} | PB
                                {{ formatRupiah(al.cost_pb) }}
                            </p>
                        </div>

                        <!-- Card Terakhir: Total Anggaran PO -->
                        <div
                            class="p-3.5 bg-emerald-50/70 rounded-xl border border-emerald-200/80 col-span-2 sm:col-span-1"
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
                                Total Porsi:
                                {{ totalPM.toLocaleString("id-ID") }} Porsi
                            </p>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 1. IDENTITAS MENU & KOMPOSISI 5 SUB MENU HIDANGAN -->
                    <!-- ========================================================================= -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-4"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <UtensilsCrossed
                                        class="h-4 w-4 text-primary"
                                    />
                                    <span>1. Menu & Komposisi 5 Sub Menu</span>
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Rincian nama masakan per Sub Menu 1 hingga
                                    Sub Menu 5 beserta konfigurasi varian menu
                                    pengganti alergi.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-primary/10 text-primary border border-primary/20"
                                >
                                    {{
                                        props.selectedSource === 'csv' ||
                                        props.selectedSource === 'tkpi2020'
                                            ? 'TKPI 2020'
                                            : 'Nutri Survey'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Banner Nama Menu Utama -->
                        <div
                            class="p-3.5 rounded-xl bg-white border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-black shrink-0"
                                >
                                    <Utensils class="h-4 w-4" />
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                        >Nama Menu Utama MBG</span
                                    >
                                    <h3
                                        class="text-sm sm:text-base font-black text-slate-900"
                                    >
                                        {{
                                            namaMenuAktif ||
                                            "Menu Belum Diberi Nama"
                                        }}
                                    </h3>
                                </div>
                            </div>
                            <div
                                class="text-xs text-slate-500 flex items-center gap-1.5 self-start sm:self-auto"
                            >
                                <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                <span
                                    >Distribusi:
                                    <strong>{{
                                        formatTanggalIndo(tanggalRencana)
                                    }}</strong></span
                                >
                            </div>
                        </div>

                        <!-- Grid 5 Sub Menu Cards -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                        >
                            <div
                                v-for="(sm, sIdx) in subMenuKeysConfig"
                                :key="sm.key"
                                class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2 hover:border-slate-300 transition-colors"
                            >
                                <div>
                                    <div
                                        class="flex items-center justify-between gap-1 mb-1.5"
                                    >
                                        <span
                                            class="text-[11px] font-black text-slate-800 flex items-center gap-1.5"
                                        >
                                            <span
                                                class="w-2 h-2 rounded-full"
                                                :class="sm.dotColor"
                                            ></span>
                                            {{ sm.label }}
                                        </span>
                                        <span
                                            class="text-[9.5px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded"
                                        >
                                            {{ sm.defaultName }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs font-black text-slate-900 leading-snug"
                                    >
                                        {{
                                            subMenuKomponen[sm.key] ||
                                            "(Belum Diisi)"
                                        }}
                                    </p>
                                </div>

                                <!-- Menu Pengganti Alergi jika ada -->
                                <div class="pt-2 border-t border-slate-100">
                                    <div
                                        v-if="
                                            subMenuAlergi[sm.key] &&
                                            subMenuAlergi[sm.key].length > 0
                                        "
                                        class="space-y-1"
                                    >
                                        <span
                                            class="text-[9.5px] font-bold text-amber-700 uppercase tracking-wider block"
                                        >
                                            Varian Alergi:
                                        </span>
                                        <div
                                            v-for="(al, alIdx) in subMenuAlergi[
                                                sm.key
                                            ]"
                                            :key="alIdx"
                                            class="text-[10px] bg-amber-50 text-amber-900 border border-amber-200/80 px-2 py-1 rounded-lg"
                                        >
                                            <span class="font-bold"
                                                >{{ al.jenis_alergi }}:</span
                                            >
                                            <span class="ml-1 text-slate-700">{{
                                                al.menu_pengganti
                                            }}</span>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="text-[10px] text-slate-400 italic"
                                    >
                                        Tidak ada varian alergi
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 2. REKAPITULASI TARGET SASARAN PENERIMA MANFAAT -->
                    <!-- ========================================================================= -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-4"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <Users class="h-4 w-4 text-indigo-600" />
                                    <span
                                        >2. Daftar Kelompok Penerima
                                        Manfaat</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Status penerimaan porsi, proporsi porsi
                                    kecil (PK), porsi besar (PB), dan catatan
                                    alergi per kelompok sasaran.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200"
                                >
                                    {{
                                        woKelompokList.filter(
                                            (k) => k.status_menerima !== false,
                                        ).length
                                    }}
                                    dari {{ woKelompokList.length }} Kelompok
                                    Menerima
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Kelompok Penerima Manfaat -->
                        <div
                            class="overflow-x-auto rounded-xl border border-slate-200 bg-white"
                        >
                            <table class="w-full text-xs text-left">
                                <thead
                                    class="bg-slate-50/90 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 w-10 text-center">No</th>
                                        <th class="p-3">
                                            Kelompok Penerima Manfaat
                                        </th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-center">Status</th>
                                        <th class="p-3 text-center">
                                            Porsi Kecil (PK)
                                        </th>
                                        <th class="p-3 text-center">
                                            Porsi Besar (PB)
                                        </th>
                                        <th class="p-3 text-center">
                                            Total Porsi
                                        </th>
                                        <th class="p-3">Keterangan Alergi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(kel, kIdx) in woKelompokList"
                                        :key="kel.id || kIdx"
                                        :class="
                                            kel.status_menerima === false
                                                ? 'bg-slate-50/50 text-slate-400'
                                                : 'hover:bg-slate-50/60'
                                        "
                                    >
                                        <td
                                            class="p-3 text-center font-bold text-slate-500"
                                        >
                                            {{ kIdx + 1 }}
                                        </td>
                                        <td
                                            class="p-3 font-black text-slate-800"
                                        >
                                            {{ kel.nama_kelompok }}
                                        </td>
                                        <td class="p-3 text-slate-600">
                                            <span
                                                class="px-2 py-0.5 rounded bg-slate-100 font-semibold text-[10px]"
                                            >
                                                {{ kel.kategori }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 text-[10.5px] font-black rounded-md border inline-block',
                                                    kel.status_menerima !==
                                                    false
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                        : 'bg-slate-100 text-slate-500 border-slate-300',
                                                ]"
                                            >
                                                {{
                                                    kel.status_menerima !==
                                                    false
                                                        ? "Menerima"
                                                        : "Tidak Menerima"
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold"
                                            :class="
                                                kel.status_menerima !== false
                                                    ? 'text-amber-800'
                                                    : 'text-slate-400'
                                            "
                                        >
                                            {{
                                                (
                                                    Number(
                                                        kel.total_porsi_kecil,
                                                    ) || 0
                                                ).toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold"
                                            :class="
                                                kel.status_menerima !== false
                                                    ? 'text-indigo-800'
                                                    : 'text-slate-400'
                                            "
                                        >
                                            {{
                                                (
                                                    Number(
                                                        kel.total_porsi_besar,
                                                    ) || 0
                                                ).toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-center font-black"
                                            :class="
                                                kel.status_menerima !== false
                                                    ? 'text-slate-900'
                                                    : 'text-slate-400'
                                            "
                                        >
                                            {{
                                                (
                                                    (Number(
                                                        kel.total_porsi_kecil,
                                                    ) || 0) +
                                                    (Number(
                                                        kel.total_porsi_besar,
                                                    ) || 0)
                                                ).toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td class="p-3">
                                            <div
                                                v-if="
                                                    Array.isArray(
                                                        kel.keterangan_alergi,
                                                    ) &&
                                                    kel.keterangan_alergi.some(
                                                        (da) =>
                                                            activeAlergiFoodCostList.some(
                                                                (al) => al.jenis_alergi === (typeof da === 'string' ? da : da?.jenis_alergi)
                                                            ) &&
                                                            ((Number(da?.porsi_kecil) || 0) + (Number(da?.porsi_besar) || 0)) > 0
                                                    )
                                                "
                                                class="flex flex-wrap gap-1"
                                            >
                                                <template
                                                    v-for="(detAl, daIdx) in kel.keterangan_alergi"
                                                    :key="daIdx"
                                                >
                                                    <span
                                                        v-if="
                                                            activeAlergiFoodCostList.some(
                                                                (al) => al.jenis_alergi === (typeof detAl === 'string' ? detAl : detAl?.jenis_alergi)
                                                            ) &&
                                                            ((Number(detAl?.porsi_kecil) || 0) + (Number(detAl?.porsi_besar) || 0)) > 0
                                                        "
                                                        class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-50 text-amber-800 border border-amber-200"
                                                    >
                                                        {{
                                                            (typeof detAl === 'string' ? detAl : detAl.jenis_alergi) +
                                                            ': ' +
                                                            ((Number(detAl?.porsi_kecil) || 0) + (Number(detAl?.porsi_besar) || 0)) +
                                                            ' siswa'
                                                        }}
                                                    </span>
                                                </template>
                                            </div>
                                            <span
                                                v-else
                                                class="text-[10px] text-slate-400 italic"
                                            >
                                                Bebas Alergi
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="p-3 text-right text-slate-700"
                                        >
                                            Total Porsi Aktif:
                                        </td>
                                        <td
                                            class="p-3 text-center text-amber-900 font-black"
                                        >
                                            {{
                                                totalPK.toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-center text-indigo-900 font-black"
                                        >
                                            {{
                                                totalPB.toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-center text-slate-900 font-black"
                                        >
                                            {{
                                                totalPM.toLocaleString("id-ID")
                                            }}
                                            Porsi
                                        </td>
                                        <td
                                            class="p-3 text-slate-500 font-medium text-[11px]"
                                        >
                                            {{
                                                totalTerdampakAlergi > 0
                                                    ? totalTerdampakAlergi +
                                                      " Siswa Alergi Terdampak"
                                                    : "Semua Normal"
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 3. EVALUASI PEMENUHAN STANDAR AKG BGN (PORSI KECIL & PORSI BESAR & VARIAN ALERGI) -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-5"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <Activity
                                        class="h-4 w-4 text-emerald-600"
                                    />
                                    <span
                                        >3. Evaluasi Standar Angka Kecukupan
                                        Gizi (AKG Standar BGN)</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Evaluasi pemenuhan Angka Kecukupan Gizi
                                    untuk porsi normal dan seluruh varian porsi
                                    alergi hasil formulasi bahan makanan siap
                                    santap.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="showRumusAkg = !showRumusAkg"
                                class="text-[11px] text-primary font-bold hover:underline cursor-pointer flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs transition-colors hover:bg-slate-50 self-start sm:self-auto shrink-0"
                            >
                                <Info class="h-3.5 w-3.5 text-primary shrink-0" />
                                <span>{{
                                    showRumusAkg
                                        ? "Sembunyikan Penjelasan Rumus"
                                        : "Tampilkan Penjelasan Rumus & Standar AKG"
                                }}</span>
                            </button>
                        </div>

                        <!-- Panel Penjelasan Rumus & Standar AKG BGN (Langkah 3) -->
                        <div
                            v-if="showRumusAkg"
                            class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 text-xs space-y-4 text-slate-700 shadow-2xs"
                        >
                            <div
                                class="flex items-center gap-2 text-amber-950 font-bold border-b border-amber-200/80 pb-2"
                            >
                                <Info class="h-4 w-4 text-amber-600 shrink-0" />
                                <span
                                    >Metode Perhitungan Nilai Zat Gizi & Standar Acuan AKG BGN (Makan Siang MBG)</span
                                >
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 leading-relaxed">
                                <div
                                    class="p-3 bg-white rounded-xl border border-amber-100 space-y-2 shadow-2xs"
                                >
                                    <strong class="text-amber-950 font-bold block flex items-center gap-1.5">
                                        <Calculator class="h-4 w-4 text-amber-600" />
                                        1. Rumus Perhitungan Nilai Gizi per Porsi:
                                    </strong>
                                    <p
                                        class="text-[11px] text-slate-800 font-mono bg-amber-50/70 p-2 rounded-lg border border-amber-200 text-center font-bold"
                                    >
                                        Zat Gizi = &sum; [ (Gram Bersih &divide; 100) &times; Nilai Gizi per 100g TKPI ]
                                    </p>
                                    <p class="text-[11px] text-slate-500 leading-snug">
                                        Kandungan zat gizi (Energi, Protein, Lemak, Karbohidrat, Serat) dihitung otomatis berdasarkan porsi berat bersih (<em>edible weight</em>) masing-masing bahan masakan terhadap basis data Tabel Komposisi Pangan Indonesia (TKPI).
                                    </p>
                                </div>
                                <div
                                    class="p-3 bg-white rounded-xl border border-amber-100 space-y-2 shadow-2xs"
                                >
                                    <strong class="text-amber-950 font-bold block flex items-center gap-1.5">
                                        <HeartPulse class="h-4 w-4 text-rose-500" />
                                        2. Standar Acuan AKG Makan Siap Santap BGN:
                                    </strong>
                                    <div class="grid grid-cols-2 gap-2 text-[11px]">
                                        <div class="p-2.5 rounded-lg bg-amber-50/80 border border-amber-200/70 space-y-1">
                                            <span class="font-extrabold text-amber-950 block border-b border-amber-200 pb-0.5 text-[11px]">Porsi Kecil (PK):</span>
                                            <div class="text-slate-600 space-y-0.5 text-[10.5px]">
                                                <div>&bull; Energi: <strong class="text-slate-900">450 - 550 kkal</strong></div>
                                                <div>&bull; Protein: <strong class="text-slate-900">15 - 22 g</strong></div>
                                                <div>&bull; Lemak: <strong class="text-slate-900">15 - 20 g</strong></div>
                                                <div>&bull; Karbo: <strong class="text-slate-900">60 - 85 g</strong></div>
                                                <div>&bull; Serat: <strong class="text-slate-900">5 - 7 g</strong></div>
                                            </div>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-blue-50/80 border border-blue-200/70 space-y-1">
                                            <span class="font-extrabold text-blue-950 block border-b border-blue-200 pb-0.5 text-[11px]">Porsi Besar (PB):</span>
                                            <div class="text-slate-600 space-y-0.5 text-[10.5px]">
                                                <div>&bull; Energi: <strong class="text-slate-900">650 - 800 kkal</strong></div>
                                                <div>&bull; Protein: <strong class="text-slate-900">20 - 30 g</strong></div>
                                                <div>&bull; Lemak: <strong class="text-slate-900">20 - 30 g</strong></div>
                                                <div>&bull; Karbo: <strong class="text-slate-900">90 - 120 g</strong></div>
                                                <div>&bull; Serat: <strong class="text-slate-900">7 - 10 g</strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3.A Evaluasi AKG Porsi Normal -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-500"
                                ></span>
                                <h5
                                    class="text-xs font-black text-slate-800 uppercase tracking-wider"
                                >
                                    A. Standar AKG Porsi Normal (PK & PB)
                                </h5>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Evaluasi PK Normal -->
                                <div
                                    class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3"
                                >
                                    <div
                                        class="flex items-center justify-between border-b border-slate-100 pb-2.5"
                                    >
                                        <div>
                                            <span
                                                class="text-xs font-black text-slate-900 block"
                                                >Porsi Kecil (PK) Normal</span
                                            >
                                            <span
                                                class="text-[10px] text-slate-500"
                                                >PAUD, TK, SD Kelas 1-3</span
                                            >
                                        </div>
                                        <span
                                            :class="[
                                                'px-2.5 py-1 text-[10px] rounded-lg border',
                                                getAkgStatusBadge(
                                                    akgResultPKNormal,
                                                    false,
                                                ).badgeClass,
                                            ]"
                                        >
                                            {{
                                                getAkgStatusBadge(
                                                    akgResultPKNormal,
                                                    false,
                                                ).label
                                            }}
                                        </span>
                                    </div>
                                    <!-- 5 Kotak: 3 Di Atas, 2 Di Bawah -->
                                    <div class="grid grid-cols-6 gap-2 text-xs">
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Energi</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPKNormal.energi.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >kkal</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 450 - 550</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Protein</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPKNormal.protein.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 15 - 20g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Lemak</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPKNormal.lemak.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 13 - 18g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Karbohidrat</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPKNormal.karbohidrat.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 68 - 83g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Serat</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPKNormal.serat.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 6 - 8g</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Evaluasi PB Normal -->
                                <div
                                    class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3"
                                >
                                    <div
                                        class="flex items-center justify-between border-b border-slate-100 pb-2.5"
                                    >
                                        <div>
                                            <span
                                                class="text-xs font-black text-slate-900 block"
                                                >Porsi Besar (PB) Normal</span
                                            >
                                            <span
                                                class="text-[10px] text-slate-500"
                                                >SD 4-6, SMP, SMA/SMK,
                                                Bumil/Busui</span
                                            >
                                        </div>
                                        <span
                                            :class="[
                                                'px-2.5 py-1 text-[10px] rounded-lg border',
                                                getAkgStatusBadge(
                                                    akgResultPBNormal,
                                                    true,
                                                ).badgeClass,
                                            ]"
                                        >
                                            {{
                                                getAkgStatusBadge(
                                                    akgResultPBNormal,
                                                    true,
                                                ).label
                                            }}
                                        </span>
                                    </div>
                                    <!-- 5 Kotak: 3 Di Atas, 2 Di Bawah -->
                                    <div class="grid grid-cols-6 gap-2 text-xs">
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Energi</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPBNormal.energi.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >kkal</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 650 - 800</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Protein</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPBNormal.protein.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 22 - 30g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Lemak</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPBNormal.lemak.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 18 - 27g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Karbohidrat</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPBNormal.karbohidrat.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 98 - 120g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                        >
                                            <span
                                                class="text-[10px] text-slate-500 block font-semibold"
                                                >Serat</span
                                            >
                                            <span
                                                class="font-black text-slate-900 text-sm"
                                                >{{
                                                    akgResultPBNormal.serat.toFixed(
                                                        1,
                                                    )
                                                }}
                                                <span
                                                    class="text-[10px] font-normal text-slate-500"
                                                    >g</span
                                                ></span
                                            >
                                            <span
                                                class="text-[9.5px] text-slate-400 block mt-0.5"
                                                >Std: 8 - 12g</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3.B Evaluasi AKG Varian Alergi (Jika Ada) -->
                        <div
                            v-if="activeAlergiAkgList.length > 0"
                            class="space-y-4 pt-2 border-t border-slate-200"
                        >
                            <div
                                class="flex items-center justify-between flex-wrap gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2 h-2 rounded-full bg-rose-500"
                                    ></span>
                                    <h5
                                        class="text-xs font-black text-rose-900 uppercase tracking-wider flex items-center gap-1.5"
                                    >
                                        <ShieldAlert
                                            class="h-3.5 w-3.5 text-rose-600"
                                        />
                                        <span
                                            >B. Standar AKG Varian Khusus
                                            Alergi</span
                                        >
                                    </h5>
                                </div>
                                <span
                                    class="text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-lg"
                                >
                                    {{ activeAlergiAkgList.length }} Varian
                                    Alergi Dikonfigurasi
                                </span>
                            </div>

                            <div
                                v-for="alRes in activeAlergiAkgList"
                                :key="'akg-al-' + alRes.jenis_alergi"
                                class="p-3.5 bg-rose-50/40 rounded-xl border border-rose-200 space-y-3"
                            >
                                <div
                                    class="flex items-center justify-between flex-wrap gap-2"
                                >
                                    <span
                                        class="text-xs font-black text-rose-950 flex items-center gap-1.5"
                                    >
                                        <span
                                            >Varian Alergi:
                                            <strong
                                                class="text-rose-700 underline underline-offset-2"
                                                >{{
                                                    alRes.jenis_alergi
                                                }}</strong
                                            ></span
                                        >
                                        <span
                                            class="text-[10.5px] font-bold text-slate-600"
                                            >({{ alRes.total_siswa }} Porsi •
                                            PK: {{ alRes.siswa_pk }}, PB:
                                            {{ alRes.siswa_pb }})</span
                                        >
                                    </span>
                                    <span
                                        class="text-[10px] font-semibold text-rose-800 bg-white px-2 py-0.5 rounded border border-rose-200"
                                    >
                                        {{ alRes.bahan_count }} Bahan Substitusi
                                        Terpilih
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                >
                                    <!-- Evaluasi PK Alergi -->
                                    <div
                                        class="p-3.5 bg-white rounded-xl border border-rose-100 shadow-2xs space-y-3"
                                    >
                                        <div
                                            class="flex items-center justify-between border-b border-slate-100 pb-2"
                                        >
                                            <div>
                                                <span
                                                    class="text-xs font-black text-slate-900 block"
                                                    >Porsi Kecil (PK) •
                                                    {{
                                                        alRes.jenis_alergi
                                                    }}</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-500"
                                                    >PAUD, TK, SD Kelas
                                                    1-3</span
                                                >
                                            </div>
                                            <span
                                                :class="[
                                                    'px-2.5 py-1 text-[10px] rounded-lg border',
                                                    getAkgStatusBadge(
                                                        alRes.pk,
                                                        false,
                                                    ).badgeClass,
                                                ]"
                                            >
                                                {{
                                                    getAkgStatusBadge(
                                                        alRes.pk,
                                                        false,
                                                    ).label
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="grid grid-cols-6 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pk.energi.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >kkal</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 450 - 550</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pk.protein.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 15 - 20g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pk.lemak.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 13 - 18g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pk.karbohidrat.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 68 - 83g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pk.serat.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 6 - 8g</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Evaluasi PB Alergi -->
                                    <div
                                        class="p-3.5 bg-white rounded-xl border border-rose-100 shadow-2xs space-y-3"
                                    >
                                        <div
                                            class="flex items-center justify-between border-b border-slate-100 pb-2"
                                        >
                                            <div>
                                                <span
                                                    class="text-xs font-black text-slate-900 block"
                                                    >Porsi Besar (PB) •
                                                    {{
                                                        alRes.jenis_alergi
                                                    }}</span
                                                >
                                                <span
                                                    class="text-[10px] text-slate-500"
                                                    >SD 4-6, SMP, SMA/SMK,
                                                    Bumil/Busui</span
                                                >
                                            </div>
                                            <span
                                                :class="[
                                                    'px-2.5 py-1 text-[10px] rounded-lg border',
                                                    getAkgStatusBadge(
                                                        alRes.pb,
                                                        true,
                                                    ).badgeClass,
                                                ]"
                                            >
                                                {{
                                                    getAkgStatusBadge(
                                                        alRes.pb,
                                                        true,
                                                    ).label
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="grid grid-cols-6 gap-2 text-xs"
                                        >
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pb.energi.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >kkal</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 650 - 800</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pb.protein.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 22 - 30g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-2"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pb.lemak.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 18 - 27g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pb.karbohidrat.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 98 - 120g</span
                                                >
                                            </div>
                                            <div
                                                class="p-2 bg-slate-50 rounded-lg border border-slate-100 col-span-3"
                                            >
                                                <span
                                                    class="text-[10px] text-slate-500 block font-semibold"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="font-black text-slate-900 text-sm"
                                                    >{{
                                                        alRes.pb.serat.toFixed(
                                                            1,
                                                        )
                                                    }}
                                                    <span
                                                        class="text-[10px] font-normal text-slate-500"
                                                        >g</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[9.5px] text-slate-400 block mt-0.5"
                                                    >Std: 8 - 12g</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. EVALUASI FOOD COST & PAGU ANGGARAN PER PORSI & PER SUB MENU -->
                    <!-- ========================================================================= -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-4"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <Coins class="h-4 w-4 text-amber-600" />
                                    <span
                                        >4. Evaluasi Food Cost & Pagu Anggaran
                                        Belanja</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Perbandingan Food Cost aktual per porsi
                                    terhadap pagu standar BGN (PK: Rp 8.000, PB:
                                    Rp 10.000) dan rincian per Sub Menu.
                                </p>
                            </div>
                        </div>

                        <!-- Ringkasan Alergi Jika Ada -->
                        <div
                            v-if="activeAlergiFoodCostList.length > 0"
                            class="p-3.5 bg-amber-50/40 rounded-xl border border-amber-200/80 space-y-2"
                        >
                            <div
                                class="flex items-center justify-between flex-wrap gap-2 text-xs font-bold text-amber-950"
                            >
                                <span class="flex items-center gap-1.5">
                                    <ShieldAlert
                                        class="h-4 w-4 text-amber-600"
                                    />
                                    <span
                                        >Rincian Sasaran & Food Cost Varian
                                        Khusus Alergi</span
                                    >
                                </span>
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5"
                            >
                                <div
                                    v-for="al in activeAlergiFoodCostList"
                                    :key="al.jenis_alergi"
                                    class="p-2.5 bg-white rounded-xl border border-amber-200 shadow-2xs text-xs space-y-1"
                                >
                                    <div
                                        class="font-bold text-slate-800 flex items-center justify-between"
                                    >
                                        <span>{{ al.jenis_alergi }}</span>
                                        <span
                                            class="text-[10.5px] text-amber-800 font-black"
                                        >
                                            {{ al.total_siswa }} Porsi (PK:
                                            {{ al.siswa_pk }}, PB:
                                            {{ al.siswa_pb }})
                                        </span>
                                    </div>
                                    <div
                                        class="text-[11px] text-slate-600 flex items-center justify-between pt-1 border-t border-slate-100"
                                    >
                                        <span
                                            >Cost PK:
                                            <strong>{{
                                                formatRupiah(al.cost_pk)
                                            }}</strong></span
                                        >
                                        <span
                                            >Cost PB:
                                            <strong>{{
                                                formatRupiah(al.cost_pb)
                                            }}</strong></span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABEL RINCIAN FOOD COST PER SUB MENU (PORSI NORMAL) -->
                        <div
                            class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs"
                        >
                            <div
                                class="p-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2"
                            >
                                <span
                                    class="text-xs font-black text-slate-800 flex items-center gap-1.5 uppercase tracking-wider"
                                >
                                    <Layers
                                        class="h-3.5 w-3.5 text-slate-600"
                                    />
                                    RINCIAN FOOD COST PER SUB MENU (PORSI
                                    NORMAL)
                                </span>
                                <span
                                    class="text-[11px] text-slate-500 font-medium"
                                >
                                    Alokasi biaya bahan baku per komponen
                                    hidangan
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full text-left text-xs border-collapse"
                                >
                                    <thead
                                        class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] select-none"
                                    >
                                        <tr>
                                            <th class="p-2.5 text-center w-10">
                                                NO
                                            </th>
                                            <th class="p-2.5 min-w-[200px]">
                                                SUB MENU
                                            </th>
                                            <th
                                                class="p-2.5 text-center min-w-[100px]"
                                            >
                                                BAHAN BAKU
                                            </th>
                                            <th
                                                class="p-2.5 text-right min-w-[150px] bg-amber-50/50 text-amber-950 font-black"
                                            >
                                                FOOD COST PK
                                            </th>
                                            <th
                                                class="p-2.5 text-right min-w-[150px] bg-blue-50/50 text-blue-950 font-black"
                                            >
                                                FOOD COST PB
                                            </th>
                                            <th
                                                class="p-2.5 min-w-[140px] text-center"
                                            >
                                                PORSI BIAYA (PK / PB)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 text-slate-800"
                                    >
                                        <tr
                                            v-for="(
                                                smCost, idx
                                            ) in foodCostSubMenuNormal"
                                            :key="smCost.key"
                                            class="hover:bg-slate-50/70 transition-colors"
                                        >
                                            <td
                                                class="p-2.5 text-center font-bold text-slate-400 align-middle"
                                            >
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="p-2.5 align-middle">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <!-- <span
                                                        class="h-2 w-2 rounded-full shrink-0"
                                                        :class="smCost.dotColor"
                                                    ></span> -->
                                                    <div>
                                                        <span
                                                            class="text-[10px] font-extrabold uppercase px-1.5 py-1 rounded border"
                                                            :class="
                                                                smCost.badgeColor
                                                            "
                                                        >
                                                            {{ smCost.label }}
                                                        </span>
                                                        <div
                                                            class="font-bold text-slate-900 text-xs mt-1.5"
                                                        >
                                                            {{
                                                                smCost.nama_menu
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td
                                                class="p-2.5 text-center align-middle font-medium text-slate-600"
                                            >
                                                <span
                                                    v-if="
                                                        smCost.items_count > 0
                                                    "
                                                    class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700"
                                                >
                                                    {{ smCost.items_count }}
                                                    Bahan
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-slate-400 italic text-[11px]"
                                                    >-</span
                                                >
                                            </td>
                                            <td
                                                class="p-2.5 text-right align-middle bg-amber-50/20"
                                            >
                                                <div
                                                    class="font-black text-slate-900 text-xs"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            smCost.cost_pk,
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-[10px] text-amber-800 font-medium"
                                                >
                                                    {{
                                                        smCost.percent_pk.toFixed(
                                                            1,
                                                        )
                                                    }}% dari total
                                                </div>
                                            </td>
                                            <td
                                                class="p-2.5 text-right align-middle bg-blue-50/20"
                                            >
                                                <div
                                                    class="font-black text-slate-900 text-xs"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            smCost.cost_pb,
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-[10px] text-blue-800 font-medium"
                                                >
                                                    {{
                                                        smCost.percent_pb.toFixed(
                                                            1,
                                                        )
                                                    }}% dari total
                                                </div>
                                            </td>
                                            <td class="p-2.5 align-middle">
                                                <div
                                                    class="space-y-1 w-28 mx-auto"
                                                >
                                                    <div
                                                        class="flex items-center gap-1.5 text-[9.5px]"
                                                    >
                                                        <span
                                                            class="font-bold text-amber-800 w-5 shrink-0"
                                                            >PK</span
                                                        >
                                                        <div
                                                            class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                        >
                                                            <div
                                                                class="bg-amber-500 h-full rounded-full transition-all"
                                                                :style="{
                                                                    width:
                                                                        smCost.percent_pk +
                                                                        '%',
                                                                }"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-1.5 text-[9.5px]"
                                                    >
                                                        <span
                                                            class="font-bold text-blue-800 w-5 shrink-0"
                                                            >PB</span
                                                        >
                                                        <div
                                                            class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                        >
                                                            <div
                                                                class="bg-blue-600 h-full rounded-full transition-all"
                                                                :style="{
                                                                    width:
                                                                        smCost.percent_pb +
                                                                        '%',
                                                                }"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot
                                        class="bg-slate-50/90 font-black border-t border-slate-200 text-xs"
                                    >
                                        <tr>
                                            <td
                                                colspan="3"
                                                class="p-2.5 text-right uppercase tracking-wider text-slate-700"
                                            >
                                                TOTAL FOOD COST PORSI NORMAL:
                                            </td>
                                            <td
                                                class="p-2.5 text-right text-emerald-900 bg-amber-100/40 text-sm"
                                            >
                                                {{
                                                    formatRupiah(
                                                        totalFoodCostPKNormal,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="p-2.5 text-right text-emerald-900 bg-blue-100/40 text-sm"
                                            >
                                                {{
                                                    formatRupiah(
                                                        totalFoodCostPBNormal,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="p-2.5 text-center text-[10px] text-slate-500"
                                            >
                                                100% Total
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- TABEL RINCIAN FOOD COST VARIAN ALERGI (JIKA ADA) -->
                        <div
                            v-if="activeAlergiFoodCostList.length > 0"
                            class="space-y-4 pt-3 border-t border-slate-200"
                        >
                            <div
                                class="flex items-center justify-between flex-wrap gap-2"
                            >
                                <span
                                    class="text-xs font-black text-amber-950 flex items-center gap-1.5 uppercase tracking-wider"
                                >
                                    <ShieldAlert
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />
                                    <span
                                        >RINCIAN FOOD COST PER SUB MENU (VARIAN
                                        ALERGI)</span
                                    >
                                </span>
                                <span
                                    class="text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-lg"
                                >
                                    {{ activeAlergiFoodCostList.length }} Varian
                                    Alergi Terdaftar
                                </span>
                            </div>

                            <div
                                v-for="alCost in activeAlergiFoodCostList"
                                :key="'review-al-fc-' + alCost.jenis_alergi"
                                class="bg-white rounded-2xl border border-amber-200/90 overflow-hidden shadow-2xs"
                            >
                                <div
                                    class="p-3 bg-amber-50/70 border-b border-amber-200 flex items-center justify-between flex-wrap gap-2"
                                >
                                    <span
                                        class="text-xs font-black text-amber-950 flex items-center gap-1.5 uppercase tracking-wider"
                                    >
                                        <Layers
                                            class="h-3.5 w-3.5 text-amber-700"
                                        />
                                        RINCIAN FOOD COST PER SUB MENU (VARIAN
                                        {{ alCost.jenis_alergi }})
                                    </span>
                                    <span
                                        class="text-[11px] text-amber-800 font-medium"
                                    >
                                        {{ alCost.total_siswa }} Porsi (PK:
                                        {{ alCost.siswa_pk }}, PB:
                                        {{ alCost.siswa_pb }}) • Termasuk
                                        penyesuaian substitusi & eliminasi
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table
                                        class="w-full text-left text-xs border-collapse"
                                    >
                                        <thead
                                            class="bg-amber-50/40 text-slate-700 font-bold border-b border-amber-200 uppercase text-[10px] select-none"
                                        >
                                            <tr>
                                                <th
                                                    class="p-2.5 text-center w-10"
                                                >
                                                    NO
                                                </th>
                                                <th class="p-2.5 min-w-[220px]">
                                                    SUB MENU
                                                </th>
                                                <th
                                                    class="p-2.5 text-center min-w-[100px]"
                                                >
                                                    BAHAN BAKU
                                                </th>
                                                <th
                                                    class="p-2.5 text-right min-w-[150px] bg-amber-50/50 text-amber-950 font-black"
                                                >
                                                    FOOD COST PK
                                                </th>
                                                <th
                                                    class="p-2.5 text-right min-w-[150px] bg-blue-50/50 text-blue-950 font-black"
                                                >
                                                    FOOD COST PB
                                                </th>
                                                <th
                                                    class="p-2.5 min-w-[140px] text-center"
                                                >
                                                    PORSI BIAYA (PK / PB)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-amber-100/60 text-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    smCost, idx
                                                ) in getFoodCostSubMenuForAlergi(
                                                    alCost.jenis_alergi,
                                                )"
                                                :key="smCost.key"
                                                class="hover:bg-amber-50/30 transition-colors"
                                            >
                                                <td
                                                    class="p-2.5 text-center font-bold text-slate-400 align-middle"
                                                >
                                                    {{ idx + 1 }}
                                                </td>
                                                <td class="p-2.5 align-middle">
                                                    <div class="space-y-1">
                                                        <div
                                                            class="flex items-center gap-1.5 flex-wrap"
                                                        >
                                                            <span
                                                                class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded border"
                                                                :class="
                                                                    smCost.badgeColor
                                                                "
                                                            >
                                                                {{
                                                                    smCost.label
                                                                }}
                                                            </span>
                                                            <span
                                                                v-if="
                                                                    smCost.is_substituted
                                                                "
                                                                class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-300 font-black text-[9.5px]"
                                                            >
                                                                ★ Substitusi
                                                                Khusus
                                                            </span>
                                                            <span
                                                                v-else-if="
                                                                    smCost.is_eliminated
                                                                "
                                                                class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300 font-black text-[9.5px]"
                                                            >
                                                                ⚠ Alergen
                                                                Dieliminasi
                                                            </span>
                                                        </div>
                                                        <div
                                                            class="font-bold text-slate-900 text-xs"
                                                        >
                                                            {{
                                                                smCost.nama_menu
                                                            }}
                                                        </div>
                                                        <div
                                                            v-if="
                                                                smCost.is_substituted &&
                                                                smCost.normal_menu_name !==
                                                                    smCost.nama_menu
                                                            "
                                                            class="text-[10px] text-slate-400"
                                                        >
                                                            Menu Normal:
                                                            {{
                                                                smCost.normal_menu_name
                                                            }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td
                                                    class="p-2.5 text-center align-middle font-medium text-slate-600"
                                                >
                                                    <span
                                                        v-if="
                                                            smCost.items_count >
                                                            0
                                                        "
                                                        class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700"
                                                    >
                                                        {{ smCost.items_count }}
                                                        Bahan
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-slate-400 italic text-[11px]"
                                                        >-</span
                                                    >
                                                </td>
                                                <td
                                                    class="p-2.5 text-right align-middle bg-amber-50/20"
                                                >
                                                    <div
                                                        class="font-black text-slate-900 text-xs"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                smCost.cost_pk,
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-[10px] text-amber-800 font-medium"
                                                    >
                                                        {{
                                                            smCost.percent_pk.toFixed(
                                                                1,
                                                            )
                                                        }}% dari total
                                                    </div>
                                                </td>
                                                <td
                                                    class="p-2.5 text-right align-middle bg-blue-50/20"
                                                >
                                                    <div
                                                        class="font-black text-slate-900 text-xs"
                                                    >
                                                        {{
                                                            formatRupiah(
                                                                smCost.cost_pb,
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-[10px] text-blue-800 font-medium"
                                                    >
                                                        {{
                                                            smCost.percent_pb.toFixed(
                                                                1,
                                                            )
                                                        }}% dari total
                                                    </div>
                                                </td>
                                                <td class="p-2.5 align-middle">
                                                    <div
                                                        class="space-y-1 w-28 mx-auto"
                                                    >
                                                        <div
                                                            class="flex items-center gap-1.5 text-[9.5px]"
                                                        >
                                                            <span
                                                                class="font-bold text-amber-800 w-5 shrink-0"
                                                                >PK</span
                                                            >
                                                            <div
                                                                class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                            >
                                                                <div
                                                                    class="bg-amber-500 h-full rounded-full transition-all"
                                                                    :style="{
                                                                        width:
                                                                            smCost.percent_pk +
                                                                            '%',
                                                                    }"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-1.5 text-[9.5px]"
                                                        >
                                                            <span
                                                                class="font-bold text-blue-800 w-5 shrink-0"
                                                                >PB</span
                                                            >
                                                            <div
                                                                class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden"
                                                            >
                                                                <div
                                                                    class="bg-blue-600 h-full rounded-full transition-all"
                                                                    :style="{
                                                                        width:
                                                                            smCost.percent_pb +
                                                                            '%',
                                                                    }"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot
                                            class="bg-amber-50/70 font-black border-t border-amber-200 text-xs"
                                        >
                                            <tr>
                                                <td
                                                    colspan="3"
                                                    class="p-2.5 text-right uppercase tracking-wider text-amber-950"
                                                >
                                                    TOTAL FOOD COST VARIAN
                                                    {{ alCost.jenis_alergi }}:
                                                </td>
                                                <td
                                                    class="p-2.5 text-right text-emerald-900 bg-amber-100/60 text-sm"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            alCost.cost_pk,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="p-2.5 text-right text-emerald-900 bg-blue-100/60 text-sm"
                                                >
                                                    {{
                                                        formatRupiah(
                                                            alCost.cost_pb,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="p-2.5 text-center text-[10px] text-slate-500"
                                                >
                                                    100% Total
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 5. TABEL REKAPITULASI KEBUTUHAN BAHAN PANGAN & ESTIMASI BELANJA PO -->
                    <!-- ========================================================================= -->
                    <div
                        class="bg-slate-50/70 rounded-2xl p-4 sm:p-5 border border-slate-200 space-y-4"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <Package class="h-4 w-4 text-primary" />
                                    <span
                                        >5. Rekapitulasi Kebutuhan Bahan Pangan
                                        & Order Pembelian (PO)</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Daftar seluruh bahan baku pangan, standar
                                    gramasi per porsi, faktor BDD & buffer
                                    susut, total berat kotor (kg), dan estimasi
                                    biaya belanja PO.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    {{ bahanCalculations.length }} Bahan Baku
                                    Terdaftar
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Detail Bahan Baku PO -->
                        <div
                            class="overflow-x-auto rounded-xl border border-slate-200 bg-white"
                        >
                            <table class="w-full text-xs text-left">
                                <thead
                                    class="bg-slate-50/90 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 w-10 text-center">No</th>
                                        <th class="p-3">
                                            Bahan Pangan & Nama di PO
                                        </th>
                                        <th class="p-3 text-center">
                                            Peruntukan / Sub Menu
                                        </th>
                                        <th class="p-3 text-center">
                                            Tipe Porsi
                                        </th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-center">
                                            Gram PK / PB
                                        </th>
                                        <th class="p-3 text-center">
                                            BDD / Buffer
                                        </th>
                                        <th class="p-3 text-right">
                                            Total Gross
                                        </th>
                                        <th class="p-3 text-right">
                                            Harga Master
                                        </th>
                                        <th class="p-3 text-right">
                                            Subtotal Estimasi PO
                                        </th>
                                        <th
                                            class="p-3 text-left min-w-[150px] whitespace-normal break-words"
                                        >
                                            Keterangan
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(b, i) in bahanCalculations"
                                        :key="b.id || i"
                                        class="hover:bg-slate-50/60"
                                    >
                                        <td
                                            class="p-3 text-center font-bold text-slate-500"
                                        >
                                            {{ i + 1 }}
                                        </td>
                                        <td class="p-3">
                                            <div
                                                class="font-black text-slate-900 leading-tight"
                                            >
                                                {{ b.nama }}
                                            </div>
                                            <div
                                                class="text-[11px] text-primary font-bold mt-0.5"
                                            >
                                                PO: {{ b.nama_po || b.nama }}
                                            </div>
                                            <span
                                                v-if="b.alergen"
                                                class="inline-block text-[9.5px] bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.2 rounded font-semibold mt-1"
                                            >
                                                Alergen: {{ b.alergen }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div
                                                class="inline-flex flex-col items-center"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded text-[10.5px] font-black border"
                                                    :class="
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).label
                                                    }}
                                                </span>
                                                <span
                                                    v-if="
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).namaMenu &&
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).namaMenu !== '-'
                                                    "
                                                    class="text-[10px] text-slate-600 font-bold mt-0.5 max-w-[150px] truncate"
                                                    :title="
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).namaMenu
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).namaMenu
                                                    }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 text-[10px] font-bold rounded-md border inline-block',
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
                                                        : "Normal"
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 text-[11px] font-medium"
                                        >
                                            {{ b.kategori }}
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold text-slate-800 whitespace-nowrap"
                                        >
                                            {{ b.gram_pk || 0 }}g /
                                            {{ b.gram_pb || 0 }}g
                                        </td>
                                        <td
                                            class="p-3 text-center text-[11px] text-slate-600 whitespace-nowrap"
                                        >
                                            {{ b.bdd || 100 }}% / +{{
                                                b.buffer || 0
                                            }}%
                                        </td>
                                        <td
                                            class="p-3 text-right font-bold text-slate-900 whitespace-nowrap"
                                        >
                                            {{
                                                formatGrossWeight(
                                                    b.totalGrossKg,
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-600 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.harga_master) }}
                                        </td>
                                        <td
                                            class="p-3 text-right font-black text-emerald-900 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.subtotalMaster) }}
                                        </td>
                                        <td
                                            class="p-3 text-slate-600 align-middle text-xs min-w-[150px] whitespace-normal break-words"
                                        >
                                            {{ b.keterangan || "-" }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                                >
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="p-3.5 text-right text-slate-700"
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
                            @click="handleSwitchSubTab('bahan_pangan')"
                            className="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 h-11 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-2xs"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            <span>Kembali ke Formula Makanan (Langkah 2)</span>
                        </Button>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto">
                            <Button
                                type="button"
                                @click="simpanSebagaiDraft"
                                :disabled="isSubmitting"
                                className="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-5 h-11 rounded-xl cursor-pointer w-full sm:w-auto flex items-center justify-center gap-1.5 shadow-2xs"
                            >
                                <FileText class="h-4 w-4 text-slate-600" />
                                <span>Simpan sebagai Draft</span>
                            </Button>
                            <Button
                                type="button"
                                @click="ajukanKeKeuangan"
                                :disabled="isSubmitting"
                                className="bg-primary hover:bg-primary/90 text-white text-xs font-black px-7 h-11 rounded-xl shadow-xs cursor-pointer w-full sm:w-auto flex items-center justify-center gap-2"
                            >
                                <Send class="h-4 w-4" />
                                <span>Ajukan ke Keuangan</span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
