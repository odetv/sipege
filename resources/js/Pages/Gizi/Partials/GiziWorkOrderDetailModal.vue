<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import {
    checkTextMatchesAllergen,
    matchWordBoundary,
} from "@/Services/penerimaManfaatConfig";
import {
    exportWorkOrderExcel,
    exportWorkOrderWord,
    exportWorkOrderPdf,
    printWorkOrder,
} from "@/Services/exportDocHelper";
import {
    getAkgStatusBadge,
    getNutrientStatus,
    STANDAR_AKG_PORSI_KECIL,
    STANDAR_AKG_PORSI_BESAR,
    RUJUKAN_AKG_MBG,
} from "@/Services/akgConfig";
import {
    SATUAN_LIST,
    SATUAN_VALUES,
    formatGrossQty,
    getGroupedUnitList,
    formatGroupedUnitSummary,
} from "@/Services/satuanConfig";
import {
    FileSpreadsheet,
    Users,
    Activity,
    Coins,
    Package,
    Clock,
    FileText,
    FilePenLine,
    Printer,
    Utensils,
    UtensilsCrossed,
    ShieldAlert,
    X,
    Calendar,
    Layers,
} from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    workOrder: {
        type: Object,
        default: null,
    },
    formatRupiah: {
        type: Function,
        default: (num) => {
            const val = Number(num);
            if (!val || isNaN(val) || val <= 0) return "Rp 0";
            if (Number.isInteger(val)) {
                return "Rp " + val.toLocaleString("id-ID");
            }
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
            return (
                "Rp " +
                val.toLocaleString("id-ID", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })
            );
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (tgl) => {
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
        },
    },
});

const emit = defineEmits(["close"]);

function formatDateTimeIndo(dt) {
    if (!dt) return "-";
    try {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return dt;
        const dateStr = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
        const hours = String(d.getHours()).padStart(2, "0");
        const minutes = String(d.getMinutes()).padStart(2, "0");
        return `${dateStr}, ${hours}:${minutes} WIB`;
    } catch {
        return dt;
    }
}

function getPortionCostBarColor(percent) {
    const p = Number(percent) || 0;
    if (p <= 0) return "bg-slate-200";
    if (p <= 25) return "bg-emerald-500";
    if (p <= 50) return "bg-amber-500";
    return "bg-rose-500";
}

function getPortionUnit(satuan = "Kg") {
    const s = (satuan || "Kg").toLowerCase().trim();
    if (s === "kg" || s === "gram" || s === "g") return "g";
    if (s === "liter" || s === "l" || s === "ml") return "mL";
    return satuan || "Kg";
}

function formatGrossWeight(kg, satuan = "Kg") {
    return formatGrossQty(kg, satuan);
}

// Config 5 Sub Menu
const subMenuKeysConfig = [
    {
        key: "sub_menu_1",
        label: "Sub Menu 1",
        defaultName: "Makanan Pokok",
        dotColor: "bg-slate-700",
        badgeColor: "bg-slate-100 text-slate-700 border-slate-200",
    },
    {
        key: "sub_menu_2",
        label: "Sub Menu 2",
        defaultName: "Protein Hewani",
        dotColor: "bg-rose-500",
        badgeColor: "bg-slate-100 text-slate-700 border-slate-200",
    },
    {
        key: "sub_menu_3",
        label: "Sub Menu 3",
        defaultName: "Protein Nabati",
        dotColor: "bg-yellow-500",
        badgeColor: "bg-slate-100 text-slate-700 border-slate-200",
    },
    {
        key: "sub_menu_4",
        label: "Sub Menu 4",
        defaultName: "Sayuran",
        dotColor: "bg-blue-500",
        badgeColor: "bg-slate-100 text-slate-700 border-slate-200",
    },
    {
        key: "sub_menu_5",
        label: "Sub Menu 5",
        defaultName: "Buah",
        dotColor: "bg-emerald-500",
        badgeColor: "bg-slate-100 text-slate-700 border-slate-200",
    },
];

// Data Binding & Parsing dari props.workOrder
const woNo = computed(() => {
    return (
        props.workOrder?.id ||
        props.workOrder?.nomor_wo ||
        props.workOrder?.raw?.nomor_wo ||
        "-"
    );
});

const tanggalRencana = computed(() => {
    return (
        props.workOrder?.tanggal ||
        props.workOrder?.tanggal_distribusi ||
        props.workOrder?.raw?.tanggal_distribusi ||
        ""
    );
});

const namaMenuAktif = computed(() => {
    return (
        props.workOrder?.nama ||
        props.workOrder?.nama_menu ||
        props.workOrder?.raw?.nama_menu ||
        ""
    );
});

const statusPengajuanWo = computed(() => {
    return (
        props.workOrder?.status_wo ||
        props.workOrder?.status ||
        props.workOrder?.raw?.status ||
        "Draft"
    );
});

const databasePangan = computed(() => {
    return (
        props.workOrder?.raw?.database_pangan ||
        props.workOrder?.database_pangan ||
        "fta"
    );
});

const totalPK = computed(() => {
    return Number(
        props.workOrder?.porsi_pk ||
            props.workOrder?.total_pk ||
            props.workOrder?.raw?.total_pk ||
            0,
    );
});

const totalPB = computed(() => {
    return Number(
        props.workOrder?.porsi_pb ||
            props.workOrder?.total_pb ||
            props.workOrder?.raw?.total_pb ||
            0,
    );
});

const totalPM = computed(() => {
    return Number(
        props.workOrder?.total_porsi ||
            props.workOrder?.total_pm ||
            props.workOrder?.raw?.total_pm ||
            totalPK.value + totalPB.value,
    );
});

// Helper Perhitungan Gizi & Food Cost Sesuai Standar GiziRancangMenuTab
function calculateNutritionFromNetGram(itemTkpi, netGram, jenis = "bahan_baku") {
    if (jenis === "operasional" || !itemTkpi || !netGram) {
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
    hargaPerSatuan,
    satuan = "Kg",
    jenis = "bahan_baku",
) {
    if (jenis === "operasional" || !netGram || !hargaPerSatuan || !bddPercent || bddPercent <= 0) return 0;
    const bddFactor = (bddPercent || 100) / 100;
    const bufferFactor = 1 + (bufferPercent || 0) / 100;
    const grossPerPortion = (netGram / bddFactor) * bufferFactor;
    const s = (satuan || "Kg").toLowerCase().trim();

    if (s === "kg" || s === "liter" || s === "l") {
        const cost = (grossPerPortion / 1000) * hargaPerSatuan;
        return Number(cost) || 0;
    }
    const cost = grossPerPortion * hargaPerSatuan;
    return Number(cost) || 0;
}

function isBahanContainsAlergen(b, jenisAlergi) {
    if (!b || !jenisAlergi) return false;
    const namaBahan = (b.nama || (b.tkpi && b.tkpi.nama) || "").trim();
    const alergenField = (b.alergen || (b.tkpi && b.tkpi.alergen) || "").trim();
    const kategori = (b.kategori || (b.tkpi && b.tkpi.kategori) || "").trim();
    const combinedText = `${namaBahan} ${alergenField} ${kategori}`.trim();

    return checkTextMatchesAllergen(combinedText, jenisAlergi);
}

// Sub Menu Komponen dari Work Order
const subMenuKomponen = computed(() => {
    const raw = props.workOrder?.raw || props.workOrder || {};
    return {
        sub_menu_1:
            raw.sub_menu_1 ||
            props.workOrder?.sub_menu_1 ||
            props.workOrder?.sub_menus?.[0] ||
            "",
        sub_menu_2:
            raw.sub_menu_2 ||
            props.workOrder?.sub_menu_2 ||
            props.workOrder?.sub_menus?.[1] ||
            "",
        sub_menu_3:
            raw.sub_menu_3 ||
            props.workOrder?.sub_menu_3 ||
            props.workOrder?.sub_menus?.[2] ||
            "",
        sub_menu_4:
            raw.sub_menu_4 ||
            props.workOrder?.sub_menu_4 ||
            props.workOrder?.sub_menus?.[3] ||
            "",
        sub_menu_5:
            raw.sub_menu_5 ||
            props.workOrder?.sub_menu_5 ||
            props.workOrder?.sub_menus?.[4] ||
            "",
    };
});

// Sub Menu Alergi mapping
const subMenuAlergi = computed(() => {
    const rawAlergi =
        props.workOrder?.raw?.sub_menu_alergi ||
        props.workOrder?.sub_menu_alergi ||
        {};
    const res = {
        sub_menu_1: [],
        sub_menu_2: [],
        sub_menu_3: [],
        sub_menu_4: [],
        sub_menu_5: [],
    };
    if (Array.isArray(rawAlergi)) {
        rawAlergi.forEach((al) => {
            if (typeof al === "object" && al !== null) {
                const key = al.sub_menu_key || "sub_menu_1";
                if (res[key]) {
                    res[key].push({
                        jenis_alergi: al.jenis_alergi || al.alergen || "Alergi",
                        menu_pengganti:
                            al.menu_pengganti || al.nama || al.nama_menu || "-",
                    });
                }
            }
        });
    } else if (typeof rawAlergi === "object" && rawAlergi !== null) {
        Object.keys(res).forEach((key) => {
            if (Array.isArray(rawAlergi[key])) {
                res[key] = rawAlergi[key];
            }
        });
    }
    return res;
});

// Kelompok List
const woKelompokList = computed(() => {
    const rawList =
        props.workOrder?.kelompoks || props.workOrder?.raw?.kelompoks || [];
    return rawList.map((k) => ({
        id: k.id || k.kelompok_id,
        nama_kelompok:
            k.nama_kelompok || k.kelompok?.nama_kelompok || "Kelompok",
        kategori: k.kategori || k.kelompok?.kategori || "Sekolah",
        status_menerima:
            k.is_menerima !== undefined
                ? k.is_menerima
                : k.status_menerima !== undefined
                  ? k.status_menerima
                  : true,
        total_porsi_kecil: Number(
            k.porsi_kecil !== undefined ? k.porsi_kecil : k.total_porsi_kecil || 0,
        ),
        total_porsi_besar: Number(
            k.porsi_besar !== undefined ? k.porsi_besar : k.total_porsi_besar || 0,
        ),
        detail_alergi: Array.isArray(k.detail_alergi) ? k.detail_alergi : [],
    }));
});

const totalPKAlergi = computed(() => {
    return Number(
        props.workOrder?.raw?.total_alergi_pk ||
            props.workOrder?.total_alergi_pk ||
            0,
    );
});

const totalPBAlergi = computed(() => {
    return Number(
        props.workOrder?.raw?.total_alergi_pb ||
            props.workOrder?.total_alergi_pb ||
            props.workOrder?.total_alergi ||
            0,
    );
});

// Helper Pencocokan PM Terdampak Alergi
function findAlergiDetail(jenisName) {
    if (!jenisName) return { porsi_kecil: 0, porsi_besar: 0, total: 0 };
    const clean = jenisName.toLowerCase().trim();
    const cleanNoPrefix = clean.replace(/^alergi\s+/, "");
    
    let pmPK = 0, pmPB = 0;
    for (const kel of woKelompokList.value) {
        if (!Array.isArray(kel.detail_alergi)) continue;
        for (const da of kel.detail_alergi) {
            if (!da || !da.jenis_alergi) continue;
            const rClean = da.jenis_alergi.toLowerCase().trim();
            const rCleanNoPrefix = rClean.replace(/^alergi\s+/, "");
            if (
                rClean === clean ||
                rCleanNoPrefix === cleanNoPrefix ||
                matchWordBoundary(cleanNoPrefix, rCleanNoPrefix) ||
                matchWordBoundary(rCleanNoPrefix, cleanNoPrefix) ||
                checkTextMatchesAllergen(da.jenis_alergi, jenisName) ||
                checkTextMatchesAllergen(jenisName, da.jenis_alergi)
            ) {
                pmPK += Number(da.porsi_kecil) || 0;
                pmPB += Number(da.porsi_besar) || 0;
            }
        }
    }
    if (pmPK === 0 && pmPB === 0) {
        pmPK = totalPKAlergi.value || 0;
        pmPB = totalPBAlergi.value || 0;
    }
    return {
        porsi_kecil: pmPK,
        porsi_besar: pmPB,
        total: pmPK + pmPB,
    };
}

// Bahan Calculations List (Mengikuti GiziRancangMenuTab: harga_aktual || harga_master)
const bahanCalculations = computed(() => {
    const rawItems = props.workOrder?.items || props.workOrder?.raw?.items || [];
    return rawItems.map((it, idx) => {
        const pkGram = Number(it.gram_pk || 0);
        const pbGram = Number(it.gram_pb || 0);
        const bdd = Number(it.bdd || 100);
        const buffer = Number(it.buffer || 0);
        const harga = Number(
            (it.harga_aktual !== undefined && it.harga_aktual !== null && Number(it.harga_aktual) > 0)
                ? it.harga_aktual
                : (it.harga_master || 0)
        );
        const totalGross = Number(it.total_gross_kg || it.gross_kg || 0);
        const subtotal = Number(
            it.subtotal_aktual !== undefined && it.subtotal_aktual !== null && Number(it.subtotal_aktual) > 0
                ? it.subtotal_aktual
                : it.subtotal_master !== undefined
                  ? it.subtotal_master
                  : totalGross * harga
        );

        const s = (it.satuan || "Kg").toLowerCase().trim();
        const jenis = it.jenis || "bahan_baku";
        const isOperasional = jenis === "operasional";

        const costPK = calculateItemFoodCostPerPortion(
            pkGram,
            bdd,
            buffer,
            harga,
            it.satuan || "Kg",
            jenis,
        );
        const costPB = calculateItemFoodCostPerPortion(
            pbGram,
            bdd,
            buffer,
            harga,
            it.satuan || "Kg",
            jenis,
        );

        const tkpi = it.tkpi || {
            energi: Number(it.energi || it.kalori || 0),
            protein: Number(it.protein || 0),
            lemak: Number(it.lemak || 0),
            karbohidrat: Number(it.karbohidrat || 0),
            serat: Number(it.serat || 0),
            nama: it.nama,
            alergen: it.alergen || "",
            kategori: it.kategori || "",
        };

        let nutrisiPK = null;
        let nutrisiPB = null;
        if (!isOperasional) {
            if (it.nutrisi_pk && (it.nutrisi_pk.energi || it.nutrisi_pk.protein)) {
                nutrisiPK = {
                    energi: Number(it.nutrisi_pk.energi || 0),
                    protein: Number(it.nutrisi_pk.protein || 0),
                    lemak: Number(it.nutrisi_pk.lemak || 0),
                    karbohidrat: Number(it.nutrisi_pk.karbohidrat || 0),
                    serat: Number(it.nutrisi_pk.serat || 0),
                };
            } else if (it.nutrisiPK) {
                nutrisiPK = it.nutrisiPK;
            } else {
                nutrisiPK = calculateNutritionFromNetGram(tkpi, pkGram, jenis);
            }

            if (it.nutrisi_pb && (it.nutrisi_pb.energi || it.nutrisi_pb.protein)) {
                nutrisiPB = {
                    energi: Number(it.nutrisi_pb.energi || 0),
                    protein: Number(it.nutrisi_pb.protein || 0),
                    lemak: Number(it.nutrisi_pb.lemak || 0),
                    karbohidrat: Number(it.nutrisi_pb.karbohidrat || 0),
                    serat: Number(it.nutrisi_pb.serat || 0),
                };
            } else if (it.nutrisiPB) {
                nutrisiPB = it.nutrisiPB;
            } else {
                nutrisiPB = calculateNutritionFromNetGram(tkpi, pbGram, jenis);
            }
        } else {
            nutrisiPK = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
            nutrisiPB = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
        }

        let totalWeightKg = 0;
        if (s === "kg" || s === "liter" || s === "l") {
            totalWeightKg = totalGross;
        } else if (s === "gram" || s === "g" || s === "ml") {
            totalWeightKg = totalGross / 1000;
        } else {
            totalWeightKg = totalGross;
        }

        return {
            id: it.id || idx + 1,
            nama: it.nama || "Bahan",
            nama_po: it.nama_po || it.nama,
            sub_menu_key: it.sub_menu_key || "sub_menu_1",
            sub_menu_block_id: it.sub_menu_block_id,
            nama_sub_menu: it.nama_sub_menu,
            kategori: it.kategori || "Lainnya",
            satuan: it.satuan || "Kg",
            tipe_porsi: it.tipe_porsi || "normal",
            jenis_alergi: (it.jenis_alergi || "").trim(),
            alergen: it.alergen || "",
            gram_pk: pkGram,
            gram_pb: pbGram,
            bdd: bdd,
            buffer: buffer,
            totalGrossKg: totalGross,
            totalWeightKg: totalWeightKg,
            harga_master: harga,
            harga_aktual: it.harga_aktual,
            subtotalMaster: subtotal,
            costPK: costPK,
            costPB: costPB,
            jenis: jenis,
            tkpi: tkpi,
            nutrisiPK: nutrisiPK,
            nutrisiPB: nutrisiPB,
            keterangan: it.keterangan || "",
            is_custom:
                it.is_custom ||
                (typeof it.code === "string" && it.code.startsWith("custom_")) ||
                (typeof it.id === "string" && it.id.startsWith("custom_")) ||
                false,
        };
    });
});

// Total Food Cost Normal dihitung dinamis dari bahanCalculations (Persis GiziRancangMenuTab)
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

const grandTotalDraftMaster = computed(() => {
    return Number(
        props.workOrder?.total_anggaran ||
            props.workOrder?.total_anggaran_master ||
            props.workOrder?.raw?.total_anggaran_master ||
            0,
    );
});

// Total AKG Normal dihitung dinamis dari bahanCalculations (Persis GiziRancangMenuTab)
const akgResultPKNormal = computed(() => {
    const res = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    bahanCalculations.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            if (b.nutrisiPK) {
                res.energi += Number(b.nutrisiPK.energi || 0);
                res.protein += Number(b.nutrisiPK.protein || 0);
                res.lemak += Number(b.nutrisiPK.lemak || 0);
                res.karbohidrat += Number(b.nutrisiPK.karbohidrat || 0);
                res.serat += Number(b.nutrisiPK.serat || 0);
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
    bahanCalculations.value
        .filter((b) => b.tipe_porsi !== "alergi")
        .forEach((b) => {
            if (b.nutrisiPB) {
                res.energi += Number(b.nutrisiPB.energi || 0);
                res.protein += Number(b.nutrisiPB.protein || 0);
                res.lemak += Number(b.nutrisiPB.lemak || 0);
                res.karbohidrat += Number(b.nutrisiPB.karbohidrat || 0);
                res.serat += Number(b.nutrisiPB.serat || 0);
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

// Seluruh Jenis Alergi yang Aktif
const determinedActiveAlergiTypes = computed(() => {
    const types = new Set();
    if (subMenuAlergi.value && typeof subMenuAlergi.value === "object") {
        Object.values(subMenuAlergi.value).forEach((arr) => {
            if (Array.isArray(arr)) {
                arr.forEach((al) => {
                    if (al && al.jenis_alergi && al.jenis_alergi.trim()) {
                        types.add(al.jenis_alergi.trim());
                    }
                });
            }
        });
    }
    bahanCalculations.value.forEach((b) => {
        if (b.tipe_porsi === "alergi" && b.jenis_alergi && b.jenis_alergi.trim()) {
            types.add(b.jenis_alergi.trim());
        }
    });
    return Array.from(types);
});

// Target Sasaran Normal (dikurangi PM Alergi, persis GiziRancangMenuTab)
const targetSasaranNormal = computed(() => {
    let pk = totalPK.value || 0;
    let pb = totalPB.value || 0;

    determinedActiveAlergiTypes.value.forEach((jenis) => {
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

// Active Alergi Food Cost List (Persis GiziRancangMenuTab: bahan aman + substitusi)
const activeAlergiFoodCostList = computed(() => {
    const activeTypes = determinedActiveAlergiTypes.value;
    if (activeTypes.length === 0) return [];

    return activeTypes.map((jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : (jmlPk + jmlPb);

        const substitusiBahans = bahanCalculations.value.filter(
            (b) => b.tipe_porsi === "alergi" && b.jenis_alergi === jenis,
        );

        const bahanNormalSafe = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            return !isBahanContainsAlergen(b, jenis);
        });

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

        return {
            jenis_alergi: jenis,
            total_pm: jmlTotal,
            pm_pk: jmlPk,
            pm_pb: jmlPb,
            cost_pk: costPK,
            cost_pb: costPB,
            total_biaya: jmlPk * costPK + jmlPb * costPB,
            bahan_normal_aman: bahanNormalSafe,
            bahan_substitusi: substitusiBahans,
            bahan_dikeluarkan: bahanNormalDikeluarkan,
            all_included_items: allItems,
        };
    });
});

const activeAlergiJenisSet = computed(() => {
    return new Set(determinedActiveAlergiTypes.value);
});

const totalTerdampakAlergi = computed(() =>
    activeAlergiFoodCostList.value.reduce(
        (acc, al) => acc + (Number(al.total_pm) || 0),
        0,
    )
);

// Rincian Food Cost per Sub Menu untuk Porsi Normal (Persis GiziRancangMenuTab)
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

// Helper Rincian Food Cost per Sub Menu Varian Alergi (Persis GiziRancangMenuTab)
function getFoodCostSubMenuForAlergi(jenisAlergi) {
    const detail = activeAlergiFoodCostList.value.find(
        (a) => a.jenis_alergi === jenisAlergi,
    );
    const totalPK = detail ? detail.cost_pk : 0;
    const totalPB = detail ? detail.cost_pb : 0;

    return subMenuKeysConfig.map((sm) => {
        const normalMenuName =
            (subMenuKomponen.value[sm.key] || "").trim() || sm.defaultName;

        const substitusiBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi !== "alergi" || b.jenis_alergi !== jenisAlergi)
                return false;
            if (b.sub_menu_key) return b.sub_menu_key === sm.key;
            if (b.sub_menu_block_id)
                return b.sub_menu_block_id.startsWith(sm.key);
            return false;
        });

        const normalSafeBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            const matchKey = b.sub_menu_key
                ? b.sub_menu_key === sm.key
                : b.sub_menu_block_id && b.sub_menu_block_id.startsWith(sm.key);
            if (!matchKey) return false;
            return !isBahanContainsAlergen(b, jenisAlergi);
        });

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
        const isEliminated = normalDikeluarkanBahans.length > 0 && substitusiBahans.length === 0;

        let displayName = normalMenuName;
        if (isSubstituted) {
            const alMenu = subMenuAlergi.value[sm.key]?.find(
                (x) => x.jenis_alergi === jenisAlergi,
            );
            if (alMenu && alMenu.menu_pengganti) {
                displayName = alMenu.menu_pengganti;
            } else if (substitusiBahans[0]?.nama_sub_menu) {
                displayName = substitusiBahans[0].nama_sub_menu;
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
            badgeColor: isSubstituted
                ? "bg-rose-50 text-rose-900 border-rose-200"
                : sm.badgeColor,
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

// Rincian Kandungan Gizi per Sub Menu untuk Porsi Normal (5 Komponen)
const nutrisiSubMenuNormal = computed(() => {
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

        const pk = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
        const pb = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };

        items.forEach((b) => {
            if (b.nutrisiPK) {
                pk.energi += Number(b.nutrisiPK.energi || 0);
                pk.protein += Number(b.nutrisiPK.protein || 0);
                pk.lemak += Number(b.nutrisiPK.lemak || 0);
                pk.karbohidrat += Number(b.nutrisiPK.karbohidrat || 0);
                pk.serat += Number(b.nutrisiPK.serat || 0);
            }
            if (b.nutrisiPB) {
                pb.energi += Number(b.nutrisiPB.energi || 0);
                pb.protein += Number(b.nutrisiPB.protein || 0);
                pb.lemak += Number(b.nutrisiPB.lemak || 0);
                pb.karbohidrat += Number(b.nutrisiPB.karbohidrat || 0);
                pb.serat += Number(b.nutrisiPB.serat || 0);
            }
        });

        return {
            key: sm.key,
            label: sm.label,
            nama_menu: menuName,
            dotColor: sm.dotColor,
            badgeColor: sm.badgeColor,
            items_count: items.length,
            bahan_names: items.map((b) => b.nama),
            pk: {
                energi: Number(pk.energi.toFixed(1)),
                protein: Number(pk.protein.toFixed(1)),
                lemak: Number(pk.lemak.toFixed(1)),
                karbohidrat: Number(pk.karbohidrat.toFixed(1)),
                serat: Number(pk.serat.toFixed(1)),
            },
            pb: {
                energi: Number(pb.energi.toFixed(1)),
                protein: Number(pb.protein.toFixed(1)),
                lemak: Number(pb.lemak.toFixed(1)),
                karbohidrat: Number(pb.karbohidrat.toFixed(1)),
                serat: Number(pb.serat.toFixed(1)),
            },
        };
    });
});

// Helper Rincian Kandungan Gizi per Sub Menu Varian Alergi (5 Komponen)
function getNutrisiSubMenuForAlergi(jenisAlergi) {
    return subMenuKeysConfig.map((sm) => {
        const normalMenuName =
            (subMenuKomponen.value[sm.key] || "").trim() || sm.defaultName;

        const substitusiBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi !== "alergi" || b.jenis_alergi !== jenisAlergi)
                return false;
            if (b.sub_menu_key) return b.sub_menu_key === sm.key;
            if (b.sub_menu_block_id)
                return b.sub_menu_block_id.startsWith(sm.key);
            return false;
        });

        const normalSafeBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            const matchKey = b.sub_menu_key
                ? b.sub_menu_key === sm.key
                : b.sub_menu_block_id && b.sub_menu_block_id.startsWith(sm.key);
            if (!matchKey) return false;
            return !isBahanContainsAlergen(b, jenisAlergi);
        });

        const normalDikeluarkanBahans = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            const matchKey = b.sub_menu_key
                ? b.sub_menu_key === sm.key
                : b.sub_menu_block_id && b.sub_menu_block_id.startsWith(sm.key);
            if (!matchKey) return false;
            return isBahanContainsAlergen(b, jenisAlergi);
        });

        const allItems = [...normalSafeBahans, ...substitusiBahans];
        const isSubstituted = substitusiBahans.length > 0;
        const isEliminated = normalDikeluarkanBahans.length > 0 && substitusiBahans.length === 0;

        let displayName = normalMenuName;
        if (isSubstituted) {
            const alMenu = subMenuAlergi.value[sm.key]?.find(
                (x) => x.jenis_alergi === jenisAlergi,
            );
            if (alMenu && alMenu.menu_pengganti) {
                displayName = alMenu.menu_pengganti;
            } else if (substitusiBahans[0]?.nama_sub_menu) {
                displayName = substitusiBahans[0].nama_sub_menu;
            }
        }

        const pk = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
        const pb = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };

        allItems.forEach((b) => {
            if (b.nutrisiPK) {
                pk.energi += Number(b.nutrisiPK.energi || 0);
                pk.protein += Number(b.nutrisiPK.protein || 0);
                pk.lemak += Number(b.nutrisiPK.lemak || 0);
                pk.karbohidrat += Number(b.nutrisiPK.karbohidrat || 0);
                pk.serat += Number(b.nutrisiPK.serat || 0);
            }
            if (b.nutrisiPB) {
                pb.energi += Number(b.nutrisiPB.energi || 0);
                pb.protein += Number(b.nutrisiPB.protein || 0);
                pb.lemak += Number(b.nutrisiPB.lemak || 0);
                pb.karbohidrat += Number(b.nutrisiPB.karbohidrat || 0);
                pb.serat += Number(b.nutrisiPB.serat || 0);
            }
        });

        return {
            key: sm.key,
            label: sm.label,
            nama_menu: displayName,
            normal_menu_name: normalMenuName,
            is_substituted: isSubstituted,
            is_eliminated: isEliminated,
            dotColor: sm.dotColor,
            badgeColor: isSubstituted
                ? "bg-rose-50 text-rose-900 border-rose-200"
                : sm.badgeColor,
            items_count: allItems.length,
            bahan_names: allItems.map((b) => b.nama),
            dikeluarkan_names: normalDikeluarkanBahans.map((b) => b.nama),
            pk: {
                energi: Number(pk.energi.toFixed(1)),
                protein: Number(pk.protein.toFixed(1)),
                lemak: Number(pk.lemak.toFixed(1)),
                karbohidrat: Number(pk.karbohidrat.toFixed(1)),
                serat: Number(pk.serat.toFixed(1)),
            },
            pb: {
                energi: Number(pb.energi.toFixed(1)),
                protein: Number(pb.protein.toFixed(1)),
                lemak: Number(pb.lemak.toFixed(1)),
                karbohidrat: Number(pb.karbohidrat.toFixed(1)),
                serat: Number(pb.serat.toFixed(1)),
            },
        };
    });
}

// Evaluasi AKG Varian Alergi (Persis GiziRancangMenuTab: bahan aman + substitusi)
const activeAlergiAkgList = computed(() => {
    const activeTypes = determinedActiveAlergiTypes.value;
    if (activeTypes.length === 0) return [];

    return activeTypes.map((jenis) => {
        const detailPm = findAlergiDetail(jenis);
        const jmlPk = detailPm ? Number(detailPm.porsi_kecil) || 0 : 0;
        const jmlPb = detailPm ? Number(detailPm.porsi_besar) || 0 : 0;
        const jmlTotal = detailPm ? Number(detailPm.total) || 0 : (jmlPk + jmlPb);

        const substitusiBahans = bahanCalculations.value.filter(
            (b) => b.tipe_porsi === "alergi" && b.jenis_alergi === jenis,
        );
        const bahanNormalSafe = bahanCalculations.value.filter((b) => {
            if (b.tipe_porsi === "alergi") return false;
            return !isBahanContainsAlergen(b, jenis);
        });

        const allBahanVarian = [...bahanNormalSafe, ...substitusiBahans];

        const pk = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
        const pb = { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };

        allBahanVarian.forEach((b) => {
            if (b.nutrisiPK) {
                pk.energi += Number(b.nutrisiPK.energi || 0);
                pk.protein += Number(b.nutrisiPK.protein || 0);
                pk.lemak += Number(b.nutrisiPK.lemak || 0);
                pk.karbohidrat += Number(b.nutrisiPK.karbohidrat || 0);
                pk.serat += Number(b.nutrisiPK.serat || 0);
            }
            if (b.nutrisiPB) {
                pb.energi += Number(b.nutrisiPB.energi || 0);
                pb.protein += Number(b.nutrisiPB.protein || 0);
                pb.lemak += Number(b.nutrisiPB.lemak || 0);
                pb.karbohidrat += Number(b.nutrisiPB.karbohidrat || 0);
                pb.serat += Number(b.nutrisiPB.serat || 0);
            }
        });

        return {
            jenis_alergi: jenis,
            total_pm: jmlTotal,
            pm_pk: jmlPk,
            pm_pb: jmlPb,
            bahan_count: substitusiBahans.length,
            pk: {
                energi: Number(pk.energi.toFixed(1)),
                protein: Number(pk.protein.toFixed(1)),
                lemak: Number(pk.lemak.toFixed(1)),
                karbohidrat: Number(pk.karbohidrat.toFixed(1)),
                serat: Number(pk.serat.toFixed(1)),
            },
            pb: {
                energi: Number(pb.energi.toFixed(1)),
                protein: Number(pb.protein.toFixed(1)),
                lemak: Number(pb.lemak.toFixed(1)),
                karbohidrat: Number(pb.karbohidrat.toFixed(1)),
                serat: Number(pb.serat.toFixed(1)),
            },
            sub_menus: getNutrisiSubMenuForAlergi(jenis),
        };
    });
});

function getSubMenuLabelForBahan(it) {
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

</script>

<template>
    <Modal :show="show" max-width="landscape" @close="emit('close')">
        <div v-if="workOrder" class="p-4 sm:p-6 space-y-6 max-h-[90vh] overflow-y-auto">
            <!-- Header Modal: Step 3 Review & Pengajuan Style -->
            <Card className="bg-white border-slate-200 shadow-xs">
                <CardHeader
                    className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <CardTitle
                                class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2"
                            >
                                <FileSpreadsheet class="h-5 w-5 text-primary" />
                                <span>Review Lengkap Rancangan Menu MBG</span>
                            </CardTitle>
                        </div>
                        <CardDescription class="text-xs sm:text-sm text-slate-500 mt-1">
                            Tinjauan menyeluruh hasil perencanaan produksi, 5
                            sub menu hidangan, kuota sasaran penerima manfaat,
                            formulasi bahan makanan & PO belanja, evaluasi
                            pemenuhan standar AKG BGN, serta kalkulasi food cost.
                        </CardDescription>
                    </div>

                    <!-- Status Badge Dokumen & Close Button -->
                    <div class="flex items-center gap-3 self-end md:self-auto">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500 font-medium">Status:</span>
                            <span
                                :class="[
                                    'px-3 py-1 text-xs font-black rounded-lg border flex items-center gap-1.5',
                                    statusPengajuanWo === 'Siap Produksi' ||
                                    statusPengajuanWo === 'Terverifikasi'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                        : statusPengajuanWo === 'Diajukan ke Keuangan'
                                          ? 'bg-blue-50 text-blue-700 border-blue-300'
                                          : statusPengajuanWo === 'Ditolak'
                                            ? 'bg-rose-50 text-rose-700 border-rose-300'
                                            : 'bg-amber-50 text-amber-700 border-amber-300',
                                ]"
                            >
                                <Clock class="h-3.5 w-3.5" />
                                {{ statusPengajuanWo }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </CardHeader>

                <CardContent className="p-4 sm:p-6 space-y-6">
                    <!-- Ringkasan Info Menu & Kuota Sasaran (Grid Cards) -->
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
                                {{ al.total_pm.toLocaleString("id-ID") }}
                                Porsi
                            </p>
                            <p class="text-[10px] text-rose-700 font-bold">
                                PK: {{ al.pm_pk }} • PB: {{ al.pm_pb }}
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

                    <!-- Log Riwayat Verifikasi Keuangan / Audit Trail jika ada -->
                    <div
                        v-if="
                            workOrder.catatan_keuangan ||
                            (workOrder.riwayat_verifikasi &&
                                workOrder.riwayat_verifikasi.length > 0) ||
                            statusPengajuanWo.toLowerCase().includes('ditolak')
                        "
                        class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 text-xs space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5"
                            >
                                <Clock class="h-4 w-4 text-primary shrink-0" />
                                Riwayat Catatan & Status Verifikasi Keuangan
                            </span>
                            <span class="text-[10.5px] font-bold text-slate-500">
                                {{ workOrder.riwayat_verifikasi?.length || 1 }} Riwayat Tercatat
                            </span>
                        </div>

                        <div
                            v-if="
                                workOrder.riwayat_verifikasi &&
                                workOrder.riwayat_verifikasi.length > 0
                            "
                            class="space-y-2 max-h-48 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="(log, lIdx) in workOrder.riwayat_verifikasi"
                                :key="lIdx"
                                :class="[
                                    'p-3 rounded-xl border text-xs space-y-1',
                                    log.status === 'Ditolak' ||
                                    log.status?.includes('Ditolak')
                                        ? 'bg-rose-50/80 border-rose-200 text-rose-950'
                                        : log.status === 'Terverifikasi' ||
                                            log.status === 'Siap Produksi'
                                          ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950'
                                          : 'bg-white border-slate-200 text-slate-800',
                                ]"
                            >
                                <div class="flex items-center justify-between font-bold">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        Status: {{ log.status || "Verifikasi" }}
                                    </span>
                                    <span class="text-[10.5px] opacity-75 font-normal">
                                        {{ formatDateTimeIndo(log.tanggal || log.created_at) }}
                                    </span>
                                </div>
                                <p v-if="log.catatan" class="text-xs mt-1 pl-3 border-l-2 border-current/30 italic">
                                    "{{ log.catatan }}"
                                </p>
                                <p v-if="log.verifikator" class="text-[10.5px] opacity-80 pt-0.5">
                                    Oleh: <strong>{{ log.verifikator }}</strong>
                                </p>
                            </div>
                        </div>

                        <div
                            v-else-if="workOrder.catatan_keuangan"
                            class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-950 text-xs"
                        >
                            <p class="font-bold">Catatan Keuangan:</p>
                            <p class="italic mt-0.5">"{{ workOrder.catatan_keuangan }}"</p>
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
                                    <UtensilsCrossed class="h-4 w-4 text-primary" />
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
                                        databasePangan === "csv" ||
                                        databasePangan === "tkpi2020"
                                            ? "Kemenkes"
                                            : "Nutri Survey"
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
                                v-for="sm in subMenuKeysConfig"
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
                                    <span>2. Daftar Kelompok Penerima Manfaat</span>
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
                                            <template
                                                v-if="
                                                    Array.isArray(kel.detail_alergi) &&
                                                    kel.detail_alergi.some(
                                                        (da) =>
                                                            activeAlergiJenisSet.has(da?.jenis_alergi) &&
                                                            ((Number(da?.porsi_kecil) || 0) + (Number(da?.porsi_besar) || 0)) > 0
                                                    )
                                                "
                                            >
                                                <div class="flex flex-wrap gap-1">
                                                    <template
                                                        v-for="(detAl, daIdx) in kel.detail_alergi"
                                                        :key="daIdx"
                                                    >
                                                        <span
                                                            v-if="
                                                                activeAlergiJenisSet.has(detAl?.jenis_alergi) &&
                                                                ((Number(detAl?.porsi_kecil) || 0) + (Number(detAl?.porsi_besar) || 0)) > 0
                                                            "
                                                            class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-50 text-amber-800 border border-amber-200"
                                                        >
                                                            {{
                                                                detAl.jenis_alergi +
                                                                ": " +
                                                                ((Number(detAl.porsi_kecil) || 0) + (Number(detAl.porsi_besar) || 0)) +
                                                                " PM"
                                                            }}
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
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
                                                      " PM Alergi Terdampak"
                                                    : "Semua Normal"
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- 3. EVALUASI PEMENUHAN STANDAR AKG BGN -->
                    <!-- ========================================================================= -->
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
                                    <Activity class="h-4 w-4 text-emerald-600" />
                                    <span
                                        >3. Evaluasi Standar Angka Kecukupan Gizi
                                        (AKG Standar BGN)</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Evaluasi pemenuhan Angka Kecukupan Gizi
                                    untuk porsi normal dan seluruh varian porsi
                                    alergi hasil formulasi bahan makanan siap
                                    santap.
                                </p>
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
                                    {{
                                        activeAlergiAkgList &&
                                        activeAlergiAkgList.length > 0
                                            ? "A. Standar AKG Porsi Normal (PK & PB)"
                                            : "Standar AKG Porsi Normal (PK & PB)"
                                    }}
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
                                    <div class="grid grid-cols-6 gap-2 text-xs">
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPKNormal.energi, 330, 413).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPKNormal.energi, 330, 413).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPKNormal.energi, 330, 413).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 330 - 413 kkal</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPKNormal.protein, 8.0, 10.0).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPKNormal.protein, 8.0, 10.0).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPKNormal.protein, 8.0, 10.0).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 8.0 - 10.0g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPKNormal.lemak, 11.0, 13.8).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPKNormal.lemak, 11.0, 13.8).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPKNormal.lemak, 11.0, 13.8).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 11.0 - 13.8g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPKNormal.karbohidrat, 50.0, 62.5).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPKNormal.karbohidrat, 50.0, 62.5).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPKNormal.karbohidrat, 50.0, 62.5).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 50.0 - 62.5g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPKNormal.serat, 4.0, 7.0).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPKNormal.serat, 4.0, 7.0).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPKNormal.serat, 4.0, 7.0).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 4.0 - 7.0g</span
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
                                    <div class="grid grid-cols-6 gap-2 text-xs">
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPBNormal.energi, 585, 831).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Energi</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPBNormal.energi, 585, 831).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPBNormal.energi, 585, 831).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 585 - 831 kkal</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPBNormal.protein, 15.8, 24.5).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Protein</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPBNormal.protein, 15.8, 24.5).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPBNormal.protein, 15.8, 24.5).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 15.8 - 24.5g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPBNormal.lemak, 19.5, 26.3).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Lemak</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPBNormal.lemak, 19.5, 26.3).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPBNormal.lemak, 19.5, 26.3).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 19.5 - 26.3g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPBNormal.karbohidrat, 87.0, 122.5).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Karbohidrat</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPBNormal.karbohidrat, 87.0, 122.5).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPBNormal.karbohidrat, 87.0, 122.5).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 87.0 - 122.5g</span
                                            >
                                        </div>
                                        <div
                                            class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                            :class="getNutrientStatus(akgResultPBNormal.serat, 6.0, 10.0).borderClass"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="text-[10px] text-slate-500 font-semibold"
                                                    >Serat</span
                                                >
                                                <span
                                                    class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold"
                                                    :class="getNutrientStatus(akgResultPBNormal.serat, 6.0, 10.0).badgeClass"
                                                >
                                                    {{ getNutrientStatus(akgResultPBNormal.serat, 6.0, 10.0).label }}
                                                </span>
                                            </div>
                                            <span
                                                class="font-black text-slate-900 text-sm block"
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
                                                >Std: 6.0 - 10.0g</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                                                <!-- TABEL RINCIAN KANDUNGAN GIZI PER KOMPONEN HIDANGAN (PORSI NORMAL) -->
                        <div class="bg-white rounded-2xl border border-emerald-200 overflow-hidden shadow-2xs mt-4">
                            <div class="p-3 bg-emerald-50/70 border-b border-emerald-200 flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs font-black text-emerald-950 flex items-center gap-1.5 uppercase tracking-wider">
                                    <Layers class="h-3.5 w-3.5 text-emerald-600" />
                                    RINCIAN KANDUNGAN GIZI PER KOMPONEN HIDANGAN (PORSI NORMAL)
                                </span>
                                <span class="text-[11px] text-emerald-800 font-medium">
                                    Kontribusi zat gizi 5 sub menu hidangan utama untuk Porsi Kecil (PK) dan Porsi Besar (PB)
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-emerald-50/40 text-slate-700 font-bold border-b border-emerald-200 uppercase text-[10px] select-none">
                                        <tr>
                                            <th class="p-2.5 text-center w-10">NO</th>
                                            <th class="p-2.5 min-w-[200px]">KOMPONEN HIDANGAN</th>
                                            <th class="p-2.5 text-center min-w-[80px]">BAHAN</th>
                                            <th class="p-2.5 text-right min-w-[110px]">ENERGI (kkal)</th>
                                            <th class="p-2.5 text-right min-w-[110px]">PROTEIN (g)</th>
                                            <th class="p-2.5 text-right min-w-[110px]">LEMAK (g)</th>
                                            <th class="p-2.5 text-right min-w-[115px]">KARBOHIDRAT (g)</th>
                                            <th class="p-2.5 text-right min-w-[105px]">SERAT (g)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-800">
                                        <tr v-for="(smGizi, idx) in nutrisiSubMenuNormal" :key="'akg-sub-normal-' + smGizi.key" class="hover:bg-emerald-50/30 transition-colors">
                                            <td class="p-2.5 text-center font-bold text-slate-400 align-middle">{{ idx + 1 }}</td>
                                            <td class="p-2.5 align-middle">
                                                <div class="flex items-center gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded border" :class="smGizi.badgeColor">
                                                                {{ smGizi.label }}
                                                            </span>
                                                        </div>
                                                        <div class="font-bold text-slate-900 text-xs mt-1">{{ smGizi.nama_menu }}</div>
                                                        <div v-if="smGizi.bahan_names && smGizi.bahan_names.length" class="text-[10px] text-slate-400 truncate max-w-xs mt-0.5" :title="smGizi.bahan_names.join(', ')">
                                                            {{ smGizi.bahan_names.join(', ') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-center align-middle font-medium text-slate-600">
                                                <span v-if="smGizi.items_count > 0" class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700">
                                                    {{ smGizi.items_count }} Bahan
                                                </span>
                                                <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                            </td>
                                            <!-- Energi -->
                                            <td class="p-2.5 text-right align-middle">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pk.energi.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pb.energi.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Protein -->
                                            <td class="p-2.5 text-right align-middle">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pk.protein.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pb.protein.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Lemak -->
                                            <td class="p-2.5 text-right align-middle">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pk.lemak.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pb.lemak.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Karbohidrat -->
                                            <td class="p-2.5 text-right align-middle">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pk.karbohidrat.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pb.karbohidrat.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Serat -->
                                            <td class="p-2.5 text-right align-middle">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pk.serat.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                        <span class="font-bold text-slate-800">{{ smGizi.pb.serat.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-emerald-50/80 font-black border-t-2 border-emerald-200 text-xs">
                                        <tr>
                                            <td colspan="3" class="p-2.5 text-right uppercase tracking-wider text-emerald-950 font-extrabold">
                                                TOTAL KANDUNGAN GIZI (PORSI NORMAL):
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                        <span>{{ akgResultPKNormal.energi.toFixed(1) }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                        <span>{{ akgResultPBNormal.energi.toFixed(1) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                        <span>{{ akgResultPKNormal.protein.toFixed(1) }}g</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                        <span>{{ akgResultPBNormal.protein.toFixed(1) }}g</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                        <span>{{ akgResultPKNormal.lemak.toFixed(1) }}g</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                        <span>{{ akgResultPBNormal.lemak.toFixed(1) }}g</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                        <span>{{ akgResultPKNormal.karbohidrat.toFixed(1) }}g</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                        <span>{{ akgResultPBNormal.karbohidrat.toFixed(1) }}g</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <div class="space-y-1">
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                        <span>{{ akgResultPKNormal.serat.toFixed(1) }}g</span>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                        <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                        <span>{{ akgResultPBNormal.serat.toFixed(1) }}g</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
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
                                            >({{ alRes.total_pm }} Porsi • PK:
                                            {{ alRes.pm_pk }}, PB:
                                            {{ alRes.pm_pb }})</span
                                        >
                                    </span>
                                    <span
                                        class="text-[10px] font-semibold text-rose-800 bg-white px-2 py-0.5 rounded border border-rose-200"
                                    >
                                        {{ alRes.bahan_count }} Bahan Substitusi
                                        Terpilih
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Evaluasi PK Alergi -->
                                    <div
                                        class="p-3.5 bg-white rounded-xl border border-rose-100 shadow-2xs space-y-3"
                                    >
                                        <div
                                            class="flex items-center justify-between border-b border-slate-100 pb-2.5"
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
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pk.energi, 330, 413).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Energi</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pk.energi, 330, 413).badgeClass">
                                                        {{ getNutrientStatus(alRes.pk.energi, 330, 413).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pk.energi.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">kkal</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 330 - 413 kkal</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pk.protein, 8.0, 10.0).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Protein</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pk.protein, 8.0, 10.0).badgeClass">
                                                        {{ getNutrientStatus(alRes.pk.protein, 8.0, 10.0).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pk.protein.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 8.0 - 10.0g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pk.lemak, 11.0, 13.8).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Lemak</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pk.lemak, 11.0, 13.8).badgeClass">
                                                        {{ getNutrientStatus(alRes.pk.lemak, 11.0, 13.8).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pk.lemak.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 11.0 - 13.8g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pk.karbohidrat, 50.0, 62.5).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Karbohidrat</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pk.karbohidrat, 50.0, 62.5).badgeClass">
                                                        {{ getNutrientStatus(alRes.pk.karbohidrat, 50.0, 62.5).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pk.karbohidrat.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 50.0 - 62.5g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pk.serat, 4.0, 7.0).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Serat</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pk.serat, 4.0, 7.0).badgeClass">
                                                        {{ getNutrientStatus(alRes.pk.serat, 4.0, 7.0).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pk.serat.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 4.0 - 7.0g</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Evaluasi PB Alergi -->
                                    <div
                                        class="p-3.5 bg-white rounded-xl border border-rose-100 shadow-2xs space-y-3"
                                    >
                                        <div
                                            class="flex items-center justify-between border-b border-slate-100 pb-2.5"
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
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pb.energi, 585, 831).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Energi</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pb.energi, 585, 831).badgeClass">
                                                        {{ getNutrientStatus(alRes.pb.energi, 585, 831).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pb.energi.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">kkal</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 585 - 831 kkal</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pb.protein, 15.8, 24.5).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Protein</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pb.protein, 15.8, 24.5).badgeClass">
                                                        {{ getNutrientStatus(alRes.pb.protein, 15.8, 24.5).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pb.protein.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 15.8 - 24.5g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-2 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pb.lemak, 19.5, 26.3).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Lemak</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pb.lemak, 19.5, 26.3).badgeClass">
                                                        {{ getNutrientStatus(alRes.pb.lemak, 19.5, 26.3).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pb.lemak.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 19.5 - 26.3g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pb.karbohidrat, 87.0, 122.5).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Karbohidrat</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pb.karbohidrat, 87.0, 122.5).badgeClass">
                                                        {{ getNutrientStatus(alRes.pb.karbohidrat, 87.0, 122.5).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pb.karbohidrat.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 87.0 - 122.5g</span>
                                            </div>
                                            <div
                                                class="p-2 bg-white rounded-lg border col-span-3 space-y-0.5"
                                                :class="getNutrientStatus(alRes.pb.serat, 6.0, 10.0).borderClass"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-500 font-semibold">Serat</span>
                                                    <span class="text-[8.5px] px-1 py-0.2 rounded border font-extrabold" :class="getNutrientStatus(alRes.pb.serat, 6.0, 10.0).badgeClass">
                                                        {{ getNutrientStatus(alRes.pb.serat, 6.0, 10.0).label }}
                                                    </span>
                                                </div>
                                                <span class="font-black text-slate-900 text-sm block">
                                                    {{ alRes.pb.serat.toFixed(1) }}
                                                    <span class="text-[10px] font-normal text-slate-500">g</span>
                                                </span>
                                                <span class="text-[9.5px] text-slate-400 block mt-0.5">Std: 6.0 - 10.0g</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TABEL RINCIAN KANDUNGAN GIZI PER KOMPONEN HIDANGAN (VARIAN ALERGI) -->
                                <div class="bg-white rounded-2xl border border-rose-200 overflow-hidden shadow-2xs">
                                    <div class="p-3 bg-rose-50/70 border-b border-rose-200 flex items-center justify-between flex-wrap gap-2">
                                        <span class="text-xs font-black text-rose-950 flex items-center gap-1.5 uppercase tracking-wider">
                                            <Layers class="h-3.5 w-3.5 text-rose-600" />
                                            RINCIAN KANDUNGAN GIZI PER KOMPONEN HIDANGAN (VARIAN {{ alRes.jenis_alergi }})
                                        </span>
                                        <span class="text-[11px] text-rose-800 font-medium">
                                            Kontribusi zat gizi setelah penyesuaian substitusi alergen & hidangan aman
                                        </span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs border-collapse">
                                            <thead class="bg-rose-50/40 text-slate-700 font-bold border-b border-rose-200 uppercase text-[10px] select-none">
                                                <tr>
                                                    <th class="p-2.5 text-center w-10">NO</th>
                                                    <th class="p-2.5 min-w-[200px]">KOMPONEN HIDANGAN</th>
                                                    <th class="p-2.5 text-center min-w-[80px]">BAHAN</th>
                                                    <th class="p-2.5 text-right min-w-[110px]">ENERGI (kkal)</th>
                                                    <th class="p-2.5 text-right min-w-[110px]">PROTEIN (g)</th>
                                                    <th class="p-2.5 text-right min-w-[110px]">LEMAK (g)</th>
                                                    <th class="p-2.5 text-right min-w-[115px]">KARBOHIDRAT (g)</th>
                                                    <th class="p-2.5 text-right min-w-[105px]">SERAT (g)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                                <tr v-for="(smGizi, idx) in alRes.sub_menus" :key="'akg-sub-' + alRes.jenis_alergi + '-' + smGizi.key" class="hover:bg-rose-50/30 transition-colors">
                                                    <td class="p-2.5 text-center font-bold text-slate-400 align-middle">{{ idx + 1 }}</td>
                                                    <td class="p-2.5 align-middle">
                                                        <div class="flex items-center gap-2">
                                                            <div>
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded border" :class="smGizi.badgeColor">
                                                                        {{ smGizi.label }}
                                                                    </span>
                                                                    <span v-if="smGizi.is_substituted" class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded bg-rose-100 text-rose-800 border border-rose-300">
                                                                        Substitusi Alergi
                                                                    </span>
                                                                    <span v-else-if="smGizi.is_eliminated" class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 border border-slate-300">
                                                                        Dieliminasi
                                                                    </span>
                                                                    <span v-else class="text-[9px] font-bold uppercase px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                        Aman
                                                                    </span>
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs mt-1">{{ smGizi.nama_menu }}</div>
                                                                <div v-if="smGizi.bahan_names && smGizi.bahan_names.length" class="text-[10px] text-slate-400 truncate max-w-xs mt-0.5" :title="smGizi.bahan_names.join(', ')">
                                                                    {{ smGizi.bahan_names.join(', ') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-center align-middle font-medium text-slate-600">
                                                        <span v-if="smGizi.items_count > 0" class="px-2 py-0.5 rounded-md bg-slate-100 font-bold text-[11px] text-slate-700">
                                                            {{ smGizi.items_count }} Bahan
                                                        </span>
                                                        <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                                    </td>
                                                    <!-- Energi -->
                                                    <td class="p-2.5 text-right align-middle">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pk.energi.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pb.energi.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <!-- Protein -->
                                                    <td class="p-2.5 text-right align-middle">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pk.protein.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pb.protein.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <!-- Lemak -->
                                                    <td class="p-2.5 text-right align-middle">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pk.lemak.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pb.lemak.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <!-- Karbohidrat -->
                                                    <td class="p-2.5 text-right align-middle">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pk.karbohidrat.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pb.karbohidrat.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <!-- Serat -->
                                                    <td class="p-2.5 text-right align-middle">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200">PK</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pk.serat.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1.5 text-[11px]">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-50 text-blue-800 border border-blue-200">PB</span>
                                                                <span class="font-bold text-slate-800">{{ smGizi.pb.serat.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="bg-rose-50/80 font-black border-t-2 border-rose-200 text-xs">
                                                <tr>
                                                    <td colspan="3" class="p-2.5 text-right uppercase tracking-wider text-rose-950 font-extrabold">
                                                        TOTAL KANDUNGAN GIZI (VARIAN {{ alRes.jenis_alergi }}):
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                                <span>{{ alRes.pk.energi.toFixed(1) }}</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                                <span>{{ alRes.pb.energi.toFixed(1) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                                <span>{{ alRes.pk.protein.toFixed(1) }}g</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                                <span>{{ alRes.pb.protein.toFixed(1) }}g</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                                <span>{{ alRes.pk.lemak.toFixed(1) }}g</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                                <span>{{ alRes.pb.lemak.toFixed(1) }}g</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                                <span>{{ alRes.pk.karbohidrat.toFixed(1) }}g</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                                <span>{{ alRes.pb.karbohidrat.toFixed(1) }}g</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="p-2.5 text-right">
                                                        <div class="space-y-1">
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-amber-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-amber-100 text-amber-900 border border-amber-300">PK</span>
                                                                <span>{{ alRes.pk.serat.toFixed(1) }}g</span>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-1 text-[11px] font-black text-blue-900">
                                                                <span class="text-[8.5px] font-extrabold px-1 py-0.2 rounded bg-blue-100 text-blue-900 border border-blue-300">PB</span>
                                                                <span>{{ alRes.pb.serat.toFixed(1) }}g</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="p-2.5 bg-rose-50/50 border-t border-rose-200 flex items-center justify-between flex-wrap gap-2 text-[10.5px] text-rose-800">
                                        <div class="flex items-center gap-3">
                                            <span class="flex items-center gap-1 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                PK: Porsi Kecil (PAUD, TK, SD 1-3)
                                            </span>
                                            <span class="flex items-center gap-1 font-semibold">
                                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                PB: Porsi Besar (SD 4-6, SMP, SMA/SMK, Bumil/Busui)
                                            </span>
                                        </div>
                                        <span class="italic text-rose-600">Seluruh komponen hidangan dihitung untuk varian alergi ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
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
                                            {{ al.total_pm }} Porsi (PK:
                                            {{ al.pm_pk }}, PB:
                                            {{ al.pm_pb }})
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
                                    <Layers class="h-3.5 w-3.5 text-slate-600" />
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
                                                                :class="getPortionCostBarColor(smCost.percent_pk)" class="h-full rounded-full transition-all" :style="{ width: Math.min(smCost.percent_pk || 0, 100) + '%' }"
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
                                                                :class="getPortionCostBarColor(smCost.percent_pb)" class="h-full rounded-full transition-all" :style="{ width: Math.min(smCost.percent_pb || 0, 100) + '%' }"
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
                            <!-- Keterangan Simbol Warna / Legenda Porsi Biaya -->
                            <div class="mt-3 p-3 bg-slate-50/80 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 text-[11px]">
                                    <span>📊</span>
                                    <span>Keterangan Indikator Porsi Biaya (PK / PB):</span>
                                </div>
                                <div class="flex items-center gap-4 text-[11px] font-medium flex-wrap">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3.5 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span><strong>Hijau:</strong> Rendah (&le; 25%)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3.5 h-2 rounded-full bg-amber-500 shrink-0"></span>
                                        <span><strong>Kuning:</strong> Sedang (26% - 50%)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3.5 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                        <span><strong>Merah:</strong> Dominan / Tinggi (&gt; 50%)</span>
                                    </div>
                                </div>
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
                                        {{ alCost.total_pm }} Porsi (PK:
                                        {{ alCost.pm_pk }}, PB:
                                        {{ alCost.pm_pb }}) • Termasuk
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
                                                                    :class="getPortionCostBarColor(smCost.percent_pk)" class="h-full rounded-full transition-all" :style="{ width: Math.min(smCost.percent_pk || 0, 100) + '%' }"
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
                                                                    :class="getPortionCostBarColor(smCost.percent_pb)" class="h-full rounded-full transition-all" :style="{ width: Math.min(smCost.percent_pb || 0, 100) + '%' }"
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
                                        >5. Rekapitulasi Kebutuhan Bahan Pangan &
                                        Order Pembelian (PO)</span
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
                                        <th class="p-3 text-center">Satuan</th>
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
                                                class="font-black text-slate-900 leading-tight flex items-center gap-1.5 flex-wrap"
                                            >
                                                <span>{{ b.nama }}</span>
                                                <span
                                                    v-if="
                                                        b.is_custom ||
                                                        (typeof b.code ===
                                                            'string' &&
                                                            b.code.startsWith(
                                                                'custom_',
                                                            )) ||
                                                        (typeof b.id ===
                                                            'string' &&
                                                            b.id.startsWith(
                                                                'custom_',
                                                            ))
                                                    "
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-amber-100 text-amber-800 border border-amber-300 shadow-2xs"
                                                    title="Bahan Ditambahkan Secara Manual"
                                                >
                                                    ✍️ Manual
                                                </span>
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
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">
                                                {{ b.satuan || 'Kg' }}
                                            </span>
                                        </td>
                                        <td
                                            class="p-3 text-center font-bold text-slate-800 whitespace-nowrap"
                                        >
                                            {{ b.gram_pk || 0 }} {{ getPortionUnit(b.satuan) }} /
                                            {{ b.gram_pb || 0 }} {{ getPortionUnit(b.satuan) }}
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
                                                formatGrossQty(
                                                    b.totalGrossKg,
                                                    b.satuan,
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="p-3 text-right text-slate-600 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.harga_master) }} / {{ b.satuan || 'Kg' }}
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
                                            colspan="8"
                                            class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold"
                                        >
                                            Grand Total Estimasi Biaya Belanja
                                            Bahan:
                                        </td>
                                        <td
                                            class="p-3.5 text-right font-black text-slate-900 whitespace-nowrap align-middle"
                                        >
                                            <div class="flex flex-col items-end justify-center gap-1">
                                                <div
                                                    v-for="(grp, gIdx) in getGroupedUnitList(bahanCalculations)"
                                                    :key="gIdx"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-900 text-xs font-black shadow-2xs"
                                                >
                                                    <span>{{ grp.label }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td></td>
                                        <td
                                            class="p-3.5 text-right font-black text-emerald-900 text-sm whitespace-nowrap align-middle"
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

                    <!-- Tombol Aksi Ekspor & Tutup di Modal -->
                    <div
                        class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                            <!-- Export Excel -->
                            <Button
                                type="button"
                                @click="exportWorkOrderExcel(workOrder)"
                                className="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 font-bold text-xs px-3.5 h-9 rounded-xl flex items-center gap-1.5 cursor-pointer"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                <span>Export Excel</span>
                            </Button>

                            <!-- Export Word -->
                            <Button
                                type="button"
                                @click="exportWorkOrderWord(workOrder)"
                                className="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-300 font-bold text-xs px-3.5 h-9 rounded-xl flex items-center gap-1.5 cursor-pointer"
                            >
                                <FilePenLine class="h-4 w-4" />
                                <span>Export Word</span>
                            </Button>

                            <!-- Export PDF -->
                            <Button
                                type="button"
                                @click="exportWorkOrderPdf(workOrder)"
                                className="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 font-bold text-xs px-3.5 h-9 rounded-xl flex items-center gap-1.5 cursor-pointer"
                            >
                                <FileText class="h-4 w-4" />
                                <span>Export PDF</span>
                            </Button>

                            <!-- Cetak -->
                            <Button
                                type="button"
                                @click="printWorkOrder(workOrder)"
                                className="bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-xs px-3.5 h-9 rounded-xl flex items-center gap-1.5 cursor-pointer"
                            >
                                <Printer class="h-4 w-4" />
                                <span>Cetak</span>
                            </Button>
                        </div>

                        <div class="w-full sm:w-auto flex justify-end">
                            <Button
                                type="button"
                                @click="emit('close')"
                                className="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-5 h-9 rounded-xl cursor-pointer"
                            >
                                Tutup
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </Modal>
</template>
