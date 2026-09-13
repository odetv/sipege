<script setup>
import { ref, computed, watch, nextTick } from "vue";
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
    "NASI PUTIH",
    "AYAM CRISPY",
    "TEMPE MANIS DADU",
    "SELADA, TIMUN",
    "MELON",
]);

// Parameter Komponen Menu Makanan Porsi Alergi (Diet Khusus)
const menuItemsAlergi = ref([
    "NASI PUTIH",
    "AYAM CRISPY (BEBAS TELUR)",
    "TEMPE MANIS DADU",
    "SELADA, TIMUN",
    "MELON",
]);

// Pilihan Jenis Alergi & Custom Tag Banner
const selectedJenisAlergi = ref("Semua Alergi");
const customTagAlergi = ref("");

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

// Filter Cetak / Download: 'semua' | 'normal' | 'alergi'
const filterCetakTipe = ref("normal");

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

        // 2. Ambil dari items / bahan WO yang memiliki tipe_porsi 'alergi' atau penanda alergen
        if (Array.isArray(wo.items)) {
            for (const it of wo.items) {
                if (
                    it.tipe_porsi === "alergi" &&
                    it.jenis_alergi &&
                    typeof it.jenis_alergi === "string"
                ) {
                    list.add(it.jenis_alergi.trim());
                }
                if (
                    it.alergen &&
                    typeof it.alergen === "string" &&
                    it.alergen.trim() &&
                    !it.alergen.toLowerCase().startsWith("sub_menu_")
                ) {
                    list.add(it.alergen.trim());
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

        // Mode otomatis HANYA menampilkan alergi yang memang terdampak pada Work Order ini
        return Array.from(list).filter(Boolean);
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

// Helper hitung jumlah porsi alergi yang terdampak pada kelompok tertentu
function getKelompokAlergiCount(k) {
    if (!k) return 0;
    const isAuto = labelMode.value === "auto";
    const affectedList = detectedAlergiList.value;
    const details = k.detail_alergi || k.keterangan_alergi || [];

    let count = 0;
    if (Array.isArray(details)) {
        for (const d of details) {
            if (typeof d === "object") {
                const jenis = (d.jenis_alergi || d.nama || "").trim();
                if (
                    isAuto &&
                    affectedList.length > 0 &&
                    !affectedList.includes(jenis)
                ) {
                    continue;
                }
                if (
                    selectedJenisAlergi.value !== "Semua Alergi" &&
                    jenis !== selectedJenisAlergi.value
                ) {
                    continue;
                }
                count +=
                    (Number(d.porsi_kecil) || 0) +
                    (Number(d.porsi_besar) || 0) +
                    (Number(d.jumlah) || 0);
            } else if (typeof d === "string" && d.trim()) {
                const jenis = d.trim();
                if (
                    isAuto &&
                    affectedList.length > 0 &&
                    !affectedList.includes(jenis)
                ) {
                    continue;
                }
                if (
                    selectedJenisAlergi.value !== "Semua Alergi" &&
                    jenis !== selectedJenisAlergi.value
                ) {
                    continue;
                }
                count += 1;
            }
        }
    }
    return count;
}

function hasKelompokImpactedAlergi(k) {
    return getKelompokAlergiCount(k) > 0;
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
    filterCetakTipe.value = tab;
}

// Helper mengambil menu pengganti alergi berdasarkan sub_menu 1-5 dan sub_menu_alergi di WO
function getAllergyMenuItemsForTarget(
    targetAlergi,
    wo = activeWorkOrder.value,
) {
    if (!wo) return menuItemsAlergi.value;

    const normal1 = wo.sub_menu_1 || menuItems.value[0] || "";
    const normal2 = wo.sub_menu_2 || menuItems.value[1] || "";
    const normal3 = wo.sub_menu_3 || menuItems.value[2] || "";
    const normal4 = wo.sub_menu_4 || menuItems.value[3] || "";
    const normal5 = wo.sub_menu_5 || menuItems.value[4] || "";

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

    // 3. Sinkronisasi komponen menu alergi (Sub Menu pengganti yang sesuai)
    menuItemsAlergi.value = getAllergyMenuItemsForTarget(
        selectedJenisAlergi.value,
        wo,
    );

    // 4. Sinkronisasi AKG
    if (wo.akg_pb && wo.akg_pk) {
        giziData.value = {
            energi_pb: String(wo.akg_pb.energi || "624"),
            prot_pb: String(wo.akg_pb.protein || "29.8"),
            lmk_pb: String(wo.akg_pb.lemak || "18.8"),
            karbo_pb: String(wo.akg_pb.karbohidrat || "82.4"),
            serat_pb: String(wo.akg_pb.serat || "2.0"),
            energi_pk: String(wo.akg_pk.energi || "469"),
            prot_pk: String(wo.akg_pk.protein || "23.4"),
            lmk_pk: String(wo.akg_pk.lemak || "15.0"),
            karbo_pk: String(wo.akg_pk.karbohidrat || "58.9"),
            serat_pk: String(wo.akg_pk.serat || "1.5"),
        };
        giziDataAlergi.value = { ...giziData.value };
    }

    // 5. Centang sasaran kelompok PM sesuai mode (Hanya yang alergi jika tab alergi)
    if (
        activeLabelTab.value === "alergi" ||
        filterCetakTipe.value === "alergi"
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
        const isAlergiMode = tab === "alergi" || filter === "alergi";
        if (isAlergiMode) {
            selectedKelompokIds.value = activeKelompokList.value
                .filter(
                    (k) =>
                        k.is_menerima !== false && hasKelompokImpactedAlergi(k),
                )
                .map((k) => k.id);
        } else {
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

watch(selectedJenisAlergi, (val) => {
    if (labelMode.value === "auto" && activeWorkOrder.value) {
        menuItemsAlergi.value = getAllergyMenuItemsForTarget(
            val,
            activeWorkOrder.value,
        );
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

// Menu item normal helpers
function addMenuItem() {
    menuItems.value.push("KOMPONEN BARU");
    hargaItems.value.push({ nama: "Komponen Baru", harga_pb: 0, harga_pk: 0 });
}

function removeMenuItem(idx) {
    if (menuItems.value.length <= 1) return;
    menuItems.value.splice(idx, 1);
    if (hargaItems.value[idx]) {
        hargaItems.value.splice(idx, 1);
    }
}

function updateMenuItemName(idx, val) {
    menuItems.value[idx] = val;
    if (hargaItems.value[idx]) {
        hargaItems.value[idx].nama = val;
    }
}

// Menu item alergi helpers
function addMenuItemAlergi() {
    menuItemsAlergi.value.push("KOMPONEN PENGGANTI BARU");
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
    const isAuto = labelMode.value === "auto";
    const affectedAlergiList = detectedAlergiList.value;

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

        // 1. Tambahkan Label Porsi Normal
        if (
            filterCetakTipe.value === "semua" ||
            filterCetakTipe.value === "normal"
        ) {
            if (normalCount > 0) {
                list.push({
                    kelompok: k,
                    tipeLabel: "normal",
                    jenisAlergi: "",
                    tagAlergi: "",
                    count: normalCount,
                });
            }
        }

        // 2. Tambahkan Label Porsi Alergi
        if (
            filterCetakTipe.value === "semua" ||
            filterCetakTipe.value === "alergi"
        ) {
            if (alergiBreakdown.length > 0) {
                for (const ab of alergiBreakdown) {
                    if (
                        selectedJenisAlergi.value === "Semua Alergi" ||
                        selectedJenisAlergi.value === ab.jenis
                    ) {
                        list.push({
                            kelompok: k,
                            tipeLabel: "alergi",
                            jenisAlergi: ab.jenis,
                            tagAlergi:
                                customTagAlergi.value ||
                                `⚠️ KHUSUS ALERGI: ${ab.jenis.toUpperCase()}`,
                            count: ab.count,
                        });
                    }
                }
            }
        }
    }

    if (list.length === 0) {
        if (labelMode.value === "manual" && kelompokList.length > 0) {
            return kelompokList.map((k) => ({
                kelompok: k,
                tipeLabel:
                    filterCetakTipe.value === "alergi" ? "alergi" : "normal",
                jenisAlergi:
                    selectedJenisAlergi.value === "Semua Alergi"
                        ? ""
                        : selectedJenisAlergi.value,
                tagAlergi: customTagAlergi.value || "",
                count: 1,
            }));
        }
        return [];
    }

    return list;
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
        total_porsi: printableKelompokList.value.reduce(
            (sum, k) =>
                sum +
                Number(
                    k.total_penerima ||
                        (k.total_porsi_kecil || 0) + (k.total_porsi_besar || 0),
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

    sandboxParams.value = {
        kelompok: k || null,
        tipeLabel: tipe,
        jenisAlergi: alergi,
        tagAlergi: tag,
        menuItems:
            tipe === "alergi"
                ? labelMode.value === "auto"
                    ? getAllergyMenuItemsForTarget(alergi)
                    : menuItemsAlergi.value
                : menuItems.value,
        giziData: tipe === "alergi" ? giziDataAlergi.value : giziData.value,
    };

    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 20));
    return (
        sandboxCardRef.value?.querySelector(".bgn-label-card") ||
        sandboxCardRef.value
    );
}

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
                                    <span
                                        >Konfigurasi & Cetak Label Resmi SPPG
                                        BGN (9 x 6 cm)</span
                                    >
                                </CardTitle>
                                <span
                                    class="bg-blue-100 text-blue-900 border border-blue-200 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <span
                                        >Ukuran Fixed 9 x 6 cm (Landscape)</span
                                    >
                                </span>
                            </div>
                            <CardDescription class="text-xs sm:text-sm mt-0.5">
                                Mendukung pembuatan Label Porsi Normal & Label Porsi Alergi (Diet Khusus) sesuai Work Order dan data kelompok penerima.
                            </CardDescription>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                            <!-- Filter Cetak: Semua / Normal / Alergi -->
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                                <select
                                    v-model="filterCetakTipe"
                                    class="h-8 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg px-2.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                    title="Pilih porsi yang akan dicetak atau diunduh"
                                >
                                    <option value="semua">🏷️ + ⚠️ Semua ({{ totalPorsiNormal }} Nml + {{ totalPorsiAlergi }} Alergi)</option>
                                    <option value="normal">🏷️ Hanya Porsi Normal ({{ totalPorsiNormal }} pcs)</option>
                                    <option value="alergi">⚠️ Hanya Porsi Alergi ({{ totalPorsiAlergi }} pcs)</option>
                                </select>
                            </div>

                            <!-- Tombol RESET KE AWAL -->
                            <Button
                                type="button"
                                @click="resetLabelToDefault"
                                :disabled="isSaving || isDownloading"
                                className="h-10 px-3.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 font-bold text-xs flex items-center gap-1.5 rounded-xl shadow-xs cursor-pointer"
                                title="Kembalikan semua pengaturan dan data label ke konfigurasi default / awal"
                            >
                                <RotateCcw class="h-4 w-4 text-slate-500" />
                                <span>Reset Awal</span>
                            </Button>

                            <!-- Tombol SIMPAN LABEL -->
                            <Button
                                type="button"
                                @click="saveLabelToDatabase"
                                :disabled="
                                    printableKelompokList.length === 0 ||
                                    isSaving
                                "
                                className="h-10 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-2 rounded-xl shadow-xs cursor-pointer"
                                :title="
                                    editingLabel
                                        ? 'Perbarui data label ini di Daftar Label'
                                        : 'Simpan konfigurasi label ini ke Daftar Label'
                                "
                            >
                                <Loader2
                                    v-if="isSaving"
                                    class="h-4 w-4 animate-spin"
                                />
                                <BookmarkCheck v-else class="h-4 w-4" />
                                <span>{{
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
                                    class="h-8 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg px-2.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                >
                                    <option value="single">Label Tunggal (9x6 cm)</option>
                                    <option value="a4">Lembar A4 (9 Label/Hal)</option>
                                    <option value="custom">Custom Jumlah Label (A4)</option>
                                </select>

                                <!-- Custom Count Input (Appears when Custom is selected) -->
                                <div
                                    v-if="downloadFormatOption === 'custom'"
                                    class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg px-2 h-8 shadow-2xs"
                                >
                                    <span class="text-[11px] font-bold text-slate-500">Jml:</span>
                                    <input
                                        type="number"
                                        v-model.number="customLabelCount"
                                        min="1"
                                        max="50000"
                                        class="w-16 h-6 text-center text-xs font-black text-slate-800 border-none p-0 focus:outline-none focus:ring-0"
                                        title="Jumlah label otomatis terisi dari total porsi WO hari ini (dapat diubah manual sesuai kebutuhan)"
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
                                    className="h-8 px-3.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs flex items-center gap-1.5 rounded-lg shadow-xs cursor-pointer"
                                    title="Download file PDF sesuai format yang dipilih"
                                >
                                    <Loader2
                                        v-if="
                                            isDownloading &&
                                            (downloadType === 'single' ||
                                                downloadType === 'a4' ||
                                                downloadType === 'custom')
                                        "
                                        class="h-3.5 w-3.5 animate-spin"
                                    />
                                    <Download v-else class="h-3.5 w-3.5" />
                                    <span>Download PDF</span>
                                </Button>
                            </div>

                            <!-- Print Direct Exact 9x6cm (Icon Only) -->
                            <Button
                                type="button"
                                @click="startPrintDirect"
                                :disabled="
                                    printableItems.length === 0 ||
                                    isDownloading
                                "
                                className="h-10 w-10 p-0 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center rounded-xl shadow-xs cursor-pointer shrink-0"
                                title="Cetak Langsung Label (9x6 cm)"
                            >
                                <Loader2
                                    v-if="isDownloading && downloadType === 'print'"
                                    class="h-4 w-4 animate-spin"
                                />
                                <Printer v-else class="h-4 w-4 text-white" />
                            </Button>
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
                            <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Layers class="h-4 w-4 text-primary" />
                                    <span>Area Tempel Isolasi / Perekat</span>
                                </div>
                                <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                    Tinggi Total: 6 cm
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3 text-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <label class="font-bold text-slate-700 block">
                                        Lebar Atas-Bawah / Tinggi Area Isolasi (cm):
                                    </label>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Mengatur tinggi area kosong perekat pada bagian atas label (Default: 1.5 cm).
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <div class="relative w-28">
                                        <input
                                            v-model.number="tinggiIsolasiCm"
                                            type="number"
                                            step="0.1"
                                            min="0.5"
                                            max="3.5"
                                            class="w-full px-3 py-1.5 pr-9 border border-slate-300 rounded-lg font-black text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none text-center"
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">cm</span>
                                    </div>
                                </div>
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
                                <span class="text-[11px] font-bold bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full">
                                    {{ totalPorsiAlergi }} Porsi
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
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border',
                                            selectedJenisAlergi === 'Semua Alergi'
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                        ]"
                                    >
                                        ✨ Semua Jenis Alergi (Dinamis)
                                    </button>
                                    <button
                                        type="button"
                                        v-for="alergi in detectedAlergiList"
                                        :key="alergi"
                                        @click="selectedJenisAlergi = alergi"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border',
                                            selectedJenisAlergi === alergi
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                        ]"
                                    >
                                        ⚠️ {{ alergi }}
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
                                        :value="item"
                                        @input="updateMenuItemName(idx, $event.target.value)"
                                        type="text"
                                        placeholder="Contoh: AYAM CRISPY"
                                        class="flex-1 px-3 py-1.5 border border-slate-200 rounded-lg font-extrabold uppercase text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
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
                                            <input v-model="giziData.energi_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                            <input v-model="giziData.prot_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                            <input v-model="giziData.lmk_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                            <input v-model="giziData.karbo_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                            <input v-model="giziData.serat_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Porsi Kecil -->
                                <div class="pt-2 border-t border-slate-100">
                                    <p class="font-bold text-slate-700 mb-1.5">Porsi Kecil:</p>
                                    <div class="grid grid-cols-5 gap-1.5">
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Energi (kcal)</label>
                                            <input v-model="giziData.energi_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                            <input v-model="giziData.prot_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                            <input v-model="giziData.lmk_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                            <input v-model="giziData.karbo_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                            <input v-model="giziData.serat_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Rincian Harga Satuan per Item -->
                        <Card v-if="labelMode === 'manual'" className="bg-white border-slate-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-slate-100">
                                <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Coins class="h-4 w-4 text-emerald-600" />
                                    <span>Rincian Harga Satuan per Item</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-2 text-xs">
                                <div
                                    v-for="(item, idx) in hargaItems"
                                    :key="idx"
                                    class="grid grid-cols-12 gap-2 items-center"
                                >
                                    <div class="col-span-6 font-bold text-slate-700 truncate">
                                        {{ item.nama }}
                                    </div>
                                    <div class="col-span-3">
                                        <input
                                            v-model="item.harga_pb"
                                            type="number"
                                            placeholder="P. Besar"
                                            class="w-full px-2 py-1 border border-slate-200 rounded font-bold text-right text-xs"
                                        />
                                    </div>
                                    <div class="col-span-3">
                                        <input
                                            v-model="item.harga_pk"
                                            type="number"
                                            placeholder="P. Kecil"
                                            class="w-full px-2 py-1 border border-slate-200 rounded font-bold text-right text-xs"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </template>

                    <!-- SECTION 2: KONTEN PORSI ALERGI (DIET KHUSUS) -->
                    <template v-else-if="activeLabelTab === 'alergi'">
                        <!-- Pengaturan Allergen & Banner Badge -->
                        <Card className="bg-amber-50/40 border-amber-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-amber-200/60">
                                <CardTitle class="text-xs sm:text-sm font-bold text-amber-950 flex items-center gap-2">
                                    <ShieldAlert class="h-4 w-4 text-amber-600" />
                                    <span>Identitas & Sasaran Jenis Alergen</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3.5 text-xs">
                                <div>
                                    <label class="font-bold text-amber-900 block mb-1.5">
                                        Pilih Target Jenis Alergi:
                                    </label>
                                    <div v-if="detectedAlergiList.length === 0" class="p-3 bg-white border border-amber-200 rounded-xl text-amber-900 text-xs flex items-center gap-2">
                                        <Sparkles class="h-4 w-4 text-amber-600 shrink-0" />
                                        <span>Tidak ada penerima manfaat yang tercatat memiliki alergi/pantangan khusus pada Work Order ini.</span>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            type="button"
                                            @click="selectedJenisAlergi = 'Semua Alergi'"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border',
                                                selectedJenisAlergi === 'Semua Alergi'
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                            ]"
                                        >
                                            ✨ Semua Jenis Alergi (Dinamis)
                                        </button>
                                        <button
                                            type="button"
                                            v-for="alergi in detectedAlergiList"
                                            :key="alergi"
                                            @click="selectedJenisAlergi = alergi"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border',
                                                selectedJenisAlergi === alergi
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300'
                                            ]"
                                        >
                                            ⚠️ {{ alergi }}
                                        </button>
                                    </div>
                                    <p v-if="detectedAlergiList.length > 0" class="text-[11px] text-amber-800/80 mt-1.5">
                                        * Jika memilih "Semua Jenis Alergi", cetakan label akan otomatis menyesuaikan jenis alergi di masing-masing kelompok penerima.
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
                            </CardContent>
                        </Card>

                        <!-- Komponen Menu Pengganti Porsi Alergi -->
                        <Card className="bg-white border-slate-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between">
                                <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Utensils class="h-4 w-4 text-amber-600" />
                                    <span>Komponen Menu Pengganti (Bebas Alergen)</span>
                                </CardTitle>
                                <button
                                    type="button"
                                    @click="addMenuItemAlergi"
                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white rounded-lg font-bold text-[11px] transition-colors flex items-center gap-1 cursor-pointer border border-amber-200"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    <span>Tambah Menu Alergi</span>
                                </button>
                            </CardHeader>
                            <CardContent className="p-4 space-y-2 text-xs">
                                <div
                                    v-for="(item, idx) in menuItemsAlergi"
                                    :key="idx"
                                    class="flex items-center gap-2"
                                >
                                    <span class="font-mono font-bold text-amber-600 w-4">{{ idx + 1 }}.</span>
                                    <input
                                        :value="item"
                                        @input="updateMenuItemNameAlergi(idx, $event.target.value)"
                                        type="text"
                                        placeholder="Contoh: AYAM CRISPY (BEBAS TELUR)"
                                        class="flex-1 px-3 py-1.5 border border-amber-200 bg-amber-50/30 rounded-lg font-extrabold uppercase text-slate-900 focus:ring-2 focus:ring-amber-300 outline-none"
                                    />
                                    <button
                                        type="button"
                                        @click="removeMenuItemAlergi(idx)"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                        title="Hapus menu pengganti ini"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Kandungan Gizi Porsi Alergi (AKG) -->
                        <Card className="bg-white border-slate-200/80 shadow-2xs">
                            <CardHeader className="p-4 pb-2 border-b border-slate-100">
                                <CardTitle class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Flame class="h-4 w-4 text-amber-500" />
                                    <span>Nilai Kandungan Gizi Porsi Alergi (AKG)</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <!-- Porsi Besar -->
                                <div>
                                    <p class="font-bold text-slate-700 mb-1.5">Porsi Besar:</p>
                                    <div class="grid grid-cols-5 gap-1.5">
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Energi (kcal)</label>
                                            <input v-model="giziDataAlergi.energi_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                            <input v-model="giziDataAlergi.prot_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                            <input v-model="giziDataAlergi.lmk_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                            <input v-model="giziDataAlergi.karbo_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                            <input v-model="giziDataAlergi.serat_pb" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Porsi Kecil -->
                                <div class="pt-2 border-t border-slate-100">
                                    <p class="font-bold text-slate-700 mb-1.5">Porsi Kecil:</p>
                                    <div class="grid grid-cols-5 gap-1.5">
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Energi (kcal)</label>
                                            <input v-model="giziDataAlergi.energi_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Prot (g)</label>
                                            <input v-model="giziDataAlergi.prot_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Lmk (g)</label>
                                            <input v-model="giziDataAlergi.lmk_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Karbo (g)</label>
                                            <input v-model="giziDataAlergi.karbo_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                        <div>
                                            <label class="text-[10.5px] text-slate-500">Serat (g)</label>
                                            <input v-model="giziDataAlergi.serat_pk" type="text" class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center" />
                                        </div>
                                    </div>
                                </div>
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
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ k.nama_kelompok }}</span>
                                            <span
                                                v-if="hasKelompokImpactedAlergi(k)"
                                                class="px-1.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-200 rounded text-[10px] font-extrabold"
                                            >
                                                ⚠️ {{ getKelompokAlergiCount(k) }} Alergi
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span class="font-mono text-[11px]">
                                    {{ k.total_porsi_kecil || 0 }} PK /
                                    {{ k.total_porsi_besar || 0 }} PB
                                </span>
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
                                Standar Stiker Box (9 x 6 cm)
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
                            v-if="activeLabelTab === 'alergi' && detectedAlergiList.length > 0"
                            class="flex items-center gap-1.5 px-3 py-2 bg-amber-50/80 border border-amber-200 rounded-xl text-xs"
                        >
                            <span class="text-[11px] font-bold text-amber-900 shrink-0">Preview Jenis:</span>
                            <div class="flex items-center gap-1 overflow-x-auto py-0.5">
                                <button
                                    type="button"
                                    @click="selectedJenisAlergi = 'Semua Alergi'"
                                    :class="[
                                        'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer',
                                        selectedJenisAlergi === 'Semua Alergi'
                                            ? 'bg-amber-600 text-white font-extrabold'
                                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100'
                                    ]"
                                >
                                    <span>✨ Semua Alergi</span>
                                </button>
                                <button
                                    type="button"
                                    v-for="al in detectedAlergiList"
                                    :key="al"
                                    @click="selectedJenisAlergi = al"
                                    :class="[
                                        'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer',
                                        selectedJenisAlergi === al
                                            ? 'bg-amber-600 text-white font-extrabold'
                                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100'
                                    ]"
                                >
                                    {{ al }}
                                </button>
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
                                :jenis-alergi="selectedJenisAlergi === 'Semua Alergi' ? (detectedAlergiList[0] || 'Khusus') : selectedJenisAlergi"
                                :tag-alergi="customTagAlergi"
                                :menu-items="activeLabelTab === 'alergi' ? menuItemsAlergi : menuItems"
                                :gizi-data="activeLabelTab === 'alergi' ? giziDataAlergi : giziData"
                                :harga-items="hargaItems"
                                :kelompok="printableKelompokList[0] || activeKelompokList[0]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PRINT VIEW CONTAINER ================= -->
        <div class="hidden print:block print-mode-9x6">
            <div
                v-for="(item, idx) in expandedPrintableItems"
                :key="idx"
                class="bgn-print-page"
            >
                <div class="bgn-print-card-wrapper">
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
                        :tipe-label="item.tipeLabel"
                        :jenis-alergi="item.jenisAlergi"
                        :tag-alergi="item.tagAlergi"
                        :menu-items="
                            item.tipeLabel === 'alergi'
                                ? labelMode === 'auto'
                                    ? getAllergyMenuItemsForTarget(
                                          item.jenisAlergi,
                                      )
                                    : menuItemsAlergi
                                : menuItems
                        "
                        :gizi-data="
                            item.tipeLabel === 'alergi'
                                ? giziDataAlergi
                                : giziData
                        "
                        :harga-items="hargaItems"
                        :kelompok="item.kelompok"
                    />
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
                                        ? "Dialog Cetak 9x6cm Siap!"
                                        : "File PDF Berhasil Dibuat!"
                                    : downloadError
                                      ? "Gagal Memproses"
                                      : downloadType === "print"
                                        ? "Menyiapkan Dialog Cetak Langsung..."
                                        : downloadType === "single"
                                          ? "Membuat PDF Label Tunggal (9x6 cm)..."
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
