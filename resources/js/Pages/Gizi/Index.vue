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
    UtensilsCrossed,
    Users,
    ClipboardList,
    FileSpreadsheet,
    Activity,
    Coins,
    CheckCircle2,
    XCircle,
    AlertCircle,
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
    tkpiList: {
        type: Array,
        default: () => [],
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

// Active Sub-Menu Tab (Start dari 1: TKPI 2020 urutan paling awal)
// '1_tkpi_2020' | '2_jumlah_pm' | '3_pre_order' | '4_order' | '5_hasil_akg' | '6_hasil_food_cost'
const activeSubMenu = ref("1_tkpi_2020");

// Sub-menu definitions (Mulai dari no 1)
const subMenus = [
    {
        id: "1_tkpi_2020",
        no: "1",
        title: "TKPI 2020",
        subtitle: "Database & Pangan MBG",
        icon: Database,
    },
    {
        id: "2_jumlah_pm",
        no: "2",
        title: "Jumlah PM",
        subtitle: "Detail per Kategori",
        icon: Users,
    },
    {
        id: "3_pre_order",
        no: "3",
        title: "Pre Order",
        subtitle: "Ahli Gizi (Draft PO)",
        icon: ClipboardList,
    },
    {
        id: "4_order",
        no: "4",
        title: "Order",
        subtitle: "Akuntan (Validasi & Approval)",
        icon: FileSpreadsheet,
    },
    {
        id: "5_hasil_akg",
        no: "5",
        title: "Hasil AKG",
        subtitle: "Evaluasi Standar BGN",
        icon: Activity,
    },
    {
        id: "6_hasil_food_cost",
        no: "6",
        title: "Hasil Food Cost",
        subtitle: "Batas Plafon PK & PB",
        icon: Coins,
    },
];

// ==========================================
// 1. STATE PRE-ORDER (AHLI GIZI)
// ==========================================
const namaMenuAktif = ref(
    "Paket Nasi Kuning Ayam Suwir, Telur Balado, Tempe Orek, Tumis Buncis Wortel & Pisang",
);
const tanggalRencana = ref(new Date().toISOString().split("T")[0]);

// Resep Bahan Baku Baku Terpilih dari Database TKPI 2020
const selectedBahanList = ref([
    {
        tkpi_id: "KHB-01",
        kategori: "Karbohidrat",
        nama: "Beras Giling Putih Lokal",
        gram_pk: 75, // Gram beras mentah per PK (menghasilkan ~165g nasi matang)
        gram_pb: 100, // Gram beras mentah per PB (menghasilkan ~220g nasi matang)
        bdd: 100,
        fmm: 220,
        buffer: 3,
        harga_master: 15500,
        harga_aktual: 15500,
        alergen: null,
    },
    {
        tkpi_id: "LKH-01",
        kategori: "Lauk Hewani",
        nama: "Daging Ayam Broiler Fillet (Dada/Paha)",
        gram_pk: 40,
        gram_pb: 60,
        bdd: 90,
        fmm: 75,
        buffer: 5,
        harga_master: 38000,
        harga_aktual: 38500,
        alergen: null,
    },
    {
        tkpi_id: "LKH-02",
        kategori: "Lauk Hewani",
        nama: "Telur Ayam Ras Segar",
        gram_pk: 30, // ~1/2 butir
        gram_pb: 55, // 1 butir utuh
        bdd: 89,
        fmm: 95,
        buffer: 4,
        harga_master: 29000,
        harga_aktual: 29000,
        alergen: "Telur",
    },
    {
        tkpi_id: "LKN-01",
        kategori: "Lauk Nabati",
        nama: "Tempe Kedelai Murni",
        gram_pk: 25,
        gram_pb: 35,
        bdd: 100,
        fmm: 90,
        buffer: 3,
        harga_master: 16000,
        harga_aktual: 16000,
        alergen: "Kedelai",
    },
    {
        tkpi_id: "SYR-01",
        kategori: "Sayuran",
        nama: "Wortel Segar Lokal",
        gram_pk: 35,
        gram_pb: 45,
        bdd: 88,
        fmm: 85,
        buffer: 5,
        harga_master: 14000,
        harga_aktual: 14000,
        alergen: null,
    },
    {
        tkpi_id: "SYR-02",
        kategori: "Sayuran",
        nama: "Buncis Muda Segar",
        gram_pk: 35,
        gram_pb: 45,
        bdd: 90,
        fmm: 80,
        buffer: 5,
        harga_master: 16000,
        harga_aktual: 16500,
        alergen: null,
    },
    {
        tkpi_id: "BUH-01",
        kategori: "Buah Segar",
        nama: "Pisang Cavendish / Barangan",
        gram_pk: 100,
        gram_pb: 120,
        bdd: 68,
        fmm: 100,
        buffer: 3,
        harga_master: 18000,
        harga_aktual: 18000,
        alergen: null,
    },
    {
        tkpi_id: "BMB-01",
        kategori: "Bumbu & Pelengkap",
        nama: "Minyak Goreng Sawit",
        gram_pk: 8,
        gram_pb: 12,
        bdd: 100,
        fmm: 100,
        buffer: 2,
        harga_master: 17500,
        harga_aktual: 17500,
        alergen: null,
    },
    {
        tkpi_id: "BMB-02",
        kategori: "Bumbu & Pelengkap",
        nama: "Bumbu Rempah Masak (Bawang, Kunyit, Garam)",
        gram_pk: 10,
        gram_pb: 15,
        bdd: 85,
        fmm: 90,
        buffer: 5,
        harga_master: 30000,
        harga_aktual: 30000,
        alergen: null,
    },
]);

// Resep Bahan Pengganti / Substitusi untuk Varian Alergi (misal: Alergi Telur diganti Daging Sapi & Tahu Extra)
const varianAlergiTelurBahan = computed(() => {
    return selectedBahanList.value
        .filter((b) => b.alergen !== "Telur")
        .concat([
            {
                tkpi_id: "LKN-02",
                kategori: "Lauk Nabati",
                nama: "Tahu Putih Segar (Substitusi Alergi)",
                gram_pk: 40,
                gram_pb: 50,
                bdd: 100,
                fmm: 85,
                buffer: 3,
                harga_master: 14000,
                harga_aktual: 14000,
                alergen: null,
            },
        ]);
});

// Database Master TKPI 2020 (dari CSV backend props.tkpiList)
const tkpiItems = ref(props.tkpiList || []);

// Fungsi Kalkulasi MBG
function calculateGrossWeightKg(netGram, totalPortions, bddPercent, bufferPercent) {
    if (!netGram || !totalPortions) return 0;
    const bddFactor = Math.max(0.1, (bddPercent || 100) / 100);
    const bufferFactor = 1 + ((bufferPercent || 0) / 100);
    const grossGramPerPortion = (netGram / bddFactor) * bufferFactor;
    const totalGrossKg = (grossGramPerPortion * totalPortions) / 1000;
    return Number(totalGrossKg.toFixed(2));
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

function calculateItemFoodCostPerPortion(netGram, bddPercent, bufferPercent, hargaPerKg) {
    if (!netGram || !hargaPerKg) return 0;
    const bddFactor = Math.max(0.1, (bddPercent || 100) / 100);
    const bufferFactor = 1 + ((bufferPercent || 0) / 100);
    const grossGram = (netGram / bddFactor) * bufferFactor;
    const cost = (grossGram / 1000) * hargaPerKg;
    return Math.round(cost);
}

// Pilihan Bahan Tambahan dari Master TKPI
const selectedTkpiOption = ref("");
function handleAddBahan() {
    if (!selectedTkpiOption.value) return;
    const master = tkpiItems.value.find(
        (i) =>
            i.id === selectedTkpiOption.value ||
            i.code === selectedTkpiOption.value,
    );
    if (!master) return;
    selectedBahanList.value.push({
        tkpi_id: master.id || master.code,
        kategori: master.kategori,
        nama: master.nama,
        gram_pk: 30,
        gram_pb: 40,
        bdd: master.bdd || 100,
        fmm: master.fmm || 100,
        buffer: master.buffer || 4,
        harga_master: master.harga_master || 15000,
        harga_aktual: master.harga_master || 15000,
        alergen: master.alergen,
    });
    selectedTkpiOption.value = "";
}

function handleRemoveBahan(index) {
    selectedBahanList.value.splice(index, 1);
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
// KALKULASI REKAPITULASI MBG
// ==========================================
const totalPK = computed(() => props.stats.total_porsi_kecil || 0);
const totalPB = computed(() => props.stats.total_porsi_besar || 0);
const totalPM = computed(() => props.stats.total_penerima || 0);

// Kalkulasi Detail per Bahan (Gross Weight, Biaya Draft Master, Biaya Aktual Akuntan)
const bahanCalculations = computed(() => {
    return selectedBahanList.value.map((b) => {
        const tkpi =
            tkpiItems.value.find(
                (i) => i.id === b.tkpi_id || i.code === b.tkpi_id,
            ) || {};
        const bdd = b.bdd || 100;
        const buffer = b.buffer || 0;

        // Kebutuhan Kotor Kg untuk PK dan PB
        const grossKgPK = calculateGrossWeightKg(
            b.gram_pk,
            totalPK.value,
            bdd,
            buffer,
        );
        const grossKgPB = calculateGrossWeightKg(
            b.gram_pb,
            totalPB.value,
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
    bahanCalculations.value.forEach((b) => {
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
    bahanCalculations.value.forEach((b) => {
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
            <!-- TOP SUB-MENU NAVIGATOR (6 Langkah MBG) -->
            <div
                class="bg-white rounded-2xl border border-slate-200/90 p-2.5 shadow-xs print:hidden"
            >
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-2"
                >
                    <button
                        v-for="menu in subMenus"
                        :key="menu.id"
                        type="button"
                        @click="activeSubMenu = menu.id"
                        :class="[
                            'p-2.5 rounded-xl transition-all flex items-center gap-2.5 cursor-pointer text-left border',
                            activeSubMenu === menu.id
                                ? 'bg-primary text-white border-primary shadow-md shadow-primary/20 font-bold'
                                : 'bg-slate-50/70 hover:bg-slate-100/90 text-slate-700 border-slate-200/80 hover:border-slate-300 font-semibold',
                        ]"
                    >
                        <div
                            :class="[
                                'h-7 w-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-colors shadow-2xs',
                                activeSubMenu === menu.id
                                    ? 'bg-white text-primary font-black'
                                    : 'bg-white text-slate-700 font-black border border-slate-200',
                            ]"
                        >
                            {{ menu.no }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-xs leading-snug font-extrabold truncate"
                            >
                                {{ menu.title }}
                            </p>
                            <p
                                :class="[
                                    'text-[10px] leading-tight font-medium truncate',
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
            <!-- 1. SUB MENU 1: TKPI 2020 (TABEL KOMPOSISI PANGAN & IMPORT EXCEL) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '1_tkpi_2020'" class="space-y-6">
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
                                    <th class="p-3 text-center">FMM (%)</th>
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
                                        class="p-3 text-center font-semibold text-slate-700"
                                    >
                                        {{ item.fmm }}%
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
                                        colspan="12"
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
            <!-- 2. SUB MENU 2: JUMLAH PM (DETAIL PER KATEGORI) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '2_jumlah_pm'" class="space-y-6">
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
                                        >Unit</span
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
                                        >Jiwa</span
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
                                    <th class="p-3.5">Nama Kelompok Sasaran</th>
                                    <th class="p-3.5">Kategori Jenjang</th>
                                    <th class="p-3.5 text-center">Laki-Laki</th>
                                    <th class="p-3.5 text-center">Perempuan</th>
                                    <th class="p-3.5 text-center">
                                        Porsi Kecil (PK)
                                    </th>
                                    <th class="p-3.5 text-center">
                                        Porsi Besar (PB)
                                    </th>
                                    <th class="p-3.5 text-right">Total PM</th>
                                    <th class="p-3.5">
                                        Status Alergi / Khusus
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
                                        {{ k.total_porsi_kecil || 0 }} PK
                                    </td>
                                    <td
                                        class="p-3.5 text-center font-bold text-indigo-800 bg-indigo-50/30"
                                    >
                                        {{ k.total_porsi_besar || 0 }} PB
                                    </td>
                                    <td
                                        class="p-3.5 text-right font-black text-slate-900 text-sm"
                                    >
                                        {{ k.total_penerima || 0 }}
                                    </td>
                                    <td class="p-3.5">
                                        <span
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"
                                        >
                                            <CheckCircle2 class="h-3 w-3" />
                                            Standar Normal
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
                                        {{ totalPK }} PK
                                    </td>
                                    <td
                                        class="p-3.5 text-center text-indigo-900"
                                    >
                                        {{ totalPB }} PB
                                    </td>
                                    <td
                                        class="p-3.5 text-right text-emerald-950 text-sm"
                                    >
                                        {{ totalPM }} Porsi
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- ========================================================================================= -->
            <!-- 3. SUB MENU 3: PRE ORDER (AHLI GIZI) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '3_pre_order'" class="space-y-6">
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
                                        >Formulir Pre Order & Formula Gramasi
                                        Bahan (Ahli Gizi)</span
                                    >
                                </CardTitle>
                                <CardDescription class="text-xs sm:text-sm">
                                    Penentuan menu, gramasi bahan bersih,
                                    konversi ke kebutuhan kotor (BDD, FMM,
                                    Buffer %), dan penyusunan Draft PO.
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    @click="activeSubMenu = '4_order'"
                                    className="bg-primary text-white hover:bg-primary/90 text-xs font-bold px-4 h-9 flex items-center gap-1.5 shadow-xs cursor-pointer"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    <span>Ajukan Draft PO ke Akuntan</span>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Nama Menu -->
                            <div class="md:col-span-2 space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Nama Menu Produksi MBG:</label
                                >
                                <input
                                    type="text"
                                    v-model="namaMenuAktif"
                                    class="w-full text-xs font-bold text-slate-900 rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5 bg-slate-50/40"
                                />
                            </div>
                            <!-- Tanggal Rencana -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Tanggal Rencana Masak & Distribusi:</label
                                >
                                <input
                                    type="date"
                                    v-model="tanggalRencana"
                                    class="w-full text-xs font-bold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2.5"
                                />
                            </div>
                        </div>

                        <!-- Selector Tambah Bahan dari TKPI 2020 -->
                        <div
                            class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <Sparkles
                                    class="h-4 w-4 text-blue-600 shrink-0"
                                />
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-blue-950">
                                        Database Standar Pangan TKPI 2020
                                    </p>
                                    <p class="text-[11px] text-blue-700">
                                        Pilih dari {{ tkpiItems.length }} bahan
                                        makanan TKPI untuk resep menu
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 shrink-0 w-full sm:w-auto"
                            >
                                <select
                                    v-model="selectedTkpiOption"
                                    class="text-xs font-semibold rounded-lg border-slate-300 bg-white p-2 w-full sm:w-64"
                                >
                                    <option value="">
                                        -- Pilih Bahan TKPI 2020 --
                                    </option>
                                    <option
                                        v-for="item in tkpiItems"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        [{{ item.kategori }}] {{ item.nama }}
                                    </option>
                                </select>
                                <Button
                                    type="button"
                                    @click="handleAddBahan"
                                    :disabled="!selectedTkpiOption"
                                    className="h-8 px-3 text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold cursor-pointer shrink-0"
                                >
                                    <Plus class="h-3.5 w-3.5 mr-1" /> Tambah
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
                        <table class="w-full text-left text-xs border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]"
                            >
                                <tr>
                                    <th class="p-3">Bahan (TKPI 2020)</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-center">
                                        Gram PK (Bersih)
                                    </th>
                                    <th class="p-3 text-center">
                                        Gram PB (Bersih)
                                    </th>
                                    <th class="p-3 text-center">BDD (%)</th>
                                    <th class="p-3 text-center">FMM (%)</th>
                                    <th class="p-3 text-center">Buffer (%)</th>
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
                                <tr
                                    v-for="(b, idx) in bahanCalculations"
                                    :key="idx"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ b.nama }}
                                        <span
                                            v-if="b.alergen"
                                            class="block text-[9.5px] text-amber-700 font-normal"
                                            >Alergen: {{ b.alergen }}</span
                                        >
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ b.kategori }}
                                    </td>
                                    <!-- Input Gram PK -->
                                    <td class="p-2 text-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].gram_pk
                                            "
                                            class="w-16 text-center text-xs font-bold rounded border-slate-300 p-1 bg-amber-50/40 text-amber-900"
                                            min="0"
                                        />
                                    </td>
                                    <!-- Input Gram PB -->
                                    <td class="p-2 text-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].gram_pb
                                            "
                                            class="w-16 text-center text-xs font-bold rounded border-slate-300 p-1 bg-indigo-50/40 text-indigo-900"
                                            min="0"
                                        />
                                    </td>
                                    <!-- BDD -->
                                    <td class="p-2 text-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].bdd
                                            "
                                            class="w-14 text-center text-xs font-semibold rounded border-slate-300 p-1"
                                            min="1"
                                            max="100"
                                        />
                                    </td>
                                    <!-- FMM -->
                                    <td class="p-2 text-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].fmm
                                            "
                                            class="w-14 text-center text-xs font-semibold rounded border-slate-300 p-1"
                                            min="1"
                                        />
                                    </td>
                                    <!-- Buffer % -->
                                    <td class="p-2 text-center">
                                        <input
                                            type="number"
                                            v-model.number="
                                                selectedBahanList[idx].buffer
                                            "
                                            class="w-14 text-center text-xs font-semibold rounded border-slate-300 p-1 text-rose-800"
                                            min="0"
                                        />
                                    </td>
                                    <!-- Total Kg Kotor -->
                                    <td
                                        class="p-3 text-right font-black text-slate-900 bg-slate-50/50"
                                    >
                                        {{ b.totalGrossKg }} kg
                                    </td>
                                    <!-- Harga Master -->
                                    <td
                                        class="p-3 text-right text-slate-700 font-semibold"
                                    >
                                        {{ formatRupiah(b.harga_master) }} /kg
                                    </td>
                                    <!-- Subtotal Draft -->
                                    <td
                                        class="p-3 text-right font-bold text-blue-900"
                                    >
                                        {{ formatRupiah(b.subtotalMaster) }}
                                    </td>
                                    <td class="p-2 text-center">
                                        <button
                                            type="button"
                                            @click="handleRemoveBahan(idx)"
                                            class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 cursor-pointer"
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
                                        Total Estimasi Kebutuhan Belanja Draft
                                        PO (Harga Master):
                                    </td>
                                    <td class="p-3.5 text-right text-slate-900">
                                        {{
                                            bahanCalculations
                                                .reduce(
                                                    (acc, i) =>
                                                        acc + i.totalGrossKg,
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
                                            formatRupiah(grandTotalDraftMaster)
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
            <!-- 4. SUB MENU 4: ORDER (AKUNTAN) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '4_order'" class="space-y-6">
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
                        <table class="w-full text-left text-xs border-collapse">
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
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ b.nama }}
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ b.kategori }}
                                    </td>
                                    <td
                                        class="p-3 text-right font-black text-slate-900"
                                    >
                                        {{ b.totalGrossKg }} kg
                                    </td>
                                    <td class="p-3 text-right text-slate-500">
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
                                            b.subtotalAktual > b.subtotalMaster
                                                ? 'text-rose-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        {{
                                            b.subtotalAktual > b.subtotalMaster
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
            <!-- 5. SUB MENU 5: HASIL AKG -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '5_hasil_akg'" class="space-y-6">
                <!-- Info Standar BGN Banner -->
                <div
                    class="p-4 rounded-xl bg-blue-50 border border-blue-200/80 flex items-start gap-3"
                >
                    <Activity class="h-5 w-5 text-blue-700 shrink-0 mt-0.5" />
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
                                >PK (450 - 550 kkal, Protein 15 - 22g)</strong
                            >
                            dan
                            <strong
                                >PB (650 - 800 kkal, Protein 24 - 35g)</strong
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
                                    >{{ akgResultPKNormal.serat }} gram (Min.
                                    4.0g)</span
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
                                    >{{ akgResultPBNormal.serat }} gram (Min.
                                    6.0g)</span
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
                                        >{{ akgResultPKAlergi.protein }} g</span
                                    >
                                </div>
                                <div class="p-2 rounded bg-slate-50">
                                    <span
                                        class="text-[10px] text-slate-500 font-bold block"
                                        >Lemak</span
                                    >
                                    <span class="font-bold text-indigo-900"
                                        >{{ akgResultPKAlergi.lemak }} g</span
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
                                        >{{ akgResultPBAlergi.protein }} g</span
                                    >
                                </div>
                                <div class="p-2 rounded bg-slate-50">
                                    <span
                                        class="text-[10px] text-slate-500 font-bold block"
                                        >Lemak</span
                                    >
                                    <span class="font-bold text-indigo-900"
                                        >{{ akgResultPBAlergi.lemak }} g</span
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
            <!-- 6. SUB MENU 6: HASIL FOOD COST (BATAS PLAFON PK 8RB & PB 10RB) -->
            <!-- ========================================================================================= -->
            <div v-if="activeSubMenu === '6_hasil_food_cost'" class="space-y-6">
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
                                    <p class="text-xs text-slate-500 font-bold">
                                        Total Food Cost PK Normal:
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-slate-900 mt-0.5"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPKNormal)
                                        }}
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500 font-bold">
                                        Sisa Plafon Anggaran:
                                    </p>
                                    <h4
                                        class="text-base font-extrabold"
                                        :class="
                                            8000 - totalFoodCostPKNormal >= 0
                                                ? 'text-emerald-700'
                                                : 'text-rose-700'
                                        "
                                    >
                                        {{
                                            formatRupiah(
                                                8000 - totalFoodCostPKNormal,
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
                                    >{{ formatRupiah(totalFoodCostPKAlergi) }} /
                                    porsi</span
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
                                    <p class="text-xs text-slate-500 font-bold">
                                        Total Food Cost PB Normal:
                                    </p>
                                    <h3
                                        class="text-2xl font-black text-slate-900 mt-0.5"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPBNormal)
                                        }}
                                    </h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500 font-bold">
                                        Sisa Plafon Anggaran:
                                    </p>
                                    <h4
                                        class="text-base font-extrabold"
                                        :class="
                                            10000 - totalFoodCostPBNormal >= 0
                                                ? 'text-emerald-700'
                                                : 'text-rose-700'
                                        "
                                    >
                                        {{
                                            formatRupiah(
                                                10000 - totalFoodCostPBNormal,
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
                                    >{{ formatRupiah(totalFoodCostPBAlergi) }} /
                                    porsi</span
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
                                >Rincian Food Cost per Komponen Bahan Menu</span
                            >
                        </CardTitle>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
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
                                    <td class="p-3 font-bold text-slate-900">
                                        {{ b.nama }}
                                    </td>
                                    <td class="p-3 text-slate-600">
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
                                        colspan="3"
                                        class="p-3.5 uppercase tracking-wider text-slate-700"
                                    >
                                        Grand Total Food Cost per Porsi:
                                    </td>
                                    <td
                                        class="p-3.5 text-right text-amber-950 font-black text-sm"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPKNormal)
                                        }}
                                    </td>
                                    <td></td>
                                    <td
                                        class="p-3.5 text-right text-indigo-950 font-black text-sm"
                                    >
                                        {{
                                            formatRupiah(totalFoodCostPBNormal)
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
