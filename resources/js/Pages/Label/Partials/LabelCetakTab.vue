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

// Parameter Area Isolasi / Perekat Kemasan (cm) - Default 2cm
const tinggiIsolasiCm = ref(2);

// Parameter Waktu
const tanggalProduksi = ref(todayStr);
const jamProduksi = ref("12:14");
const tanggalExpired = ref(todayStr);
const jamExpired = ref("12:14");

// Parameter Waktu Maksimal & Larangan
const waktuMaksimal = ref("2 JAM SETELAH DITERIMA!");
const teksLaranganHeader = ref("MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.");
const teksLaranganSub = ref("DILARANG MEMBAWA PULANG!");

// Parameter Komponen Menu Makanan
const menuItems = ref([
    "NASI PUTIH",
    "AYAM CRISPY",
    "TEMPE MANIS DADU",
    "SELADA, TIMUN",
    "MELON",
]);

// Parameter Kandungan Gizi
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

const selectedKelompokIds = ref([]);

const receivingKelompokList = computed(() => {
    return activeKelompokList.value.filter((k) => k.is_menerima !== false);
});

const isAllSelected = computed({
    get() {
        return (
            receivingKelompokList.value.length > 0 &&
            selectedKelompokIds.value.length ===
                receivingKelompokList.value.length
        );
    },
    set(val) {
        if (val) {
            selectedKelompokIds.value = receivingKelompokList.value.map(
                (k) => k.id,
            );
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

function syncDataFromWorkOrder(wo) {
    if (!wo) return;
    tanggalProduksi.value = wo.tanggal || todayStr;
    tanggalExpired.value = wo.tanggal || todayStr;

    // Sinkronisasi komponen menu
    if (Array.isArray(wo.items) && wo.items.length > 0) {
        menuItems.value = wo.items.map((it) => it.nama);
        hargaItems.value = wo.items.map((it) => ({
            nama: it.nama,
            harga_pb: it.cost_pb || 0,
            harga_pk: it.cost_pk || 0,
        }));
    } else if (Array.isArray(wo.komponen) && wo.komponen.length > 0) {
        menuItems.value = wo.komponen.map((k) => k);
        hargaItems.value = wo.komponen.map((k) => ({
            nama: k,
            harga_pb: 0,
            harga_pk: 0,
        }));
    }

    // Sinkronisasi AKG
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
    }

    selectedKelompokIds.value = activeKelompokList.value
        .filter((k) => k.is_menerima !== false)
        .map((k) => k.id);
}

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
            jamExpired.value = item.batas_konsumsi || "12:14";

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
            }

            if (Array.isArray(item.selected_kelompok_ids)) {
                selectedKelompokIds.value = [...item.selected_kelompok_ids];
            }
        }
    },
    { immediate: true },
);

// Menu item helpers
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
        hargaItems.value.nama = val;
    }
}

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
        batas_konsumsi: jamExpired.value,
        petunjuk_menu: menuItems.value.join("\n"),
        template_id: "bgn_standard_fixed_white",
        template_name: "Standar Resmi BGN (Putih)",
        aspect_ratio: "4:3",
        gizi_data: giziData.value,
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

// PDF Download State & Handlers
const isDownloading = ref(false);
const downloadType = ref("");
const downloadFormatOption = ref("single");
const customLabelCount = ref(totalWoPorsi.value || 9);

// Auto-populate custom label count when WO / kelompok changes, but keep it freely editable
watch(
    totalWoPorsi,
    (val) => {
        if (val > 0) {
            customLabelCount.value = val;
        }
    },
    { immediate: true },
);

watch(downloadFormatOption, (newFormat) => {
    if (newFormat === "custom") {
        if (!customLabelCount.value || customLabelCount.value <= 0) {
            customLabelCount.value = totalWoPorsi.value || 9;
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

const sandboxKelompok = ref(null);
const sandboxCardRef = ref(null);

async function getSandboxCardElement(kelompok) {
    sandboxKelompok.value = kelompok;
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 20));
    return (
        sandboxCardRef.value?.querySelector(".bgn-label-card") ||
        sandboxCardRef.value
    );
}

async function startDownload(type = null) {
    const selectedType = type || downloadFormatOption.value || "single";
    if (printableKelompokList.value.length === 0) return;
    isDownloading.value = true;
    downloadType.value = selectedType;
    downloadError.value = "";
    downloadSuccess.value = false;

    const totalCount =
        selectedType === "custom"
            ? parseInt(customLabelCount.value, 10) || 9
            : printableKelompokList.value.length;

    downloadProgress.value = {
        current: 0,
        total: totalCount,
        totalPages: Math.ceil(totalCount / 9),
        currentPage: 1,
        percentage: 0,
        etaText: "Menyiapkan render...",
        speedText: "",
        message: "Menyiapkan elemen kartu label resmi BGN...",
    };

    try {
        const dateSuffix = (tanggalProduksi.value || todayStr).replace(
            /-/g,
            "",
        );

        if (selectedType === "single") {
            const filename = `Label_BGN_9x6cm_Tunggal_${dateSuffix}.pdf`;
            await downloadPdfSingleMode({
                printableKelompokList: printableKelompokList.value,
                customCount: totalCount,
                getRenderElement: getSandboxCardElement,
                filename,
                onProgress: (p) => {
                    downloadProgress.value = { ...downloadProgress.value, ...p };
                },
            });
        } else if (selectedType === "a4" || selectedType === "custom") {
            const filename = `Label_BGN_Lembar_A4_${totalCount}Label_${dateSuffix}.pdf`;
            await downloadPdfA4GridMode({
                printableKelompokList: printableKelompokList.value,
                customCount: totalCount,
                getRenderElement: getSandboxCardElement,
                filename,
                onProgress: (p) => {
                    downloadProgress.value = { ...downloadProgress.value, ...p };
                },
            });
        }

        downloadSuccess.value = true;
        setTimeout(() => {
            if (downloadSuccess.value) {
                isDownloading.value = false;
            }
        }, 1200);
    } catch (err) {
        console.error("Gagal download PDF label:", err);
        downloadError.value =
            err.message || "Terjadi kesalahan saat memproses file PDF.";
    }
}

async function startPrintDirect() {
    if (printableKelompokList.value.length === 0) {
        alert("Silakan pilih minimal 1 kelompok sasaran.");
        return;
    }
    isDownloading.value = true;
    downloadType.value = "print";
    downloadError.value = "";
    downloadSuccess.value = false;
    downloadProgress.value = {
        current: 0,
        total: printableKelompokList.value.length,
        totalPages: printableKelompokList.value.length,
        currentPage: 1,
        percentage: 0,
        etaText: "Menyiapkan cetakan...",
        speedText: "",
        message: "Menyiapkan label ukuran 9x6cm untuk dicetak...",
    };

    try {
        await printPdfSingleMode({
            printableKelompokList: printableKelompokList.value,
            getRenderElement: getSandboxCardElement,
            onProgress: (p) => {
                downloadProgress.value = { ...downloadProgress.value, ...p };
            },
        });

        downloadSuccess.value = true;
        setTimeout(() => {
            if (downloadSuccess.value) {
                isDownloading.value = false;
            }
        }, 1000);
    } catch (err) {
        console.error("Gagal mencetak label:", err);
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
                                Seluruh data terisi dinamis dari Work Order /
                                input manual dan siap dicetak dalam format
                                stiker standar 9 x 6 cm.
                            </CardDescription>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
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
                                        printableKelompokList.length === 0 ||
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
                                    printableKelompokList.length === 0 ||
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
                                            Seluruh data (Komponen Menu,
                                            Kandungan Gizi, Tanggal, dan
                                            Sasaran) secara otomatis terhubung
                                            dari Work Order yang dipilih.
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
                                        Mengatur tinggi area kosong perekat pada bagian atas label (Default: 2 cm).
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
                                    {{ preset === 2 ? '2 cm (Default)' : preset + ' cm' }}
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- FORM KHUSUS MODE MANUAL -->
                    <template v-if="labelMode === 'manual'">
                        <!-- 2. Identitas Header & Waktu -->
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
                                        <label class="font-bold text-slate-600"
                                            >Jam Produksi:</label
                                        >
                                        <input
                                            v-model="jamProduksi"
                                            type="text"
                                            placeholder="12:14"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-slate-800 focus:ring-2 focus:ring-primary/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-600"
                                            >Jam Expired:</label
                                        >
                                        <input
                                            v-model="jamExpired"
                                            type="text"
                                            placeholder="12:14"
                                            class="w-full mt-1 px-2.5 py-1.5 border border-slate-200 rounded-lg font-bold text-rose-600 focus:ring-2 focus:ring-rose-200 outline-none"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 3. Pengaturan Waktu Konsumsi & Teks Larangan (Bisa Diubah di Mode Manual) -->
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

                        <!-- 4. Komponen Menu Makanan -->
                        <Card
                            className="bg-white border-slate-200/80 shadow-2xs"
                        >
                            <CardHeader
                                className="p-4 pb-2 border-b border-slate-100 flex flex-row items-center justify-between"
                            >
                                <CardTitle
                                    class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Utensils class="h-4 w-4 text-primary" />
                                    <span>Komponen Menu Makanan</span>
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
                                        :value="item"
                                        @input="
                                            updateMenuItemName(
                                                idx,
                                                $event.target.value,
                                            )
                                        "
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

                        <!-- 5. Kandungan Gizi (AKG) -->
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
                                    <span>Nilai Kandungan Gizi (AKG)</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <!-- Porsi Besar -->
                                <div>
                                    <p class="font-bold text-slate-700 mb-1.5">
                                        Porsi Besar:
                                    </p>
                                    <div class="grid grid-cols-5 gap-1.5">
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Energi (kcal)</label
                                            >
                                            <input
                                                v-model="giziData.energi_pb"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Prot (g)</label
                                            >
                                            <input
                                                v-model="giziData.prot_pb"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Lmk (g)</label
                                            >
                                            <input
                                                v-model="giziData.lmk_pb"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Karbo (g)</label
                                            >
                                            <input
                                                v-model="giziData.karbo_pb"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Serat (g)</label
                                            >
                                            <input
                                                v-model="giziData.serat_pb"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Porsi Kecil -->
                                <div class="pt-2 border-t border-slate-100">
                                    <p class="font-bold text-slate-700 mb-1.5">
                                        Porsi Kecil:
                                    </p>
                                    <div class="grid grid-cols-5 gap-1.5">
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Energi (kcal)</label
                                            >
                                            <input
                                                v-model="giziData.energi_pk"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Prot (g)</label
                                            >
                                            <input
                                                v-model="giziData.prot_pk"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Lmk (g)</label
                                            >
                                            <input
                                                v-model="giziData.lmk_pk"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Karbo (g)</label
                                            >
                                            <input
                                                v-model="giziData.karbo_pk"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10.5px] text-slate-500"
                                                >Serat (g)</label
                                            >
                                            <input
                                                v-model="giziData.serat_pk"
                                                type="text"
                                                class="w-full mt-0.5 px-2 py-1 border border-slate-200 rounded font-bold text-center"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- 6. Rincian Harga Satuan per Item -->
                        <Card
                            className="bg-white border-slate-200/80 shadow-2xs"
                        >
                            <CardHeader
                                className="p-4 pb-2 border-b border-slate-100"
                            >
                                <CardTitle
                                    class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2"
                                >
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
                                    <div
                                        class="col-span-6 font-bold text-slate-700 truncate"
                                    >
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

                    <!-- 7. Sasaran Kelompok Penerima -->
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
                                    'p-2 rounded-xl border flex items-center justify-between transition-colors cursor-pointer',
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
                                    <span>{{ k.nama_kelompok }}</span>
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
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-sm font-black text-slate-900 flex items-center gap-2"
                            >
                                <Sparkles class="h-4 w-4 text-primary" />
                                <span>Live Preview Label Resmi BGN</span>
                            </h3>
                            <span
                                class="text-[11px] text-slate-500 font-medium"
                            >
                                Ukuran Cetak Stiker Box Makanan
                            </span>
                        </div>

                        <!-- Card Preview Container -->
                        <div
                            class="p-4 bg-slate-100/80 rounded-3xl border border-slate-200 flex items-center justify-center"
                        >
                            <LabelCardItem
                                :unit-sppg="unitSppg"
                                :nama-sppg="namaSppg"
                                :zona-waktu="zonaWaktu"
                                :tinggi-isolasi-cm="tinggiIsolasiCm"
                                :tanggal-produksi="tanggalProduksi"
                                :jam-produksi="jamProduksi"
                                :tanggal-expired="tanggalExpired"
                                :jam-expired="jamExpired"
                                :waktu-maksimal="waktuMaksimal"
                                :teks-larangan-header="teksLaranganHeader"
                                :teks-larangan-sub="teksLaranganSub"
                                :menu-items="menuItems"
                                :gizi-data="giziData"
                                :harga-items="hargaItems"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PRINT VIEW CONTAINER ================= -->
        <div
            :class="[
                'hidden print:block',
                printScaleMode === 'fixed9x6'
                    ? 'print-mode-9x6'
                    : 'print-mode-full',
            ]"
        >
            <div
                v-for="kelompok in printableKelompokList"
                :key="kelompok.id"
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
                        :tanggal-expired="tanggalExpired"
                        :jam-expired="jamExpired"
                        :waktu-maksimal="waktuMaksimal"
                        :teks-larangan-header="teksLaranganHeader"
                        :teks-larangan-sub="teksLaranganSub"
                        :menu-items="menuItems"
                        :gizi-data="giziData"
                        :harga-items="hargaItems"
                        :kelompok="kelompok"
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
                    :tanggal-expired="tanggalExpired"
                    :jam-expired="jamExpired"
                    :waktu-maksimal="waktuMaksimal"
                    :teks-larangan-header="teksLaranganHeader"
                    :teks-larangan-sub="teksLaranganSub"
                    :menu-items="menuItems"
                    :gizi-data="giziData"
                    :harga-items="hargaItems"
                    :kelompok="sandboxKelompok"
                />
            </div>
        </div>
    </div>
</template>
