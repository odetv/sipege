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
import LabelCardItemTemplate02 from "./LabelCardItemTemplate02.vue";
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
    PlusCircle,
    Minus,
    PhoneCall,
    Database,
} from "lucide-vue-next";
import {
    downloadPdfSingleMode,
    downloadPdfA4GridMode,
    printPdfSingleMode,
    calculateA4TotalPages,
    downloadPdfTemplate02SingleMode,
    downloadPdfTemplate02A4GridMode,
    printPdfTemplate02SingleMode,
    getSocialMediaTpl02,
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

// Pilihan Template Desain Label: 'template-01' (Grafis Modern) | 'template-02' (Standar Excel / Teks Murni)
const selectedTemplateId = ref("template-01");

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
        const target = String(selectedWoId.value);
        const found = props.workOrders.find(
            (w) =>
                String(w.id) === target ||
                String(w.nomor_wo) === target ||
                String(w.uuid) === target ||
                String(w.db_id) === target,
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

// Parameter Kontak Pengaduan (Khusus Template 02: Tampil di Sumber Data Otomatis WO maupun Input Manual)
const defaultSmInit = getSocialMediaTpl02(namaSppg.value);
const kontakPengaduanBaris1 = ref("Email: halo@bgn.go.id");
const kontakPengaduanBaris2 = ref(defaultSmInit.ig);
const kontakPengaduanBaris3 = ref(defaultSmInit.tiktok);
const kontakPengaduanBaris4 = ref(defaultSmInit.fb);
const isKontakEdited = ref(false);

const syncKontakOtomatis = (sppgName = namaSppg.value) => {
    const sm = getSocialMediaTpl02(sppgName);
    kontakPengaduanBaris1.value = "Email: halo@bgn.go.id";
    kontakPengaduanBaris2.value = sm.ig;
    kontakPengaduanBaris3.value = sm.tiktok;
    kontakPengaduanBaris4.value = sm.fb;
    isKontakEdited.value = false;
};

watch(namaSppg, (newVal) => {
    if (!isKontakEdited.value) {
        syncKontakOtomatis(newVal);
    }
});

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
    if (
        selectedJenisAlergi.value &&
        selectedJenisAlergi.value !== "Semua Alergi"
    ) {
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

// Helper normalisasi nama alergi untuk pencocokan toleran prefix "Alergi:" dan case
function normalizeAllergyName(name) {
    if (!name) return "";
    let s = String(
        typeof name === "object"
            ? name.value || name.label || name.jenis_alergi || ""
            : name,
    );
    return s
        .trim()
        .replace(/^alergi\s*[:\-]?\s*/i, "")
        .trim()
        .toLowerCase();
}

function matchAllergyKey(k1, k2) {
    if (!k1 || !k2) return false;
    const s1 = String(
        typeof k1 === "object"
            ? k1.value || k1.label || k1.jenis_alergi || ""
            : k1,
    )
        .trim()
        .toLowerCase();
    const s2 = String(
        typeof k2 === "object"
            ? k2.value || k2.label || k2.jenis_alergi || ""
            : k2,
    )
        .trim()
        .toLowerCase();
    if (s1 === s2) return true;
    const n1 = normalizeAllergyName(s1);
    const n2 = normalizeAllergyName(s2);
    return n1.length > 0 && n1 === n2;
}

// Daftar Jenis Alergi yang Terdeteksi (Hanya yang terdampak pada Work Order saat Mode Otomatis)
const detectedAlergiList = computed(() => {
    const list = new Set();

    if (labelMode.value === "auto") {
        const wo = activeWorkOrder.value;
        if (!wo) return [];

        // 1. Ambil dari akg_alergi yang tersimpan di WO (Kandungan gizi alergi rill)
        if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
            Object.keys(wo.akg_alergi).forEach((k) => {
                if (k && typeof k === "string" && k.trim()) {
                    list.add(k.trim());
                }
            });
        }

        // 2. Ambil dari sub_menu_alergi di WO (Menu pengganti diet khusus yang sudah diset)
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
                        typeof sm === "object"
                            ? sm.jenis_alergi?.value ||
                              sm.jenis_alergi ||
                              sm.alergen ||
                              sm.nama_alergi
                            : sm;
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

        // 3. Ambil dari items / bahan WO yang memiliki tipe_porsi 'alergi'
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

        // 4. Fallback jika list masih kosong tetapi ada total_alergi pada WO
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

        // Mode otomatis HANYA menampilkan alergi yang memang memiliki konfigurasi sub menu, AKG khusus, ATAU memiliki porsi > 0
        const validList = Array.from(list).filter((jenis) => {
            if (!jenis) return false;
            // Jika ada nilai AKG khusus di akg_alergi WO, wajib tampilkan
            if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
                if (
                    wo.akg_alergi[jenis] ||
                    Object.keys(wo.akg_alergi).some((k) =>
                        matchAllergyKey(k, jenis),
                    )
                ) {
                    return true;
                }
            }

            let inSubMenu = false;
            if (wo.sub_menu_alergi) {
                const subMenus = Array.isArray(wo.sub_menu_alergi)
                    ? wo.sub_menu_alergi
                    : typeof wo.sub_menu_alergi === "object"
                      ? Object.values(wo.sub_menu_alergi).flat()
                      : [];
                inSubMenu = subMenus.some((sm) => {
                    if (!sm) return false;
                    const smVal =
                        typeof sm === "object"
                            ? sm.jenis_alergi?.value ||
                              sm.jenis_alergi ||
                              sm.alergen ||
                              sm.nama_alergi
                            : sm;
                    return matchAllergyKey(smVal, jenis);
                });
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
                            if (matchAllergyKey(j, jenis)) {
                                count +=
                                    (Number(d.porsi_kecil) || 0) +
                                    (Number(d.porsi_besar) || 0) +
                                    (Number(d.jumlah) || 0);
                            }
                        } else if (
                            typeof d === "string" &&
                            matchAllergyKey(d.trim(), jenis)
                        ) {
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
        : [
              "Telur",
              "Seafood / Udang",
              "Kacang-kacangan",
              "Ikan",
              "Gluten / Gandum",
          ];
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
                        count +=
                            (Number(d.porsi_kecil) || 0) +
                            (Number(d.porsi_besar) || 0) +
                            (Number(d.jumlah) || 0);
                    }
                } else if (
                    typeof d === "string" &&
                    d.trim().toLowerCase() === jenis.toLowerCase()
                ) {
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
            menuItems:
                defaultMenu.length > 0
                    ? [...defaultMenu]
                    : [
                          "Nasi Putih",
                          `Lauk (Bebas ${jenis})`,
                          "Tempe",
                          "Sayur",
                          "Buah",
                      ],
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
        if (
            !manualAllergyConfigs.value[jenis].porsi ||
            manualAllergyConfigs.value[jenis].porsi <= 0
        ) {
            const initialPorsi = getInitialGroupAllergyPorsi(jenis);
            manualAllergyConfigs.value[jenis].porsi =
                initialPorsi > 0 ? initialPorsi : 1;
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
    if (
        !activeEditingAllergy.value &&
        selectedManualAllergies.value.length > 0
    ) {
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
    if (
        !force &&
        Object.keys(manualAllergyConfigs.value).length > 0 &&
        manualPorsiNormal.value > 0
    )
        return;

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
                    count =
                        (Number(d.porsi_kecil) || 0) +
                        (Number(d.porsi_besar) || 0) +
                        (Number(d.jumlah) || 0);
                } else if (typeof d === "string" && d.trim()) {
                    jenis = d.trim();
                    count = 1;
                }
                if (jenis) {
                    allergyPorsiMap[jenis] =
                        (allergyPorsiMap[jenis] || 0) + count;
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
                menuItems: menuItems.value.map((m, idx) =>
                    idx === 1 ? `${m} (Bebas ${jenis})` : m,
                ),
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
                menuItems: menuItems.value.map((m, idx) =>
                    idx === 1 ? `${m} (Bebas ${jenis})` : m,
                ),
                giziData: { ...giziData.value },
            };
        }
    });

    manualAllergyConfigs.value = configs;
    if (selectedManualAllergies.value.length === 0 || force) {
        selectedManualAllergies.value =
            initialSelected.length > 0
                ? initialSelected
                : detectedAlergiList.value.slice(0, 1);
    }
    if (!activeEditingAllergy.value) {
        activeEditingAllergy.value =
            selectedManualAllergies.value[0] ||
            detectedAlergiList.value[0] ||
            "";
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

// ─── State Tambahan Buffer / Cadangan Label untuk Antisipasi Kerusakan ────────
const activeBufferTab = ref("normal");
const extraLabelBuffers = ref({
    normal: {}, // { [kelompokId]: count }
    normalManual: 0,
    alergi: {}, // { [`${kelompokId || 'manual'}_${jenis.toLowerCase()}`]: count }
    tambahan: {
        organoleptik: 0,
        sampel: 0,
        buffer: 0,
    },
});

function getExtraBufferForKelompok(kelompokId) {
    if (kelompokId === undefined || kelompokId === null) return 0;
    const val = extraLabelBuffers.value.normal?.[kelompokId];
    return Math.max(0, parseInt(val, 10) || 0);
}

function setExtraBufferForKelompok(kelompokId, val) {
    const num = Math.max(0, parseInt(val, 10) || 0);
    extraLabelBuffers.value.normal = {
        ...extraLabelBuffers.value.normal,
        [kelompokId]: num,
    };
}

function adjustKelompokBuffer(kelompokId, delta) {
    const current = getExtraBufferForKelompok(kelompokId);
    setExtraBufferForKelompok(kelompokId, Math.max(0, current + delta));
}

function getExtraBufferForManualNormal() {
    return Math.max(0, parseInt(extraLabelBuffers.value.normalManual, 10) || 0);
}

function setManualNormalBuffer(val) {
    extraLabelBuffers.value.normalManual = Math.max(0, parseInt(val, 10) || 0);
}

function adjustManualNormalBuffer(delta) {
    const current = getExtraBufferForManualNormal();
    setManualNormalBuffer(Math.max(0, current + delta));
}

function getExtraBufferForAlergiItem(kelompokId, jenis) {
    const key = `${kelompokId || "manual"}_${(jenis || "").trim().toLowerCase()}`;
    const val = extraLabelBuffers.value.alergi?.[key];
    return Math.max(0, parseInt(val, 10) || 0);
}

function setExtraBufferForAlergiItem(kelompokId, jenis, val) {
    const key = `${kelompokId || "manual"}_${(jenis || "").trim().toLowerCase()}`;
    const num = Math.max(0, parseInt(val, 10) || 0);
    extraLabelBuffers.value.alergi = {
        ...extraLabelBuffers.value.alergi,
        [key]: num,
    };
}

function adjustAlergiItemBuffer(kelompokId, jenis, delta) {
    const current = getExtraBufferForAlergiItem(kelompokId, jenis);
    setExtraBufferForAlergiItem(
        kelompokId,
        jenis,
        Math.max(0, current + delta),
    );
}

function getExtraBufferForTambahan(subTipe) {
    const val = extraLabelBuffers.value.tambahan?.[subTipe];
    return Math.max(0, parseInt(val, 10) || 0);
}

function setExtraBufferForTambahan(subTipe, val) {
    const num = Math.max(0, parseInt(val, 10) || 0);
    extraLabelBuffers.value.tambahan = {
        ...extraLabelBuffers.value.tambahan,
        [subTipe]: num,
    };
}

function adjustTambahanBuffer(subTipe, delta) {
    const current = getExtraBufferForTambahan(subTipe);
    setExtraBufferForTambahan(subTipe, Math.max(0, current + delta));
}

// Quick Presets
function quickAddBufferNormal(amount) {
    if (labelMode.value === "manual") {
        setManualNormalBuffer(getExtraBufferForManualNormal() + amount);
    } else {
        const updated = { ...extraLabelBuffers.value.normal };
        printableKelompokList.value.forEach((k) => {
            updated[k.id] = (updated[k.id] || 0) + amount;
        });
        extraLabelBuffers.value.normal = updated;
    }
}

function quickAddBufferAlergi(amount) {
    const updated = { ...extraLabelBuffers.value.alergi };
    if (labelMode.value === "manual") {
        selectedManualAllergies.value.forEach((al) => {
            const key = `manual_${al.trim().toLowerCase()}`;
            updated[key] = (updated[key] || 0) + amount;
        });
    } else {
        printableKelompokList.value.forEach((k) => {
            const breakdown = getKelompokAlergiBreakdown(k);
            breakdown.forEach((b) => {
                const key = `${k.id}_${b.jenis.trim().toLowerCase()}`;
                updated[key] = (updated[key] || 0) + amount;
            });
        });
    }
    extraLabelBuffers.value.alergi = updated;
}

function quickAddBufferTambahan(amount) {
    extraLabelBuffers.value.tambahan = {
        organoleptik:
            (extraLabelBuffers.value.tambahan.organoleptik || 0) + amount,
        sampel: (extraLabelBuffers.value.tambahan.sampel || 0) + amount,
        buffer: (extraLabelBuffers.value.tambahan.buffer || 0) + amount,
    };
}

function autoFillA4EmptySlots() {
    // 1. Normal
    if (labelMode.value === "manual") {
        const count = Number(manualPorsiNormal.value) || 0;
        const rem = count % 9;
        const empty = rem > 0 ? 9 - rem : 0;
        setManualNormalBuffer(empty);
    } else {
        const updatedNormal = { ...extraLabelBuffers.value.normal };
        printableKelompokList.value.forEach((k) => {
            const base = getKelompokNormalBaseCount(k);
            const rem = base % 9;
            updatedNormal[k.id] = rem > 0 ? 9 - rem : 0;
        });
        extraLabelBuffers.value.normal = updatedNormal;
    }

    // 2. Alergi
    const updatedAlergi = { ...extraLabelBuffers.value.alergi };
    if (labelMode.value === "manual") {
        selectedManualAllergies.value.forEach((al) => {
            const cfg = manualAllergyConfigs.value[al];
            const base = Number(cfg?.porsi) || 0;
            const rem = base % 9;
            const key = `manual_${al.trim().toLowerCase()}`;
            updatedAlergi[key] = rem > 0 ? 9 - rem : 0;
        });
    } else {
        printableKelompokList.value.forEach((k) => {
            const breakdown = getKelompokAlergiBreakdown(k);
            breakdown.forEach((b) => {
                const rem = b.count % 9;
                const key = `${k.id}_${b.jenis.trim().toLowerCase()}`;
                updatedAlergi[key] = rem > 0 ? 9 - rem : 0;
            });
        });
    }
    extraLabelBuffers.value.alergi = updatedAlergi;

    // 3. Tambahan
    if (labelMode.value === "auto") {
        const pt = porsiTambahanData.value;
        const updatedTambahan = { ...extraLabelBuffers.value.tambahan };
        if (pt.organoleptik > 0) {
            const rem = pt.organoleptik % 9;
            updatedTambahan.organoleptik = rem > 0 ? 9 - rem : 0;
        }
        if (pt.sampel > 0) {
            const rem = pt.sampel % 9;
            updatedTambahan.sampel = rem > 0 ? 9 - rem : 0;
        }
        if (pt.buffer > 0) {
            const rem = pt.buffer % 9;
            updatedTambahan.buffer = rem > 0 ? 9 - rem : 0;
        }
        extraLabelBuffers.value.tambahan = updatedTambahan;
    }
}

const totalA4EmptySlots = computed(() => {
    let empty = 0;
    // Normal
    if (labelMode.value === "manual") {
        const rem = (Number(manualPorsiNormal.value) || 0) % 9;
        if (rem > 0) empty += 9 - rem;
    } else {
        printableKelompokList.value.forEach((k) => {
            const rem = getKelompokNormalBaseCount(k) % 9;
            if (rem > 0) empty += 9 - rem;
        });
    }
    // Alergi
    if (labelMode.value === "manual") {
        selectedManualAllergies.value.forEach((al) => {
            const base = Number(manualAllergyConfigs.value[al]?.porsi) || 0;
            const rem = base % 9;
            if (rem > 0) empty += 9 - rem;
        });
    } else {
        printableKelompokList.value.forEach((k) => {
            const breakdown = getKelompokAlergiBreakdown(k);
            breakdown.forEach((b) => {
                const rem = b.count % 9;
                if (rem > 0) empty += 9 - rem;
            });
        });
    }
    // Tambahan
    if (labelMode.value === "auto") {
        const pt = porsiTambahanData.value;
        if (pt.organoleptik > 0) {
            const rem = pt.organoleptik % 9;
            if (rem > 0) empty += 9 - rem;
        }
        if (pt.sampel > 0) {
            const rem = pt.sampel % 9;
            if (rem > 0) empty += 9 - rem;
        }
        if (pt.buffer > 0) {
            const rem = pt.buffer % 9;
            if (rem > 0) empty += 9 - rem;
        }
    }
    return empty;
});

function resetAllBuffers() {
    extraLabelBuffers.value = {
        normal: {},
        normalManual: 0,
        alergi: {},
        tambahan: {
            organoleptik: 0,
            sampel: 0,
            buffer: 0,
        },
    };
}

const totalExtraBufferNormal = computed(() => {
    if (labelMode.value === "manual") {
        return getExtraBufferForManualNormal();
    }
    return printableKelompokList.value.reduce((sum, k) => {
        return sum + getExtraBufferForKelompok(k.id);
    }, 0);
});

const totalExtraBufferAlergi = computed(() => {
    if (labelMode.value === "manual") {
        return selectedManualAllergies.value.reduce((sum, al) => {
            return sum + getExtraBufferForAlergiItem(null, al);
        }, 0);
    }
    let sum = 0;
    printableKelompokList.value.forEach((k) => {
        const breakdown = getKelompokAlergiBreakdown(k);
        breakdown.forEach((b) => {
            sum += getExtraBufferForAlergiItem(k.id, b.jenis);
        });
    });
    return sum;
});

const totalExtraBufferTambahan = computed(() => {
    return (
        getExtraBufferForTambahan("organoleptik") +
        getExtraBufferForTambahan("sampel") +
        getExtraBufferForTambahan("buffer")
    );
});

const totalExtraAllBuffers = computed(() => {
    return (
        totalExtraBufferNormal.value +
        totalExtraBufferAlergi.value +
        totalExtraBufferTambahan.value
    );
});

const activeAlergiBufferItems = computed(() => {
    if (labelMode.value === "manual") {
        return selectedManualAllergies.value.map((al) => {
            const cfg = manualAllergyConfigs.value[al];
            const baseCount = Number(cfg?.porsi) || 0;
            const extra = getExtraBufferForAlergiItem(null, al);
            return {
                kelompokId: null,
                namaKelompok: "Input Manual",
                jenis: al,
                baseCount,
                extra,
                total: baseCount + extra,
            };
        });
    }

    const items = [];
    printableKelompokList.value.forEach((k) => {
        const breakdown = getKelompokAlergiBreakdown(k);
        breakdown.forEach((b) => {
            const extra = getExtraBufferForAlergiItem(k.id, b.jenis);
            items.push({
                kelompokId: k.id,
                namaKelompok: k.nama_kelompok,
                jenis: b.jenis,
                baseCount: b.count,
                extra,
                total: b.count + extra,
            });
        });
    });
    return items;
});

function getKelompokNormalBaseCount(k) {
    if (!k) return 0;
    const pk = Number(k.total_porsi_kecil) || 0;
    const pb = Number(k.total_porsi_besar) || 0;
    const totalPm = Number(k.total_penerima) || pk + pb;
    const totalAlergi = getKelompokAlergiCount(k);
    return Math.max(0, totalPm - totalAlergi);
}

// Hitung Breakdown Porsi Normal vs Alergi
const totalPorsiAlergiBase = computed(() => {
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

const totalPorsiAlergi = computed(() => {
    return totalPorsiAlergiBase.value + totalExtraBufferAlergi.value;
});

const totalPorsiNormalBase = computed(() => {
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
        : Math.max(0, (totalWoPorsi.value || 0) - totalPorsiAlergiBase.value);
});

const totalPorsiNormal = computed(() => {
    return totalPorsiNormalBase.value + totalExtraBufferNormal.value;
});

// Tambahan Porsi Diluar Jumlah Penerima Manfaat (Uji Organoleptik, Sampel Makanan, Buffer Produksi)
const porsiTambahanData = computed(() => {
    if (labelMode.value !== "auto") {
        return {
            organoleptik: 0,
            sampel: 0,
            buffer: 0,
            total: 0,
        };
    }
    const wo = activeWorkOrder.value;
    if (!wo) {
        return { organoleptik: 0, sampel: 0, buffer: 0, total: 0 };
    }
    const raw = wo.porsi_tambahan;
    if (!raw) {
        const kpmCount =
            activeKelompokList.value.filter((k) => k.is_menerima !== false)
                .length || 0;
        const organoleptik = kpmCount * 1;
        const sampel = 4;
        return {
            organoleptik,
            sampel,
            buffer: 0,
            total: organoleptik + sampel,
        };
    }
    const pt = typeof raw === "string" ? JSON.parse(raw) : raw;
    const organoleptik =
        Number(
            pt.organoleptik?.total ??
                Number(pt.organoleptik?.pk || 0) +
                    Number(pt.organoleptik?.pb || 0),
        ) || 0;
    const sampel =
        Number(
            pt.sampel?.total ??
                Number(pt.sampel?.pk || 0) + Number(pt.sampel?.pb || 0),
        ) || 0;
    const buffer =
        Number(
            pt.buffer?.total ??
                Number(pt.buffer?.pk || 0) + Number(pt.buffer?.pb || 0),
        ) || 0;
    const total =
        Number(
            pt.total ??
                Number(pt.total_pk || 0) + Number(pt.total_pb || 0) ??
                organoleptik + sampel + buffer,
        ) || organoleptik + sampel + buffer;

    return {
        organoleptik,
        sampel,
        buffer,
        total,
    };
});

const totalPorsiTambahanBase = computed(() => porsiTambahanData.value.total);

const totalPorsiTambahan = computed(() => {
    return totalPorsiTambahanBase.value + totalExtraBufferTambahan.value;
});

const totalSemuaPorsi = computed(() => {
    return (
        totalPorsiNormal.value +
        totalPorsiAlergi.value +
        totalPorsiTambahan.value
    );
});

// Helper hitung jumlah porsi per jenis alergi (Mode Otomatis & Manual)
function getAllergyPorsiBaseCount(jenis) {
    if (labelMode.value === "manual") {
        if (!jenis || jenis === "Semua Alergi") {
            return totalPorsiAlergiBase.value;
        }
        return Number(manualAllergyConfigs.value[jenis]?.porsi) || 0;
    }
    // Mode Otomatis
    if (!jenis || jenis === "Semua Alergi") {
        return totalPorsiAlergiBase.value;
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

function getAllergyPorsiCount(jenis) {
    const base = getAllergyPorsiBaseCount(jenis);
    if (!jenis || jenis === "Semua Alergi") {
        return totalPorsiAlergi.value;
    }
    let extra = 0;
    if (labelMode.value === "manual") {
        extra = getExtraBufferForAlergiItem(null, jenis);
    } else {
        printableKelompokList.value.forEach((k) => {
            extra += getExtraBufferForAlergiItem(k.id, jenis);
        });
    }
    return base + extra;
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
                                ? affectedList.some((al) =>
                                      matchAllergyKey(al, j),
                                  )
                                : true
                            : matchAllergyKey(j, jenis);

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
                                ? affectedList.some((al) =>
                                      matchAllergyKey(al, j),
                                  )
                                : true
                            : matchAllergyKey(j, jenis);

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
                    !affectedList.some((al) => matchAllergyKey(al, j))
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
                    !affectedList.some((al) => matchAllergyKey(al, j))
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
    const found = items.find((it) =>
        matchAllergyKey(it.jenis, selectedJenisAlergi.value),
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
    return items.some((it) =>
        matchAllergyKey(it.jenis, selectedJenisAlergi.value),
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
    // Jangan ubah filterCetakTipe jika user telah memilih "semua" atau "pm"
    if (filterCetakTipe.value !== "semua" && filterCetakTipe.value !== "pm") {
        filterCetakTipe.value = tab;
    }
}

const lastSyncedWoId = ref(null);

// Helper mengambil menu pengganti alergi berdasarkan sub_menu 1-5 (atau lebih) dan sub_menu_alergi di WO
function getAllergyMenuItemsForTarget(
    targetAlergi,
    wo = activeWorkOrder.value,
) {
    if (!wo) return [...menuItemsAlergi.value];

    // 1. Dapatkan base sub menu normal dari WO
    let baseList = [];
    if (Array.isArray(wo.sub_menus) && wo.sub_menus.length > 0) {
        baseList = wo.sub_menus
            .map((s) => (s != null ? String(s).trim() : ""))
            .filter(Boolean);
    }
    if (baseList.length === 0) {
        const slots = [
            wo.sub_menu_1,
            wo.sub_menu_2,
            wo.sub_menu_3,
            wo.sub_menu_4,
            wo.sub_menu_5,
        ]
            .map((s) => (s != null ? String(s).trim() : ""))
            .filter(Boolean);
        if (slots.length > 0) {
            baseList = slots;
        }
    }
    if (
        baseList.length === 0 &&
        Array.isArray(wo.komponen) &&
        wo.komponen.length > 0
    ) {
        baseList = wo.komponen
            .map((s) => (s != null ? String(s).trim() : ""))
            .filter(Boolean);
    }
    if (baseList.length === 0) {
        baseList = [...menuItems.value];
    }

    if (baseList.length === 0) {
        return [...menuItemsAlergi.value];
    }

    const result = [...baseList];

    // Helper ekstraksi string nama pengganti
    const extractReplName = (rep) => {
        if (!rep) return "";
        if (
            typeof rep.menu_pengganti === "object" &&
            rep.menu_pengganti !== null
        ) {
            return (
                rep.menu_pengganti.nama || rep.menu_pengganti.nama_menu || ""
            );
        }
        if (rep.menu_pengganti && typeof rep.menu_pengganti === "string") {
            return rep.menu_pengganti.trim();
        }
        if (typeof rep.nama === "string" && rep.nama.trim()) {
            return rep.nama.trim();
        }
        if (typeof rep.nama_menu === "string" && rep.nama_menu.trim()) {
            return rep.nama_menu.trim();
        }
        if (typeof rep.item === "string" && rep.item.trim()) {
            return rep.item.trim();
        }
        return "";
    };

    // Helper ekstraksi nama jenis alergi
    const extractJenis = (rep) => {
        if (!rep) return "";
        const raw =
            rep.jenis_alergi?.value ||
            rep.jenis_alergi ||
            rep.alergen ||
            rep.nama_alergi ||
            "";
        return normalizeAllergyName(raw);
    };

    const targetNorm = normalizeAllergyName(targetAlergi);

    // Kumpulkan semua pengganti alergi berdasarkan indeks (0-based)
    const replacementMap = {};

    const checkAndSetReplacement = (slotIdx, rep) => {
        if (slotIdx < 0 || slotIdx >= result.length) return;
        const replName = extractReplName(rep);
        if (!replName || replName === "-") return;

        const jNorm = extractJenis(rep);
        const match =
            !targetNorm ||
            targetNorm === "semua alergi" ||
            !jNorm ||
            jNorm === targetNorm ||
            matchAllergyKey(targetNorm, jNorm);

        if (match) {
            if (!replacementMap[slotIdx] || (jNorm && jNorm === targetNorm)) {
                replacementMap[slotIdx] = replName;
            }
        }
    };

    const rawAlergi = wo.sub_menu_alergi;

    if (rawAlergi) {
        if (Array.isArray(rawAlergi)) {
            rawAlergi.forEach((rep) => {
                if (!rep) return;
                let slotIdx = -1;
                const subKey = rep.sub_menu_key || "";
                const keyMatch = subKey.match(/sub_menu_(\d+)/i);
                if (keyMatch) {
                    slotIdx = parseInt(keyMatch[1], 10) - 1;
                } else if (rep.sub_menu_ke != null) {
                    slotIdx = parseInt(rep.sub_menu_ke, 10) - 1;
                } else if (rep.index != null) {
                    slotIdx = parseInt(rep.index, 10);
                } else {
                    slotIdx = 1; // Default sub_menu_2
                }
                checkAndSetReplacement(slotIdx, rep);
            });
        } else if (typeof rawAlergi === "object") {
            Object.entries(rawAlergi).forEach(([key, val]) => {
                const keyMatch = key.match(/sub_menu_(\d+)/i);
                if (keyMatch) {
                    const slotIdx = parseInt(keyMatch[1], 10) - 1;
                    if (Array.isArray(val)) {
                        val.forEach((rep) =>
                            checkAndSetReplacement(slotIdx, rep),
                        );
                    } else if (val && typeof val === "object") {
                        checkAndSetReplacement(slotIdx, val);
                    }
                } else {
                    const jKeyNorm = normalizeAllergyName(key);
                    const match =
                        !targetNorm ||
                        targetNorm === "semua alergi" ||
                        jKeyNorm === targetNorm ||
                        matchAllergyKey(targetNorm, jKeyNorm);

                    if (match) {
                        if (Array.isArray(val)) {
                            val.forEach((rep) => {
                                let slotIdx = 1;
                                const subKey = rep?.sub_menu_key || "";
                                const km = subKey.match(/sub_menu_(\d+)/i);
                                if (km) slotIdx = parseInt(km[1], 10) - 1;
                                checkAndSetReplacement(slotIdx, rep);
                            });
                        } else if (val && typeof val === "object") {
                            Object.entries(val).forEach(
                                ([innerKey, innerVal]) => {
                                    const km =
                                        innerKey.match(/sub_menu_(\d+)/i);
                                    if (km) {
                                        const slotIdx = parseInt(km[1], 10) - 1;
                                        const repObj =
                                            typeof innerVal === "object"
                                                ? innerVal
                                                : {
                                                      menu_pengganti: innerVal,
                                                      jenis_alergi: key,
                                                  };
                                        checkAndSetReplacement(slotIdx, repObj);
                                    }
                                },
                            );
                        }
                    }
                }
            });
        }
    }

    // Cek juga wo.items jika ada yang bertipe 'alergi'
    if (Array.isArray(wo.items)) {
        wo.items.forEach((it) => {
            if (it.tipe_porsi === "alergi") {
                let slotIdx = 1;
                const km = (it.sub_menu_key || "").match(/sub_menu_(\d+)/i);
                if (km) slotIdx = parseInt(km[1], 10) - 1;
                const repObj = {
                    jenis_alergi: it.jenis_alergi,
                    menu_pengganti: it.nama_sub_menu || it.nama,
                };
                checkAndSetReplacement(slotIdx, repObj);
            }
        });
    }

    // Terapkan semua penggantian ke result
    Object.entries(replacementMap).forEach(([idxStr, replName]) => {
        const idx = parseInt(idxStr, 10);
        if (result[idx] !== undefined && replName) {
            result[idx] = replName;
        }
    });

    return result;
}

// Inisialisasi konfigurasi komponen menu dan gizi terpisah untuk tiap jenis alergi (Mode Otomatis)
function initAutoAllergyConfigs(
    wo = activeWorkOrder.value,
    forceReset = false,
) {
    if (!wo) return;
    const woIdentifier = String(
        wo.id || wo.nomor_wo || wo.uuid || wo.tanggal || "",
    );
    const isDifferentWo = lastSyncedWoId.value !== woIdentifier;

    // Jika berganti Work Order atau forceReset, konfigurasi lama HARUS di-reset bersih agar tidak nyangkut
    const configs =
        isDifferentWo || forceReset ? {} : { ...autoAllergyConfigs.value };

    const affectedSet = new Set(detectedAlergiList.value || []);
    if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
        Object.keys(wo.akg_alergi).forEach((k) => {
            if (k && k.trim()) affectedSet.add(k.trim());
        });
    }
    if (wo.sub_menu_alergi) {
        if (Array.isArray(wo.sub_menu_alergi)) {
            wo.sub_menu_alergi.forEach((rep) => {
                const j =
                    rep?.jenis_alergi?.value ||
                    rep?.jenis_alergi ||
                    rep?.alergen ||
                    rep?.nama_alergi;
                if (j && typeof j === "string" && j.trim())
                    affectedSet.add(j.trim());
            });
        } else if (typeof wo.sub_menu_alergi === "object") {
            Object.entries(wo.sub_menu_alergi).forEach(([k, val]) => {
                if (Array.isArray(val)) {
                    val.forEach((rep) => {
                        const j =
                            rep?.jenis_alergi?.value ||
                            rep?.jenis_alergi ||
                            rep?.alergen ||
                            rep?.nama_alergi;
                        if (j && typeof j === "string" && j.trim())
                            affectedSet.add(j.trim());
                    });
                } else if (!k.toLowerCase().startsWith("sub_menu_")) {
                    affectedSet.add(k.trim());
                }
            });
        }
    }
    const affectedList = Array.from(affectedSet);

    const pb = wo.akg_pb || {};
    const pk = wo.akg_pk || {};

    const baseGizi = {
        energi_pb: String(
            pb.energi ?? pb.energy ?? giziData.value?.energi_pb ?? "624",
        ),
        prot_pb: String(
            pb.protein ?? pb.prot ?? giziData.value?.prot_pb ?? "25.2",
        ),
        lmk_pb: String(pb.lemak ?? pb.lmk ?? giziData.value?.lmk_pb ?? "17.7"),
        karbo_pb: String(
            pb.karbohidrat ?? pb.karbo ?? giziData.value?.karbo_pb ?? "69.6",
        ),
        serat_pb: String(pb.serat ?? giziData.value?.serat_pb ?? "3.8"),
        energi_pk: String(
            pk.energi ?? pk.energy ?? giziData.value?.energi_pk ?? "384.2",
        ),
        prot_pk: String(
            pk.protein ?? pk.prot ?? giziData.value?.prot_pk ?? "21.4",
        ),
        lmk_pk: String(pk.lemak ?? pk.lmk ?? giziData.value?.lmk_pk ?? "14.2"),
        karbo_pk: String(
            pk.karbohidrat ?? pk.karbo ?? giziData.value?.karbo_pk ?? "45.7",
        ),
        serat_pk: String(pk.serat ?? giziData.value?.serat_pk ?? "2.6"),
    };

    affectedList.forEach((jenis) => {
        const existing = isDifferentWo || forceReset ? null : configs[jenis];

        // Cari nilai AKG spesifik alergi dari Work Order
        let akgSpecific = null;
        if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
            if (wo.akg_alergi[jenis]) {
                akgSpecific = wo.akg_alergi[jenis];
            } else {
                const foundEntry = Object.entries(wo.akg_alergi).find(([k]) =>
                    matchAllergyKey(k, jenis),
                );
                if (foundEntry) akgSpecific = foundEntry[1];
            }
        }

        let specificGizi = { ...baseGizi };
        if (
            akgSpecific &&
            (akgSpecific.akg_pb ||
                akgSpecific.akg_pk ||
                akgSpecific.pb ||
                akgSpecific.pk)
        ) {
            const specPb = akgSpecific.pb || akgSpecific.akg_pb || {};
            const specPk = akgSpecific.pk || akgSpecific.akg_pk || {};
            specificGizi = {
                energi_pb: String(
                    specPb.energi ?? specPb.energy ?? baseGizi.energi_pb,
                ),
                prot_pb: String(
                    specPb.protein ?? specPb.prot ?? baseGizi.prot_pb,
                ),
                lmk_pb: String(specPb.lemak ?? specPb.lemak ?? baseGizi.lmk_pb),
                karbo_pb: String(
                    specPb.karbohidrat ?? specPb.karbo ?? baseGizi.karbo_pb,
                ),
                serat_pb: String(specPb.serat ?? baseGizi.serat_pb),
                energi_pk: String(
                    specPk.energi ?? specPk.energy ?? baseGizi.energi_pk,
                ),
                prot_pk: String(
                    specPk.protein ?? specPk.prot ?? baseGizi.prot_pk,
                ),
                lmk_pk: String(specPk.lemak ?? specPk.lmk ?? baseGizi.lmk_pk),
                karbo_pk: String(
                    specPk.karbohidrat ?? specPk.karbo ?? baseGizi.karbo_pk,
                ),
                serat_pk: String(specPk.serat ?? baseGizi.serat_pk),
            };
        }

        configs[jenis] = {
            menuItems:
                existing?.menuItems && existing.menuItems.length > 0
                    ? [...existing.menuItems]
                    : getAllergyMenuItemsForTarget(jenis, wo),
            giziData: { ...specificGizi },
            tagAlergi: existing?.tagAlergi
                ? existing.tagAlergi.replace(/KHUSUS\s+ALERGI/gi, "ALERGI")
                : `⚠️ ALERGI: ${jenis.toUpperCase()}`,
        };
    });

    autoAllergyConfigs.value = configs;

    // Sinkronkan giziDataAlergi aktif dengan alergi yang sedang dipilih/diedit
    const targetAllergy =
        selectedJenisAlergi.value &&
        selectedJenisAlergi.value !== "Semua Alergi"
            ? selectedJenisAlergi.value
            : currentAutoEditingAllergy.value || affectedList[0];

    let targetCfg = targetAllergy ? configs[targetAllergy] : null;
    if (!targetCfg && targetAllergy) {
        const found = Object.entries(configs).find(([k]) =>
            matchAllergyKey(k, targetAllergy),
        );
        if (found) targetCfg = found[1];
    }

    if (targetCfg?.giziData) {
        giziDataAlergi.value = { ...targetCfg.giziData };
    }
}

function syncDataFromWorkOrder(wo, forceReset = false) {
    if (!wo) return;
    const woIdentifier = String(
        wo.id || wo.nomor_wo || wo.uuid || wo.tanggal || "",
    );
    const isDifferentWo = lastSyncedWoId.value !== woIdentifier;

    if (isDifferentWo || forceReset) {
        // Reset pilihan alergi & custom tag jika berpindah ke WO/tanggal yang berbeda
        selectedJenisAlergi.value = "Semua Alergi";
        activeEditingAllergyAuto.value = "";
        customTagAlergi.value = "";
        autoAllergyConfigs.value = {};
    }

    tanggalProduksi.value = wo.tanggal || todayStr;
    tanggalExpired.value = wo.tanggal || todayStr;

    // 1. Sinkronisasi komponen menu normal (Sub Menu dari WO)
    const normalSubMenus =
        Array.isArray(wo.sub_menus) && wo.sub_menus.length > 0
            ? wo.sub_menus.filter(Boolean)
            : [
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
    const pb = wo.akg_pb || {};
    const pk = wo.akg_pk || {};
    if (wo.akg_pb || wo.akg_pk) {
        giziData.value = {
            energi_pb: String(
                pb.energi ?? pb.energy ?? giziData.value?.energi_pb ?? "624",
            ),
            prot_pb: String(
                pb.protein ?? pb.prot ?? giziData.value?.prot_pb ?? "25.2",
            ),
            lmk_pb: String(
                pb.lemak ?? pb.lmk ?? giziData.value?.lmk_pb ?? "17.7",
            ),
            karbo_pb: String(
                pb.karbohidrat ??
                    pb.karbo ??
                    giziData.value?.karbo_pb ??
                    "69.6",
            ),
            serat_pb: String(pb.serat ?? giziData.value?.serat_pb ?? "3.8"),
            energi_pk: String(
                pk.energi ?? pk.energy ?? giziData.value?.energi_pk ?? "384.2",
            ),
            prot_pk: String(
                pk.protein ?? pk.prot ?? giziData.value?.prot_pk ?? "21.4",
            ),
            lmk_pk: String(
                pk.lemak ?? pk.lmk ?? giziData.value?.lmk_pk ?? "14.2",
            ),
            karbo_pk: String(
                pk.karbohidrat ??
                    pk.karbo ??
                    giziData.value?.karbo_pk ??
                    "45.7",
            ),
            serat_pk: String(pk.serat ?? giziData.value?.serat_pk ?? "2.6"),
        };
    }

    // 4. Inisialisasi konfigurasi menu dan gizi terpisah untuk tiap jenis alergi terdampak dari WO
    initAutoAllergyConfigs(wo, isDifferentWo || forceReset);
    lastSyncedWoId.value = woIdentifier;

    // 5. Sinkronisasi komponen menu & gizi alergi aktif
    const targetAllergy =
        selectedJenisAlergi.value &&
        selectedJenisAlergi.value !== "Semua Alergi"
            ? selectedJenisAlergi.value
            : currentAutoEditingAllergy.value || detectedAlergiList.value[0];

    let targetCfg = targetAllergy
        ? autoAllergyConfigs.value[targetAllergy]
        : null;
    if (!targetCfg && targetAllergy) {
        const found = Object.entries(autoAllergyConfigs.value).find(([k]) =>
            matchAllergyKey(k, targetAllergy),
        );
        if (found) targetCfg = found[1];
    }

    if (targetCfg) {
        menuItemsAlergi.value = [...targetCfg.menuItems];
        if (targetCfg.giziData) {
            giziDataAlergi.value = { ...targetCfg.giziData };
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
    } else if (val && val.startsWith("tambahan")) {
        activeLabelTab.value = "tambahan";
        if (val === "tambahan_sampel") selectedSubTambahan.value = "sampel";
        else if (val === "tambahan_buffer")
            selectedSubTambahan.value = "buffer";
        else if (val === "tambahan_organoleptik")
            selectedSubTambahan.value = "organoleptik";
    }
});

watch(
    [currentAutoEditingAllergy, selectedJenisAlergi],
    ([currAuto, selJenis]) => {
        if (labelMode.value === "auto" && activeWorkOrder.value) {
            const target =
                selJenis && selJenis !== "Semua Alergi"
                    ? selJenis
                    : currAuto || detectedAlergiList.value[0];
            let foundCfg = target ? autoAllergyConfigs.value[target] : null;
            if (!foundCfg && target) {
                const entry = Object.entries(autoAllergyConfigs.value).find(
                    ([k]) => matchAllergyKey(k, target),
                );
                if (entry) foundCfg = entry[1];
            }
            if (foundCfg) {
                menuItemsAlergi.value = [...foundCfg.menuItems];
                if (foundCfg.giziData) {
                    giziDataAlergi.value = { ...foundCfg.giziData };
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
                            k.is_menerima !== false &&
                            hasKelompokImpactedAlergi(k),
                    )
                    .map((k) => k.id);
            }
        }
    },
);

watch(
    [labelMode, activeWorkOrder, selectedWoId],
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

watch(
    autoAllergyConfigs,
    (newVal) => {
        if (labelMode.value === "auto" && currentAutoEditingAllergy.value) {
            const cfg = newVal[currentAutoEditingAllergy.value];
            if (cfg && Array.isArray(cfg.menuItems)) {
                menuItemsAlergi.value = [...cfg.menuItems];
            }
        }
    },
    { deep: true },
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
                        manualPorsiNormal.value =
                            Number(parsed.manual_porsi_normal) || 0;
                    }
                    if (parsed.manual_allergy_configs) {
                        manualAllergyConfigs.value =
                            parsed.manual_allergy_configs;
                    }
                    if (
                        Array.isArray(parsed.selected_manual_allergies) &&
                        parsed.selected_manual_allergies.length > 0
                    ) {
                        selectedManualAllergies.value =
                            parsed.selected_manual_allergies;
                        activeEditingAllergy.value =
                            parsed.selected_manual_allergies[0] || "";
                    }
                    if (parsed.extra_label_buffers) {
                        extraLabelBuffers.value = {
                            normal: parsed.extra_label_buffers.normal || {},
                            normalManual:
                                parsed.extra_label_buffers.normalManual || 0,
                            alergi: parsed.extra_label_buffers.alergi || {},
                            tambahan: {
                                organoleptik:
                                    parsed.extra_label_buffers.tambahan
                                        ?.organoleptik || 0,
                                sampel:
                                    parsed.extra_label_buffers.tambahan
                                        ?.sampel || 0,
                                buffer:
                                    parsed.extra_label_buffers.tambahan
                                        ?.buffer || 0,
                            },
                        };
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

// Responsive Preview Scaler: Menjaga proporsi & tata letak 1:1 identik di semua perangkat (Desktop, Tablet, HP)
const previewContainerRef = ref(null);
const previewScale = ref(1);

const updatePreviewScale = () => {
    if (!previewContainerRef.value) return;
    const containerW = previewContainerRef.value.clientWidth;
    const padding = containerW < 500 ? 24 : 40;
    const availW = Math.max(100, containerW - padding);
    const nativeW = selectedTemplateId.value === "template-02" ? 440 : 555;

    if (availW < nativeW) {
        previewScale.value = Number((availW / nativeW).toFixed(4));
    } else {
        previewScale.value = 1;
    }
};

const previewScalerStyle = computed(() => {
    const isTpl02 = selectedTemplateId.value === "template-02";
    const nativeW = isTpl02 ? 440 : 555;
    const nativeH = isTpl02 ? 305 : 370;
    const s = previewScale.value;

    if (s >= 1) {
        return {
            width: `${nativeW}px`,
            height: `${nativeH}px`,
            flexShrink: 0,
        };
    }

    const scaledW = Math.round(nativeW * s);
    const scaledH = Math.round(nativeH * s);

    return {
        width: `${nativeW}px`,
        height: `${nativeH}px`,
        transform: `scale(${s})`,
        transformOrigin: "center center",
        flexShrink: 0,
        margin: `-${Math.round((nativeH - scaledH) / 2)}px -${Math.round((nativeW - scaledW) / 2)}px`,
    };
});

onMounted(() => {
    if (!props.editingLabel) {
        initManualConfigsFromGroups();
    }
    nextTick(() => {
        updatePreviewScale();
        window.addEventListener("resize", updatePreviewScale);
        if (window.ResizeObserver && previewContainerRef.value) {
            const ro = new ResizeObserver(() => {
                updatePreviewScale();
            });
            ro.observe(previewContainerRef.value);
        }
    });
});

watch(selectedTemplateId, () => {
    nextTick(updatePreviewScale);
});

watch(
    () => props.kelompokList,
    (newList) => {
        if (
            newList &&
            newList.length > 0 &&
            !props.editingLabel &&
            manualPorsiNormal.value === 0
        ) {
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
        const baseKelompok = kelompokList[0] ||
            (props.kelompokList || [])[0] || {
                id: 1,
                nama_kelompok: namaSppg.value,
            };

        const normalList = [];
        const alergiList = [];

        // 1. Simpan Label Porsi Normal
        const count = Number(manualPorsiNormal.value) || 0;
        const extraNormal = getExtraBufferForManualNormal();
        const totalNormalCount = count + extraNormal;
        if (totalNormalCount > 0) {
            normalList.push({
                kelompok: baseKelompok,
                tipeLabel: "normal",
                jenisAlergi: "",
                tagAlergi: "",
                menuItems: [...menuItems.value],
                giziData: { ...giziData.value },
                count: totalNormalCount,
                baseCount: count,
                extraCount: extraNormal,
            });
        }

        // 2. Simpan Label Porsi Alergi per Alergi yang Dipilih
        selectedManualAllergies.value.forEach((jenis) => {
            const cfg = manualAllergyConfigs.value[jenis];
            const aCount = Number(cfg?.porsi) || 0;
            const extraAlergi = getExtraBufferForAlergiItem(null, jenis);
            const totalAlergiCount = aCount + extraAlergi;
            if (totalAlergiCount > 0) {
                alergiList.push({
                    kelompok: baseKelompok,
                    tipeLabel: "alergi",
                    jenisAlergi: jenis,
                    tagAlergi: customTagAlergi.value
                        ? customTagAlergi.value.replace(
                              /KHUSUS\s+ALERGI/gi,
                              "ALERGI",
                          )
                        : `⚠️ ALERGI: ${jenis.toUpperCase()}`,
                    menuItems: cfg?.menuItems
                        ? [...cfg.menuItems]
                        : [...menuItemsAlergi.value],
                    giziData: cfg?.giziData
                        ? { ...cfg.giziData }
                        : { ...giziDataAlergi.value },
                    count: totalAlergiCount,
                    baseCount: aCount,
                    extraCount: extraAlergi,
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
                    !affectedAlergiList.some((al) => matchAllergyKey(al, j))
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
                    !affectedAlergiList.some((al) => matchAllergyKey(al, j))
                ) {
                    continue;
                }
                totalAlergiInGroup += 1;
                alergiBreakdown.push({ jenis: j, count: 1 });
            }
        }

        const normalCount = Math.max(0, totalPm - totalAlergiInGroup);
        const extraNormal = getExtraBufferForKelompok(k.id);
        const totalNormalCount = normalCount + extraNormal;

        // 1. Simpan Label Porsi Normal kelompok ini
        if (totalNormalCount > 0) {
            normalList.push({
                kelompok: k,
                tipeLabel: "normal",
                jenisAlergi: "",
                tagAlergi: "",
                menuItems: [...menuItems.value],
                giziData: { ...giziData.value },
                count: totalNormalCount,
                baseCount: normalCount,
                extraCount: extraNormal,
            });
        }

        // 2. Simpan Label Porsi Alergi kelompok ini
        if (alergiBreakdown.length > 0) {
            for (const ab of alergiBreakdown) {
                if (
                    filterCetakTipe.value === "semua" ||
                    selectedJenisAlergi.value === "Semua Alergi" ||
                    matchAllergyKey(selectedJenisAlergi.value, ab.jenis)
                ) {
                    let cfg = autoAllergyConfigs.value[ab.jenis];
                    if (!cfg) {
                        const entry = Object.entries(
                            autoAllergyConfigs.value,
                        ).find(([k]) => matchAllergyKey(k, ab.jenis));
                        if (entry) cfg = entry[1];
                    }
                    const menuList =
                        cfg &&
                        Array.isArray(cfg.menuItems) &&
                        cfg.menuItems.length > 0
                            ? [...cfg.menuItems]
                            : getAllergyMenuItemsForTarget(
                                  ab.jenis,
                                  activeWorkOrder.value,
                              );
                    const giziItem =
                        cfg && cfg.giziData
                            ? { ...cfg.giziData }
                            : { ...giziDataAlergi.value };
                    const tagItem = cfg?.tagAlergi
                        ? cfg.tagAlergi.replace(/KHUSUS\s+ALERGI/gi, "ALERGI")
                        : customTagAlergi.value &&
                            matchAllergyKey(selectedJenisAlergi.value, ab.jenis)
                          ? customTagAlergi.value.replace(
                                /KHUSUS\s+ALERGI/gi,
                                "ALERGI",
                            )
                          : `⚠️ ALERGI: ${ab.jenis.toUpperCase()}`;

                    const extraAlergi = getExtraBufferForAlergiItem(
                        k.id,
                        ab.jenis,
                    );
                    const totalAlergiCount = ab.count + extraAlergi;

                    if (totalAlergiCount > 0) {
                        alergiList.push({
                            kelompok: k,
                            tipeLabel: "alergi",
                            jenisAlergi: ab.jenis,
                            tagAlergi: tagItem,
                            menuItems: menuList,
                            giziData: giziItem,
                            count: totalAlergiCount,
                            baseCount: ab.count,
                            extraCount: extraAlergi,
                        });
                    }
                }
            }
        }
    }

    // Porsi Tambahan (Uji Organoleptik, Sampel Makanan, Buffer Produksi)
    const tambahanList = [];
    const pt = porsiTambahanData.value;
    const baseUnitKelompok = {
        id: "unit-sppg-tambahan",
        nama_kelompok: namaSppg.value || "SPPG",
        kategori: "Internal SPPG",
    };

    const extraOrg = getExtraBufferForTambahan("organoleptik");
    const totalOrg = pt.organoleptik + extraOrg;
    if (totalOrg > 0) {
        tambahanList.push({
            kelompok: baseUnitKelompok,
            tipeLabel: "tambahan",
            subTipeTambahan: "organoleptik",
            jenisAlergi: "",
            tagAlergi: "🔬 UJI ORGANOLEPTIK",
            menuItems: [...menuItems.value],
            giziData: { ...giziData.value },
            count: totalOrg,
            baseCount: pt.organoleptik,
            extraCount: extraOrg,
        });
    }

    const extraSampel = getExtraBufferForTambahan("sampel");
    const totalSampel = pt.sampel + extraSampel;
    if (totalSampel > 0) {
        tambahanList.push({
            kelompok: baseUnitKelompok,
            tipeLabel: "tambahan",
            subTipeTambahan: "sampel",
            jenisAlergi: "",
            tagAlergi: "🧪 SAMPEL MAKANAN",
            menuItems: [...menuItems.value],
            giziData: { ...giziData.value },
            count: totalSampel,
            baseCount: pt.sampel,
            extraCount: extraSampel,
        });
    }

    const extraBuf = getExtraBufferForTambahan("buffer");
    const totalBuf = pt.buffer + extraBuf;
    if (totalBuf > 0) {
        tambahanList.push({
            kelompok: baseUnitKelompok,
            tipeLabel: "tambahan",
            subTipeTambahan: "buffer",
            jenisAlergi: "",
            tagAlergi: "📦 BUFFER PRODUKSI",
            menuItems: [...menuItems.value],
            giziData: { ...giziData.value },
            count: totalBuf,
            baseCount: pt.buffer,
            extraCount: extraBuf,
        });
    }

    if (tambahanList.length === 0 && pt.total > 0) {
        tambahanList.push({
            kelompok: baseUnitKelompok,
            tipeLabel: "tambahan",
            subTipeTambahan: "tambahan",
            jenisAlergi: "",
            tagAlergi: "➕ PORSI TAMBAHAN",
            menuItems: [...menuItems.value],
            giziData: { ...giziData.value },
            count: pt.total,
            baseCount: pt.total,
            extraCount: 0,
        });
    }

    if (filterCetakTipe.value === "normal") {
        return normalList;
    }
    if (filterCetakTipe.value === "alergi") {
        return alergiList;
    }
    if (filterCetakTipe.value === "pm") {
        return [...normalList, ...alergiList];
    }
    if (filterCetakTipe.value === "tambahan") {
        return tambahanList;
    }
    if (filterCetakTipe.value === "tambahan_organoleptik") {
        return tambahanList.filter((t) => t.subTipeTambahan === "organoleptik");
    }
    if (filterCetakTipe.value === "tambahan_sampel") {
        return tambahanList.filter((t) => t.subTipeTambahan === "sampel");
    }
    if (filterCetakTipe.value === "tambahan_buffer") {
        return tambahanList.filter((t) => t.subTipeTambahan === "buffer");
    }
    // "semua": Gabungkan semua Normal terlebih dahulu, lalu semua Alergi, dan Porsi Tambahan di akhir
    return [...normalList, ...alergiList, ...tambahanList];
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

// Daftar expanded khusus untuk mode Lembar A4 (9 label/halaman)
// Cadangan label langsung mengisi area kosong pada lembar jenis porsi tersebut tanpa membuat lembaran baru
const expandedPrintableItemsA4 = computed(() => {
    const expanded = [];
    for (const item of printableItems.value) {
        const base = Number(item.baseCount ?? item.count) || 0;
        const extra = Number(item.extraCount) || 0;
        if (base <= 0 && extra <= 0) continue;

        let totalForThisItem = base;
        if (extra > 0) {
            const rem = base % 9;
            const emptySlotsOnSheet = rem > 0 ? 9 - rem : 0;
            // Cadangan mengisi area kosong pada lembar jenis porsi tersebut (tidak membuat lembaran baru)
            const allowedExtra = Math.min(extra, emptySlotsOnSheet);
            totalForThisItem = base + allowedExtra;
        }

        for (let i = 0; i < totalForThisItem; i++) {
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
            extra_label_buffers: extraLabelBuffers.value,
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

// Target porsi aktif sesuai filter / tab cetak saat ini (Normal, Alergi, Tambahan, atau Semua)
const activeScopePorsiCount = computed(() => {
    if (filterCetakTipe.value === "normal") {
        return totalPorsiNormal.value;
    }
    if (filterCetakTipe.value === "alergi") {
        return totalPorsiAlergi.value;
    }
    if (filterCetakTipe.value === "pm") {
        return totalPorsiNormal.value + totalPorsiAlergi.value;
    }
    if (filterCetakTipe.value === "tambahan") {
        return totalPorsiTambahan.value;
    }
    if (filterCetakTipe.value === "tambahan_organoleptik") {
        return porsiTambahanData.value.organoleptik;
    }
    if (filterCetakTipe.value === "tambahan_sampel") {
        return porsiTambahanData.value.sampel;
    }
    if (filterCetakTipe.value === "tambahan_buffer") {
        return porsiTambahanData.value.buffer;
    }
    return totalSemuaPorsi.value;
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

    // Reset Cadangan Label
    resetAllBuffers();

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
        isItem &&
        itemOrKelompok.menuItems &&
        itemOrKelompok.menuItems.length > 0
            ? itemOrKelompok.menuItems
            : tipe === "alergi"
              ? labelMode.value === "auto"
                  ? autoAllergyConfigs.value[alergi]?.menuItems ||
                    getAllergyMenuItemsForTarget(alergi, activeWorkOrder.value)
                  : menuItemsAlergi.value
              : menuItems.value;

    const gData =
        isItem &&
        itemOrKelompok.giziData &&
        Object.keys(itemOrKelompok.giziData).length > 0
            ? itemOrKelompok.giziData
            : tipe === "alergi"
              ? labelMode.value === "auto"
                  ? autoAllergyConfigs.value[alergi]?.giziData ||
                    giziDataAlergi.value
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
    if (
        activeLabelTab.value === "normal" ||
        activeLabelTab.value === "tambahan"
    ) {
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
    if (jenis) {
        if (autoAllergyConfigs.value[jenis]?.menuItems) {
            return autoAllergyConfigs.value[jenis].menuItems;
        }
        const entry = Object.entries(autoAllergyConfigs.value).find(([k]) =>
            matchAllergyKey(k, jenis),
        );
        if (entry && entry[1]?.menuItems) {
            return entry[1].menuItems;
        }
    }
    return getAllergyMenuItemsForTarget(
        jenis || "Semua Alergi",
        activeWorkOrder.value,
    );
});

const currentPreviewGiziData = computed(() => {
    if (
        activeLabelTab.value === "normal" ||
        activeLabelTab.value === "tambahan"
    ) {
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
    if (jenis) {
        if (autoAllergyConfigs.value[jenis]?.giziData) {
            return autoAllergyConfigs.value[jenis].giziData;
        }
        const entry = Object.entries(autoAllergyConfigs.value).find(([k]) =>
            matchAllergyKey(k, jenis),
        );
        if (entry && entry[1]?.giziData) {
            return entry[1].giziData;
        }
    }
    return giziDataAlergi.value;
});

const selectedSubTambahan = ref("organoleptik");

const currentPreviewTag = computed(() => {
    if (activeLabelTab.value === "tambahan") {
        if (selectedSubTambahan.value === "sampel") return "🧪 SAMPEL MAKANAN";
        if (selectedSubTambahan.value === "buffer") return "📦 BUFFER PRODUKSI";
        return "🔬 UJI ORGANOLEPTIK";
    }
    if (activeLabelTab.value === "alergi") {
        if (customTagAlergi.value) {
            return customTagAlergi.value.replace(/KHUSUS\s+ALERGI/gi, "ALERGI");
        }
        return currentPreviewJenisAlergi.value
            ? `⚠️ ALERGI: ${currentPreviewJenisAlergi.value.toUpperCase()}`
            : "";
    }
    return "";
});

const previewKelompok = computed(() => {
    if (activeLabelTab.value === "tambahan") {
        return {
            id: "unit-sppg-tambahan",
            nama_kelompok: namaSppg.value || "SPPG",
            kategori: "Internal SPPG",
        };
    }
    return printableKelompokList.value[0] || activeKelompokList.value[0];
});

async function startDownload(type = null) {
    const selectedType = type || downloadFormatOption.value || "single";
    const itemsToPrint =
        selectedType === "a4" || selectedType === "custom"
            ? expandedPrintableItemsA4.value
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
              : filterCetakTipe.value === "tambahan"
                ? "Porsi Tambahan"
                : filterCetakTipe.value === "tambahan_organoleptik"
                  ? "Porsi Tambahan Organoleptik"
                  : filterCetakTipe.value === "tambahan_sampel"
                    ? "Porsi Tambahan Sampel"
                    : filterCetakTipe.value === "tambahan_buffer"
                      ? "Porsi Tambahan Buffer"
                      : filterCetakTipe.value === "pm"
                        ? "Porsi PM Saja"
                        : "Semua Porsi";

    downloadProgress.value = {
        current: 0,
        total: totalCount,
        totalPages:
            selectedType === "a4" || selectedType === "custom"
                ? calculateA4TotalPages(itemsToPrint, totalCount, 9)
                : totalCount,
        currentPage: 1,
        percentage: 0,
        etaText: "Menyiapkan render...",
        speedText: "",
        message: `Menyiapkan ${totalCount} kartu label resmi BGN (${tipeDesc})...`,
    };

    try {
        // 1. Prefix Sumber Data: WO vs Manual
        const sumberPrefix = labelMode.value === "manual" ? "Manual" : "WO";

        // 2. Format Tanggal YYYYMMDD
        const rawTgl = tanggalProduksi.value || todayStr;
        let yyyyMmDd = "";
        if (rawTgl && rawTgl.includes("-")) {
            const parts = rawTgl.split("-");
            if (parts.length === 3 && parts[0].length === 4) {
                yyyyMmDd = `${parts[0]}${parts[1].padStart(2, "0")}${parts[2].padStart(2, "0")}`;
            }
        }
        if (!yyyyMmDd || yyyyMmDd.length !== 8) {
            const d = new Date(rawTgl);
            if (!isNaN(d.getTime())) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, "0");
                const day = String(d.getDate()).padStart(2, "0");
                yyyyMmDd = `${y}${m}${day}`;
            } else {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, "0");
                const day = String(now.getDate()).padStart(2, "0");
                yyyyMmDd = `${y}${m}${day}`;
            }
        }

        // 3. Tipe Cetak: Tunggal, Kolase, Kustom
        const jenisCetak =
            selectedType === "single"
                ? "Tunggal"
                : selectedType === "custom"
                  ? "Kustom"
                  : "Kolase";

        // 4. ID SPPG
        const idSppg = (props.unitSppg?.id_sppg || "SPPG").trim();

        // 5. Kode Template (T1 = Template 01, T2 = Template 02)
        const templateCode = selectedTemplateId.value === "template-02" ? "T2" : "T1";

        // 6. Nama File PDF Standar Resmi
        const filename = `Label_${templateCode}_${jenisCetak}_${sumberPrefix}-${yyyyMmDd}_${idSppg}.pdf`;

        const globalConfig = {
            namaSppg: namaSppg.value,
            tanggalProduksi: tanggalProduksi.value,
            jamProduksi: jamProduksi.value,
            tampilkanJamProduksi: gunakanJamProduksi.value,
            jamExpired: jamExpired.value,
            tampilkanJamExpired: gunakanJamExpired.value,
            zonaWaktu: zonaWaktu.value,
            waktuMaksimal: waktuMaksimal.value,
            teksLaranganHeader: teksLaranganHeader.value,
            teksLaranganSub: teksLaranganSub.value,
            kontakBaris1: kontakPengaduanBaris1.value,
            kontakBaris2: kontakPengaduanBaris2.value,
            kontakBaris3: kontakPengaduanBaris3.value,
            kontakBaris4: kontakPengaduanBaris4.value,
            menuItems: menuItems.value,
            giziData: giziData.value,
        };

        if (selectedTemplateId.value === "template-02") {
            // Template 02 (Standar Layout Excel BGN - Pure Vector/Text PDF)
            if (selectedType === "single") {
                await downloadPdfTemplate02SingleMode({
                    printableItems: itemsToPrint,
                    printableKelompokList: printableKelompokList.value,
                    customCount: totalCount,
                    globalConfig,
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
                await downloadPdfTemplate02A4GridMode({
                    printableItems: itemsToPrint,
                    printableKelompokList: printableKelompokList.value,
                    customCount: totalCount,
                    globalConfig,
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
        } else {
            // Template 01 (Visual Kartu Grafis Modern)
            if (selectedType === "single") {
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
              : filterCetakTipe.value === "tambahan"
                ? "Porsi Tambahan"
                : filterCetakTipe.value === "tambahan_organoleptik"
                  ? "Porsi Tambahan Organoleptik"
                  : filterCetakTipe.value === "tambahan_sampel"
                    ? "Porsi Tambahan Sampel"
                    : filterCetakTipe.value === "tambahan_buffer"
                      ? "Porsi Tambahan Buffer"
                      : filterCetakTipe.value === "pm"
                        ? "Porsi PM Saja"
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
        if (selectedTemplateId.value === "template-02") {
            const globalConfig = {
                namaSppg: namaSppg.value,
                tanggalProduksi: tanggalProduksi.value,
                jamProduksi: jamProduksi.value,
                tampilkanJamProduksi: gunakanJamProduksi.value,
                jamExpired: jamExpired.value,
                tampilkanJamExpired: gunakanJamExpired.value,
                zonaWaktu: zonaWaktu.value,
                waktuMaksimal: waktuMaksimal.value,
                teksLaranganHeader: teksLaranganHeader.value,
                teksLaranganSub: teksLaranganSub.value,
                kontakBaris1: kontakPengaduanBaris1.value,
                kontakBaris2: kontakPengaduanBaris2.value,
                kontakBaris3: kontakPengaduanBaris3.value,
                kontakBaris4: kontakPengaduanBaris4.value,
                menuItems: menuItems.value,
                giziData: giziData.value,
            };
            await printPdfTemplate02SingleMode({
                printableItems: itemsToPrint,
                printableKelompokList: printableKelompokList.value,
                globalConfig,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = { ...downloadProgress.value, ...p };
                },
            });
        } else {
            await printPdfSingleMode({
                printableItems: itemsToPrint,
                printableKelompokList: printableKelompokList.value,
                getRenderElement: getSandboxCardElement,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = { ...downloadProgress.value, ...p };
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
                            <div class="flex items-center gap-3 flex-wrap">
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Tag class="h-5 w-5 text-primary" />
                                    <span>Konfigurasi Label</span>
                                </CardTitle>

                                <!-- Pilihan Template Desain: Template 01 vs Template 02 -->
                                <div
                                    class="flex items-center gap-1 p-1 bg-slate-200/90 rounded-xl border border-slate-300"
                                >
                                    <button
                                        type="button"
                                        @click="selectedTemplateId = 'template-01'"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center',
                                            selectedTemplateId === 'template-01'
                                                ? 'bg-white text-blue-900 shadow-2xs font-extrabold ring-1 ring-blue-300'
                                                : 'text-slate-600 hover:text-slate-900',
                                        ]"
                                    >
                                        <span>Template 01</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectedTemplateId = 'template-02'"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center',
                                            selectedTemplateId === 'template-02'
                                                ? 'bg-emerald-600 text-white shadow-2xs font-extrabold'
                                                : 'text-slate-600 hover:text-slate-900',
                                        ]"
                                    >
                                        <span>Template 02</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                            <!-- Filter Cetak: Semua / PM / Normal / Alergi / Tambahan -->

                            <select
                                v-model="filterCetakTipe"
                                class="h-8 sm:h-9 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg pl-2.5 sm:pl-3 pr-8 sm:pr-8.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                title="Pilih porsi yang akan dicetak atau diunduh"
                            >
                                <option value="semua">
                                    ✨ Semua Porsi
                                    {{
                                        totalPorsiTambahan > 0
                                            ? "(Normal, Alergi & Tambahan)"
                                            : "(Normal & Alergi)"
                                    }}
                                    [{{ totalSemuaPorsi }}]
                                </option>
                                <option
                                    v-if="totalPorsiTambahan > 0"
                                    value="pm"
                                >
                                    👥 Semua Porsi PM Saja [{{
                                        totalPorsiNormal + totalPorsiAlergi
                                    }}]
                                </option>
                                <option value="normal">
                                    🏷️ Hanya Porsi Normal ({{
                                        totalPorsiNormal
                                    }})
                                </option>
                                <option value="alergi">
                                    ⚠️ Hanya Porsi Alergi ({{
                                        totalPorsiAlergi
                                    }})
                                </option>
                                <option value="tambahan">
                                    ➕ Hanya Porsi Tambahan Saja ({{
                                        totalPorsiTambahan
                                    }})
                                </option>
                                <option
                                    v-if="porsiTambahanData.organoleptik > 0"
                                    value="tambahan_organoleptik"
                                >
                                    &nbsp;&nbsp;&nbsp;🔬 Uji Organoleptik ({{
                                        porsiTambahanData.organoleptik
                                    }})
                                </option>
                                <option
                                    v-if="porsiTambahanData.sampel > 0"
                                    value="tambahan_sampel"
                                >
                                    &nbsp;&nbsp;&nbsp;🧪 Sampel Makanan ({{
                                        porsiTambahanData.sampel
                                    }})
                                </option>
                                <option
                                    v-if="porsiTambahanData.buffer > 0"
                                    value="tambahan_buffer"
                                >
                                    &nbsp;&nbsp;&nbsp;📦 Buffer Produksi ({{
                                        porsiTambahanData.buffer
                                    }})
                                </option>
                            </select>

                            <!-- Tombol RESET KE AWAL -->
                            <Button
                                type="button"
                                @click="resetLabelToDefault"
                                :disabled="isSaving || isDownloading"
                                className="h-8 sm:h-9 px-2.5 sm:px-3.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 font-bold text-xs flex items-center gap-1.5 rounded-xl shadow-xs cursor-pointer"
                                title="Reset Konfigurasi Awal"
                            >
                                <RotateCcw
                                    class="h-4 w-4 text-slate-500 shrink-0"
                                />
                                <!-- <span class="hidden md:inline">Reset</span> -->
                            </Button>

                            <!-- Tombol SIMPAN LABEL (Hanya Icon Save) -->
                            <Button
                                type="button"
                                @click="saveLabelToDatabase"
                                :disabled="
                                    printableKelompokList.length === 0 ||
                                    isSaving
                                "
                                className="h-8 sm:h-9 px-2.5 sm:px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center rounded-xl shadow-xs cursor-pointer"
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
                                <Save
                                    v-else
                                    class="h-4 w-4 shrink-0"
                                />
                            </Button>

                            <!-- Select Format & Download PDF Button -->
                            <div
                                class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200 flex-wrap"
                            >
                                <select
                                    v-model="downloadFormatOption"
                                    class="h-8 sm:h-9 bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg pl-2.5 sm:pl-3 pr-8 sm:pr-8.5 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer"
                                >
                                    <option value="single">
                                        Label Tunggal
                                    </option>
                                    <option value="a4">
                                        Lembar A4 (9 Label)
                                    </option>
                                    <option value="custom">
                                        Kustom Jumlah (A4)
                                    </option>
                                </select>

                                <!-- Custom Count Input (Appears when Custom is selected) -->
                                <div
                                    v-if="downloadFormatOption === 'custom'"
                                    class="flex items-center gap-1 bg-white border border-slate-300 rounded-lg px-2 h-8 sm:h-9 shadow-2xs"
                                >
                                    <span
                                        class="text-[11px] font-bold text-slate-500"
                                        >Jml:</span
                                    >
                                    <input
                                        type="number"
                                        v-model.number="customLabelCount"
                                        min="1"
                                        max="50000"
                                        class="w-14 sm:w-16 h-6 text-center text-xs font-black text-slate-800 border-none p-0 focus:outline-none focus:ring-0"
                                        title="Jumlah label untuk dicetak"
                                    />
                                    <span
                                        class="text-[10px] font-semibold text-slate-400"
                                        >pcs</span
                                    >
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
                                    <Download
                                        v-else
                                        class="h-3.5 w-3.5 shrink-0"
                                    />
                                    <span class="hidden sm:inline"
                                        >Download PDF</span
                                    >
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
                                    <Database
                                        class="h-4 w-4 text-slate-500 shrink-0"
                                        title="Sumber Data"
                                    />
                                    <div
                                        class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200"
                                    >
                                        <button
                                            type="button"
                                            @click="setLabelMode('auto')"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center',
                                                labelMode === 'auto'
                                                    ? 'bg-white text-primary shadow-2xs font-extrabold'
                                                    : 'text-slate-600 hover:text-slate-900',
                                            ]"
                                        >
                                            <span>Otomatis (WO)</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="setLabelMode('manual')"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center',
                                                labelMode === 'manual'
                                                    ? 'bg-white text-amber-700 shadow-2xs font-extrabold'
                                                    : 'text-slate-600 hover:text-slate-900',
                                            ]"
                                        >
                                            <span>Manual (Kustom)</span>
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
                                            Data menu normal, menu porsi alergi,
                                            kandungan gizi, dan sasaran kelompok
                                            terhubung langsung dari Work Order.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pengaturan Dimensi & Area Tempel Isolasi (Berlaku untuk Mode Otomatis & Manual) -->
                    <Card className="bg-white border-slate-200/80 shadow-2xs">
                        <CardHeader
                            className="p-4 pb-2 border-b border-slate-100"
                        >
                            <CardTitle
                                class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                            >
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
                                    Pilih ukuran area kosong perekat pada bagian
                                    atas label.
                                </p>
                            </div>

                            <!-- Preset Cepat -->
                            <div
                                class="flex items-center gap-1.5 flex-wrap pt-1"
                            >
                                <span
                                    class="text-[11px] font-bold text-slate-400 mr-1"
                                    >Preset:</span
                                >
                                <button
                                    type="button"
                                    v-for="preset in [1.0, 1.5, 2.0, 2.5]"
                                    :key="preset"
                                    @click="tinggiIsolasiCm = preset"
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[11px] font-bold transition-colors cursor-pointer border',
                                        Number(tinggiIsolasiCm) === preset
                                            ? 'bg-primary text-white border-primary shadow-2xs font-extrabold'
                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200',
                                    ]"
                                >
                                    {{
                                        preset === 1.5
                                            ? "1.5 cm (Default)"
                                            : preset + " cm"
                                    }}
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pengaturan Kontak Pengaduan (Khusus Template 02: Tampil di Mode Otomatis WO maupun Input Manual) -->
                    <Card
                        v-if="selectedTemplateId === 'template-02'"
                        className="bg-white border-slate-200/80 shadow-2xs animate-in fade-in"
                    >
                        <CardHeader
                            className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between gap-2"
                        >
                            <CardTitle
                                class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                            >
                                <PhoneCall class="h-4 w-4 text-emerald-600" />
                                <span>Kontak Pengaduan (Template 02)</span>
                            </CardTitle>
                            <button
                                type="button"
                                @click="syncKontakOtomatis()"
                                class="px-2.5 py-1 rounded-md text-[11px] font-bold text-slate-600 hover:text-emerald-700 bg-slate-100 hover:bg-emerald-50 border border-slate-200 transition-all cursor-pointer flex items-center gap-1.5 shadow-2xs"
                                title="Kembalikan isi kontak pengaduan ke format standar SPPG"
                            >
                                <RotateCcw class="h-3 w-3" />
                                <span>Reset Default SPPG</span>
                            </button>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3 text-xs">
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                            >
                                <div>
                                    <label class="font-bold text-slate-600"
                                        >Baris 1 (Email):</label
                                    >
                                    <input
                                        v-model="kontakPengaduanBaris1"
                                        @input="isKontakEdited = true"
                                        type="text"
                                        placeholder="Email: halo@bgn.go.id"
                                        class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="font-bold text-slate-600"
                                        >Baris 2 (Instagram):</label
                                    >
                                    <input
                                        v-model="kontakPengaduanBaris2"
                                        @input="isKontakEdited = true"
                                        type="text"
                                        placeholder="Instagram: @sppg.tegallinggah"
                                        class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="font-bold text-slate-600"
                                        >Baris 3 (TikTok):</label
                                    >
                                    <input
                                        v-model="kontakPengaduanBaris3"
                                        @input="isKontakEdited = true"
                                        type="text"
                                        placeholder="Tiktok: @sppg.tegallinggah"
                                        class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="font-bold text-slate-600"
                                        >Baris 4 (Facebook / Lainnya):</label
                                    >
                                    <input
                                        v-model="kontakPengaduanBaris4"
                                        @input="isKontakEdited = true"
                                        type="text"
                                        placeholder="Facebook: SPPG Tegallinggah"
                                        class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                    />
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>
                                    * Kontak pengaduan ini aktif baik di Sumber Data Otomatis WO maupun Input Manual. Anda bebas mengubah teks secara langsung atau klik tombol reset untuk kembali ke data SPPG.
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- SASARAN JENIS ALERGEN (MODE OTOMATIS: HANYA YANG TERDAMPAK PADA WORK ORDER) -->
                    <Card
                        v-if="
                            labelMode === 'auto' &&
                            detectedAlergiList.length > 0
                        "
                        className="bg-amber-50/40 border-amber-200/80 shadow-2xs"
                    >
                        <CardHeader
                            className="p-4 pb-2 border-b border-amber-200/60"
                        >
                            <CardTitle
                                class="text-xs sm:text-sm font-bold text-amber-950 flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <ShieldAlert
                                        class="h-4 w-4 text-amber-600"
                                    />
                                    <span
                                        >Identitas & Sasaran Jenis Alergen
                                        (Terdampak WO)</span
                                    >
                                </div>
                                <span
                                    class="text-[11px] font-bold bg-amber-200 text-amber-900 px-2.5 py-0.5 rounded-full border border-amber-300"
                                >
                                    {{
                                        formatPorsiBreakdownText(
                                            selectedJenisAlergi,
                                        )
                                    }}
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3.5 text-xs">
                            <div>
                                <label
                                    class="font-bold text-amber-900 block mb-1.5"
                                >
                                    Target Jenis Alergi Terdampak Work Order:
                                </label>
                                <div
                                    class="flex items-center gap-1.5 flex-wrap"
                                >
                                    <button
                                        type="button"
                                        @click="
                                            selectedJenisAlergi = 'Semua Alergi'
                                        "
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5',
                                            selectedJenisAlergi ===
                                            'Semua Alergi'
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300',
                                        ]"
                                    >
                                        <span
                                            >✨ Semua Jenis Alergi
                                            (Dinamis)</span
                                        >
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                selectedJenisAlergi ===
                                                'Semua Alergi'
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900',
                                            ]"
                                        >
                                            {{
                                                formatPorsiBreakdownText(
                                                    "Semua Alergi",
                                                )
                                            }}
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
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300',
                                        ]"
                                    >
                                        <span>⚠️ {{ alergi }}</span>
                                        <span
                                            :class="[
                                                'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                selectedJenisAlergi === alergi
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900',
                                            ]"
                                        >
                                            {{
                                                formatPorsiBreakdownText(alergi)
                                            }}
                                        </span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-amber-800/80 mt-1.5">
                                    * Menampilkan jenis alergi yang memiliki
                                    menu pengganti/terdampak pada Work Order
                                    ini.
                                </p>
                            </div>

                            <div class="pt-2 border-t border-amber-200/50">
                                <label class="font-bold text-amber-900 block">
                                    Kustom Teks Banner Badge Alergi (Opsional):
                                </label>
                                <input
                                    v-model="customTagAlergi"
                                    type="text"
                                    placeholder="Otomatis (contoh: ⚠️ ALERGI: TELUR)"
                                    class="w-full mt-1 px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-bold text-amber-950 focus:ring-2 focus:ring-amber-400 outline-none"
                                />
                            </div>

                            <!-- Panel Rincian & Penyesuaian Sub Menu & Gizi Khusus Alergi Terpilih (Mode Otomatis) -->
                            <div
                                v-if="
                                    currentAutoEditingAllergy &&
                                    autoAllergyConfigs[
                                        currentAutoEditingAllergy
                                    ]
                                "
                                class="pt-3 border-t border-amber-200/80 space-y-3 bg-amber-50/50 p-3 rounded-xl border border-amber-200"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <Sparkles
                                            class="h-4 w-4 text-amber-600"
                                        />
                                        <span
                                            class="text-xs font-black text-amber-950"
                                        >
                                            Rincian Sub Menu & Nilai Gizi:
                                            {{ currentAutoEditingAllergy }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-[10px] bg-amber-200 text-amber-900 font-extrabold px-2 py-0.5 rounded-full border border-amber-300"
                                    >
                                        {{
                                            formatPorsiBreakdownText(
                                                currentAutoEditingAllergy,
                                            )
                                        }}
                                    </span>
                                </div>

                                <!-- Switcher Tab saat memilih Semua Alergi -->
                                <div
                                    v-if="
                                        selectedJenisAlergi ===
                                            'Semua Alergi' &&
                                        detectedAlergiList.length > 1
                                    "
                                    class="flex items-center gap-1.5 pb-2 border-b border-amber-200/70 flex-wrap"
                                >
                                    <span
                                        class="text-[10px] font-bold text-amber-900"
                                        >Edit Alergi:</span
                                    >
                                    <button
                                        type="button"
                                        v-for="a in detectedAlergiList"
                                        :key="a"
                                        @click="activeEditingAllergyAuto = a"
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all cursor-pointer border',
                                            currentAutoEditingAllergy === a
                                                ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                : 'bg-white text-slate-700 border-amber-200 hover:bg-amber-100/60',
                                        ]"
                                    >
                                        ⚠️ {{ a }} ({{
                                            formatPorsiBreakdownText(a)
                                        }})
                                    </button>
                                </div>

                                <!-- 1. Sub Menu Pengganti -->
                                <div class="space-y-1.5">
                                    <label
                                        class="font-bold text-slate-700 block text-[11px]"
                                    >
                                        Komponen Sub Menu (Pengganti Alergi
                                        {{ currentAutoEditingAllergy }}):
                                    </label>
                                    <div
                                        v-for="(
                                            sub, sIdx
                                        ) in autoAllergyConfigs[
                                            currentAutoEditingAllergy
                                        ].menuItems"
                                        :key="sIdx"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span
                                            class="font-mono font-bold text-amber-700 text-[11px] w-4"
                                            >{{ sIdx + 1 }}.</span
                                        >
                                        <input
                                            v-model="
                                                autoAllergyConfigs[
                                                    currentAutoEditingAllergy
                                                ].menuItems[sIdx]
                                            "
                                            type="text"
                                            class="flex-1 px-2.5 py-1 text-xs border border-amber-200 rounded-lg font-bold text-slate-800 focus:ring-1 focus:ring-amber-500 outline-none bg-white"
                                        />
                                    </div>
                                </div>

                                <!-- 2. Nilai Kandungan Gizi Khusus Alergi Ini -->
                                <div
                                    class="space-y-2 pt-2 border-t border-amber-200/60"
                                >
                                    <label
                                        class="font-bold text-slate-700 block text-[11px]"
                                    >
                                        Nilai Kandungan Gizi (AKG) Alergi
                                        {{ currentAutoEditingAllergy }}:
                                    </label>
                                    <!-- Porsi Besar -->
                                    <div
                                        class="bg-white p-2 rounded-lg border border-amber-200/80"
                                    >
                                        <p
                                            class="font-bold text-amber-900 text-[10px] mb-1"
                                        >
                                            Porsi Besar (PB):
                                        </p>
                                        <div class="grid grid-cols-5 gap-1">
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Energi</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.energi_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Protein</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.prot_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Lemak</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.lmk_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Karbo</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.karbo_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Serat</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.serat_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Porsi Kecil -->
                                    <div
                                        class="bg-white p-2 rounded-lg border border-amber-200/80"
                                    >
                                        <p
                                            class="font-bold text-amber-900 text-[10px] mb-1"
                                        >
                                            Porsi Kecil (PK):
                                        </p>
                                        <div class="grid grid-cols-5 gap-1">
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Energi</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.energi_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Protein</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.prot_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Lemak</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.lmk_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Karbo</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.karbo_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[9px] text-slate-500 block text-center"
                                                    >Serat</label
                                                >
                                                <input
                                                    v-model.number="
                                                        autoAllergyConfigs[
                                                            currentAutoEditingAllergy
                                                        ].giziData.serat_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 py-0.5 border border-slate-200 rounded text-center text-xs font-bold focus:ring-1 focus:ring-amber-500 outline-none"
                                                />
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
                        <div
                            class="p-1.5 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1"
                        >
                            <button
                                type="button"
                                @click="activeLabelTab = 'normal'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer',
                                    activeLabelTab === 'normal'
                                        ? 'bg-white text-blue-900 shadow-xs ring-1 ring-blue-300/60'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/50',
                                ]"
                            >
                                <Tag class="h-3.5 w-3.5 text-blue-600" />
                                <span>🏷️ Konfigurasi Porsi Normal</span>
                                <span
                                    class="bg-blue-100 text-blue-800 text-[10.5px] px-1.5 py-0.2 rounded-full font-bold"
                                >
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
                                        : 'text-amber-800 hover:text-amber-950 hover:bg-amber-100/50',
                                ]"
                            >
                                <AlertTriangle class="h-3.5 w-3.5" />
                                <span>⚠️ Konfigurasi Porsi Alergi</span>
                                <span
                                    class="bg-amber-100 text-amber-900 text-[10.5px] px-1.5 py-0.2 rounded-full font-bold"
                                >
                                    {{ totalPorsiAlergi }} Porsi
                                </span>
                            </button>
                        </div>

                        <!-- IDENTITAS & WAKTU -->
                        <Card
                            className="bg-white border-slate-200/80 shadow-2xs"
                        >
                            <CardHeader
                                className="p-4 pb-2 border-b border-slate-100"
                            >
                                <CardTitle
                                    class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Building2 class="h-4 w-4 text-primary" />
                                    <span>Identitas Header & Waktu</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-3"
                                >
                                    <div class="sm:col-span-2">
                                        <label class="font-bold text-slate-600"
                                            >Nama SPPG:</label
                                        >
                                        <input
                                            v-model="namaSppg"
                                            type="text"
                                            class="w-full mt-1 px-3 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-600"
                                            >Zona Waktu:</label
                                        >
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

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1"
                                >
                                    <div>
                                        <label class="font-bold text-slate-600"
                                            >Tanggal Produksi:</label
                                        >
                                        <input
                                            v-model="tanggalProduksi"
                                            type="date"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="font-bold text-slate-600"
                                                >Jam Produksi:</label
                                            >
                                            <label
                                                class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] font-bold text-slate-600"
                                            >
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
                                            type="time"
                                            :disabled="!gunakanJamProduksi"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="font-bold text-slate-600"
                                                >Jam Expired (Batas
                                                Konsumsi):</label
                                            >
                                            <label
                                                class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] font-bold text-slate-600"
                                            >
                                                <input
                                                    type="checkbox"
                                                    v-model="gunakanJamExpired"
                                                    class="rounded text-primary focus:ring-primary h-3.5 w-3.5"
                                                />
                                                <span
                                                    >Gunakan Jam pada Batas
                                                    Konsumsi</span
                                                >
                                            </label>
                                        </div>
                                        <input
                                            v-model="jamExpired"
                                            type="time"
                                            :disabled="!gunakanJamExpired"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-rose-600 focus:ring-2 focus:ring-rose-200 outline-none disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                        />
                                        <p
                                            class="text-[10.5px] text-slate-400 mt-1"
                                        >
                                            * Jika dicentang, kotak oranye
                                            'WAKTU MAKSIMAL KONSUMSI' akan
                                            otomatis menampilkan jam batas
                                            konsumsi (contoh: SEBELUM JAM 14:14
                                            WITA).
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Pengaturan Waktu Konsumsi & Teks Larangan -->
                        <Card
                            className="bg-white border-slate-200/80 shadow-2xs"
                        >
                            <CardHeader
                                className="p-4 pb-2 border-b border-slate-100"
                            >
                                <CardTitle
                                    class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Clock class="h-4 w-4 text-amber-600" />
                                    <span
                                        >Waktu Maksimal & Peringatan
                                        Larangan</span
                                    >
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <div>
                                    <label class="font-bold text-slate-600"
                                        >Waktu Maksimal Konsumsi:</label
                                    >
                                    <input
                                        v-model="waktuMaksimal"
                                        type="text"
                                        placeholder="2 JAM SETELAH DITERIMA!"
                                        class="w-full mt-1 px-3 py-1.5 border border-amber-200 bg-amber-50/50 rounded-lg font-black text-amber-900 focus:ring-2 focus:ring-amber-200 outline-none"
                                    />
                                </div>
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                >
                                    <div>
                                        <label class="font-bold text-slate-600"
                                            >Teks Larangan (Baris 1):</label
                                        >
                                        <input
                                            v-model="teksLaranganHeader"
                                            type="text"
                                            placeholder="MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT."
                                            class="w-full mt-1 px-3 py-1.5 border border-rose-200 bg-rose-50/50 rounded-lg font-bold text-rose-900 focus:ring-2 focus:ring-rose-200 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-600"
                                            >Teks Larangan (Baris 2):</label
                                        >
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
                            <Card
                                className="bg-white border-slate-200/80 shadow-2xs"
                            >
                                <CardHeader
                                    className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between"
                                >
                                    <CardTitle
                                        class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                    >
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
                                            <label
                                                class="font-bold text-slate-700 block mb-1"
                                            >
                                                Total Porsi Normal yang
                                                Dibuatkan Label:
                                            </label>
                                            <div class="relative">
                                                <input
                                                    v-model.number="
                                                        manualPorsiNormal
                                                    "
                                                    type="number"
                                                    min="0"
                                                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-black text-slate-900 text-sm focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                                <span
                                                    class="absolute right-3 top-2 text-xs font-bold text-slate-400"
                                                    >Porsi / Lembar</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        * Menentukan berapa banyak lembar stiker
                                        label kemasan porsi normal yang akan
                                        dicetak.
                                    </p>
                                </CardContent>
                            </Card>

                            <!-- Komponen Menu Makanan Normal -->
                            <Card
                                className="bg-white border-slate-200/80 shadow-2xs"
                            >
                                <CardHeader
                                    className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between"
                                >
                                    <CardTitle
                                        class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                    >
                                        <Utensils
                                            class="h-4 w-4 text-primary"
                                        />
                                        <span
                                            >Komponen Menu Makanan (Porsi
                                            Normal)</span
                                        >
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
                                        <span
                                            class="font-mono font-bold text-slate-400 w-4"
                                            >{{ idx + 1 }}.</span
                                        >
                                        <input
                                            v-model="menuItems[idx]"
                                            type="text"
                                            :placeholder="
                                                getMenuPlaceholder(idx)
                                            "
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
                            <Card
                                className="bg-white border-slate-200/80 shadow-2xs"
                            >
                                <CardHeader
                                    className="p-4 pb-2 border-b border-slate-100"
                                >
                                    <CardTitle
                                        class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                    >
                                        <Flame class="h-4 w-4 text-amber-500" />
                                        <span
                                            >Nilai Kandungan Gizi Porsi Normal
                                            (AKG)</span
                                        >
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3 text-xs">
                                    <!-- Porsi Besar -->
                                    <div>
                                        <p
                                            class="font-bold text-slate-700 mb-1.5"
                                        >
                                            Porsi Besar:
                                        </p>
                                        <div class="grid grid-cols-5 gap-1.5">
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Energi (kcal)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.energi_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Prot (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.prot_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Lmk (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.lmk_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Karbo (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.karbo_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Serat (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.serat_pb
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Porsi Kecil -->
                                    <div class="pt-2 border-t border-slate-100">
                                        <p
                                            class="font-bold text-slate-700 mb-1.5"
                                        >
                                            Porsi Kecil:
                                        </p>
                                        <div class="grid grid-cols-5 gap-1.5">
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Energi (kcal)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.energi_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Prot (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.prot_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Lmk (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.lmk_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Karbo (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.karbo_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10.5px] text-slate-500"
                                                    >Serat (g)</label
                                                >
                                                <input
                                                    v-model.number="
                                                        giziData.serat_pk
                                                    "
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-primary/20 outline-none"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </template>

                        <!-- SECTION 2: KONTEN PORSI ALERGI (DIET KHUSUS) -->
                        <template v-else-if="activeLabelTab === 'alergi'">
                            <!-- CARD 1: PILIH JENIS ALERGI YANG DIBUATKAN LABEL -->
                            <Card
                                className="bg-amber-50/40 border-amber-200/80 shadow-2xs"
                            >
                                <CardHeader
                                    className="p-4 pb-2 border-b border-amber-200/60 flex flex-row items-center justify-between flex-wrap gap-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <ShieldAlert
                                            class="h-4 w-4 text-amber-600"
                                        />
                                        <CardTitle
                                            class="text-xs sm:text-sm font-bold text-amber-950"
                                        >
                                            Pilih Jenis Alergi yang Dibuatkan
                                            Label
                                        </CardTitle>
                                    </div>
                                    <span
                                        class="text-[11px] font-extrabold bg-amber-200 text-amber-900 px-2.5 py-0.5 rounded-full border border-amber-300"
                                    >
                                        {{ selectedManualAllergies.length }}
                                        Alergi Dipilih • Total
                                        {{ totalPorsiAlergi }} Porsi
                                    </span>
                                </CardHeader>
                                <CardContent
                                    className="p-4 space-y-3.5 text-xs"
                                >
                                    <!-- Action Bar Pintas -->
                                    <div
                                        class="flex items-center justify-between gap-2 flex-wrap"
                                    >
                                        <label class="font-bold text-amber-900">
                                            Pilih Alergi Target:
                                        </label>
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="
                                                    selectAllDetectedAllergies
                                                "
                                                class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 text-amber-900 rounded-lg font-bold text-[11px] transition-colors cursor-pointer border border-amber-300"
                                            >
                                                Pilih Semua Alergi dari PM
                                            </button>
                                            <button
                                                type="button"
                                                @click="
                                                    clearAllSelectedAllergies
                                                "
                                                class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-700 hover:text-rose-800 rounded-lg font-bold text-[11px] transition-colors cursor-pointer border border-slate-200 hover:border-rose-300"
                                            >
                                                Kosongkan Pilihan
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Grid Pilihan Jenis Alergi -->
                                    <div
                                        class="flex items-center gap-1.5 flex-wrap"
                                    >
                                        <button
                                            type="button"
                                            v-for="alergi in availableAllergiesList"
                                            :key="alergi"
                                            @click="
                                                toggleAllergySelected(alergi)
                                            "
                                            :class="[
                                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5',
                                                selectedManualAllergies.includes(
                                                    alergi,
                                                )
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-amber-50 hover:border-amber-300',
                                            ]"
                                        >
                                            <Check
                                                v-if="
                                                    selectedManualAllergies.includes(
                                                        alergi,
                                                    )
                                                "
                                                class="h-3.5 w-3.5 text-white"
                                            />
                                            <span>⚠️ {{ alergi }}</span>
                                            <span
                                                v-if="
                                                    manualAllergyConfigs[alergi]
                                                        ?.porsi
                                                "
                                                :class="[
                                                    'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                    selectedManualAllergies.includes(
                                                        alergi,
                                                    )
                                                        ? 'bg-amber-700/80 text-white'
                                                        : 'bg-amber-100 text-amber-900',
                                                ]"
                                            >
                                                {{
                                                    manualAllergyConfigs[alergi]
                                                        .porsi
                                                }}
                                                Porsi
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Form Tambah Alergi Kustom -->
                                    <div
                                        class="pt-3 border-t border-amber-200/50 flex items-center gap-2"
                                    >
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
                            <Card
                                v-if="
                                    selectedManualAllergies.length > 0 &&
                                    activeEditingAllergy &&
                                    manualAllergyConfigs[activeEditingAllergy]
                                "
                                className="bg-white border-amber-200/80 shadow-2xs"
                            >
                                <CardHeader
                                    className="p-4 pb-2 border-b border-amber-100 bg-amber-50/40"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2 flex-wrap pb-2"
                                    >
                                        <CardTitle
                                            class="text-xs sm:text-sm font-bold text-amber-950 flex items-center gap-2"
                                        >
                                            <Utensils
                                                class="h-4 w-4 text-amber-600"
                                            />
                                            <span
                                                >Pengaturan Detail Alergi
                                                Terpilih</span
                                            >
                                        </CardTitle>
                                        <span
                                            class="text-[11px] font-bold text-amber-800"
                                        >
                                            Pilih tab alergi di bawah untuk
                                            mengedit menu & gizinya
                                        </span>
                                    </div>

                                    <!-- Sub-Pill Tab Alergi Aktif yang Sedang Dikonfigurasi -->
                                    <div
                                        class="flex items-center gap-1.5 overflow-x-auto py-1"
                                    >
                                        <button
                                            v-for="jenis in selectedManualAllergies"
                                            :key="jenis"
                                            type="button"
                                            @click="
                                                activeEditingAllergy = jenis;
                                                selectedJenisAlergi = jenis;
                                            "
                                            :class="[
                                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5 border',
                                                activeEditingAllergy === jenis
                                                    ? 'bg-amber-600 text-white border-amber-600 shadow-2xs font-black'
                                                    : 'bg-white text-amber-900 border-amber-200 hover:bg-amber-50',
                                            ]"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5"
                                            />
                                            <span>{{ jenis }}</span>
                                            <span
                                                :class="[
                                                    'px-1.5 py-0.2 rounded-full text-[10px] font-extrabold',
                                                    activeEditingAllergy ===
                                                    jenis
                                                        ? 'bg-amber-700 text-white'
                                                        : 'bg-amber-100 text-amber-900',
                                                ]"
                                            >
                                                {{
                                                    manualAllergyConfigs[jenis]
                                                        ?.porsi || 0
                                                }}
                                                Porsi
                                            </span>
                                        </button>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-4 space-y-4 text-xs">
                                    <!-- 1. Input Jumlah Porsi Khusus Alergi Ini -->
                                    <div
                                        class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="font-bold text-amber-950 text-xs"
                                            >
                                                Jumlah Porsi Label Alergi:
                                                <u class="text-amber-700">{{
                                                    activeEditingAllergy
                                                }}</u>
                                            </label>
                                            <span
                                                class="text-[11px] font-extrabold text-amber-800"
                                            >
                                                Target Cetak Stiker
                                            </span>
                                        </div>
                                        <div class="relative mt-1">
                                            <input
                                                v-model.number="
                                                    manualAllergyConfigs[
                                                        activeEditingAllergy
                                                    ].porsi
                                                "
                                                type="number"
                                                min="1"
                                                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg font-black text-amber-950 text-sm focus:ring-2 focus:ring-amber-400 outline-none"
                                            />
                                            <span
                                                class="absolute right-3 top-2.5 text-xs font-bold text-amber-600"
                                                >Porsi / Lembar</span
                                            >
                                        </div>
                                        <p
                                            class="text-[10.5px] text-amber-800/80"
                                        >
                                            * Menentukan berapa lembar stiker
                                            label kemasan yang dicetak khusus
                                            bertanda alergi
                                            {{ activeEditingAllergy }}.
                                        </p>
                                    </div>

                                    <!-- 2. Komponen Menu Pengganti untuk Alergi Ini -->
                                    <div
                                        class="space-y-2 pt-2 border-t border-slate-100"
                                    >
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-2"
                                        >
                                            <label
                                                class="font-bold text-slate-800 text-xs flex items-center gap-1.5"
                                            >
                                                <Utensils
                                                    class="h-3.5 w-3.5 text-amber-600"
                                                />
                                                <span
                                                    >Komponen Menu Pengganti
                                                    (Bebas
                                                    {{
                                                        activeEditingAllergy
                                                    }}):</span
                                                >
                                            </label>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <button
                                                    type="button"
                                                    @click="
                                                        copyMenuFromNormalToActiveAllergy
                                                    "
                                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-slate-200"
                                                    title="Salin komponen menu dari porsi normal"
                                                >
                                                    Salin dari Normal
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="
                                                        addCurrentAllergyMenuItem
                                                    "
                                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-amber-200"
                                                >
                                                    + Tambah Menu
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5">
                                            <div
                                                v-for="(
                                                    item, idx
                                                ) in manualAllergyConfigs[
                                                    activeEditingAllergy
                                                ].menuItems"
                                                :key="idx"
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-mono font-bold text-amber-600 w-4 text-[11px]"
                                                    >{{ idx + 1 }}.</span
                                                >
                                                <input
                                                    v-model="
                                                        manualAllergyConfigs[
                                                            activeEditingAllergy
                                                        ].menuItems[idx]
                                                    "
                                                    type="text"
                                                    :placeholder="
                                                        getMenuAlergiPlaceholder(
                                                            idx,
                                                        )
                                                    "
                                                    class="flex-1 px-3 py-1.5 border border-amber-200 bg-amber-50/30 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-amber-300 outline-none text-xs"
                                                />
                                                <button
                                                    type="button"
                                                    @click="
                                                        removeCurrentAllergyMenuItem(
                                                            idx,
                                                        )
                                                    "
                                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                    title="Hapus menu pengganti ini"
                                                >
                                                    <Trash2
                                                        class="h-3.5 w-3.5"
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Nilai Kandungan Gizi Khusus Alergi Ini -->
                                    <div
                                        class="space-y-2 pt-3 border-t border-slate-100"
                                    >
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-2"
                                        >
                                            <label
                                                class="font-bold text-slate-800 text-xs flex items-center gap-1.5"
                                            >
                                                <Flame
                                                    class="h-3.5 w-3.5 text-amber-500"
                                                />
                                                <span
                                                    >Nilai Kandungan Gizi Khusus
                                                    {{ activeEditingAllergy }}
                                                    (AKG):</span
                                                >
                                            </label>
                                            <button
                                                type="button"
                                                @click="
                                                    copyGiziFromNormalToActiveAllergy
                                                "
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition-colors cursor-pointer border border-slate-200"
                                                title="Salin nilai gizi dari porsi normal"
                                            >
                                                Salin Gizi dari Normal
                                            </button>
                                        </div>

                                        <!-- Porsi Besar -->
                                        <div>
                                            <p
                                                class="font-bold text-slate-700 text-[11px] mb-1"
                                            >
                                                Porsi Besar:
                                            </p>
                                            <div
                                                class="grid grid-cols-5 gap-1.5"
                                            >
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Energi (kcal)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.energi_pb
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Prot (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.prot_pb
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Lmk (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.lmk_pb
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Karbo (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.karbo_pb
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Serat (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.serat_pb
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Porsi Kecil -->
                                        <div class="pt-1.5">
                                            <p
                                                class="font-bold text-slate-700 text-[11px] mb-1"
                                            >
                                                Porsi Kecil:
                                            </p>
                                            <div
                                                class="grid grid-cols-5 gap-1.5"
                                            >
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Energi (kcal)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.energi_pk
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Prot (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.prot_pk
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Lmk (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.lmk_pk
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Karbo (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.karbo_pk
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="text-[10px] text-slate-500"
                                                        >Serat (g)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            manualAllergyConfigs[
                                                                activeEditingAllergy
                                                            ].giziData.serat_pk
                                                        "
                                                        type="number"
                                                        step="0.1"
                                                        min="0"
                                                        class="w-full mt-0.5 px-1 sm:px-2 py-1 border border-slate-200 rounded font-bold text-center text-xs focus:ring-2 focus:ring-amber-400 outline-none"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Kustom Teks Banner Badge Alergi -->
                                    <div class="pt-3 border-t border-slate-100">
                                        <label
                                            class="font-bold text-amber-900 block text-xs"
                                        >
                                            Kustom Teks Banner Badge Alergi
                                            (Opsional):
                                        </label>
                                        <input
                                            v-model="customTagAlergi"
                                            type="text"
                                            :placeholder="`Otomatis (contoh: ⚠️ ALERGI: ${activeEditingAllergy.toUpperCase()})`"
                                            class="w-full mt-1 px-3 py-1.5 bg-white border border-amber-300 rounded-lg font-bold text-amber-950 focus:ring-2 focus:ring-amber-400 outline-none text-xs"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Alert Info Jika Belum Ada Alergi yang Dipilih -->
                            <Card
                                v-else
                                className="bg-amber-50/40 border-amber-200/80 shadow-2xs"
                            >
                                <CardContent
                                    className="p-6 text-center space-y-2"
                                >
                                    <ShieldAlert
                                        class="h-8 w-8 text-amber-500 mx-auto"
                                    />
                                    <p class="font-bold text-amber-950 text-xs">
                                        Belum Ada Jenis Alergi yang Dipilih
                                    </p>
                                    <p
                                        class="text-[11px] text-amber-800/80 max-w-sm mx-auto"
                                    >
                                        Silakan klik satu atau beberapa jenis
                                        alergi pada daftar di atas untuk
                                        mengaktifkan cetakan label alergi dan
                                        mengatur menu pengganti serta nilai
                                        gizinya.
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
                                        <div
                                            class="flex items-center gap-1.5 flex-wrap"
                                        >
                                            <span class="font-bold">{{
                                                k.nama_kelompok
                                            }}</span>
                                            <!-- Breakdown porsi per jenis alergi (Mode Otomatis & Manual) -->
                                            <template
                                                v-if="
                                                    getKelompokAlergiBreakdown(
                                                        k,
                                                    ).length > 0
                                                "
                                            >
                                                <span
                                                    v-for="item in getKelompokAlergiBreakdown(
                                                        k,
                                                    )"
                                                    :key="item.jenis"
                                                    :class="[
                                                        'px-1.5 py-0.5 border rounded text-[10px] font-extrabold flex items-center gap-1 transition-all',
                                                        selectedJenisAlergi ===
                                                        item.jenis
                                                            ? 'bg-amber-600 text-white border-amber-700 shadow-2xs'
                                                            : 'bg-amber-100 text-amber-900 border-amber-200',
                                                    ]"
                                                    :title="`Alergi ${item.jenis}: ${item.count} Porsi`"
                                                >
                                                    ⚠️ {{ item.jenis }}:
                                                    {{ item.count }} Porsi
                                                </span>
                                            </template>
                                            <span
                                                v-else-if="
                                                    labelMode === 'manual' &&
                                                    hasKelompokImpactedAlergi(k)
                                                "
                                                class="px-1.5 py-0.5 bg-amber-100 text-amber-900 border border-amber-200 rounded text-[10px] font-extrabold"
                                            >
                                                ⚠️
                                                {{ getKelompokAlergiCount(k) }}
                                                Alergi
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
                                        v-if="
                                            getKelompokAlergiBreakdown(k)
                                                .length > 0
                                        "
                                        class="text-[10px] text-amber-800 font-bold block"
                                    >
                                        {{ getKelompokAlergiCount(k) }} Alergi
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- CARD TAMBAHAN LABEL CADANGAN (ANTISIPASI KERUSAKAN / KEKURANGAN) -->
                    <Card className="bg-white border-slate-200/80 shadow-2xs">
                        <CardHeader
                            className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between"
                        >
                            <CardTitle
                                class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                            >
                                <PlusCircle class="h-4 w-4 text-emerald-600" />
                                <span
                                    >Cadangan Label (Antisipasi Kerusakan)</span
                                >
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[11px] font-black',
                                        totalExtraAllBuffers > 0
                                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                                            : 'bg-slate-100 text-slate-500 border border-slate-200',
                                    ]"
                                >
                                    +{{ totalExtraAllBuffers }} Cadangan
                                </span>
                                <button
                                    v-if="totalExtraAllBuffers > 0"
                                    type="button"
                                    @click="resetAllBuffers"
                                    class="text-[11px] font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1 cursor-pointer"
                                    title="Reset semua cadangan ke 0"
                                >
                                    <RotateCcw class="h-3 w-3" />
                                    <span>Reset</span>
                                </button>
                            </div>
                        </CardHeader>

                        <CardContent className="p-4 space-y-3 text-xs">
                            <!-- Quick Action Preset Buttons -->
                            <div
                                class="p-2 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5"
                            >
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block"
                                >
                                    ⚡ Tambah Cepat Cadangan:
                                </span>
                                <div
                                    class="flex items-center gap-1.5 flex-wrap"
                                >
                                    <button
                                        type="button"
                                        @click="quickAddBufferNormal(1)"
                                        class="px-2.5 py-1 bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                    >
                                        <Plus class="h-3 w-3" />
                                        <span>1 Tiap Normal</span>
                                    </button>
                                    <button
                                        v-if="
                                            activeAlergiBufferItems.length > 0
                                        "
                                        type="button"
                                        @click="quickAddBufferAlergi(1)"
                                        class="px-2.5 py-1 bg-white hover:bg-amber-50 text-amber-700 border border-amber-200 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                    >
                                        <Plus class="h-3 w-3" />
                                        <span>1 Tiap Alergi</span>
                                    </button>
                                    <button
                                        v-if="
                                            labelMode === 'auto' &&
                                            porsiTambahanData.total > 0
                                        "
                                        type="button"
                                        @click="quickAddBufferTambahan(1)"
                                        class="px-2.5 py-1 bg-white hover:bg-sky-50 text-sky-700 border border-sky-200 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                    >
                                        <Plus class="h-3 w-3" />
                                        <span>1 Tiap Tambahan</span>
                                    </button>
                                    <button
                                        v-if="totalA4EmptySlots > 0"
                                        type="button"
                                        @click="autoFillA4EmptySlots"
                                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                        title="Otomatis isi slot kosong lembar A4 (hingga pas 9 label per halaman) tanpa menambah lembar baru"
                                    >
                                        <Sparkles
                                            class="h-3 w-3 text-emerald-600"
                                        />
                                        <span
                                            >Penuhi Area Kosong A4 (+{{
                                                totalA4EmptySlots
                                            }})</span
                                        >
                                    </button>
                                </div>
                                <div
                                    class="pt-1.5 border-t border-slate-200/70 text-[10.5px] text-slate-600 flex items-start gap-1.5"
                                >
                                    <Sparkles
                                        class="h-3.5 w-3.5 text-blue-600 shrink-0 mt-0.5"
                                    />
                                    <span>
                                        <strong
                                            >Mode Lembar A4 (9
                                            Label/Halaman):</strong
                                        >
                                        Cadangan otomatis langsung mengisi area
                                        kosong pada lembar jenis porsi tersebut
                                        tanpa membuat lembaran baru.
                                    </span>
                                </div>
                            </div>

                            <!-- Tabs Switcher for Types -->
                            <div
                                class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200"
                            >
                                <button
                                    type="button"
                                    @click="activeBufferTab = 'normal'"
                                    :class="[
                                        'flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1 cursor-pointer',
                                        activeBufferTab === 'normal'
                                            ? 'bg-white text-blue-700 shadow-2xs font-black'
                                            : 'text-slate-600 hover:text-slate-900',
                                    ]"
                                >
                                    <span>🏷️ Normal</span>
                                    <span
                                        v-if="totalExtraBufferNormal > 0"
                                        class="px-1.5 py-0.2 rounded-full text-[10px] bg-blue-100 text-blue-800 font-extrabold"
                                    >
                                        +{{ totalExtraBufferNormal }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeBufferTab = 'alergi'"
                                    :class="[
                                        'flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1 cursor-pointer',
                                        activeBufferTab === 'alergi'
                                            ? 'bg-white text-amber-700 shadow-2xs font-black'
                                            : 'text-slate-600 hover:text-slate-900',
                                    ]"
                                >
                                    <span>⚠️ Alergi</span>
                                    <span
                                        v-if="totalExtraBufferAlergi > 0"
                                        class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 text-amber-800 font-extrabold"
                                    >
                                        +{{ totalExtraBufferAlergi }}
                                    </span>
                                </button>
                                <button
                                    v-if="
                                        labelMode === 'auto' &&
                                        porsiTambahanData.total > 0
                                    "
                                    type="button"
                                    @click="activeBufferTab = 'tambahan'"
                                    :class="[
                                        'flex-1 py-1.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1 cursor-pointer',
                                        activeBufferTab === 'tambahan'
                                            ? 'bg-white text-sky-700 shadow-2xs font-black'
                                            : 'text-slate-600 hover:text-slate-900',
                                    ]"
                                >
                                    <span>➕ Tambahan</span>
                                    <span
                                        v-if="totalExtraBufferTambahan > 0"
                                        class="px-1.5 py-0.2 rounded-full text-[10px] bg-sky-100 text-sky-800 font-extrabold"
                                    >
                                        +{{ totalExtraBufferTambahan }}
                                    </span>
                                </button>
                            </div>

                            <!-- Content Tab: NORMAL -->
                            <div
                                v-if="activeBufferTab === 'normal'"
                                class="space-y-2"
                            >
                                <!-- Mode Manual Normal -->
                                <div
                                    v-if="labelMode === 'manual'"
                                    class="p-2.5 rounded-xl border border-blue-200 bg-blue-50/40 flex items-center justify-between"
                                >
                                    <div>
                                        <span
                                            class="font-bold text-slate-800 block text-xs"
                                            >Porsi Normal (Manual)</span
                                        >
                                        <div
                                            class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                        >
                                            <span
                                                >Dasar:
                                                {{ manualPorsiNormal || 0 }}
                                                Label</span
                                            >
                                            <span
                                                v-if="
                                                    (Number(
                                                        manualPorsiNormal,
                                                    ) || 0) %
                                                        9 >
                                                    0
                                                "
                                                class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                title="Sisa slot kosong di lembar A4 terakhir sebelum lembar baru"
                                            >
                                                Area Kosong A4:
                                                {{
                                                    9 -
                                                    ((Number(
                                                        manualPorsiNormal,
                                                    ) || 0) %
                                                        9)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="
                                                adjustManualNormalBuffer(-1)
                                            "
                                            class="w-7 h-7 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Minus class="h-3.5 w-3.5" />
                                        </button>
                                        <input
                                            type="number"
                                            min="0"
                                            max="500"
                                            :value="
                                                getExtraBufferForManualNormal()
                                            "
                                            @input="
                                                setManualNormalBuffer(
                                                    $event.target.value,
                                                )
                                            "
                                            class="w-14 text-center font-black font-mono py-1 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-blue-400 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="adjustManualNormalBuffer(1)"
                                            class="w-7 h-7 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                        </button>
                                        <span
                                            class="text-[11px] font-bold text-blue-900 ml-1"
                                        >
                                            =
                                            {{
                                                (Number(manualPorsiNormal) ||
                                                    0) +
                                                getExtraBufferForManualNormal()
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Mode Otomatis Normal Per Kelompok -->
                                <div
                                    v-else
                                    class="max-h-60 overflow-y-auto space-y-1.5 pr-0.5"
                                >
                                    <div
                                        v-for="k in printableKelompokList"
                                        :key="k.id"
                                        class="p-2 rounded-xl border border-slate-200 bg-slate-50/60 flex items-center justify-between"
                                    >
                                        <div class="min-w-0 pr-2">
                                            <span
                                                class="font-bold text-slate-800 block truncate text-xs"
                                                >{{ k.nama_kelompok }}</span
                                            >
                                            <div
                                                class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                            >
                                                <span
                                                    >Dasar:
                                                    {{
                                                        getKelompokNormalBaseCount(
                                                            k,
                                                        )
                                                    }}
                                                    pcs</span
                                                >
                                                <span
                                                    v-if="
                                                        getKelompokNormalBaseCount(
                                                            k,
                                                        ) %
                                                            9 >
                                                        0
                                                    "
                                                    class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                    title="Sisa slot kosong di lembar A4 terakhir kelompok ini"
                                                >
                                                    Area Kosong A4:
                                                    {{
                                                        9 -
                                                        (getKelompokNormalBaseCount(
                                                            k,
                                                        ) %
                                                            9)
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center gap-1.5 shrink-0"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    adjustKelompokBuffer(
                                                        k.id,
                                                        -1,
                                                    )
                                                "
                                                class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                            >
                                                <Minus class="h-3 w-3" />
                                            </button>
                                            <input
                                                type="number"
                                                min="0"
                                                max="500"
                                                :value="
                                                    getExtraBufferForKelompok(
                                                        k.id,
                                                    )
                                                "
                                                @input="
                                                    setExtraBufferForKelompok(
                                                        k.id,
                                                        $event.target.value,
                                                    )
                                                "
                                                class="w-12 text-center font-black font-mono py-0.5 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-blue-400 outline-none"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    adjustKelompokBuffer(
                                                        k.id,
                                                        1,
                                                    )
                                                "
                                                class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                            >
                                                <Plus class="h-3 w-3" />
                                            </button>
                                            <span
                                                class="text-[11px] font-bold text-blue-900 ml-1 min-w-[2.5rem] text-right font-mono"
                                            >
                                                =
                                                {{
                                                    getKelompokNormalBaseCount(
                                                        k,
                                                    ) +
                                                    getExtraBufferForKelompok(
                                                        k.id,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Tab: ALERGI -->
                            <div
                                v-if="activeBufferTab === 'alergi'"
                                class="space-y-2"
                            >
                                <div
                                    v-if="activeAlergiBufferItems.length === 0"
                                    class="p-4 rounded-xl border border-dashed border-amber-300 bg-amber-50/50 text-center"
                                >
                                    <p class="font-bold text-amber-900 text-xs">
                                        Tidak Ada Porsi Alergi Terpilih
                                    </p>
                                    <p
                                        class="text-[11px] text-amber-700 mt-0.5"
                                    >
                                        Porsi alergi akan muncul otomatis bila
                                        ada kelompok sasaran dengan data alergi
                                        atau jenis alergi manual yang dipilih.
                                    </p>
                                </div>
                                <div
                                    v-else
                                    class="max-h-60 overflow-y-auto space-y-1.5 pr-0.5"
                                >
                                    <div
                                        v-for="(
                                            item, idx
                                        ) in activeAlergiBufferItems"
                                        :key="`${item.kelompokId || 'manual'}_${item.jenis}_${idx}`"
                                        class="p-2 rounded-xl border border-amber-200 bg-amber-50/40 flex items-center justify-between"
                                    >
                                        <div class="min-w-0 pr-2 space-y-0.5">
                                            <div
                                                class="flex items-center gap-1.5 flex-wrap"
                                            >
                                                <span
                                                    class="font-bold text-slate-800 text-xs truncate max-w-[150px]"
                                                >
                                                    {{ item.namaKelompok }}
                                                </span>
                                                <span
                                                    class="px-1.5 py-0.2 bg-amber-200/90 text-amber-950 font-black rounded text-[10px]"
                                                >
                                                    ⚠️ {{ item.jenis }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                            >
                                                <span
                                                    >Dasar:
                                                    {{ item.baseCount }}
                                                    pcs</span
                                                >
                                                <span
                                                    v-if="
                                                        item.baseCount % 9 > 0
                                                    "
                                                    class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                    title="Sisa slot kosong di lembar A4 terakhir kelompok alergi ini"
                                                >
                                                    Area Kosong A4:
                                                    {{
                                                        9 - (item.baseCount % 9)
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center gap-1.5 shrink-0"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    adjustAlergiItemBuffer(
                                                        item.kelompokId,
                                                        item.jenis,
                                                        -1,
                                                    )
                                                "
                                                class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                            >
                                                <Minus class="h-3 w-3" />
                                            </button>
                                            <input
                                                type="number"
                                                min="0"
                                                max="500"
                                                :value="item.extra"
                                                @input="
                                                    setExtraBufferForAlergiItem(
                                                        item.kelompokId,
                                                        item.jenis,
                                                        $event.target.value,
                                                    )
                                                "
                                                class="w-12 text-center font-black font-mono py-0.5 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-amber-400 outline-none"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    adjustAlergiItemBuffer(
                                                        item.kelompokId,
                                                        item.jenis,
                                                        1,
                                                    )
                                                "
                                                class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                            >
                                                <Plus class="h-3 w-3" />
                                            </button>
                                            <span
                                                class="text-[11px] font-bold text-amber-900 ml-1 min-w-[2.5rem] text-right font-mono"
                                            >
                                                = {{ item.total }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Tab: TAMBAHAN -->
                            <div
                                v-if="
                                    activeBufferTab === 'tambahan' &&
                                    labelMode === 'auto' &&
                                    porsiTambahanData.total > 0
                                "
                                class="space-y-1.5"
                            >
                                <!-- Organoleptik -->
                                <div
                                    v-if="porsiTambahanData.organoleptik > 0"
                                    class="p-2 rounded-xl border border-sky-200 bg-sky-50/40 flex items-center justify-between"
                                >
                                    <div>
                                        <span
                                            class="font-bold text-slate-800 block text-xs"
                                            >🔬 Uji Organoleptik</span
                                        >
                                        <div
                                            class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                        >
                                            <span
                                                >Dasar:
                                                {{
                                                    porsiTambahanData.organoleptik
                                                }}
                                                pcs</span
                                            >
                                            <span
                                                v-if="
                                                    porsiTambahanData.organoleptik %
                                                        9 >
                                                    0
                                                "
                                                class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                title="Sisa slot kosong di lembar A4 terakhir uji organoleptik"
                                            >
                                                Area Kosong A4:
                                                {{
                                                    9 -
                                                    (porsiTambahanData.organoleptik %
                                                        9)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'organoleptik',
                                                    -1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Minus class="h-3 w-3" />
                                        </button>
                                        <input
                                            type="number"
                                            min="0"
                                            max="500"
                                            :value="
                                                getExtraBufferForTambahan(
                                                    'organoleptik',
                                                )
                                            "
                                            @input="
                                                setExtraBufferForTambahan(
                                                    'organoleptik',
                                                    $event.target.value,
                                                )
                                            "
                                            class="w-12 text-center font-black font-mono py-0.5 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-sky-400 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'organoleptik',
                                                    1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Plus class="h-3 w-3" />
                                        </button>
                                        <span
                                            class="text-[11px] font-bold text-sky-900 ml-1 min-w-[2.5rem] text-right font-mono"
                                        >
                                            =
                                            {{
                                                porsiTambahanData.organoleptik +
                                                getExtraBufferForTambahan(
                                                    "organoleptik",
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Sampel Makanan -->
                                <div
                                    v-if="porsiTambahanData.sampel > 0"
                                    class="p-2 rounded-xl border border-sky-200 bg-sky-50/40 flex items-center justify-between"
                                >
                                    <div>
                                        <span
                                            class="font-bold text-slate-800 block text-xs"
                                            >🧪 Sampel Makanan</span
                                        >
                                        <div
                                            class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                        >
                                            <span
                                                >Dasar:
                                                {{ porsiTambahanData.sampel }}
                                                pcs</span
                                            >
                                            <span
                                                v-if="
                                                    porsiTambahanData.sampel %
                                                        9 >
                                                    0
                                                "
                                                class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                title="Sisa slot kosong di lembar A4 terakhir sampel makanan"
                                            >
                                                Area Kosong A4:
                                                {{
                                                    9 -
                                                    (porsiTambahanData.sampel %
                                                        9)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'sampel',
                                                    -1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Minus class="h-3 w-3" />
                                        </button>
                                        <input
                                            type="number"
                                            min="0"
                                            max="500"
                                            :value="
                                                getExtraBufferForTambahan(
                                                    'sampel',
                                                )
                                            "
                                            @input="
                                                setExtraBufferForTambahan(
                                                    'sampel',
                                                    $event.target.value,
                                                )
                                            "
                                            class="w-12 text-center font-black font-mono py-0.5 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-sky-400 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'sampel',
                                                    1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Plus class="h-3 w-3" />
                                        </button>
                                        <span
                                            class="text-[11px] font-bold text-sky-900 ml-1 min-w-[2.5rem] text-right font-mono"
                                        >
                                            =
                                            {{
                                                porsiTambahanData.sampel +
                                                getExtraBufferForTambahan(
                                                    "sampel",
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Buffer Produksi -->
                                <div
                                    v-if="porsiTambahanData.buffer > 0"
                                    class="p-2 rounded-xl border border-sky-200 bg-sky-50/40 flex items-center justify-between"
                                >
                                    <div>
                                        <span
                                            class="font-bold text-slate-800 block text-xs"
                                            >📦 Buffer Produksi</span
                                        >
                                        <div
                                            class="flex items-center gap-1.5 text-[11px] text-slate-500"
                                        >
                                            <span
                                                >Dasar:
                                                {{ porsiTambahanData.buffer }}
                                                pcs</span
                                            >
                                            <span
                                                v-if="
                                                    porsiTambahanData.buffer %
                                                        9 >
                                                    0
                                                "
                                                class="text-[10px] text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200 font-semibold"
                                                title="Sisa slot kosong di lembar A4 terakhir buffer produksi"
                                            >
                                                Area Kosong A4:
                                                {{
                                                    9 -
                                                    (porsiTambahanData.buffer %
                                                        9)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'buffer',
                                                    -1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Minus class="h-3 w-3" />
                                        </button>
                                        <input
                                            type="number"
                                            min="0"
                                            max="500"
                                            :value="
                                                getExtraBufferForTambahan(
                                                    'buffer',
                                                )
                                            "
                                            @input="
                                                setExtraBufferForTambahan(
                                                    'buffer',
                                                    $event.target.value,
                                                )
                                            "
                                            class="w-12 text-center font-black font-mono py-0.5 px-1 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-sky-400 outline-none"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                adjustTambahanBuffer(
                                                    'buffer',
                                                    1,
                                                )
                                            "
                                            class="w-6 h-6 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 flex items-center justify-center text-slate-700 transition cursor-pointer"
                                        >
                                            <Plus class="h-3 w-3" />
                                        </button>
                                        <span
                                            class="text-[11px] font-bold text-sky-900 ml-1 min-w-[2.5rem] text-right font-mono"
                                        >
                                            =
                                            {{
                                                porsiTambahanData.buffer +
                                                getExtraBufferForTambahan(
                                                    "buffer",
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Info Ringkasan -->
                            <div
                                class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500"
                            >
                                <span
                                    >Cadangan Aktif:
                                    <strong class="text-emerald-700"
                                        >+{{
                                            totalExtraAllBuffers
                                        }}
                                        Label</strong
                                    ></span
                                >
                                <span class="font-bold text-slate-700"
                                    >Total Semua Label:
                                    {{ totalSemuaPorsi }} pcs</span
                                >
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ================= RIGHT COLUMN: LIVE PREVIEW ================= -->
                <div class="lg:col-span-6 space-y-4">
                    <div class="sticky top-6 space-y-3">
                        <div
                            class="flex items-center justify-between gap-2 flex-wrap"
                        >
                            <h3
                                class="text-sm font-black text-slate-900 flex items-center gap-2"
                            >
                                <Sparkles class="h-4 w-4 text-primary" />
                                <span>Live Preview Desain Label</span>
                            </h3>
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="selectedTemplateId === 'template-02'"
                                    class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <FileText class="h-3 w-3" />
                                    <span>Template 02 (Excel)</span>
                                </span>
                                <span
                                    v-else
                                    class="bg-blue-100 text-blue-800 border border-blue-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full flex items-center gap-1"
                                >
                                    <Sparkles class="h-3 w-3 text-blue-600" />
                                    <span>Template 01 (Modern)</span>
                                </span>
                                <span
                                    class="text-[11px] text-slate-500 font-medium"
                                >
                                    Stiker Kemasan
                                </span>
                            </div>
                        </div>

                        <!-- Segmented Switch Preview: Normal vs Alergi vs Tambahan -->
                        <div
                            class="flex items-center justify-between gap-2 p-1.5 bg-slate-200/80 rounded-2xl border border-slate-300/80"
                        >
                            <div class="flex items-center gap-1 flex-1">
                                <button
                                    type="button"
                                    @click="setActiveLabelTab('normal')"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        activeLabelTab === 'normal'
                                            ? 'bg-white text-blue-900 shadow-xs ring-1 ring-blue-400/40'
                                            : 'text-slate-600 hover:text-slate-900 hover:bg-white/40',
                                    ]"
                                >
                                    <Tag class="h-3.5 w-3.5 text-blue-600" />
                                    <span>🏷️ Normal</span>
                                    <span
                                        class="bg-blue-100 text-blue-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold flex items-center gap-1"
                                    >
                                        <span>{{ totalPorsiNormal }}</span>
                                        <span
                                            v-if="totalExtraBufferNormal > 0"
                                            class="text-blue-600 font-extrabold text-[9px]"
                                            title="Termasuk Cadangan"
                                        >
                                            (+{{ totalExtraBufferNormal }})
                                        </span>
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="setActiveLabelTab('alergi')"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        activeLabelTab === 'alergi'
                                            ? 'bg-amber-600 text-white shadow-xs'
                                            : 'text-amber-800 hover:text-amber-950 hover:bg-amber-100/50',
                                    ]"
                                >
                                    <AlertTriangle class="h-3.5 w-3.5" />
                                    <span>⚠️ Alergi</span>
                                    <span
                                        class="bg-amber-100 text-amber-900 text-[10px] px-1.5 py-0.2 rounded-full font-bold flex items-center gap-1"
                                    >
                                        <span>{{ totalPorsiAlergi }}</span>
                                        <span
                                            v-if="totalExtraBufferAlergi > 0"
                                            class="text-amber-700 font-extrabold text-[9px]"
                                            title="Termasuk Cadangan"
                                        >
                                            (+{{ totalExtraBufferAlergi }})
                                        </span>
                                    </span>
                                </button>
                                <button
                                    v-if="totalPorsiTambahan > 0"
                                    type="button"
                                    @click="setActiveLabelTab('tambahan')"
                                    :class="[
                                        'flex-1 py-1.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        activeLabelTab === 'tambahan'
                                            ? 'bg-sky-600 text-white shadow-xs'
                                            : 'text-sky-800 hover:text-sky-950 hover:bg-sky-100/50',
                                    ]"
                                >
                                    <PlusCircle class="h-3.5 w-3.5" />
                                    <span>➕ Tambahan</span>
                                    <span
                                        class="bg-sky-100 text-sky-900 text-[10px] px-1.5 py-0.2 rounded-full font-bold flex items-center gap-1"
                                    >
                                        <span>{{ totalPorsiTambahan }}</span>
                                        <span
                                            v-if="totalExtraBufferTambahan > 0"
                                            class="text-sky-700 font-extrabold text-[9px]"
                                            title="Termasuk Cadangan"
                                        >
                                            (+{{ totalExtraBufferTambahan }})
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-Pill Quick Allergen Selector for Preview when on Alergi tab -->
                        <div
                            v-if="activeLabelTab === 'alergi'"
                            class="flex items-center gap-1.5 px-3 py-2 bg-amber-50/80 border border-amber-200 rounded-xl text-xs"
                        >
                            <span
                                class="text-[11px] font-bold text-amber-900 shrink-0"
                                >Preview Jenis:</span
                            >
                            <div
                                class="flex items-center gap-1 overflow-x-auto py-0.5"
                            >
                                <template v-if="labelMode === 'manual'">
                                    <button
                                        type="button"
                                        v-for="al in selectedManualAllergies.length >
                                        0
                                            ? selectedManualAllergies
                                            : availableAllergiesList"
                                        :key="al"
                                        @click="
                                            selectedJenisAlergi = al;
                                            activeEditingAllergy = al;
                                        "
                                        :class="[
                                            'px-2.5 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                            currentPreviewJenisAlergi === al
                                                ? 'bg-amber-600 text-white font-extrabold shadow-2xs'
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100',
                                        ]"
                                    >
                                        <span>⚠️ {{ al }}</span>
                                        <span
                                            v-if="
                                                manualAllergyConfigs[al]?.porsi
                                            "
                                            class="text-[10px] opacity-80"
                                        >
                                            ({{
                                                manualAllergyConfigs[al].porsi
                                            }})
                                        </span>
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        type="button"
                                        @click="
                                            selectedJenisAlergi = 'Semua Alergi'
                                        "
                                        :class="[
                                            'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                            selectedJenisAlergi ===
                                            'Semua Alergi'
                                                ? 'bg-amber-600 text-white font-extrabold shadow-2xs'
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100',
                                        ]"
                                    >
                                        <span>✨ Semua Alergi</span>
                                        <span
                                            :class="[
                                                'text-[10px] px-1 rounded-full font-bold',
                                                selectedJenisAlergi ===
                                                'Semua Alergi'
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900',
                                            ]"
                                        >
                                            {{
                                                getAllergyPorsiCount(
                                                    "Semua Alergi",
                                                )
                                            }}
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
                                                : 'bg-white text-slate-700 border border-slate-200 hover:bg-amber-100',
                                        ]"
                                    >
                                        <span>⚠️ {{ al }}</span>
                                        <span
                                            :class="[
                                                'text-[10px] px-1 rounded-full font-bold',
                                                selectedJenisAlergi === al
                                                    ? 'bg-amber-700 text-white'
                                                    : 'bg-amber-100 text-amber-900',
                                            ]"
                                        >
                                            {{ getAllergyPorsiCount(al) }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Sub-Pill Quick Tambahan Selector for Preview when on Tambahan tab -->
                        <div
                            v-if="activeLabelTab === 'tambahan'"
                            class="flex items-center gap-1.5 px-3 py-2 bg-sky-50/80 border border-sky-200 rounded-xl text-xs"
                        >
                            <span
                                class="text-[11px] font-bold text-sky-900 shrink-0"
                                >Preview Jenis:</span
                            >
                            <div
                                class="flex items-center gap-1 overflow-x-auto py-0.5"
                            >
                                <button
                                    v-if="porsiTambahanData.organoleptik > 0"
                                    type="button"
                                    @click="
                                        selectedSubTambahan = 'organoleptik'
                                    "
                                    :class="[
                                        'px-2.5 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                        selectedSubTambahan === 'organoleptik'
                                            ? 'bg-sky-600 text-white font-extrabold shadow-2xs'
                                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-sky-100',
                                    ]"
                                >
                                    <span>🔬 Uji Organoleptik</span>
                                    <span
                                        class="text-[10px] px-1 rounded-full font-bold bg-sky-100 text-sky-900"
                                    >
                                        {{ porsiTambahanData.organoleptik }}
                                    </span>
                                </button>
                                <button
                                    v-if="porsiTambahanData.sampel > 0"
                                    type="button"
                                    @click="selectedSubTambahan = 'sampel'"
                                    :class="[
                                        'px-2.5 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                        selectedSubTambahan === 'sampel'
                                            ? 'bg-sky-600 text-white font-extrabold shadow-2xs'
                                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-sky-100',
                                    ]"
                                >
                                    <span>🧪 Sampel Makanan</span>
                                    <span
                                        class="text-[10px] px-1 rounded-full font-bold bg-sky-100 text-sky-900"
                                    >
                                        {{ porsiTambahanData.sampel }}
                                    </span>
                                </button>
                                <button
                                    v-if="porsiTambahanData.buffer > 0"
                                    type="button"
                                    @click="selectedSubTambahan = 'buffer'"
                                    :class="[
                                        'px-2.5 py-0.5 rounded-md text-[11px] font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1',
                                        selectedSubTambahan === 'buffer'
                                            ? 'bg-sky-600 text-white font-extrabold shadow-2xs'
                                            : 'bg-white text-slate-700 border border-slate-200 hover:bg-sky-100',
                                    ]"
                                >
                                    <span>📦 Buffer Produksi</span>
                                    <span
                                        class="text-[10px] px-1 rounded-full font-bold bg-sky-100 text-sky-900"
                                    >
                                        {{ porsiTambahanData.buffer }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Card Preview Container (Auto Responsive Scaler to maintain exact 1:1 layout on all devices) -->
                        <div
                            ref="previewContainerRef"
                            class="p-3 sm:p-5 bg-slate-100/80 rounded-3xl border border-slate-200 flex items-center justify-center shadow-inner overflow-hidden min-h-[220px] sm:min-h-[350px]"
                        >
                            <div
                                :style="previewScalerStyle"
                                class="flex items-center justify-center transition-transform duration-150"
                            >
                                <!-- Template 02: Layout Standar Excel BGN (Teks Murni) -->
                                <LabelCardItemTemplate02
                                    v-if="selectedTemplateId === 'template-02'"
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
                                    :sub-tipe-tambahan="activeLabelTab === 'tambahan' ? selectedSubTambahan : ''"
                                    :jenis-alergi="currentPreviewJenisAlergi"
                                    :tag-alergi="currentPreviewTag"
                                    :menu-items="currentPreviewMenuItems"
                                    :gizi-data="currentPreviewGiziData"
                                    :harga-items="hargaItems"
                                    :kelompok="previewKelompok"
                                    :kontak-baris1="kontakPengaduanBaris1"
                                    :kontak-baris2="kontakPengaduanBaris2"
                                    :kontak-baris3="kontakPengaduanBaris3"
                                    :kontak-baris4="kontakPengaduanBaris4"
                                />
                                <!-- Template 01: Visual Kartu Grafis Modern (Eksisting) -->
                                <LabelCardItem
                                    v-else
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
                                    :tag-alergi="currentPreviewTag"
                                    :menu-items="currentPreviewMenuItems"
                                    :gizi-data="currentPreviewGiziData"
                                    :harga-items="hargaItems"
                                    :kelompok="previewKelompok"
                                />
                            </div>
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
                                {{ downloadProgress.current }} dari
                                {{ downloadProgress.total }} Desain Label
                                <span
                                    class="text-slate-400 font-sans font-normal"
                                >
                                    (Render Desain)</span
                                >
                            </span>
                            <span v-else>
                                {{ downloadProgress.current }} dari
                                {{ downloadProgress.total }} Label
                                <span
                                    v-if="
                                        (downloadType === 'a4' ||
                                            downloadType === 'custom') &&
                                        downloadProgress.totalPages > 1
                                    "
                                    class="text-slate-400 font-sans font-normal"
                                >
                                    ({{ downloadProgress.totalPages }} Hal A4)
                                </span>
                                <span
                                    v-else-if="
                                        (downloadType === 'single' ||
                                            downloadType === 'print') &&
                                        downloadProgress.totalPages > 1
                                    "
                                    class="text-slate-400 font-sans font-normal"
                                >
                                    ({{ downloadProgress.totalPages }} Lembar)
                                </span>
                            </span>
                            <span class="text-primary font-black"
                                >{{ downloadProgress.percentage }}%</span
                            >
                        </div>

                        <!-- ETA & Speed Badges -->
                        <div
                            v-if="
                                !downloadSuccess &&
                                (downloadProgress.etaText ||
                                    downloadProgress.speedText)
                            "
                            class="flex items-center justify-center gap-2 pt-1 flex-wrap"
                        >
                            <div
                                v-if="downloadProgress.etaText"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-lg text-[11px] font-bold text-amber-800"
                            >
                                <Clock
                                    class="h-3.5 w-3.5 text-amber-600 animate-pulse"
                                />
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
                min-width: 555px;
                max-width: 555px;
                height: 370px;
                min-height: 370px;
                max-height: 370px;
                pointer-events: none;
                overflow: hidden;
                z-index: -99999;
                background: #ffffff;
            "
        >
            <div
                ref="sandboxCardRef"
                style="
                    width: 555px;
                    min-width: 555px;
                    max-width: 555px;
                    height: 370px;
                    min-height: 370px;
                    max-height: 370px;
                    background: white;
                "
            >
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
