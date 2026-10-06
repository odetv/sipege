<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import LabelCardItem from "./LabelCardItem.vue";
import {
    Tag,
    Printer,
    Calendar,
    Clock,
    Zap,
    Utensils,
    PackageCheck,
    Check,
    Sparkles,
    Edit3,
    Download,
    FileText,
    Layers,
    Loader2,
    CheckCircle2,
    AlertCircle,
    X,
    Plus,
    Trash2,
    Save,
    BookmarkCheck,
    ArrowLeft,
    Coins,
    Flame,
    Building2,
    RotateCcw,
    AlertTriangle,
    ShieldAlert,
} from "lucide-vue-next";
import {
    downloadPdfSingleMode,
    downloadPdfA4GridMode,
    printPdfSingleMode,
} from "../labelPdfHelper.js";

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
    workOrders: {
        type: Array,
        default: () => [],
    },
    initialActiveWo: {
        type: Object,
        default: null,
    },
    editingLabel: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["cancel-edit"]);

function getTodayDateString() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

const todayStr = getTodayDateString();

// Mode cetak label: 'auto' (Sesuai Work Order) | 'manual' (Input Bebas)
const labelMode = ref("auto");

// Selected Work Order ID
const selectedWoId = ref(
    props.initialActiveWo?.id ||
        props.workOrders.find(
            (w) => (w.tanggal || "").substring(0, 10) === todayStr,
        )?.id ||
        props.workOrders[0]?.id ||
        null,
);

const todayWorkOrder = computed(() => {
    if (!props.workOrders || props.workOrders.length === 0) return null;
    return (
        props.workOrders.find(
            (w) => (w.tanggal || "").substring(0, 10) === todayStr,
        ) || null
    );
});

const activeWorkOrder = computed(() => {
    if (labelMode.value !== "auto") return null;
    if (!props.workOrders || props.workOrders.length === 0) return null;
    if (selectedWoId.value) {
        const found = props.workOrders.find(
            (w) =>
                w.id === selectedWoId.value ||
                w.uuid === selectedWoId.value ||
                w.db_id === selectedWoId.value,
        );
        if (found) return found;
    }
    return todayWorkOrder.value || props.workOrders[0];
});

// Parameter Identitas & Header
const namaSppg = ref(props.unitSppg?.nama || "SPPG BULELENG BANJAR DENCARIK");
const zonaWaktu = ref("WITA");

// Parameter Area Isolasi / Perekat Kemasan (cm) - Default 1.5cm
const tinggiIsolasiCm = ref(1.5);

// Parameter Waktu
const tanggalProduksi = ref(todayStr);
const jamProduksi = ref("12:14");
const gunakanJamProduksi = ref(false);
const tanggalExpired = ref(todayStr);
const jamExpired = ref("12:14");
const gunakanJamExpired = ref(false);

// Parameter Waktu Maksimal & Larangan
const waktuMaksimal = ref("2 JAM SETELAH DITERIMA!");
const teksLaranganHeader = ref("MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.");
const teksLaranganSub = ref("DILARANG MEMBAWA PULANG!");

// Mode Tab Editor/Preview Label Aktif: 'normal' | 'alergi'
const activeLabelTab = ref("normal");

// Parameter Komponen Menu Makanan Porsi Normal
const menuItems = ref([
    "Nasi Putih",
    "Ayam Crispy",
    "Tempe Manis Dadu",
    "Selada, Timun",
    "Melon",
]);

// Parameter Komponen Menu Makanan Porsi Alergi (Diet Khusus)
const menuItemsAlergi = ref([
    "Nasi Putih",
    "Ayam Crispy (Bebas Telur)",
    "Tempe Manis Dadu",
    "Selada, Timun",
    "Melon",
]);

// Pilihan Jenis Alergi & Custom Tag Banner
const selectedJenisAlergi = ref("Semua Alergi");
const customTagAlergi = ref("");

// ─── State Konfigurasi Manual Porsi Normal & Alergi ───────────────────────────
const manualPorsiNormal = ref(0);
const manualAllergyConfigs = ref({});
const selectedManualAllergies = ref([]);
const activeEditingAllergy = ref("");
const customAllergyInput = ref("");

// Parameter Kandungan Gizi Porsi Normal
const giziData = ref({
    energi_pb: "624",
    prot_pb: "29.8",
    lmk_pb: "18.8",
    karbo_pb: "82.4",
    serat_pb: "2.0",
    energi_pk: "469",
    prot_pk: "23.4",
    lmk_pk: "15.0",
    karbo_pk: "58.9",
    serat_pk: "1.5",
});

// Parameter Kandungan Gizi Porsi Alergi
const giziDataAlergi = ref({
    energi_pb: "624",
    prot_pb: "29.8",
    lmk_pb: "18.8",
    karbo_pb: "82.4",
    serat_pb: "2.0",
    energi_pk: "469",
    prot_pk: "23.4",
    lmk_pk: "15.0",
    karbo_pk: "58.9",
    serat_pk: "1.5",
});

// Filter Cetak / Download: 'semua' | 'normal' | 'alergi' (Default: semua porsi agar label normal & alergi ikut terunduh lengkap)
const filterCetakTipe = ref("semua");

// Konfigurasi per-alergi untuk Mode Otomatis (Work Order)
const autoAllergyConfigs = ref({});
const activeEditingAllergyAuto = ref("");
const currentAutoEditingAllergy = computed(() => {
    if (selectedJenisAlergi.value && selectedJenisAlergi.value !== "Semua Alergi") {
        return selectedJenisAlergi.value;
    }
    return activeEditingAllergyAuto.value || detectedAlergiList.value[0] || "";
});

// Parameter Rincian Harga Satuan per Item
const hargaItems = ref([
    { nama: "Nasi Putih", harga_pb: 1155, harga_pk: 770 },
    { nama: "Ayam Crispy", harga_pb: 5812, harga_pk: 4838 },
    { nama: "Tempe Manis Dadu", harga_pb: 1085, harga_pk: 844 },
    { nama: "Selada, Timun", harga_pb: 1046, harga_pk: 792 },
    { nama: "Melon", harga_pb: 1931, harga_pk: 1931 },
]);

// Kelompok Penerima Manfaat
const activeKelompokList = computed(() => {
    if (labelMode.value === "manual") {
        return (props.kelompokList || []).map((k) => ({
            ...k,
            is_menerima: true,
        }));
    }

    const wo = activeWorkOrder.value;
    if (!wo || !wo.kelompoks || wo.kelompoks.length === 0) {
        return (props.kelompokList || []).map((k) => ({
            ...k,
            is_menerima: true,
        }));
    }

    return (props.kelompokList || []).map((masterK) => {
        const savedK = wo.kelompoks.find(
            (sk) =>
                sk.kelompok_id === masterK.id ||
                sk.id === masterK.id ||
                sk.nama_kelompok === masterK.nama_kelompok,
        );
        if (savedK) {
            return {
                ...masterK,
                nama_kelompok: savedK.nama_kelompok || masterK.nama_kelompok,
                kategori: savedK.kategori || masterK.kategori,
                is_menerima: savedK.is_menerima !== false,
                total_porsi_kecil:
                    savedK.total_porsi_kecil !== undefined
                        ? savedK.total_porsi_kecil
                        : masterK.total_porsi_kecil,
                total_porsi_besar:
                    savedK.total_porsi_besar !== undefined
                        ? savedK.total_porsi_besar
                        : masterK.total_porsi_besar,
                total_penerima:
                    savedK.total_penerima !== undefined
                        ? savedK.total_penerima
                        : masterK.total_penerima,
                detail_alergi:
                    savedK.detail_alergi || masterK.keterangan_alergi || [],
            };
        }
        return {
            ...masterK,
            is_menerima: true,
        };
    });
});

// Daftar Jenis Alergi yang Terdeteksi (Hanya yang terdampak pada Work Order saat Mode Otomatis)
const detectedAlergiList = computed(() => {
    const list = new Set();

    if (labelMode.value === "auto") {
        const wo = activeWorkOrder.value;
        if (!wo) return [];

        // 1. Ambil dari sub_menu_alergi di WO (Menu pengganti diet khusus yang sudah diset)
        if (wo.sub_menu_alergi) {
            let subMenus = [];
            if (Array.isArray(wo.sub_menu_alergi)) {
                subMenus = wo.sub_menu_alergi;
            } else if (typeof wo.sub_menu_alergi === "object") {
                subMenus = Object.values(wo.sub_menu_alergi).flat();
            }
            for (const sm of subMenus) {
                if (sm) {
                    const val =
                        sm.jenis_alergi ||
                        sm.alergen ||
                        sm.nama_alergi ||
                        (typeof sm === "string" ? sm : null);
                    if (
                        val &&
                        typeof val === "string" &&
                        val.trim() &&
                        !val.toLowerCase().startsWith("sub_menu_")
                    ) {
                        list.add(val.trim());
                    }
                }
            }
        }

        // 2. Ambil dari items / bahan WO yang memiliki tipe_porsi 'alergi'
        if (Array.isArray(wo.items)) {
            for (const it of wo.items) {
                if (
                    it.tipe_porsi === "alergi" &&
                    it.jenis_alergi &&
                    typeof it.jenis_alergi === "string" &&
                    it.jenis_alergi.trim()
                ) {
                    list.add(it.jenis_alergi.trim());
                }
            }
        }

        // 3. Fallback jika list masih kosong tetapi ada total_alergi pada WO
        if (list.size === 0 && Number(wo.total_alergi || 0) > 0) {
            const groups = activeKelompokList.value || [];
            for (const g of groups) {
                if (g.is_menerima === false) continue;
                const details = g.detail_alergi || g.keterangan_alergi || [];
                if (Array.isArray(details)) {
                    for (const d of details) {
                        if (typeof d === "string" && d.trim()) {
                            list.add(d.trim());
                        } else if (typeof d === "object" && d?.jenis_alergi) {
                            const count =
                                (Number(d.porsi_kecil) || 0) +
                                (Number(d.porsi_besar) || 0) +
                                (Number(d.jumlah) || 0);
                            if (count > 0 || !("porsi_kecil" in d)) {
                                list.add(d.jenis_alergi.trim());
                            }
                        }
                    }
                }
            }
        }

        // Mode otomatis HANYA menampilkan alergi yang memang memiliki konfigurasi sub menu ATAU memiliki porsi > 0
        const validList = Array.from(list).filter((jenis) => {
            if (!jenis) return false;
            let inSubMenu = false;
            if (wo.sub_menu_alergi) {
                const subMenus = Array.isArray(wo.sub_menu_alergi)
                    ? wo.sub_menu_alergi
                    : typeof wo.sub_menu_alergi === "object"
                    ? Object.values(wo.sub_menu_alergi).flat()
                    : [];
                inSubMenu = subMenus.some(
                    (sm) =>
                        sm &&
                        ((sm.jenis_alergi || sm.alergen || sm.nama_alergi) === jenis ||
                         (typeof sm === "string" && sm === jenis))
                );
            }
            if (inSubMenu) return true;

            const groups = activeKelompokList.value || [];
            let count = 0;
            for (const g of groups) {
                if (g.is_menerima === false) continue;
                const details = g.detail_alergi || g.keterangan_alergi || [];
                if (Array.isArray(details)) {
                    for (const d of details) {
                        if (typeof d === "object") {
                            const j = (d.jenis_alergi || d.nama || "").trim();
                            if (j.toLowerCase() === jenis.toLowerCase()) {
                                count +=
                                    (Number(d.porsi_kecil) || 0) +
                                    (Number(d.porsi_besar) || 0) +
                                    (Number(d.jumlah) || 0);
                            }
                        } else if (typeof d === "string" && d.trim().toLowerCase() === jenis.toLowerCase()) {
                            count += 1;
                        }
                    }
                }
            }
            return count > 0;
        });

        return validList;
    }

    // Mode Manual: ambil dari master kelompok atau opsi umum
    const groups = activeKelompokList.value || [];
    for (const g of groups) {
        const details = g.detail_alergi || g.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "string" && d.trim()) {
                    list.add(d.trim());
                } else if (typeof d === "object" && d?.jenis_alergi) {
                    list.add(d.jenis_alergi.trim());
                }
            }
        }
    }
    const arr = Array.from(list).filter(Boolean);
    return arr.length > 0
        ? arr
        : ["Telur", "Seafood / Udang", "Kacang-kacangan", "Ikan", "Gluten / Gandum"];
});

// ─── Daftar Seluruh Opsi Alergen yang Tersedia di Mode Manual ─────────────────
const availableAllergiesList = computed(() => {
    const set = new Set();
    // 1. Dari master kelompok
    detectedAlergiList.value.forEach((a) => {
        if (a && typeof a === "string" && a.trim()) set.add(a.trim());
    });
    // 2. Dari standar opsi alergen umum
    const standardAllergens = [
        "Telur",
        "Daging Ayam",
        "Udang",
        "Santan",
        "Kacang Tanah",
        "Hati Ayam",
        "Mie",
        "Ikan Tongkol",
        "Ikan Teri",
        "Susu dan produk olahannya",
        "Telur Puyuh",
        "Saos",
        "Cokelat/Kakao",
        "Sosis",
        "Nugget",
        "Minuman Kemasan",
        "Jamur",
        "Ikan Laut",
        "Nasi/Beras",
        "Ikan Asin",
        "Daging Sapi",
    ];
    standardAllergens.forEach((a) => set.add(a));
    // 3. Tambahan custom yang pernah diinput
    Object.keys(manualAllergyConfigs.value).forEach((a) => {
        if (a && typeof a === "string" && a.trim()) set.add(a.trim());
    });
    return Array.from(set);
});

// Helper cari porsi alergi bawaan dari master kelompok
function getInitialGroupAllergyPorsi(jenis) {
    let count = 0;
    for (const k of activeKelompokList.value) {
        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "object") {
                    const j = (d.jenis_alergi || d.nama || "").trim();
                    if (j.toLowerCase() === jenis.toLowerCase()) {
                        count += (Number(d.porsi_kecil) || 0) + (Number(d.porsi_besar) || 0) + (Number(d.jumlah) || 0);
                    }
                } else if (typeof d === "string" && d.trim().toLowerCase() === jenis.toLowerCase()) {
                    count += 1;
                }
            }
        }
    }
    return count;
}

// Pastikan konfigurasi manual ada untuk jenis alergi tertentu
function ensureManualAllergyConfig(jenis) {
    if (!manualAllergyConfigs.value[jenis]) {
        const initialPorsi = getInitialGroupAllergyPorsi(jenis);
        const defaultMenu = menuItems.value.map((m, idx) => {
            if (idx === 1) return `${m} (Bebas ${jenis})`;
            return m;
        });

        manualAllergyConfigs.value[jenis] = {
            porsi: initialPorsi > 0 ? initialPorsi : 1,
            menuItems: defaultMenu.length > 0 ? [...defaultMenu] : ["Nasi Putih", `Lauk (Bebas ${jenis})`, "Tempe", "Sayur", "Buah"],
            giziData: { ...giziData.value },
        };
    }
    return manualAllergyConfigs.value[jenis];
}

// Toggle status aktif apakah jenis alergi ini dipilih untuk dibuatkan label
function toggleAllergySelected(jenis) {
    ensureManualAllergyConfig(jenis);
    const idx = selectedManualAllergies.value.indexOf(jenis);
    if (idx >= 0) {
        selectedManualAllergies.value.splice(idx, 1);
        if (activeEditingAllergy.value === jenis) {
            activeEditingAllergy.value = selectedManualAllergies.value[0] || "";
        }
    } else {
        selectedManualAllergies.value.push(jenis);
        if (!manualAllergyConfigs.value[jenis].porsi || manualAllergyConfigs.value[jenis].porsi <= 0) {
            const initialPorsi = getInitialGroupAllergyPorsi(jenis);
            manualAllergyConfigs.value[jenis].porsi = initialPorsi > 0 ? initialPorsi : 1;
        }
        activeEditingAllergy.value = jenis;
    }
}

function selectAllDetectedAllergies() {
    detectedAlergiList.value.forEach((jenis) => {
        ensureManualAllergyConfig(jenis);
        if (!selectedManualAllergies.value.includes(jenis)) {
            selectedManualAllergies.value.push(jenis);
        }
    });
    if (!activeEditingAllergy.value && selectedManualAllergies.value.length > 0) {
        activeEditingAllergy.value = selectedManualAllergies.value[0];
    }
}

function clearAllSelectedAllergies() {
    selectedManualAllergies.value = [];
    activeEditingAllergy.value = "";
}

function addNewCustomAllergy() {
    const val = (customAllergyInput.value || "").trim();
    if (!val) return;
    ensureManualAllergyConfig(val);
    if (!selectedManualAllergies.value.includes(val)) {
        selectedManualAllergies.value.push(val);
    }
    activeEditingAllergy.value = val;
    customAllergyInput.value = "";
}

function initManualConfigsFromGroups(force = false) {
    if (!props.kelompokList || props.kelompokList.length === 0) return;
    if (!force && Object.keys(manualAllergyConfigs.value).length > 0 && manualPorsiNormal.value > 0) return;

    let totalPmAll = 0;
    const allergyPorsiMap = {};

    props.kelompokList.forEach((k) => {
        const pk = Number(k.total_porsi_kecil) || 0;
        const pb = Number(k.total_porsi_besar) || 0;
        const pm = Number(k.total_penerima) || pk + pb;
        totalPmAll += pm;

        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            details.forEach((d) => {
                let jenis = "";
                let count = 0;
                if (typeof d === "object") {
                    jenis = (d.jenis_alergi || d.nama || "").trim();
                    count = (Number(d.porsi_kecil) || 0) + (Number(d.porsi_besar) || 0) + (Number(d.jumlah) || 0);
                } else if (typeof d === "string" && d.trim()) {
                    jenis = d.trim();
                    count = 1;
                }
                if (jenis) {
                    allergyPorsiMap[jenis] = (allergyPorsiMap[jenis] || 0) + count;
                }
            });
        }
    });

    let totalAlergiFound = 0;
    const initialSelected = [];
    const configs = { ...manualAllergyConfigs.value };

    Object.entries(allergyPorsiMap).forEach(([jenis, porsi]) => {
        totalAlergiFound += porsi;
        if (!initialSelected.includes(jenis)) initialSelected.push(jenis);
        if (!configs[jenis]) {
            configs[jenis] = {
                porsi: porsi,
                menuItems: menuItems.value.map((m, idx) => (idx === 1 ? `${m} (Bebas ${jenis})` : m)),
                giziData: { ...giziData.value },
            };
        } else if (force) {
            configs[jenis].porsi = porsi;
        }
    });

    // Daftarkan juga seluruh detectedAlergiList
    detectedAlergiList.value.forEach((jenis) => {
        if (!configs[jenis]) {
            configs[jenis] = {
                porsi: 0,
                menuItems: menuItems.value.map((m, idx) => (idx === 1 ? `${m} (Bebas ${jenis})` : m)),
                giziData: { ...giziData.value },
            };
        }
    });

    manualAllergyConfigs.value = configs;
    if (selectedManualAllergies.value.length === 0 || force) {
        selectedManualAllergies.value = initialSelected.length > 0 ? initialSelected : (detectedAlergiList.value.slice(0, 1));
    }
    if (!activeEditingAllergy.value) {
        activeEditingAllergy.value = selectedManualAllergies.value[0] || detectedAlergiList.value[0] || "";
    }

    if (manualPorsiNormal.value === 0 || force) {
        manualPorsiNormal.value = Math.max(0, totalPmAll - totalAlergiFound);
    }
}

function syncNormalPorsiFromKelompok() {
    let totalPm = 0;
    printableKelompokList.value.forEach((k) => {
        const pk = Number(k.total_porsi_kecil) || 0;
        const pb = Number(k.total_porsi_besar) || 0;
        totalPm += Number(k.total_penerima) || pk + pb;
    });
    manualPorsiNormal.value = Math.max(0, totalPm - totalPorsiAlergi.value);
}

function addCurrentAllergyMenuItem() {
    if (!activeEditingAllergy.value) return;
    const cfg = ensureManualAllergyConfig(activeEditingAllergy.value);
    cfg.menuItems.push("");
}

function removeCurrentAllergyMenuItem(idx) {
    if (!activeEditingAllergy.value) return;
    const cfg = ensureManualAllergyConfig(activeEditingAllergy.value);
    if (cfg.menuItems.length <= 1) return;
    cfg.menuItems.splice(idx, 1);
}

function copyMenuFromNormalToActiveAllergy() {
    if (!activeEditingAllergy.value) return;
    const cfg = ensureManualAllergyConfig(activeEditingAllergy.value);
    cfg.menuItems = menuItems.value.map((m, idx) =>
        idx === 1 ? `${m} (Bebas ${activeEditingAllergy.value})` : m,
    );
}

function copyGiziFromNormalToActiveAllergy() {
    if (!activeEditingAllergy.value) return;
    const cfg = ensureManualAllergyConfig(activeEditingAllergy.value);
    cfg.giziData = { ...giziData.value };
}

const selectedKelompokIds = ref([]);

const receivingKelompokList = computed(() => {
    return activeKelompokList.value.filter((k) => k.is_menerima !== false);
});

const isAllSelected = computed({
    get() {
        const targetList =
            activeLabelTab.value === "alergi" ||
            filterCetakTipe.value === "alergi"
                ? receivingKelompokList.value.filter((k) =>
                      hasKelompokImpactedAlergi(k),
                  )
                : receivingKelompokList.value;
        return (
            targetList.length > 0 &&
            targetList.every((k) => selectedKelompokIds.value.includes(k.id))
        );
    },
    set(val) {
        if (val) {
            const targetList =
                activeLabelTab.value === "alergi" ||
                filterCetakTipe.value === "alergi"
                    ? receivingKelompokList.value.filter((k) =>
                          hasKelompokImpactedAlergi(k),
                      )
                    : receivingKelompokList.value;
            selectedKelompokIds.value = targetList.map((k) => k.id);
        } else {
            selectedKelompokIds.value = [];
        }
    },
});

const printableKelompokList = computed(() => {
    return activeKelompokList.value.filter(
        (k) =>
            k.is_menerima !== false && selectedKelompokIds.value.includes(k.id),
    );
});

// Hitung Breakdown Porsi Normal vs Alergi
const totalPorsiAlergi = computed(() => {
    if (labelMode.value === "manual") {
        return selectedManualAllergies.value.reduce((acc, jenis) => {
            const cfg = manualAllergyConfigs.value[jenis];
            return acc + (Number(cfg?.porsi) || 0);
        }, 0);
    }

    const affectedAlergiList = detectedAlergiList.value;
    const isAuto = labelMode.value === "auto";

    let sum = 0;
    for (const k of printableKelompokList.value) {
        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "object") {
                    const jenis = (d.jenis_alergi || "").trim();
                    if (
                        isAuto &&
                        affectedAlergiList.length > 0 &&
                        !affectedAlergiList.includes(jenis)
                    ) {
                        continue;
                    }
                    sum +=
                        (Number(d.porsi_kecil) || 0) +
                        (Number(d.porsi_besar) || 0) +
                        (Number(d.jumlah) || 0);
                } else if (typeof d === "string" && d.trim()) {
                    const jenis = d.trim();
                    if (
                        isAuto &&
                        affectedAlergiList.length > 0 &&
                        !affectedAlergiList.includes(jenis)
                    ) {
                        continue;
                    }
                    sum += 1;
                }
            }
        }
    }
    return sum;
});

const totalPorsiNormal = computed(() => {
    if (labelMode.value === "manual") {
        return Number(manualPorsiNormal.value) || 0;
    }

    let sum = 0;
    const affectedAlergiList = detectedAlergiList.value;
    const isAuto = labelMode.value === "auto";

    for (const k of printableKelompokList.value) {
        const pk = Number(k.total_porsi_kecil) || 0;
        const pb = Number(k.total_porsi_besar) || 0;
        const totalPm = Number(k.total_penerima) || pk + pb;

        let alergiInGroup = 0;
        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "object") {
                    const jenis = (d.jenis_alergi || "").trim();
                    if (
                        isAuto &&
                        affectedAlergiList.length > 0 &&
                        !affectedAlergiList.includes(jenis)
                    ) {
                        continue;
                    }
                    alergiInGroup +=
                        (Number(d.porsi_kecil) || 0) +
                        (Number(d.porsi_besar) || 0) +
                        (Number(d.jumlah) || 0);
                } else if (typeof d === "string" && d.trim()) {
                    const jenis = d.trim();
                    if (
                        isAuto &&
                        affectedAlergiList.length > 0 &&
                        !affectedAlergiList.includes(jenis)
                    ) {
                        continue;
                    }
                    alergiInGroup += 1;
                }
            }
        }
        sum += Math.max(0, totalPm - alergiInGroup);
    }
    return sum > 0
        ? sum
        : Math.max(0, (totalWoPorsi.value || 0) - totalPorsiAlergi.value);
});

// Helper hitung jumlah porsi per jenis alergi (Mode Otomatis & Manual)
function getAllergyPorsiCount(jenis) {
    if (labelMode.value === "manual") {
        if (!jenis || jenis === "Semua Alergi") {
            return totalPorsiAlergi.value;
        }
        return Number(manualAllergyConfigs.value[jenis]?.porsi) || 0;
    }
    // Mode Otomatis
    if (!jenis || jenis === "Semua Alergi") {
        return totalPorsiAlergi.value;
    }
    let sum = 0;
    const targetGroups =
        printableKelompokList.value.length > 0
            ? printableKelompokList.value
            : activeKelompokList.value.filter((k) => k.is_menerima !== false);
    for (const k of targetGroups) {
        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "object") {
                    const j = (d.jenis_alergi || d.nama || "").trim();
                    if (j.toLowerCase() === jenis.toLowerCase()) {
                        sum +=
                            (Number(d.porsi_kecil) || 0) +
                            (Number(d.porsi_besar) || 0) +
                            (Number(d.jumlah) || 0);
                    }
                } else if (typeof d === "string") {
                    const j = d.trim();
                    if (j.toLowerCase() === jenis.toLowerCase()) {
                        sum += 1;
                    }
                }
            }
        }
    }
    return sum;
}

// Helper hitung breakdown porsi PK & PB per jenis alergi
function getAllergyPorsiBreakdown(jenis) {
    if (labelMode.value === "manual") {
        const total = getAllergyPorsiCount(jenis);
        return { total, pk: 0, pb: total };
    }

    const targetGroups =
        printableKelompokList.value.length > 0
            ? printableKelompokList.value
            : activeKelompokList.value.filter((k) => k.is_menerima !== false);

    let pk = 0;
    let pb = 0;
    const affectedList = detectedAlergiList.value;

    for (const k of targetGroups) {
        const details = k.detail_alergi || k.keterangan_alergi || [];
        if (Array.isArray(details)) {
            for (const d of details) {
                if (typeof d === "object") {
                    const j = (d.jenis_alergi || d.nama || "").trim();
                    const isTarget =
                        !jenis || jenis === "Semua Alergi"
                            ? affectedList.length > 0
                                ? affectedList.includes(j)
                                : true
                            : j.toLowerCase() === jenis.toLowerCase();

                    if (isTarget) {
                        pk += Number(d.porsi_kecil) || 0;
                        pb += Number(d.porsi_besar) || 0;
                        if (!("porsi_kecil" in d) && !("porsi_besar" in d)) {
                            pb += Number(d.jumlah) || 1;
                        }
                    }
                } else if (typeof d === "string") {
                    const j = d.trim();
                    const isTarget =
                        !jenis || jenis === "Semua Alergi"
                            ? affectedList.length > 0
                                ? affectedList.includes(j)
                                : true
                            : j.toLowerCase() === jenis.toLowerCase();

                    if (isTarget) {
                        pb += 1;
                    }
                }
            }
        }
    }
    return { total: pk + pb, pk, pb };
}

// Helper format ringkas porsi alergi (Contoh: "5 Porsi (2 PK, 3 PB)")
function formatPorsiBreakdownText(jenis) {
    const b = getAllergyPorsiBreakdown(jenis);
    if (b.total === 0) return "0 Porsi";
    if (b.pk > 0 && b.pb > 0) {
        return `${b.total} Porsi (${b.pk} PK, ${b.pb} PB)`;
    }
    if (b.pk > 0) {
        return `${b.total} Porsi (${b.pk} PK)`;
    }
    return `${b.total} Porsi (${b.pb} PB)`;
}

// Helper mengambil rincian jenis alergi dan porsi pada kelompok tertentu
function getKelompokAlergiBreakdown(k) {
    if (!k) return [];
    const details = k.detail_alergi || k.keterangan_alergi || [];
    const breakdown = {};
    const affectedList = detectedAlergiList.value;
    const isAuto = labelMode.value === "auto";

    if (Array.isArray(details)) {
        for (const d of details) {
            if (typeof d === "object") {
                const j = (d.jenis_alergi || d.nama || "Alergi").trim();
                if (
                    isAuto &&
                    affectedList.length > 0 &&
                    !affectedList.includes(j)
                ) {
                    continue;
                }
                const count =
                    (Number(d.porsi_kecil) || 0) +
                    (Number(d.porsi_besar) || 0) +
                    (Number(d.jumlah) || 0);
                if (count > 0) {
                    breakdown[j] = (breakdown[j] || 0) + count;
                }
            } else if (typeof d === "string" && d.trim()) {
                const j = d.trim();
                if (
                    isAuto &&
                    affectedList.length > 0 &&
                    !affectedList.includes(j)
                ) {
                    continue;
                }
                breakdown[j] = (breakdown[j] || 0) + 1;
            }
        }
    }
    return Object.entries(breakdown).map(([jenis, count]) => ({
        jenis,
        count,
    }));
}

// Helper hitung jumlah porsi alergi yang terdampak pada kelompok tertentu
function getKelompokAlergiCount(k) {
    if (!k) return 0;
    const items = getKelompokAlergiBreakdown(k);
    if (
        selectedJenisAlergi.value === "Semua Alergi" ||
        activeLabelTab.value !== "alergi"
    ) {
        return items.reduce((acc, it) => acc + it.count, 0);
    }
    const found = items.find(
        (it) =>
            it.jenis.toLowerCase() === selectedJenisAlergi.value.toLowerCase(),
    );
    return found ? found.count : 0;
}

function hasKelompokImpactedAlergi(k) {
    if (!k) return false;
    const items = getKelompokAlergiBreakdown(k);
    if (items.length === 0) return false;
    if (
        selectedJenisAlergi.value === "Semua Alergi" ||
        activeLabelTab.value !== "alergi"
    ) {
        return true;
    }
    return items.some(
        (it) =>
            it.jenis.toLowerCase() === selectedJenisAlergi.value.toLowerCase(),
    );
}

function toggleKelompok(k) {
    if (k.is_menerima === false) return;
    const idx = selectedKelompokIds.value.indexOf(k.id);
    if (idx > -1) {
        selectedKelompokIds.value.splice(idx, 1);
    } else {
        selectedKelompokIds.value.push(k.id);
    }
}

function setLabelMode(mode) {
    labelMode.value = mode;
    if (mode === "auto" && activeWorkOrder.value) {
        syncDataFromWorkOrder(activeWorkOrder.value);
    }
}

function setActiveLabelTab(tab) {
    activeLabelTab.value = tab;
    // Jangan ubah filterCetakTipe jika user telah memilih "semua"
    if (filterCetakTipe.value !== "semua") {
        filterCetakTipe.value = tab;
    }
}

// Inisialisasi konfigurasi komponen menu dan gizi terpisah untuk tiap jenis alergi (Mode Otomatis)
function initAutoAllergyConfigs(wo = activeWorkOrder.value) {
    if (!wo) return;
    const configs = { ...autoAllergyConfigs.value };
    const affectedList = detectedAlergiList.value;

    const baseGizi = {
        energi_pb: String(wo.akg_pb?.energi || giziData.value?.energi_pb || "624"),
        prot_pb: String(wo.akg_pb?.protein || giziData.value?.prot_pb || "25.2"),
        lmk_pb: String(wo.akg_pb?.lemak || giziData.value?.lmk_pb || "17.7"),
        karbo_pb: String(wo.akg_pb?.karbohidrat || giziData.value?.karbo_pb || "69.6"),
        serat_pb: String(wo.akg_pb?.serat || giziData.value?.serat_pb || "3.8"),
        energi_pk: String(wo.akg_pk?.energi || giziData.value?.energi_pk || "384.2"),
        prot_pk: String(wo.akg_pk?.protein || giziData.value?.prot_pk || "21.4"),
        lmk_pk: String(wo.akg_pk?.lemak || giziData.value?.lmk_pk || "14.2"),
        karbo_pk: String(wo.akg_pk?.karbohidrat || giziData.value?.karbo_pk || "45.7"),
        serat_pk: String(wo.akg_pk?.serat || giziData.value?.serat_pk || "2.6"),
    };

    affectedList.forEach((jenis) => {
        const existing = configs[jenis];

        // Cari nilai AKG spesifik alergi dari Work Order
        const akgSpecific =
            wo.akg_alergi?.[jenis] ||
            (wo.akg_alergi
                ? Object.entries(wo.akg_alergi).find(
                      ([k]) => k.toLowerCase() === jenis.toLowerCase(),
                  )?.[1]
                : null);

        let specificGizi = { ...baseGizi };
        if (akgSpecific && (akgSpecific.akg_pb || akgSpecific.akg_pk)) {
            specificGizi = {
                energi_pb: String(akgSpecific.akg_pb?.energi ?? baseGizi.energi_pb),
                prot_pb: String(akgSpecific.akg_pb?.protein ?? baseGizi.prot_pb),
                lmk_pb: String(akgSpecific.akg_pb?.lemak ?? baseGizi.lmk_pb),
                karbo_pb: String(akgSpecific.akg_pb?.karbohidrat ?? baseGizi.karbo_pb),
                serat_pb: String(akgSpecific.akg_pb?.serat ?? baseGizi.serat_pb),
                energi_pk: String(akgSpecific.akg_pk?.energi ?? baseGizi.energi_pk),
                prot_pk: String(akgSpecific.akg_pk?.protein ?? baseGizi.prot_pk),
                lmk_pk: String(akgSpecific.akg_pk?.lemak ?? baseGizi.lmk_pk),
                karbo_pk: String(akgSpecific.akg_pk?.karbohidrat ?? baseGizi.karbo_pk),
                serat_pk: String(akgSpecific.akg_pk?.serat ?? baseGizi.serat_pk),
            };
        }

        configs[jenis] = {
            menuItems:
                existing?.menuItems && existing.menuItems.length > 0
                    ? [...existing.menuItems]
                    : getAllergyMenuItemsForTarget(jenis, wo),
            giziData: existing?.giziData
                ? { ...existing.giziData }
                : { ...specificGizi },
            tagAlergi:
                existing?.tagAlergi ||
                `⚠️ KHUSUS ALERGI: ${jenis.toUpperCase()}`,
        };
    });

    autoAllergyConfigs.value = configs;

    // Sinkronkan giziDataAlergi aktif dengan alergi yang sedang dipilih/diedit
    const targetAllergy =
        (selectedJenisAlergi.value && selectedJenisAlergi.value !== "Semua Alergi")
            ? selectedJenisAlergi.value
            : (currentAutoEditingAllergy.value || affectedList[0]);

    if (targetAllergy && configs[targetAllergy]?.giziData) {
        giziDataAlergi.value = { ...configs[targetAllergy].giziData };
    }
}

// Helper mengambil menu pengganti alergi berdasarkan sub_menu 1-5 dan sub_menu_alergi di WO
function getAllergyMenuItemsForTarget(
    targetAlergi,
    wo = activeWorkOrder.value,
) {
    if (!wo) return menuItemsAlergi.value;

    const normal1 = menuItems.value[0] || wo.sub_menu_1 || "";
    const normal2 = menuItems.value[1] || wo.sub_menu_2 || "";
    const normal3 = menuItems.value[2] || wo.sub_menu_3 || "";
    const normal4 = menuItems.value[3] || wo.sub_menu_4 || "";
    const normal5 = menuItems.value[4] || wo.sub_menu_5 || "";

    const baseList = [normal1, normal2, normal3, normal4, normal5].filter(
        Boolean,
    );
    if (baseList.length === 0) {
        return menuItemsAlergi.value;
    }

    const rawAlergi = wo.sub_menu_alergi;
    if (!rawAlergi || typeof rawAlergi !== "object") {
        return baseList;
    }

    const result = [...baseList];

    // Cek sub_menu_1 sampai sub_menu_5 untuk mencari menu pengganti
    for (let i = 1; i <= 5; i++) {
        const key = `sub_menu_${i}`;
        const replacements = rawAlergi[key];
        if (Array.isArray(replacements) && replacements.length > 0) {
            for (const rep of replacements) {
                if (!rep) continue;
                const j = (rep.jenis_alergi || rep.alergen || "").trim();
                const replName =
                    rep.menu_pengganti || rep.nama || rep.item || "";

                if (
                    replName &&
                    (!targetAlergi ||
                        targetAlergi === "Semua Alergi" ||
                        targetAlergi.toLowerCase() === j.toLowerCase() ||
                        j === "")
                ) {
                    if (result[i - 1] !== undefined) {
                        result[i - 1] = replName;
                    }
                    break;
                }
            }
        }
    }

    return result;
}

function syncDataFromWorkOrder(wo) {
    if (!wo) return;
    tanggalProduksi.value = wo.tanggal || todayStr;
    tanggalExpired.value = wo.tanggal || todayStr;

    // 1. Sinkronisasi komponen menu normal (Sub Menu 1 sampai 5 dari WO)
    const normalSubMenus = [
        wo.sub_menu_1,
        wo.sub_menu_2,
        wo.sub_menu_3,
        wo.sub_menu_4,
        wo.sub_menu_5,
    ].filter(Boolean);

    if (normalSubMenus.length > 0) {
        menuItems.value = normalSubMenus;
    } else if (Array.isArray(wo.komponen) && wo.komponen.length > 0) {
        menuItems.value = wo.komponen.filter(Boolean);
    } else if (Array.isArray(wo.items) && wo.items.length > 0) {
        menuItems.value = wo.items.map((it) => it.nama);
    }

    // 2. Sinkronisasi rincian harga jika ada
    if (Array.isArray(wo.items) && wo.items.length > 0) {
        hargaItems.value = wo.items.map((it) => ({
            nama: it.nama,
            harga_pb: it.cost_pb || 0,
            harga_pk: it.cost_pk || 0,
        }));
    }

    // 3. Sinkronisasi AKG Normal
    if (wo.akg_pb && wo.akg_pk) {
        giziData.value = {
            energi_pb: String(wo.akg_pb.energi || "624"),
            prot_pb: String(wo.akg_pb.protein || "25.2"),
            lmk_pb: String(wo.akg_pb.lemak || "17.7"),
            karbo_pb: String(wo.akg_pb.karbohidrat || "69.6"),
            serat_pb: String(wo.akg_pb.serat || "3.8"),
            energi_pk: String(wo.akg_pk.energi || "384.2"),
            prot_pk: String(wo.akg_pk.protein || "21.4"),
            lmk_pk: String(wo.akg_pk.lemak || "14.2"),
            karbo_pk: String(wo.akg_pk.karbohidrat || "45.7"),
            serat_pk: String(wo.akg_pk.serat || "2.6"),
        };
    }

    // 4. Inisialisasi konfigurasi menu dan gizi terpisah untuk tiap jenis alergi terdampak dari WO
    initAutoAllergyConfigs(wo);

    // 5. Sinkronisasi komponen menu & gizi alergi aktif
    const targetAllergy =
        (selectedJenisAlergi.value && selectedJenisAlergi.value !== "Semua Alergi")
            ? selectedJenisAlergi.value
            : (currentAutoEditingAllergy.value || detectedAlergiList.value[0]);

    if (targetAllergy && autoAllergyConfigs.value[targetAllergy]) {
        menuItemsAlergi.value = [...autoAllergyConfigs.value[targetAllergy].menuItems];
        if (autoAllergyConfigs.value[targetAllergy].giziData) {
            giziDataAlergi.value = { ...autoAllergyConfigs.value[targetAllergy].giziData };
        }
    } else {
        menuItemsAlergi.value = getAllergyMenuItemsForTarget(
            selectedJenisAlergi.value,
            wo,
        );
    }

    // 6. Centang sasaran kelompok PM sesuai mode
    if (
        filterCetakTipe.value === "alergi" ||
        (activeLabelTab.value === "alergi" && filterCetakTipe.value !== "semua")
    ) {
        selectedKelompokIds.value = activeKelompokList.value
            .filter(
                (k) => k.is_menerima !== false && hasKelompokImpactedAlergi(k),
            )
            .map((k) => k.id);
    } else {
        selectedKelompokIds.value = activeKelompokList.value
            .filter((k) => k.is_menerima !== false)
            .map((k) => k.id);
    }
}

watch([activeLabelTab, filterCetakTipe], ([tab, filter]) => {
    if (labelMode.value === "auto" && activeWorkOrder.value) {
        if (filter === "semua") {
            // Ketika filter 'semua' dipilih untuk download/cetak, semua kelompok penerima manfaat dipilih
            selectedKelompokIds.value = activeKelompokList.value
                .filter((k) => k.is_menerima !== false)
                .map((k) => k.id);
        } else if (filter === "alergi") {
            selectedKelompokIds.value = activeKelompokList.value
                .filter(
                    (k) =>
                        k.is_menerima !== false && hasKelompokImpactedAlergi(k),
                )
                .map((k) => k.id);
        } else if (filter === "normal") {
            selectedKelompokIds.value = activeKelompokList.value
                .filter((k) => k.is_menerima !== false)
                .map((k) => k.id);
        }
    }
});

watch(filterCetakTipe, (val) => {
    if (val === "normal" || val === "alergi") {
        activeLabelTab.value = val;
    }
});

watch([currentAutoEditingAllergy, selectedJenisAlergi], ([currAuto, selJenis]) => {
    if (labelMode.value === "auto" && activeWorkOrder.value) {
        const target =
            selJenis && selJenis !== "Semua Alergi"
                ? selJenis
                : currAuto || detectedAlergiList.value[0];
        if (target && autoAllergyConfigs.value[target]) {
            menuItemsAlergi.value = [...autoAllergyConfigs.value[target].menuItems];
            if (autoAllergyConfigs.value[target].giziData) {
                giziDataAlergi.value = { ...autoAllergyConfigs.value[target].giziData };
            }
        } else {
            menuItemsAlergi.value = getAllergyMenuItemsForTarget(
                target || "Semua Alergi",
                activeWorkOrder.value,
            );
        }
        if (
            activeLabelTab.value === "alergi" &&
            filterCetakTipe.value !== "semua"
        ) {
            selectedKelompokIds.value = activeKelompokList.value
                .filter(
                    (k) =>
                        k.is_menerima !== false && hasKelompokImpactedAlergi(k),
                )
                .map((k) => k.id);
        }
    }
});

watch(
    [labelMode, activeWorkOrder],
    () => {
        if (labelMode.value === "auto" && activeWorkOrder.value) {
            syncDataFromWorkOrder(activeWorkOrder.value);
        } else if (labelMode.value === "manual") {
            selectedKelompokIds.value = (props.kelompokList || []).map(
                (k) => k.id,
            );
        }
    },
    { immediate: true },
);

// Watch editingLabel
watch(
    () => props.editingLabel,
    (item) => {
        if (item) {
            labelMode.value = "manual";
            tanggalProduksi.value =
                (item.tanggal_produksi || "").substring(0, 10) || todayStr;
            tanggalExpired.value =
                (item.tanggal_produksi || "").substring(0, 10) || todayStr;
            jamProduksi.value = item.jam_produksi || "12:14";
            gunakanJamProduksi.value = item.tampilkan_jam_produksi ?? false;
            jamExpired.value = item.batas_konsumsi || "12:14";
            gunakanJamExpired.value = item.tampilkan_jam_expired ?? false;

            if (item.petunjuk_menu) {
                const parts = item.petunjuk_menu
                    .split("\n")
                    .map((p) => p.replace(/^[•\-\*]\s*/, "").trim())
                    .filter(Boolean);
                if (parts.length > 0) {
                    menuItems.value = parts;
                }
            }

            if (item.gizi_data) {
                giziData.value = { ...giziData.value, ...item.gizi_data };
                if (item.gizi_data.gizi_alergi) {
                    giziDataAlergi.value = {
                        ...giziDataAlergi.value,
                        ...item.gizi_data.gizi_alergi,
                    };
                }
            }

            if (item.keterangan) {
                try {
                    const parsed = JSON.parse(item.keterangan);
                    if (
                        Array.isArray(parsed.menu_items_alergi) &&
                        parsed.menu_items_alergi.length > 0
                    ) {
                        menuItemsAlergi.value = parsed.menu_items_alergi;
                    }
                    if (parsed.jenis_alergi) {
                        selectedJenisAlergi.value = parsed.jenis_alergi;
                    }
                    if (parsed.tag_alergi) {
                        customTagAlergi.value = parsed.tag_alergi;
                    }
                    if (parsed.manual_porsi_normal !== undefined) {
                        manualPorsiNormal.value = Number(parsed.manual_porsi_normal) || 0;
                    }
                    if (parsed.manual_allergy_configs) {
                        manualAllergyConfigs.value = parsed.manual_allergy_configs;
                    }
                    if (Array.isArray(parsed.selected_manual_allergies) && parsed.selected_manual_allergies.length > 0) {
                        selectedManualAllergies.value = parsed.selected_manual_allergies;
                        activeEditingAllergy.value = parsed.selected_manual_allergies[0] || "";
                    }
                } catch {
                    // ignore
                }
            }

            if (Array.isArray(item.selected_kelompok_ids)) {
                selectedKelompokIds.value = [...item.selected_kelompok_ids];
            }
        }
    },
    { immediate: true },
);

onMounted(() => {
    if (!props.editingLabel) {
        initManualConfigsFromGroups();
    }
});

watch(
    () => props.kelompokList,
    (newList) => {
        if (newList && newList.length > 0 && !props.editingLabel && manualPorsiNormal.value === 0) {
            initManualConfigsFromGroups();
        }
    },
    { immediate: true },
);

// Menu item placeholder helpers (Sesuai kategori sub menu Rancang Menu)
function getMenuPlaceholder(idx) {
    const examples = [
        "Contoh: Nasi Putih (Makanan Pokok)",
        "Contoh: Ayam Crispy (Lauk Hewani)",
        "Contoh: Tempe Manis Dadu (Lauk Nabati)",
        "Contoh: Sayur Sop / Selada & Timun (Sayuran)",
        "Contoh: Buah Melon / Semangka (Buah)",
    ];
    return examples[idx] || `Contoh: Komponen Menu ${idx + 1}`;
}

function getMenuAlergiPlaceholder(idx) {
    const examples = [
        "Contoh: Nasi Putih (Makanan Pokok)",
        "Contoh: Ayam Crispy (Bebas Telur)",
        "Contoh: Tempe Manis Dadu (Lauk Nabati)",
        "Contoh: Sayur Sop / Selada & Timun (Sayuran)",
        "Contoh: Buah Melon / Semangka (Buah)",
    ];
    return examples[idx] || `Contoh: Menu Pengganti ${idx + 1}`;
}

// Menu item normal helpers
function addMenuItem() {
    menuItems.value.push("");
}

function removeMenuItem(idx) {
    if (menuItems.value.length <= 1) return;
    menuItems.value.splice(idx, 1);
}

function updateMenuItemName(idx, val) {
    menuItems.value[idx] = val;
}

// Menu item alergi helpers
function addMenuItemAlergi() {
    menuItemsAlergi.value.push("");
}

function removeMenuItemAlergi(idx) {
    if (menuItemsAlergi.value.length <= 1) return;
    menuItemsAlergi.value.splice(idx, 1);
}

function updateMenuItemNameAlergi(idx, val) {
    menuItemsAlergi.value[idx] = val;
}

// Generate list of items to print (Supporting Normal & Allergy)
const printableItems = computed(() => {
    const list = [];
    const kelompokList = printableKelompokList.value;

    if (labelMode.value === "manual") {
        const baseKelompok = kelompokList[0] || (props.kelompokList || [])[0] || {
            id: 1,
            nama_kelompok: namaSppg.value,
        };

        const normalList = [];
        const alergiList = [];

        // 1. Simpan Label Porsi Normal
        const count = Number(manualPorsiNormal.value) || 0;
        if (count > 0) {
            normalList.push({
                kelompok: baseKelompok,
                tipeLabel: "normal",
                jenisAlergi: "",
                tagAlergi: "",
                menuItems: [...menuItems.value],
                giziData: { ...giziData.value },
                count: count,
            });
        }

        // 2. Simpan Label Porsi Alergi per Alergi yang Dipilih
        selectedManualAllergies.value.forEach((jenis) => {
            const cfg = manualAllergyConfigs.value[jenis];
            const aCount = Number(cfg?.porsi) || 0;
            if (aCount > 0) {
                alergiList.push({
                    kelompok: baseKelompok,
                    tipeLabel: "alergi",
                    jenisAlergi: jenis,
                    tagAlergi:
                        customTagAlergi.value ||
                        `⚠️ KHUSUS ALERGI: ${jenis.toUpperCase()}`,
                    menuItems: cfg?.menuItems ? [...cfg.menuItems] : [...menuItemsAlergi.value],
                    giziData: cfg?.giziData ? { ...cfg.giziData } : { ...giziDataAlergi.value },
                    count: aCount,
                });
            }
        });

        if (filterCetakTipe.value === "normal") return normalList;
        if (filterCetakTipe.value === "alergi") return alergiList;
        return [...normalList, ...alergiList];
    }

    // Mode Otomatis (Work Order)
    const isAuto = true;
    const affectedAlergiList = detectedAlergiList.value;
    const normalList = [];
    const alergiList = [];

    for (const k of kelompokList) {
        const pk = Number(k.total_porsi_kecil) || 0;
        const pb = Number(k.total_porsi_besar) || 0;
        const totalPm = Number(k.total_penerima) || pk + pb;

        let alergiDetails = [];
        if (Array.isArray(k.detail_alergi) && k.detail_alergi.length > 0) {
            alergiDetails = k.detail_alergi;
        } else if (
            Array.isArray(k.keterangan_alergi) &&
            k.keterangan_alergi.length > 0
        ) {
            alergiDetails = k.keterangan_alergi;
        }

        let totalAlergiInGroup = 0;
        const alergiBreakdown = [];

        for (const item of alergiDetails) {
            if (typeof item === "object") {
                const j = (item.jenis_alergi || item.nama || "Alergi").trim();
                if (
                    isAuto &&
                    affectedAlergiList.length > 0 &&
                    !affectedAlergiList.includes(j)
                ) {
                    continue;
                }
                const count =
                    (Number(item.porsi_kecil) || 0) +
                    (Number(item.porsi_besar) || 0) +
                    (Number(item.jumlah) || 0);
                if (count > 0) {
                    totalAlergiInGroup += count;
                    alergiBreakdown.push({ jenis: j, count });
                }
            } else if (typeof item === "string" && item.trim()) {
                const j = item.trim();
                if (
                    isAuto &&
                    affectedAlergiList.length > 0 &&
                    !affectedAlergiList.includes(j)
                ) {
                    continue;
                }
                totalAlergiInGroup += 1;
                alergiBreakdown.push({ jenis: j, count: 1 });
            }
        }

        const normalCount = Math.max(0, totalPm - totalAlergiInGroup);

        // 1. Simpan Label Porsi Normal kelompok ini
        if (normalCount > 0) {
            normalList.push({
                kelompok: k,
                tipeLabel: "normal",
                jenisAlergi: "",
                tagAlergi: "",
                menuItems: [...menuItems.value],
                giziData: { ...giziData.value },
                count: normalCount,
            });
        }

        // 2. Simpan Label Porsi Alergi kelompok ini
        if (alergiBreakdown.length > 0) {
            for (const ab of alergiBreakdown) {
                if (
                    filterCetakTipe.value === "semua" ||
                    selectedJenisAlergi.value === "Semua Alergi" ||
                    selectedJenisAlergi.value === ab.jenis
                ) {
                    const cfg = autoAllergyConfigs.value[ab.jenis];
                    const menuList =
                        cfg && Array.isArray(cfg.menuItems) && cfg.menuItems.length > 0
                            ? [...cfg.menuItems]
                            : getAllergyMenuItemsForTarget(ab.jenis);
                    const giziItem =
                        cfg && cfg.giziData
                            ? { ...cfg.giziData }
                            : { ...giziDataAlergi.value };
                    const tagItem =
                        cfg?.tagAlergi ||
                        (customTagAlergi.value &&
                        selectedJenisAlergi.value === ab.jenis
                            ? customTagAlergi.value
                            : `⚠️ KHUSUS ALERGI: ${ab.jenis.toUpperCase()}`);

                    alergiList.push({
                        kelompok: k,
                        tipeLabel: "alergi",
                        jenisAlergi: ab.jenis,
                        tagAlergi: tagItem,
                        menuItems: menuList,
                        giziData: giziItem,
                        count: ab.count,
                    });
                }
            }
        }
    }

    if (filterCetakTipe.value === "normal") {
        return normalList;
    }
    if (filterCetakTipe.value === "alergi") {
        return alergiList;
    }
    // "semua": Gabungkan semua Normal terlebih dahulu, lalu semua Alergi di akhir
    return [...normalList, ...alergiList];
});

// Daftar expanded per individu porsi untuk dicetak atau diunduh
const expandedPrintableItems = computed(() => {
    const expanded = [];
    for (const item of printableItems.value) {
        const count =
            item.count && Number(item.count) > 0 ? Number(item.count) : 1;
        for (let i = 0; i < count; i++) {
            expanded.push(item);
        }
    }
    return expanded;
});

// Simpan Label ke Database
const isSaving = ref(false);

function saveLabelToDatabase() {
    if (menuItems.value.length === 0) {
        alert("Silakan isi minimal 1 komponen menu makanan.");
        return;
    }

    if (printableKelompokList.value.length === 0) {
        alert("Silakan pilih minimal 1 kelompok sasaran.");
        return;
    }

    isSaving.value = true;

    const namaMenuHeader = menuItems.value.join(", ");

    const payload = {
        work_order_id:
            labelMode.value === "auto"
                ? activeWorkOrder.value?.id || activeWorkOrder.value?.nomor_wo
                : null,
        nama_menu: (
            activeWorkOrder.value?.nama ||
            namaMenuHeader ||
            "Menu SPPG BGN"
        ).substring(0, 255),
        tanggal_produksi: tanggalProduksi.value,
        jam_produksi: jamProduksi.value,
        tampilkan_jam_produksi: gunakanJamProduksi.value,
        batas_konsumsi: jamExpired.value,
        tampilkan_jam_expired: gunakanJamExpired.value,
        petunjuk_menu: menuItems.value.join("\n"),
        template_id: "bgn_standard_fixed_white",
        template_name: "Standar Resmi BGN (Putih)",
        aspect_ratio: "4:3",
        gizi_data: {
            ...giziData.value,
            gizi_alergi: giziDataAlergi.value,
        },
        selected_kelompok_ids: selectedKelompokIds.value,
        kelompoks_snapshot: printableKelompokList.value.map((k) => ({
            id: k.id,
            nama_kelompok: k.nama_kelompok,
            kategori: k.kategori,
            total_penerima:
                k.total_penerima ||
                (k.total_porsi_kecil || 0) + (k.total_porsi_besar || 0),
            total_porsi_kecil: k.total_porsi_kecil || 0,
            total_porsi_besar: k.total_porsi_besar || 0,
            status_alergi:
                k.status_alergi ||
                (k.detail_alergi && k.detail_alergi.length > 0),
            detail_alergi: k.detail_alergi || [],
        })),
        total_sasaran: printableKelompokList.value.length,
        total_porsi:
            labelMode.value === "manual"
                ? totalPorsiNormal.value + totalPorsiAlergi.value
                : printableKelompokList.value.reduce(
                      (sum, k) =>
                          sum +
                          Number(
                              k.total_penerima ||
                                  (k.total_porsi_kecil || 0) +
                                      (k.total_porsi_besar || 0),
                          ),
                      0,
                  ),
        total_pk: printableKelompokList.value.reduce(
            (sum, k) => sum + Number(k.total_porsi_kecil || 0),
            0,
        ),
        total_pb: printableKelompokList.value.reduce(
            (sum, k) => sum + Number(k.total_porsi_besar || 0),
            0,
        ),
        keterangan: JSON.stringify({
            menu_items_alergi: menuItemsAlergi.value,
            jenis_alergi: selectedJenisAlergi.value,
            tag_alergi: customTagAlergi.value,
            manual_porsi_normal: manualPorsiNormal.value,
            manual_allergy_configs: manualAllergyConfigs.value,
            selected_manual_allergies: selectedManualAllergies.value,
        }),
    };

    if (props.editingLabel?.id) {
        router.put(route("label.update", props.editingLabel.id), payload, {
            preserveScroll: true,
            onFinish: () => {
                isSaving.value = false;
            },
        });
    } else {
        router.post(route("label.store"), payload, {
            preserveScroll: true,
            onFinish: () => {
                isSaving.value = false;
            },
        });
    }
}

// Total porsi Work Order di hari tersebut untuk auto-fill custom label
const totalWoPorsi = computed(() => {
    if (activeWorkOrder.value) {
        if (
            activeWorkOrder.value.total_porsi &&
            Number(activeWorkOrder.value.total_porsi) > 0
        ) {
            return Number(activeWorkOrder.value.total_porsi);
        }
        if (
            activeWorkOrder.value.total_penerima &&
            Number(activeWorkOrder.value.total_penerima) > 0
        ) {
            return Number(activeWorkOrder.value.total_penerima);
        }
        if (
            activeWorkOrder.value.total_sasaran &&
            Number(activeWorkOrder.value.total_sasaran) > 0
        ) {
            return Number(activeWorkOrder.value.total_sasaran);
        }
    }
    const list =
        printableKelompokList.value.length > 0
            ? printableKelompokList.value
            : activeKelompokList.value;
    const sum = list.reduce((acc, k) => {
        const porsiKecil = Number(k.total_porsi_kecil) || 0;
        const porsiBesar = Number(k.total_porsi_besar) || 0;
        const penerima = Number(k.total_penerima) || porsiKecil + porsiBesar;
        return acc + penerima;
    }, 0);
    return sum > 0 ? sum : 9;
});

// Target porsi aktif sesuai filter / tab cetak saat ini (Normal, Alergi, atau Semua)
const activeScopePorsiCount = computed(() => {
    if (filterCetakTipe.value === "normal") {
        return totalPorsiNormal.value;
    }
    if (filterCetakTipe.value === "alergi") {
        return totalPorsiAlergi.value;
    }
    return totalPorsiNormal.value + totalPorsiAlergi.value;
});

// PDF Download State & Handlers
const isDownloading = ref(false);
const downloadType = ref("");
const downloadFormatOption = ref("single");
const customLabelCount = ref(activeScopePorsiCount.value || 9);

// Auto-populate custom label count when active scope changes, but keep freely editable
watch(
    activeScopePorsiCount,
    (val) => {
        if (val !== undefined && val !== null) {
            customLabelCount.value = val;
        }
    },
    { immediate: true },
);

watch(downloadFormatOption, (newFormat) => {
    if (newFormat === "custom") {
        if (!customLabelCount.value || customLabelCount.value <= 0) {
            customLabelCount.value = activeScopePorsiCount.value || 9;
        }
    }
});

const downloadProgress = ref({
    phase: "template",
    current: 0,
    total: 0,
    totalPages: 0,
    currentPage: 0,
    percentage: 0,
    etaText: "",
    speedText: "",
    message: "",
});
const downloadError = ref("");
const downloadSuccess = ref(false);
const isDownloadCancelled = ref(false);

function cancelDownloadProcess() {
    isDownloadCancelled.value = true;
    downloadProgress.value = {
        ...downloadProgress.value,
        message: "Membatalkan proses...",
    };
    setTimeout(() => {
        isDownloading.value = false;
        isDownloadCancelled.value = false;
    }, 250);
}

function resetLabelToDefault() {
    if (
        !confirm(
            "Apakah Anda yakin ingin mengembalikan semua konfigurasi dan isian label ke kondisi awal / default?",
        )
    ) {
        return;
    }

    // Reset Mode & Selected WO
    labelMode.value = "auto";
    selectedWoId.value =
        props.initialActiveWo?.id ||
        props.workOrders.find(
            (w) => (w.tanggal || "").substring(0, 10) === todayStr,
        )?.id ||
        props.workOrders[0]?.id ||
        null;

    // Reset Dimensi & Header
    tinggiIsolasiCm.value = 1.5;
    namaSppg.value = props.unitSppg?.nama || "SPPG BULELENG BANJAR DENCARIK";
    zonaWaktu.value = "WITA";

    // Reset Waktu
    tanggalProduksi.value = todayStr;
    jamProduksi.value = "12:14";
    gunakanJamProduksi.value = false;
    tanggalExpired.value = todayStr;
    jamExpired.value = "12:14";
    gunakanJamExpired.value = false;

    // Reset Waktu Maksimal & Larangan
    waktuMaksimal.value = "2 JAM SETELAH DITERIMA!";
    teksLaranganHeader.value = "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.";
    teksLaranganSub.value = "DILARANG MEMBAWA PULANG!";

    // Reset Menu Makanan Normal
    menuItems.value = [
        "NASI PUTIH",
        "AYAM CRISPY",
        "TEMPE MANIS DADU",
        "SELADA, TIMUN",
        "MELON",
    ];

    // Reset Menu Makanan Alergi
    menuItemsAlergi.value = [
        "NASI PUTIH",
        "AYAM CRISPY (BEBAS TELUR)",
        "TEMPE MANIS DADU",
        "SELADA, TIMUN",
        "MELON",
    ];
    selectedJenisAlergi.value = "Semua Alergi";
    customTagAlergi.value = "";
    filterCetakTipe.value = "semua";

    // Reset Kandungan Gizi
    giziData.value = {
        energi_pb: "624",
        prot_pb: "29.8",
        lmk_pb: "18.8",
        karbo_pb: "82.4",
        serat_pb: "2.0",
        energi_pk: "469",
        prot_pk: "23.4",
        lmk_pk: "15.0",
        karbo_pk: "58.9",
        serat_pk: "1.5",
    };

    giziDataAlergi.value = {
        energi_pb: "624",
        prot_pb: "29.8",
        lmk_pb: "18.8",
        karbo_pb: "82.4",
        serat_pb: "2.0",
        energi_pk: "469",
        prot_pk: "23.4",
        lmk_pk: "15.0",
        karbo_pk: "58.9",
        serat_pk: "1.5",
    };

    // Reset Rincian Harga
    hargaItems.value = [
        { nama: "Nasi Putih", harga_pb: 1155, harga_pk: 770 },
        { nama: "Ayam Crispy", harga_pb: 5812, harga_pk: 4838 },
        { nama: "Tempe Manis Dadu", harga_pb: 1085, harga_pk: 844 },
        { nama: "Selada, Timun", harga_pb: 1046, harga_pk: 792 },
        { nama: "Melon", harga_pb: 1931, harga_pk: 1931 },
    ];

    // Reset Sasaran Kelompok
    selectedKelompokIds.value = receivingKelompokList.value.map((k) => k.id);

    // Reset Download Option
    downloadFormatOption.value = "single";
    customLabelCount.value = totalWoPorsi.value || 9;

    // Jika sedang dalam mode edit label dari daftar, batalkan edit mode
    if (props.editingLabel) {
        emit("cancel-edit");
    }
}

const sandboxParams = ref({
    kelompok: null,
    tipeLabel: "normal",
    jenisAlergi: "",
    tagAlergi: "",
    menuItems: [],
    giziData: {},
});

const sandboxCardRef = ref(null);

async function getSandboxCardElement(itemOrKelompok) {
    const isItem =
        itemOrKelompok &&
        typeof itemOrKelompok === "object" &&
        ("tipeLabel" in itemOrKelompok || "kelompok" in itemOrKelompok);

    const k = isItem ? itemOrKelompok.kelompok : itemOrKelompok;
    const tipe = isItem ? itemOrKelompok.tipeLabel || "normal" : "normal";
    const alergi = isItem ? itemOrKelompok.jenisAlergi || "" : "";
    const tag = isItem ? itemOrKelompok.tagAlergi || "" : "";

    const mItems =
        isItem && itemOrKelompok.menuItems && itemOrKelompok.menuItems.length > 0
            ? itemOrKelompok.menuItems
            : tipe === "alergi"
              ? labelMode.value === "auto"
                  ? autoAllergyConfigs.value[alergi]?.menuItems || getAllergyMenuItemsForTarget(alergi)
                  : menuItemsAlergi.value
              : menuItems.value;

    const gData =
        isItem &&
        itemOrKelompok.giziData &&
        Object.keys(itemOrKelompok.giziData).length > 0
            ? itemOrKelompok.giziData
            : tipe === "alergi"
              ? labelMode.value === "auto"
                  ? autoAllergyConfigs.value[alergi]?.giziData || giziDataAlergi.value
                  : giziDataAlergi.value
              : giziData.value;

    sandboxParams.value = {
        kelompok: k || null,
        tipeLabel: tipe,
        jenisAlergi: alergi,
        tagAlergi: tag,
        menuItems: mItems,
        giziData: gData,
    };

    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 50));
    return (
        sandboxCardRef.value?.querySelector(".bgn-label-card") ||
        sandboxCardRef.value
    );
}

// ─── Computed Preview Properties ──────────────────────────────────────────────
const currentPreviewJenisAlergi = computed(() => {
    if (labelMode.value === "manual") {
        if (activeLabelTab.value === "alergi") {
            if (
                selectedJenisAlergi.value &&
                selectedJenisAlergi.value !== "Semua Alergi"
            ) {
                return selectedJenisAlergi.value;
            }
            return (
                activeEditingAllergy.value ||
                selectedManualAllergies.value[0] ||
                "Alergi"
            );
        }
        return "";
    }
    return selectedJenisAlergi.value === "Semua Alergi"
        ? detectedAlergiList.value[0] || "Khusus"
        : selectedJenisAlergi.value;
});

const currentPreviewMenuItems = computed(() => {
    if (activeLabelTab.value === "normal") {
        return menuItems.value;
    }
    if (labelMode.value === "manual") {
        const jenis = currentPreviewJenisAlergi.value;
        const cfg = manualAllergyConfigs.value[jenis];
        if (cfg && Array.isArray(cfg.menuItems) && cfg.menuItems.length > 0) {
            return cfg.menuItems;
        }
        return menuItemsAlergi.value;
    }
    // Mode Otomatis
    const jenis = currentPreviewJenisAlergi.value;
    if (jenis && autoAllergyConfigs.value[jenis]?.menuItems) {
        return autoAllergyConfigs.value[jenis].menuItems;
    }
    return getAllergyMenuItemsForTarget(jenis || "Semua Alergi");
});

const currentPreviewGiziData = computed(() => {
    if (activeLabelTab.value === "normal") {
        return giziData.value;
    }
    if (labelMode.value === "manual") {
        const jenis = currentPreviewJenisAlergi.value;
        const cfg = manualAllergyConfigs.value[jenis];
        if (cfg && cfg.giziData) {
            return cfg.giziData;
        }
        return giziDataAlergi.value;
    }
    // Mode Otomatis
    const jenis = currentPreviewJenisAlergi.value;
    if (jenis && autoAllergyConfigs.value[jenis]?.giziData) {
        return autoAllergyConfigs.value[jenis].giziData;
    }
    return giziDataAlergi.value;
});

async function startDownload(type = null) {
    const selectedType = type || downloadFormatOption.value || "single";
    const itemsToPrint =
        selectedType === "custom"
            ? (expandedPrintableItems.value.length > 0
                  ? expandedPrintableItems.value
                  : printableItems.value)
            : expandedPrintableItems.value;

    if (itemsToPrint.length === 0) {
        if (
            filterCetakTipe.value === "alergi" ||
            activeLabelTab.value === "alergi"
        ) {
            alert(
                "Tidak ada porsi alergi yang terdaftar pada kelompok sasaran terpilih untuk Work Order ini.",
            );
        } else {
            alert("Tidak ada label yang dapat diunduh untuk filter terpilih.");
        }
        return;
    }

    isDownloading.value = true;
    isDownloadCancelled.value = false;
    downloadType.value = selectedType;
    downloadError.value = "";
    downloadSuccess.value = false;

    const totalCount =
        selectedType === "custom"
            ? parseInt(customLabelCount.value, 10) || itemsToPrint.length
            : itemsToPrint.length;

    const tipeDesc =
        filterCetakTipe.value === "alergi"
            ? "Porsi Alergi"
            : filterCetakTipe.value === "normal"
              ? "Porsi Normal"
              : "Normal & Alergi";

    downloadProgress.value = {
        current: 0,
        total: totalCount,
        totalPages:
            selectedType === "a4" || selectedType === "custom"
                ? Math.ceil(totalCount / 9)
                : totalCount,
        currentPage: 1,
        percentage: 0,
        etaText: "Menyiapkan render...",
        speedText: "",
        message: `Menyiapkan ${totalCount} kartu label resmi BGN (${tipeDesc})...`,
    };

    try {
        const dateSuffix = (tanggalProduksi.value || todayStr).replace(
            /-/g,
            "",
        );
        const tipeSlug =
            filterCetakTipe.value === "alergi"
                ? "Alergi"
                : filterCetakTipe.value === "normal"
                  ? "Normal"
                  : "Semua";

        if (selectedType === "single") {
            const filename = `Label_BGN_${tipeSlug}_9x6cm_${totalCount}Pcs_${dateSuffix}.pdf`;
            await downloadPdfSingleMode({
                printableItems: itemsToPrint,
                printableKelompokList: printableKelompokList.value,
                customCount: totalCount,
                getRenderElement: getSandboxCardElement,
                filename,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = {
                        ...downloadProgress.value,
                        ...p,
                    };
                },
            });
        } else if (selectedType === "a4" || selectedType === "custom") {
            const filename = `Label_BGN_${tipeSlug}_Lembar_A4_${totalCount}Label_${dateSuffix}.pdf`;
            await downloadPdfA4GridMode({
                printableItems: itemsToPrint,
                printableKelompokList: printableKelompokList.value,
                customCount: totalCount,
                getRenderElement: getSandboxCardElement,
                filename,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = {
                        ...downloadProgress.value,
                        ...p,
                    };
                },
            });
        }

        if (!isDownloadCancelled.value) {
            downloadSuccess.value = true;
            setTimeout(() => {
                if (downloadSuccess.value) {
                    isDownloading.value = false;
                }
            }, 1200);
        }
    } catch (err) {
        if (err.name === "AbortError" || isDownloadCancelled.value) {
            isDownloading.value = false;
            isDownloadCancelled.value = false;
            return;
        }
        console.error("Gagal download PDF label:", err);
        downloadError.value =
            err.message || "Terjadi kesalahan saat memproses file PDF.";
    }
}

async function startPrintDirect() {
    const itemsToPrint = expandedPrintableItems.value;
    if (itemsToPrint.length === 0) {
        if (
            filterCetakTipe.value === "alergi" ||
            activeLabelTab.value === "alergi"
        ) {
            alert(
                "Tidak ada porsi alergi yang terdaftar pada kelompok sasaran terpilih.",
            );
        } else {
            alert("Silakan pilih minimal 1 kelompok sasaran.");
        }
        return;
    }
    isDownloading.value = true;
    isDownloadCancelled.value = false;
    downloadType.value = "print";
    downloadError.value = "";
    downloadSuccess.value = false;

    const tipeDesc =
        filterCetakTipe.value === "alergi"
            ? "Porsi Alergi"
            : filterCetakTipe.value === "normal"
              ? "Porsi Normal"
              : "Semua Porsi";

    downloadProgress.value = {
        current: 0,
        total: itemsToPrint.length,
        totalPages: itemsToPrint.length,
        currentPage: 1,
        percentage: 0,
        etaText: "Menyiapkan cetakan...",
        speedText: "",
        message: `Menyiapkan ${itemsToPrint.length} label (${tipeDesc}) ukuran 9x6cm untuk dicetak...`,
    };

    try {
        await printPdfSingleMode({
            printableItems: itemsToPrint,
            printableKelompokList: printableKelompokList.value,
            getRenderElement: getSandboxCardElement,
            isCancelled: () => isDownloadCancelled.value,
            onProgress: (p) => {
                downloadProgress.value = { ...downloadProgress.value, ...p };
            },
        });

        if (!isDownloadCancelled.value) {
            downloadSuccess.value = true;
            setTimeout(() => {
                if (downloadSuccess.value) {
                    isDownloading.value = false;
                }
            }, 1200);
        }
    } catch (err) {
        if (err.name === "AbortError" || isDownloadCancelled.value) {
            isDownloading.value = false;
            isDownloadCancelled.value = false;
            return;
        }
        console.error("Gagal print dialog label:", err);
        downloadError.value =
            err.message || "Terjadi kesalahan saat menyiapkan cetakan.";
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- 1. Header Control Panel -->
        <div class="print:hidden space-y-6">
            <!-- Edit Mode Banner -->
            <div
                v-if="editingLabel"
                class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-in fade-in"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-2xs"
                    >
                        <Edit3 class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-black uppercase tracking-wider text-amber-800"
                            >
                                Mode Edit Label
                            </span>
                            <span
                                class="font-mono font-bold text-amber-900 bg-amber-200/60 px-2 py-0.5 rounded text-[11px]"
                            >
                                {{ editingLabel.nomor_label }}
                            </span>
                        </div>
                        <p class="text-xs text-amber-900 mt-0.5 font-medium">
                            Anda sedang mengedit data label:
                            <strong>{{ editingLabel.nama_menu }}</strong
                            >. Klik "Perbarui Simpanan Label" untuk menyimpan
                            perubahan.
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    @click="emit('cancel-edit')"
                    className="h-9 px-3.5 bg-white hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer shrink-0"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Batal Edit / Buat Baru</span>
                </Button>
            </div>

            <!-- Top Action Header -->
            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardHeader
                    className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Tag class="h-5 w-5 text-primary" />
                                    <span>Konfigurasi Label Resmi SPPG</span>
                                </CardTitle>
                                <span
                                    class="bg-blue-100 text-blue-900 border border-blue-200 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <span>Standar Resmi BGN</span>
                                </span>
                            </div>
                            <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Mendukung label sesuai kebutuhan SPPG.
                            </CardDescription>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                            <!-- Filter Cetak: Semua / Normal / Alergi -->
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                                <select
                                    v-model="filterCetakTipe"
                                    class="h-8 sm:h-9 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg px-2 sm:px-2.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                    title="Pilih porsi yang akan dicetak atau diunduh"
                                >
                                    <option value="semua">✨ Semua Porsi (Normal & Alergi) [{{ totalPorsiNormal + totalPorsiAlergi }}]</option>
                                    <option value="normal">🏷️ Hanya Porsi Normal ({{ totalPorsiNormal }})</option>
                                    <option value="alergi">⚠️ Hanya Porsi Alergi ({{ totalPorsiAlergi }})</option>
                                </select>
                            </div>

                            <!-- Tombol RESET KE AWAL -->
                            <Button
                                type="button"
                                @click="resetLabelToDefault"
                                :disabled="isSaving || isDownloading"
                                className="h-8 sm:h-9 px-2.5 sm:px-3.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 font-bold text-xs flex items-center gap-1.5 rounded-xl shadow-xs cursor-pointer"
                                title="Reset Konfigurasi Awal"
                            >
                                <RotateCcw class="h-4 w-4 text-slate-500 shrink-0" />
                                <span class="hidden md:inline">Reset Awal</span>
                            </Button>

                            <!-- Tombol SIMPAN LABEL -->
                            <Button
                                type="button"
                                @click="saveLabelToDatabase"
                                :disabled="
                                    printableKelompokList.length === 0 ||
                                    isSaving
                                "
                                className="h-8 sm:h-9 px-3 sm:px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 rounded-xl shadow-xs cursor-pointer"
                                :title="
                                    editingLabel
                                        ? 'Perbarui data label ini di Daftar Label'
                                        : 'Simpan konfigurasi label ini ke Daftar Label'
                                "
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-4 w-4 animate-spin shrink-0"
                                />
                                <BookmarkCheck v-else class="h-4 w-4 shrink-0" />
                                <span class="hidden sm:inline">{{
                                    isSaving
                                        ? "Menyimpan..."
                                        : editingLabel
                                          ? "Simpan Perubahan"
                                          : "Simpan Label"
                                }}</span>
                            </Button>

                            <!-- Select Format & Download PDF Button -->
                            <div
                                class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200 flex-wrap"
                            >
                                <select
                                    v-model="downloadFormatOption"
                                    class="h-8 sm:h-9 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg px-2 sm:px-2.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                >
                                    <option value="single">Label Tunggal</option>
                                    <option value="a4">Lembar A4 (9 Label)</option>
                                    <option value="custom">Kustom Jumlah (A4)</option>
                                </select>

                                <!-- Custom Count Input (Appears when Custom is selected) -->
                                <div
                                    v-if="downloadFormatOption === 'custom'"
                                    class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg px-2 h-8 sm:h-9 shadow-2xs"
                                >
                                    <span class="text-[11px] font-bold text-slate-500">Jml:</span>
                                    <input
                                        type="number"
                                        v-model.number="customLabelCount"
                                        min="1"
                                        max="50000"
                                        class="w-14 sm:w-16 h-6 text-center text-xs font-black text-slate-800 border-none p-0 focus:outline-none focus:ring-0"
                                        title="Jumlah label untuk dicetak"
                                    />
                                    <span class="text-[10px] font-semibold text-slate-400">pcs</span>
                                </div>
                                <Button
                                    type="button"
                                    @click="startDownload(downloadFormatOption)"
                                    :disabled="
                                        printableItems.length === 0 ||
                                        isDownloading
                                    "
                                    className="h-8 sm:h-9 px-3 sm:px-3.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs flex items-center gap-1.5 rounded-lg shadow-xs cursor-pointer"
                                    title="Download file PDF sesuai format yang dipilih"
                                >
                                    <Loader2
                                        v-if="
                                            isDownloading &&
                                            (downloadType === 'single' ||
                                                downloadType === 'a4' ||
                                                downloadType === 'custom')
                                        "
                                        class="h-3.5 w-3.5 animate-spin shrink-0"
                                    />
                                    <Download v-else class="h-3.5 w-3.5 shrink-0" />
                                    <span class="hidden sm:inline">Download PDF</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Main Layout: Left Form Inputs + Right Live Preview -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- ================= LEFT COLUMN: FORM INPUTS ================= -->
                <div class="lg:col-span-6 space-y-5">
                    <!-- 1. Mode Switcher & WO Selector -->
                    <Card className="bg-white border-slate-200/80 shadow-2xs">
                        <CardContent className="p-4 sm:p-5 space-y-4">
                            <div
                                class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100 flex-wrap"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                        >Sumber Data:</span
                                    >
                                    <div
                                        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200"
                                    >
                                        <button
                                            type="button"
                                            @click="setLabelMode('auto')"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                                labelMode === 'auto'
                                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                                    : 'text-slate-600 hover:text-slate-900',
                                            ]"
                                        >
                                            <Utensils class="h-3.5 w-3.5" />
                                            <span
                                                >⚡ Otomatis (Work Order)</span
                                            >
                                        </button>
                                        <button
                                            type="button"
                                            @click="setLabelMode('manual')"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                                labelMode === 'manual'
                                                    ? 'bg-white text-amber-700 shadow-2xs font-extrabold'
                                                    : 'text-slate-600 hover:text-slate-900',
                                            ]"
                                        >
                                            <Edit3 class="h-3.5 w-3.5" />
                                            <span>✍️ Input Manual</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Selector WO jika Mode Otomatis -->
                            <div v-if="labelMode === 'auto'" class="space-y-3">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >Pilih Work Order Menu:</label
                                    >
                                    <select
                                        v-model="selectedWoId"
                                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                    >
                                        <option
                                            v-for="wo in workOrders"
                                            :key="wo.id"
                                            :value="wo.id"
                                        >
                                            {{ wo.tanggal }} - {{ wo.nama }} ({{
                                                wo.id
                                            }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Ringkasan Mode Otomatis -->
                                <div
                                    class="p-3 bg-blue-50/70 border border-blue-100 rounded-xl flex items-start gap-2.5 text-xs text-blue-900"
                                >
                                    <Sparkles
                                        class="h-4 w-4 text-blue-600 shrink-0 mt-0.5"
                                    />
                                    <div class="space-y-0.5">
                                        <p class="font-bold">
                                            Mode Otomatis Work Order Aktif
                                        </p>
                                        <p
                                            class="text-[11px] text-blue-700 leading-relaxed"
                                        >
                                            Data menu normal, menu porsi alergi, kandungan gizi, dan sasaran kelompok terhubung langsung dari Work Order.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pengaturan Dimensi & Area Tempel Isolasi (Berlaku untuk Mode Otomatis & Manual) -->
                    <Card className="bg-white border-slate-200/80 shadow-2xs">
                        <CardHeader className="p-4 pb-2 border-b border-slate-100">
                            <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                <Layers class="h-4 w-4 text-primary" />
                                <span>Area Tempel / Perekat Kemasan</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-2.5 text-xs">
                            <div>
                                <label class="font-bold text-slate-700 block">
                                    Area Perekat Stiker Kemasan:
                                </label>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Pilih ukuran area kosong perekat pada bagian atas label.
                                </p>
                            </div>

                            <!-- Preset Cepat -->
                            <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                <span class="text-[11px] font-bold text-slate-400 mr-1">Preset:</span>
                                <button
                                    type="button"
                                    v-for="preset in [1.0, 1.5, 2.0, 2.5]"
                                    :key="preset"
                                    @click="tinggiIsolasiCm = preset"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-bold transition-colors cursor-pointer border',
                                        Number(tinggiIsolasiCm) === preset
                                            ? 'bg-primary text-white border-primary shadow-2xs font-extrabold'
                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                                    ]"
                                >
                                    {{ preset === 1.5 ? '1.5 cm (Default)' : preset + ' cm' }}
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- SASARAN JENIS ALERGEN (MODE OTOMATIS: HANYA YANG TERDAMPAK PADA WORK ORDER) -->
                    <Card v-if="labelMode === 'auto' && detectedAlergiList.length > 0" className="bg-amber-50/40 border-amber-200/80 shadow-2xs">
                        <CardHeader className="p-4 pb-2 border-b border-amber-200/60">
                            <CardTitle class="text-xs sm:text-sm font-bold text-amber-950 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ShieldAlert class="h-4 w-4 text-amber-600" />
                                    <span>Identitas & Sasaran Jenis Alergen (Terdampak WO)</span>
                                </div>
                                <span class="text-[11px] font-bold bg-amber-200 text-amber-900 px-2.5 py-0.5 rounded-full border border-amber-300">
                                    {{ formatPorsiBreakdownText(selectedJenisAlergi) }}
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3.5 text-xs">
                            <div>
                                <label class="font-bold text-amber-900 block mb-1.5">
                                    Target Jenis Alergi Terdampak Work Order:
                                </label>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <button
                                        type="button"
                                        @click="selectedJenisAlergi = 'Semua Alergi'"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5',
                                            selectedJenisAlergi === 'Semua Alergi'
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                        ]"
                                    >
                                        <span>✨ Semua Jenis Alergi (Dinamis)</span>
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                selectedJenisAlergi === 'Semua Alergi'
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900'
                                            ]"
                                        >
                                            {{ formatPorsiBreakdownText('Semua Alergi') }}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        v-for="alergi in detectedAlergiList"
                                        :key="alergi"
                                        @click="selectedJenisAlergi = alergi"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5',
                                            selectedJenisAlergi === alergi
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                        ]"
                                    >
                                        <span>⚠️ {{ alergi }}</span>
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                selectedJenisAlergi === alergi
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900'
                                            ]"
                                        >
                                            {{ formatPorsiBreakdownText(alergi) }}
                                        </span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-amber-800/80 mt-1.5">
                                    * Menampilkan jenis alergi yang memiliki menu pengganti/terdampak pada Work Order ini.
                                </p>
                            </div>

                            <div class="pt-2 border-t border-amber-200/50">
                                <label class="font-bold text-amber-900 block">
                                    Kustom Teks Banner Badge Alergi (Opsional):
                                </label>
                                <input
                                    v-model="customTagAlergi"
                                    type="text"
                                    placeholder="Otomatis (contoh: ⚠️ KHUSUS ALERGI: TELUR)"
                                    class="w-full mt-1 px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-bold text-amber-950 focus:ring-2 focus:ring-amber-400 outline-none"
                                />
                            </div>

                            <!-- Panel Rincian & Penyesuaian Sub Menu & Gizi Khusus Alergi Terpilih (Mode Otomatis) -->
                            <div
                                v-if="currentAutoEditingAllergy && autoAllergyConfigs[currentAutoEditingAllergy]"
                                class="pt-3 border-t border-amber-200/80 space-y-3 bg-amber-50/50 p-3 rounded-xl border border-amber-200"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <Sparkles class="h-4 w-4 text-amber-600" />
                                        <span class="text-xs font-black text-amber-950">
                                            Rincian Sub Menu & Nilai Gizi: {{ currentAutoEditingAllergy }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] bg-amber-200 text-amber-900 font-extrabold px-2 py-0.5 rounded-full border border-amber-300">
                                        {{ formatPorsiBreakdownText(currentAutoEditingAllergy) }}
                                    </span>
                                </div>

                                <!-- Switcher Tab saat memilih Semua Alergi -->
                                <div
                                    v-if="selectedJenisAlergi === 'Semua Alergi' && detectedAlergiList.length > 1"
                                    class="flex items-center gap-1.5 pb-2 border-b border-amber-200/70 flex-wrap"
                                >
                                    <span class="text-[10px] font-bold text-amber-900">Edit Alergi:</span>
                                    <button
                                        type="button"
                                        v-for="a in detectedAlergiList"
                                        :key="a"
                                        @click="activeEditingAllergyAuto = a"
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all cursor-pointer border',
                                            currentAutoEditingAllergy === a
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-amber-200 hover:bg-amber-100/60'
                                        ]"
                                    >
                                        ⚠️ {{ a }} ({{ formatPorsiBreakdownText(a) }})
                                    </button>
                                </div>

                                <!-- 1. Sub Menu Pengganti -->
                                <div class="space-y-1.5">
                                    <label class="font-bold text-slate-700 block text-[11px]">
                                        Komponen Sub Menu (Pengganti Alergi {{ currentAutoEditingAllergy }}):
                                    </label>
                                    <div
                                        v-for="(sub, sIdx) in autoAllergyConfigs[currentAutoEditingAllergy].menuItems"
                                        :key="sIdx"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span class="font-mono font-bold text-amber-700 text-[11px] w-4">{{ sIdx + 1 }}.</span>
                                        <input
                                            v-model="autoAllergyConfigs[currentAutoEditingAllergy].menuItems[sIdx]"
                                            type="text"
                                            class="flex-1 px-2.5 py-1 text-xs border border-amber-200 rounded-lg font-bold text-slate-800 focus:ring-1 focus:ring-amber-500 outline-none bg-white"
                                        />
                                    </div>
                                </div>

                                <!-- 2. Nilai Kandungan Gizi Khusus Alergi Ini -->
                                <div class="space-y-2 pt-2 border-t border-amber-200/60">
                                    <label class="font-bold text-slate-700 block text-[11px]">
                                        Nilai Kandungan Gizi (AKG) Khusus Alergi {{ currentAutoEditingAllergy }}:
                                    </label>
                                    <!-- Porsi Besar -->
                                    <div class="bg-white p-2 rounded-lg border border-amber-200/80">
                                        <p class="font-bold text-amber-900 text-[10px] mb-1">Porsi Besar (PB):</p>
                                        <div class="grid grid-cols-5 gap-1">
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Energi</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.energi_pb" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Protein</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.prot_pb" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Lemak</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.lmk_pb" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Karbo</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.karbo_pb" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Serat</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.serat_pb" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Porsi Kecil -->
                                    <div class="bg-white p-2 rounded-lg border border-amber-200/80">
                                        <p class="font-bold text-amber-900 text-[10px] mb-1">Porsi Kecil (PK):</p>
                                        <div class="grid grid-cols-5 gap-1">
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Energi</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.energi_pk" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Protein</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.prot_pk" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Lemak</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.lmk_pk" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Karbo</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.karbo_pk" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[9px] text-slate-500 block text-center">Serat</label>
                                                <input v-model="autoAllergyConfigs[currentAutoEditingAllergy].giziData.serat_pk" type="text" class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- FORM & KONFIGURASI KHUSUS MODE MANUAL: TAB SELECTOR, IDENTITAS, MENU, GIZI & ALERGI -->
                    <template v-if="labelMode === 'manual'">
                        <!-- TAB SELECTOR UNTUK KONFIGURASI MENU: NORMAL vs ALERGI -->
                        <div class="p-1.5 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1">
                            <button
                                type="button"
                                @click="activeLabelTab = 'normal'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
                                    activeLabelTab === 'normal'
                                        ? 'bg-white text-blue-900 shadow-xs ring-1 ring-blue-300/60'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'
                                ]"
                            >
                                <Tag class="h-3.5 w-3.5 text-blue-600" />
                                <span>🏷️ Konfigurasi Porsi Normal</span>
                                <span class="bg-blue-100 text-blue-800 text-[10.5px] px-1.5 py-0.2 rounded-full font-bold">
                                    {{ totalPorsiNormal }} Porsi
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="activeLabelTab = 'alergi'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
                                    activeLabelTab === 'alergi'
                                        ? 'bg-amber-600 text-white shadow-xs'
                                        : 'text-amber-800 hover:text-amber-950 hover:bg-amber-100/50'
                                ]"
                            >
                                <AlertTriangle class="h-3.5 w-3.5" />
                                <span>⚠️ Konfigurasi Porsi Alergi</span>
                                <span class="bg-amber-100 text-amber-900 text-[10.5px] px-1.5 py-0.2 rounded-full font-bold">
                                    {{ totalPorsiAlergi }} Porsi
                                </span>
                            </button>
                        </div>

                        <!-- IDENTITAS & WAKTU -->
                        <Card className="bg-white border-slate-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-slate-100">
                                <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-primary" />
                                    <span>Identitas Header & Waktu</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="font-bold text-slate-600">Nama SPPG:</label>
                                        <input
                                            v-model="namaSppg"
                                            type="text"
                                            class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-600">Zona Waktu:</label>
                                        <select
                                            v-model="zonaWaktu"
                                            class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        >
                                            <option value="WIB">WIB</option>
                                            <option value="WITA">WITA</option>
                                            <option value="WIT">WIT</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label class="font-bold text-slate-600">Tanggal Produksi:</label>
                                        <input
                                            v-model="tanggalProduksi"
                                            type="date"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="font-bold text-slate-600">Jam Produksi:</label>
                                            <label class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] font-bold text-slate-600">
                                                <input
                                                    type="checkbox"
                                                    v-model="gunakanJamProduksi"
                                                    class="rounded text-primary focus:ring-primary h-3.5 w-3.5"
                                                />
                                                <span>Tampilkan Jam</span>
                                            </label>
                                        </div>
                                        <input
                                            v-model="jamProduksi"
                                            type="text"
                                            placeholder="12:14"
                                            :disabled="!gunakanJamProduksi"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <div class="flex items-center justify-between">
                                            <label class="font-bold text-slate-600">Jam Expired (Batas Konsumsi):</label>
                                            <label class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] font-bold text-slate-600">
                                                <input
                                                    type="checkbox"
                                                    v-model="gunakanJamExpired"
                                                    class="rounded text-primary focus:ring-primary h-3.5 w-3.5"
                                                />
                                                <span>Gunakan Jam pada Batas Konsumsi</span>
                                            </label>
                                        </div>
                                        <input
                                            v-model="jamExpired"
                                            type="text"
                                            placeholder="Contoh: 14:14"
                                            :disabled="!gunakanJamExpired"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-rose-600 focus:ring-2 focus:ring-rose-200 outline-none disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        />
                                        <p class="text-[10.5px] text-slate-400 mt-1">
                                            * Jika dicentang, kotak oranye 'WAKTU MAKSIMAL KONSUMSI' akan otomatis menampilkan jam batas konsumsi (contoh: SEBELUM JAM 14:14 WITA).
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Pengaturan Waktu Konsumsi & Teks Larangan -->
                        <Card className="bg-white border-slate-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-slate-100">
                                <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-amber-600" />
                                    <span>Waktu Maksimal & Peringatan Larangan</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <div>
                                    <label class="font-bold text-slate-600">Waktu Maksimal Konsumsi:</label>
                                    <input
                                        v-model="waktuMaksimal"
                                        type="text"
                                        placeholder="2 JAM SETELAH DITERIMA!"
                                        class="w-full mt-1 px-3 py-1.5 border border-amber-200 bg-amber-50/50 rounded-lg font-black text-amber-900 focus:ring-2 focus:ring-amber-200 outline-none"
                                    />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="font-bold text-slate-600">Teks Larangan (Baris 1):</label>
                                        <input
                                            v-model="teksLaranganHeader"
                                            type="text"
                                            placeholder="MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT."
                                            class="w-full mt-1 px-3 py-1.5 border border-rose-200 bg-rose-50/50 rounded-lg font-bold text-rose-900 focus:ring-2 focus:ring-rose-200 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-600">Teks Larangan (Baris 2):</label>
                                        <input
                                            v-model="teksLaranganSub"
                                            type="text"
                                            placeholder="DILARANG MEMBAWA PULANG!"
                                            class="w-full mt-1 px-3 py-1.5 border border-rose-200 bg-rose-50/50 rounded-lg font-black text-rose-900 focus:ring-2 focus:ring-rose-200 outline-none"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- SECTION 1: KONTEN PORSI NORMAL -->
                        <template v-if="activeLabelTab === 'normal'">
                            <!-- Input Jumlah Porsi Normal (Mode Manual) -->
                            <Card className="bg-white border-slate-200/80 shadow-2xs">
                                <CardHeader className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between">
                                    <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <Tag class="h-4 w-4 text-blue-600" />
                                        <span>Jumlah Porsi Label Normal</span>
                                    </CardTitle>
                                    <button
                                        type="button"
                                        @click="syncNormalPorsiFromKelompok"
                                        class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-bold text-[11px] transition-colors flex items-center gap-1 cursor-pointer border border-blue-200"
                                        title="Hitung otomatis dari total sasaran dikurangi porsi alergi"
                                    >
                                        <RotateCcw class="h-3 w-3" />
                                        <span>Hitung dari Kelompok PM</span>
                                    </button>
                                </CardHeader>
                                <CardContent className="p-4 space-y-2 text-xs">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <label class="font-bold text-slate-700 block mb-1">
                                                Total Porsi Normal yang Dibuatkan Label:
                                            </label>
                                            <div class="relative">
                                                <input
                                                    v-model.number="manualPorsiNormal"
                                                    type="number"
                                                    min="0"
                                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-sm focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                                <span class="absolute right-3 top-2 text-xs font-bold text-slate-400">Porsi / Lembar</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        * Menentukan berapa banyak lembar stiker label kemasan porsi normal yang akan dicetak.
                                    </p>
                                </CardContent>
                            </Card>

                            <!-- Komponen Menu Makanan Normal -->
                            <Card className="bg-white border-slate-200/80 shadow-2xs">
                                <CardHeader className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between">
                                    <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <Utensils class="h-4 w-4 text-primary" />
                                        <span>Komponen Menu Makanan (Porsi Normal)</span>
                                    </CardTitle>
                                    <button
                                        type="button"
                                        @click="addMenuItem"
                                        class="px-2.5 py-1 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-lg font-bold text-[11px] transition-colors flex items-center gap-1 cursor-pointer"
                                    >
                                        <Plus class="h-3.5 w-3.5" />
                                        <span>Tambah Menu</span>
                                    </button>
                                </CardHeader>
                                <CardContent className="p-4 space-y-2 text-xs">
                                    <div
                                        v-for="(item, idx) in menuItems"
                                        :key="idx"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="font-mono font-bold text-slate-400 w-4">{{ idx + 1 }}.</span>
                                        <input
                                            v-model="menuItems[idx]"
                                            type="text"
                                            :placeholder="getMenuPlaceholder(idx)"
                                            class="flex-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="removeMenuItem(idx)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus baris ini"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Kandungan Gizi Normal (AKG) -->
                            <Card className="bg-white border-slate-200/80 shadow-2xs">
                                <CardHeader className="p-4 pb-2 border-b border-slate-100">
                                    <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <Flame class="h-4 w-4 text-amber-500" />
                                        <span>Nilai Kandungan Gizi Porsi Normal (AKG)</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3 text-xs">
                                    <!-- Porsi Besar -->
                                    <div>
                                        <p class="font-bold text-slate-700 mb-1.5">Porsi Besar:</p>
                                        <div class="grid grid-cols-5 gap-1.5">
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Energi (kcal)</label>
                                                <input v-model.number="giziData.energi_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                                <input v-model.number="giziData.prot_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                                <input v-model.number="giziData.lmk_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                                <input v-model.number="giziData.karbo_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                                <input v-model.number="giziData.serat_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Porsi Kecil -->
                                    <div class="pt-2 border-t border-slate-100">
                                        <p class="font-bold text-slate-700 mb-1.5">Porsi Kecil:</p>
                                        <div class="grid grid-cols-5 gap-1.5">
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Energi (kcal)</label>
                                                <input v-model.number="giziData.energi_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                                <input v-model.number="giziData.prot_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                                <input v-model.number="giziData.lmk_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                                <input v-model.number="giziData.karbo_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                            <div>
                                                <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                                <input v-model.number="giziData.serat_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </template>

                        <!-- SECTION 2: KONTEN PORSI ALERGI (DIET KHUSUS) -->
                        <template v-else-if="activeLabelTab === 'alergi'">
                            <!-- CARD 1: PILIH JENIS ALERGI YANG DIBUATKAN LABEL -->
                            <Card className="bg-amber-50/40 border-amber-200/80 shadow-2xs">
                                <CardHeader className="p-4 pb-2 border-b border-amber-200/60 flex flex-row items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <ShieldAlert class="h-4 w-4 text-amber-600" />
                                        <CardTitle class="text-xs sm:text-sm font-bold text-amber-950">
                                            Pilih Jenis Alergi yang Dibuatkan Label
                                        </CardTitle>
                                    </div>
                                    <span class="text-[11px] font-extrabold bg-amber-200 text-amber-900 px-2.5 py-0.5 rounded-full border border-amber-300">
                                        {{ selectedManualAllergies.length }} Alergi Dipilih • Total {{ totalPorsiAlergi }} Porsi
                                    </span>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3.5 text-xs">
                                    <!-- Action Bar Pintas -->
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <label class="font-bold text-amber-900">
                                            Pilih Alergi Target:
                                        </label>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="selectAllDetectedAllergies"
                                                class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-900 rounded-lg font-bold text-[11px] transition-colors cursor-pointer border border-amber-300"
                                            >
                                                Pilih Semua Alergi dari PM
                                            </button>
                                            <button
                                                type="button"
                                                @click="clearAllSelectedAllergies"
                                                class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-700 hover:text-rose-800 rounded-lg font-bold text-[11px] transition-colors cursor-pointer border border-slate-200 hover:border-rose-300"
                                            >
                                                Kosongkan Pilihan
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grid Pilihan Jenis Alergi -->
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            type="button"
                                            v-for="alergi in availableAllergiesList"
                                            :key="alergi"
                                            @click="toggleAllergySelected(alergi)"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5',
                                                selectedManualAllergies.includes(alergi)
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                            ]"
                                        >
                                            <Check v-if="selectedManualAllergies.includes(alergi)" class="h-3.5 w-3.5 text-white" />
                                            <span>⚠️ {{ alergi }}</span>
                                            <span
                                                v-if="manualAllergyConfigs[alergi]?.porsi"
                                                :class="[
                                                    'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                    selectedManualAllergies.includes(alergi)
                                                        ? 'bg-amber-700/80 text-white'
                                                        : 'bg-amber-100 text-amber-900'
                                                ]"
                                            >
                                                {{ manualAllergyConfigs[alergi].porsi }} Porsi
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Form Tambah Alergi Kustom -->
                                    <div class="pt-3 border-t border-amber-200/50 flex items-center gap-2">
                                        <input
                                            v-model="customAllergyInput"
                                            @keyup.enter="addNewCustomAllergy"
                                            type="text"
                                            placeholder="Tambah jenis alergi lain (contoh: Cumi-cumi, Gluten, Gandum)..."
                                            class="flex-1 px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-bold text-amber-950 focus:ring-2 focus:ring-amber-400 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="addNewCustomAllergy"
                                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-xs transition-colors flex items-center gap-1 cursor-pointer shrink-0 shadow-2xs"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                            <span>Tambah</span>
                                        </button>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- CARD 2: DETAIL KONFIGURASI MENU & GIZI TIAP ALERGI TERPILIH -->
                            <Card v-if="selectedManualAllergies.length > 0 && activeEditingAllergy && manualAllergyConfigs[activeEditingAllergy]" className="bg-white border-amber-200/80 shadow-2xs">
                                <CardHeader className="p-4 pb-2 border-b border-amber-100 bg-amber-50/40">
                                    <div class="flex items-center justify-between gap-2 flex-wrap pb-2">
                                        <CardTitle class="text-xs sm:text-sm font-bold text-amber-950 flex items-center gap-2">
                                            <Utensils class="h-4 w-4 text-amber-600" />
                                            <span>Pengaturan Detail Alergi Terpilih</span>
                                        </CardTitle>
                                        <span class="text-[11px] font-bold text-amber-800">
                                            Pilih tab alergi di bawah untuk mengedit menu & gizinya
                                        </span>
                                    </div>

                                    <!-- Sub-Pill Tab Alergi Aktif yang Sedang Dikonfigurasi -->
                                    <div class="flex items-center gap-1.5 overflow-x-auto py-1">
                                        <button
                                            v-for="jenis in selectedManualAllergies"
                                            :key="jenis"
                                            type="button"
                                            @click="activeEditingAllergy = jenis; selectedJenisAlergi = jenis"
                                            :class="[
                                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5 border',
                                                activeEditingAllergy === jenis
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-amber-900 border-amber-200 hover:bg-amber-50'
                                            ]"
                                        >
                                            <AlertTriangle class="h-3.5 w-3.5" />
                                            <span>{{ jenis }}</span>
                                            <span
                                                :class="[
                                                    'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                    activeEditingAllergy === jenis
                                                        ? 'bg-amber-700 text-white'
                                                        : 'bg-amber-100 text-amber-900'
                                                ]"
                                            >
                                                {{ manualAllergyConfigs[jenis]?.porsi || 0 }} Porsi
                                            </span>
                                        </button>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-4 space-y-4 text-xs">
                                    <!-- 1. Input Jumlah Porsi Khusus Alergi Ini -->
                                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl space-y-1">
                                        <div class="flex items-center justify-between">
                                            <label class="font-bold text-amber-950 text-xs">
                                                Jumlah Porsi Label Alergi: <u class="text-amber-700">{{ activeEditingAllergy }}</u>
                                            </label>
                                            <span class="text-[11px] font-extrabold text-amber-800">
                                                Target Cetak Stiker
                                            </span>
                                        </div>
                                        <div class="relative mt-1">
                                            <input
                                                v-model.number="manualAllergyConfigs[activeEditingAllergy].porsi"
                                                type="number"
                                                min="1"
                                                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg font-black text-amber-950 text-sm focus:ring-2 focus:ring-amber-400 outline-none"
                                            />
                                            <span class="absolute right-3 top-2.5 text-xs font-bold text-amber-600">Porsi / Lembar</span>
                                        </div>
                                        <p class="text-[10.5px] text-amber-800/80">
                                            * Menentukan berapa lembar stiker label kemasan yang dicetak khusus bertanda alergi {{ activeEditingAllergy }}.
                                        </p>
                                    </div>

                                    <!-- 2. Komponen Menu Pengganti untuk Alergi Ini -->
                                    <div class="space-y-2 pt-2 border-t border-slate-100">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <label class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                <Utensils class="h-3.5 w-3.5 text-amber-600" />
                                                <span>Komponen Menu Pengganti (Bebas {{ activeEditingAllergy }}):</span>
                                            </label>
                                            <div class="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    @click="copyMenuFromNormalToActiveAllergy"
                                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-slate-200"
                                                    title="Salin komponen menu dari porsi normal"
                                                >
                                                    Salin dari Normal
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="addCurrentAllergyMenuItem"
                                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-amber-200"
                                                >
                                                    + Tambah Menu
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5">
                                            <div
                                                v-for="(item, idx) in manualAllergyConfigs[activeEditingAllergy].menuItems"
                                                :key="idx"
                                                class="flex items-center gap-2"
                                            >
                                                <span class="font-mono font-bold text-amber-600 w-4 text-[11px]">{{ idx + 1 }}.</span>
                                                <input
                                                    v-model="manualAllergyConfigs[activeEditingAllergy].menuItems[idx]"
                                                    type="text"
                                                    :placeholder="getMenuAlergiPlaceholder(idx)"
                                                    class="flex-1 px-3 py-1.5 border border-amber-200 bg-amber-50/30 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-amber-300 outline-none text-xs"
                                                />
                                                <button
                                                    type="button"
                                                    @click="removeCurrentAllergyMenuItem(idx)"
                                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                    title="Hapus menu pengganti ini"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Nilai Kandungan Gizi Khusus Alergi Ini -->
                                    <div class="space-y-2 pt-3 border-t border-slate-100">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <label class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                <Flame class="h-3.5 w-3.5 text-amber-500" />
                                                <span>Nilai Kandungan Gizi Khusus {{ activeEditingAllergy }} (AKG):</span>
                                            </label>
                                            <button
                                                type="button"
                                                @click="copyGiziFromNormalToActiveAllergy"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-slate-200"
                                                title="Salin nilai gizi dari porsi normal"
                                            >
                                                Salin Gizi dari Normal
                                            </button>
                                        </div>

                                        <!-- Porsi Besar -->
                                        <div>
                                            <p class="font-bold text-slate-700 text-[11px] mb-1">Porsi Besar:</p>
                                            <div class="grid grid-cols-5 gap-1.5">
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Energi (kcal)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.energi_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Prot (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.prot_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Lmk (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.lmk_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Karbo (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.karbo_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Serat (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.serat_pb" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Porsi Kecil -->
                                        <div class="pt-1.5">
                                            <p class="font-bold text-slate-700 text-[11px] mb-1">Porsi Kecil:</p>
                                            <div class="grid grid-cols-5 gap-1.5">
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Energi (kcal)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.energi_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Prot (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.prot_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Lmk (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.lmk_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Karbo (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.karbo_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                                <div>
                                                    <label class="text-[10px] text-slate-500">Serat (g)</label>
                                                    <input v-model.number="manualAllergyConfigs[activeEditingAllergy].giziData.serat_pk" type="number" step="0.1" min="0" class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Kustom Teks Banner Badge Alergi -->
                                    <div class="pt-3 border-t border-slate-100">
                                        <label class="font-bold text-amber-900 block text-xs">
                                            Kustom Teks Banner Badge Alergi (Opsional):
                                        </label>
                                        <input
                                            v-model="customTagAlergi"
                                            type="text"
                                            :placeholder="`Otomatis (contoh: ⚠️ KHUSUS ALERGI: ${activeEditingAllergy.toUpperCase()})`"
                                            class="w-full mt-1 px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-bold text-amber-950 focus:ring-2 focus:ring-amber-400 outline-none text-xs"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Alert Info Jika Belum Ada Alergi yang Dipilih -->
                            <Card v-else className="bg-amber-50/40 border-amber-200/80 shadow-2xs">
                                <CardContent className="p-6 text-center space-y-2">
                                    <ShieldAlert class="h-8 w-8 text-amber-500 mx-auto" />
                                    <p class="font-bold text-amber-950 text-xs">Belum Ada Jenis Alergi yang Dipilih</p>
                                    <p class="text-[11px] text-amber-800/80 max-w-sm mx-auto">
                                        Silakan klik satu atau beberapa jenis alergi pada daftar di atas untuk mengaktifkan cetakan label alergi dan mengatur menu pengganti serta nilai gizinya.
                                    </p>
                                </CardContent>
                            </Card>
                        </template>
                    </template>

                    <!-- SASARAN KELOMPOK PENERIMA MANFAAT -->
                    <Card className="bg-white border-slate-200/80 shadow-2xs">
                        <CardHeader
                            className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between"
                        >
                            <CardTitle
                                class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                            >
                                <PackageCheck class="h-4 w-4 text-primary" />
                                <span
                                    >Sasaran Kelompok PM ({{
                                        printableKelompokList.length
                                    }}
                                    Terpilih)</span
                                >
                            </CardTitle>
                            <label
                                class="flex items-center gap-1.5 text-xs font-bold text-primary cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    v-model="isAllSelected"
                                    class="rounded text-primary focus:ring-primary"
                                />
                                <span>Pilih Semua</span>
                            </label>
                        </CardHeader>
                        <CardContent
                            className="p-4 max-h-56 overflow-y-auto space-y-1.5 text-xs"
                        >
                            <div
                                v-for="k in activeKelompokList"
                                :key="k.id"
                                @click="toggleKelompok(k)"
                                :class="[
                                    'p-2.5 rounded-xl border flex items-center justify-between transition-colors cursor-pointer',
                                    selectedKelompokIds.includes(k.id)
                                        ? 'bg-primary/5 border-primary/30 text-slate-900 font-bold'
                                        : 'bg-slate-50 border-slate-200 text-slate-500',
                                ]"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedKelompokIds.includes(k.id)
                                        "
                                        class="rounded text-primary focus:ring-primary"
                                    />
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-bold">{{ k.nama_kelompok }}</span>
                                            <!-- Breakdown porsi per jenis alergi (Mode Otomatis & Manual) -->
                                            <template v-if="getKelompokAlergiBreakdown(k).length > 0">
                                                <span
                                                    v-for="item in getKelompokAlergiBreakdown(k)"
                                                    :key="item.jenis"
                                                    :class="[
                                                        'px-1.5 py-0.5 border rounded text-[10px] font-extrabold flex items-center gap-1 transition-all',
                                                        selectedJenisAlergi === item.jenis
                                                            ? 'bg-amber-600 text-white border-amber-700 shadow-2xs'
                                                            : 'bg-amber-100 text-amber-900 border-amber-200'
                                                    ]"
                                                    :title="`Alergi ${item.jenis}: ${item.count} Porsi`"
                                                >
                                                    ⚠️ {{ item.jenis }}: {{ item.count }} Porsi
                                                </span>
                                            </template>
                                            <span
                                                v-else-if="labelMode === 'manual' && hasKelompokImpactedAlergi(k)"
                                                class="px-1.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-200 rounded text-[10px] font-extrabold"
                                            >
                                                ⚠️ {{ getKelompokAlergiCount(k) }} Alergi
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <span class="font-mono text-[11px] block">
                                        {{ k.total_porsi_kecil || 0 }} PK /
                                        {{ k.total_porsi_besar || 0 }} PB
                                    </span>
                                    <span
                                        v-if="getKelompokAlergiBreakdown(k).length > 0"
                                        class="text-[10px] text-amber-800 font-bold block"
                                    >
                                        {{ getKelompokAlergiCount(k) }} Alergi
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ================= RIGHT COLUMN: LIVE PREVIEW ================= -->
                <div class="lg:col-span-6 space-y-4">
                    <div class="sticky top-6 space-y-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <h3
                                class="text-sm font-black text-slate-900 flex items-center gap-2"
                            >
                                <Sparkles class="h-4 w-4 text-primary" />
                                <span>Live Preview Desain Label Resmi BGN</span>
                            </h3>
                            <span
                                class="text-[11px] text-slate-500 font-medium"
                            >
                                Standar Stiker Kemasan
                            </span>
                        </div>

                        <!-- Segmented Switch Preview: Normal vs Alergi -->
                        <div class="flex items-center justify-between gap-2 p-1.5 bg-slate-200/80 rounded-2xl border border-slate-300/80">
                            <div class="flex items-center gap-1 flex-1">
                                <button
                                    type="button"
                                    @click="setActiveLabelTab('normal')"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        activeLabelTab === 'normal'
                                            ? 'bg-white text-blue-900 shadow-xs ring-1 ring-blue-400/40'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
                                    ]"
                                >
                                    <Tag class="h-3.5 w-3.5 text-blue-600" />
                                    <span>🏷️ Label Porsi Normal</span>
                                    <span class="bg-blue-100 text-blue-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                                        {{ totalPorsiNormal }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="setActiveLabelTab('alergi')"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        activeLabelTab === 'alergi'
                                            ? 'bg-amber-600 text-white shadow-xs'
                                            : 'text-amber-800 hover:text-amber-950 hover:bg-amber-100/50'
                                    ]"
                                >
                                    <AlertTriangle class="h-3.5 w-3.5" />
                                    <span>⚠️ Label Porsi Alergi</span>
                                    <span class="bg-amber-100 text-amber-900 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                                        {{ totalPorsiAlergi }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-Pill Quick Allergen Selector for Preview when on Alergi tab -->
                        <div
                            v-if="activeLabelTab === 'alergi'"
                            class="flex items-center gap-1.5 px-3 py-2 bg-amber-50/80 border border-amber-200 rounded-xl text-xs"
                        >
                            <span class="text-[11px] font-bold text-amber-900 shrink-0">Preview Jenis:</span>
                            <div class="flex items-center gap-1 overflow-x-auto py-0.5">
                                <template v-if="labelMode === 'manual'">
                                    <button
                                        type="button"
                                        v-for="al in (selectedManualAllergies.length > 0 ? selectedManualAllergies : availableAllergiesList)"
                                        :key="al"
                                        @click="selectedJenisAlergi = al; activeEditingAllergy = al"
                                        :class="[
                                            'px-2.5 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                            currentPreviewJenisAlergi === al
                                                ? 'bg-amber-600 text-white font-extrabold shadow-2xs'
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100'
                                        ]"
                                    >
                                        <span>⚠️ {{ al }}</span>
                                        <span v-if="manualAllergyConfigs[al]?.porsi" class="text-[10px] opacity-80">
                                            ({{ manualAllergyConfigs[al].porsi }})
                                        </span>
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="selectedJenisAlergi = 'Semua Alergi'"
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                            selectedJenisAlergi === 'Semua Alergi'
                                                ? 'bg-amber-600 text-white font-extrabold shadow-2xs'
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100'
                                        ]"
                                    >
                                        <span>✨ Semua Alergi</span>
                                        <span
                                            :class="[
                                                'text-[10px] px-1 rounded-full font-bold',
                                                selectedJenisAlergi === 'Semua Alergi'
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900'
                                            ]"
                                        >
                                            {{ getAllergyPorsiCount('Semua Alergi') }}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        v-for="al in detectedAlergiList"
                                        :key="al"
                                        @click="selectedJenisAlergi = al"
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                            selectedJenisAlergi === al
                                                ? 'bg-amber-600 text-white font-extrabold shadow-2xs'
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100'
                                        ]"
                                    >
                                        <span>⚠️ {{ al }}</span>
                                        <span
                                            :class="[
                                                'text-[10px] px-1 rounded-full font-bold',
                                                selectedJenisAlergi === al
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900'
                                            ]"
                                        >
                                            {{ getAllergyPorsiCount(al) }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Card Preview Container -->
                        <div
                            class="p-4 bg-slate-100/80 rounded-3xl border border-slate-200 flex items-center justify-center shadow-inner"
                        >
                            <LabelCardItem
                                :unit-sppg="unitSppg"
                                :nama-sppg="namaSppg"
                                :zona-waktu="zonaWaktu"
                                :tinggi-isolasi-cm="tinggiIsolasiCm"
                                :tanggal-produksi="tanggalProduksi"
                                :jam-produksi="jamProduksi"
                                :tampilkan-jam-produksi="gunakanJamProduksi"
                                :tanggal-expired="tanggalExpired"
                                :jam-expired="jamExpired"
                                :tampilkan-jam-expired="gunakanJamExpired"
                                :waktu-maksimal="waktuMaksimal"
                                :teks-larangan-header="teksLaranganHeader"
                                :teks-larangan-sub="teksLaranganSub"
                                :tipe-label="activeLabelTab"
                                :jenis-alergi="currentPreviewJenisAlergi"
                                :tag-alergi="customTagAlergi"
                                :menu-items="currentPreviewMenuItems"
                                :gizi-data="currentPreviewGiziData"
                                :harga-items="hargaItems"
                                :kelompok="printableKelompokList[0] || activeKelompokList[0]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Download Progress Modal -->
        <Teleport to="body">
            <div
                v-if="isDownloading"
                class="fixed inset-0 z-[99999] bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in"
            >
                <div
                    class="bg-white rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl border border-slate-200 text-center space-y-4 animate-in zoom-in-95"
                >
                    <div
                        class="h-14 w-14 rounded-2xl mx-auto flex items-center justify-center transition-all shadow-xs"
                        :class="
                            downloadSuccess
                                ? 'bg-emerald-100 text-emerald-600'
                                : downloadError
                                  ? 'bg-rose-100 text-rose-600'
                                  : 'bg-primary/10 text-primary'
                        "
                    >
                        <CheckCircle2 v-if="downloadSuccess" class="h-8 w-8" />
                        <AlertCircle
                            v-else-if="downloadError"
                            class="h-8 w-8"
                        />
                        <Loader2 v-else class="h-8 w-8 animate-spin" />
                    </div>

                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 tracking-tight"
                        >
                            {{
                                downloadSuccess
                                    ? downloadType === "print"
                                        ? "File PDF Siap!"
                                        : "File PDF Berhasil Dibuat!"
                                    : downloadError
                                      ? "Gagal Memproses"
                                      : downloadType === "print"
                                        ? "Menyiapkan Dialog Cetak..."
                                        : downloadType === "single"
                                          ? "Membuat PDF Label Tunggal..."
                                          : downloadType === "custom"
                                            ? `Membuat PDF Lembar A4 (${customLabelCount} Label)...`
                                            : "Membuat PDF Lembar A4 (9 Label / Halaman)..."
                            }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                            {{
                                downloadError
                                    ? downloadError
                                    : downloadProgress.message ||
                                      "Sedang memproses dokumen label..."
                            }}
                        </p>
                    </div>

                    <!-- Progress Bar & Metrics -->
                    <div v-if="!downloadError" class="space-y-2 pt-1">
                        <div
                            class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 p-0.5"
                        >
                            <div
                                class="h-full transition-all duration-200 rounded-full"
                                :class="
                                    downloadSuccess
                                        ? 'bg-emerald-500'
                                        : 'bg-primary'
                                "
                                :style="{
                                    width: `${downloadProgress.percentage}%`,
                                }"
                            ></div>
                        </div>

                        <div
                            class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-600 px-1"
                        >
                            <span v-if="downloadProgress.phase === 'template'">
                                {{ downloadProgress.current }} dari {{ downloadProgress.total }} Desain Label
                                <span class="text-slate-400 font-sans font-normal"> (Render Desain)</span>
                            </span>
                            <span v-else>
                                {{ downloadProgress.current }} dari
                                {{ downloadProgress.total }} Label
                                <span v-if="(downloadType === 'a4' || downloadType === 'custom') && downloadProgress.totalPages > 1" class="text-slate-400 font-sans font-normal">
                                    ({{ downloadProgress.totalPages }} Hal A4)
                                </span>
                                <span v-else-if="(downloadType === 'single' || downloadType === 'print') && downloadProgress.totalPages > 1" class="text-slate-400 font-sans font-normal">
                                    ({{ downloadProgress.totalPages }} Lembar)
                                </span>
                            </span>
                            <span class="text-primary font-black">{{ downloadProgress.percentage }}%</span>
                        </div>

                        <!-- ETA & Speed Badges -->
                        <div
                            v-if="!downloadSuccess && (downloadProgress.etaText || downloadProgress.speedText)"
                            class="flex items-center justify-center gap-2 pt-1 flex-wrap"
                        >
                            <div
                                v-if="downloadProgress.etaText"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-lg text-[11px] font-bold text-amber-800"
                            >
                                <Clock class="h-3.5 w-3.5 text-amber-600 animate-pulse" />
                                <span>{{ downloadProgress.etaText }}</span>
                            </div>

                            <div
                                v-if="downloadProgress.speedText"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-lg text-[11px] font-bold text-blue-800"
                            >
                                <Zap class="h-3.5 w-3.5 text-blue-600" />
                                <span>{{ downloadProgress.speedText }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Modal (Batal saat proses / Tutup jika error) -->
                    <div
                        v-if="!downloadSuccess && !downloadError"
                        class="pt-2 flex justify-center"
                    >
                        <button
                            type="button"
                            @click="cancelDownloadProcess"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 font-bold text-xs transition-all cursor-pointer shadow-2xs active:scale-95"
                            title="Hentikan dan batalkan proses render / download"
                        >
                            <X class="h-3.5 w-3.5" />
                            <span>Batalkan Proses</span>
                        </button>
                    </div>

                    <div v-if="downloadError" class="pt-2">
                        <button
                            type="button"
                            @click="isDownloading = false"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs transition-colors cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Sandbox Render Container for High-Speed PDF Generation -->
        <div
            style="
                position: fixed;
                left: -9999px;
                top: 0;
                width: 555px;
                height: 370px;
                pointer-events: none;
                overflow: hidden;
                z-index: -99999;
                background: #ffffff;
            "
        >
            <div ref="sandboxCardRef" style="width: 555px; height: 370px; background: white">
                <LabelCardItem
                    :key="`${sandboxParams.kelompok?.id || '0'}_${sandboxParams.tipeLabel}_${sandboxParams.jenisAlergi}_${sandboxParams.tagAlergi}_${(sandboxParams.menuItems || []).join('|')}_${sandboxParams.giziData?.energi_pb || ''}_${sandboxParams.giziData?.energi_pk || ''}`"
                    :unit-sppg="unitSppg"
                    :nama-sppg="namaSppg"
                    :zona-waktu="zonaWaktu"
                    :tinggi-isolasi-cm="tinggiIsolasiCm"
                    :tanggal-produksi="tanggalProduksi"
                    :jam-produksi="jamProduksi"
                    :tampilkan-jam-produksi="gunakanJamProduksi"
                    :tanggal-expired="tanggalExpired"
                    :jam-expired="jamExpired"
                    :tampilkan-jam-expired="gunakanJamExpired"
                    :waktu-maksimal="waktuMaksimal"
                    :teks-larangan-header="teksLaranganHeader"
                    :teks-larangan-sub="teksLaranganSub"
                    :tipe-label="sandboxParams.tipeLabel"
                    :jenis-alergi="sandboxParams.jenisAlergi"
                    :tag-alergi="sandboxParams.tagAlergi"
                    :menu-items="sandboxParams.menuItems"
                    :gizi-data="sandboxParams.giziData"
                    :harga-items="hargaItems"
                    :kelompok="sandboxParams.kelompok"
                />
            </div>
        </div>
    </div>
</template>
