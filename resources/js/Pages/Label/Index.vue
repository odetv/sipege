<script setup>
import { ref, computed, watch } from "vue";
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
    Tag,
    Printer,
    Calendar,
    Clock,
    Building2,
    Utensils,
    PackageCheck,
    Check,
    Sparkles,
    UserX,
    Edit3,
    AlertTriangle,
    Flame,
    MapPin,
    Hourglass,
    Layers,
    ChevronDown,
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
    workOrders: {
        type: Array,
        default: () => [],
    },
    initialActiveWo: {
        type: Object,
        default: null,
    },
});

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

// Setup parameter cetak label
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

const categorizedWorkOrders = computed(() => {
    const todayList = [];
    const upcomingList = [];
    const pastList = [];

    (props.workOrders || []).forEach((w) => {
        const tgl = (w.tanggal || "").substring(0, 10);
        if (tgl === todayStr) {
            todayList.push(w);
        } else if (tgl > todayStr) {
            upcomingList.push(w);
        } else {
            pastList.push(w);
        }
    });

    return {
        today: todayList,
        upcoming: upcomingList.sort((a, b) =>
            (a.tanggal || "").localeCompare(b.tanggal || ""),
        ),
        past: pastList.sort((a, b) =>
            (b.tanggal || "").localeCompare(a.tanggal || ""),
        ),
    };
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

function selectTodayWo() {
    if (todayWorkOrder.value) {
        selectedWoId.value = todayWorkOrder.value.id;
    }
}

// Parameter Label yang persis seperti template resmi BGN
const tanggalProduksi = ref(todayStr);
const jamProduksi = ref("07:00");
const batasKonsumsi = ref("09:00");
const petunjukMenu = ref(
    "Nasi Putih - Dori Finger with Yellow Mayonaise - Steam Tahu - Buncis & Jagung Manis - Buah Pepaya",
);

function formatJam(val) {
    if (!val) return "-";
    return String(val).replace(":", ".");
}

// Nilai AKG Kandungan Gizi (Porsi Kecil & Porsi Besar)
const giziData = ref({
    energi_pk: "386.3",
    energi_pb: "547.3",
    karbo_pk: "50.9",
    karbo_pb: "80",
    protein_pk: "18.3",
    protein_pb: "21.6",
    lemak_pk: "13",
    lemak_pb: "17.2",
    serat_pk: "3.6",
    serat_pb: "6.4",
});

const showGiziCustomizer = ref(false);

// Kelompok list synced with active Work Order snapshot or Master PM
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

// State kelompok terpilih (hanya kelompok yang menerima yang dapat dicentang)
const selectedKelompokIds = ref([]);

watch(
    [labelMode, activeWorkOrder],
    () => {
        if (labelMode.value === "auto" && activeWorkOrder.value) {
            tanggalProduksi.value = activeWorkOrder.value.tanggal || todayStr;
            petunjukMenu.value =
                activeWorkOrder.value.nama ||
                (activeWorkOrder.value.komponen &&
                activeWorkOrder.value.komponen.length > 0
                    ? activeWorkOrder.value.komponen.join(" - ")
                    : "Nasi Putih - Dori Finger with Yellow Mayonaise - Steam Tahu - Buncis & Jagung Manis - Buah Pepaya");
            selectedKelompokIds.value = activeKelompokList.value
                .filter((k) => k.is_menerima !== false)
                .map((k) => k.id);
        } else if (labelMode.value === "manual") {
            selectedKelompokIds.value = (props.kelompokList || []).map(
                (k) => k.id,
            );
        }
    },
    { immediate: true },
);

function setLabelMode(mode) {
    labelMode.value = mode;
    if (mode === "auto") {
        if (activeWorkOrder.value) {
            tanggalProduksi.value = activeWorkOrder.value.tanggal || todayStr;
            petunjukMenu.value = activeWorkOrder.value.nama || "";
        }
    }
}

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

function handlePrint() {
    window.print();
}

// Format DD/MM/YYYY
function formatDateSlash(dateStr) {
    if (!dateStr) return "-";
    try {
        const parts = String(dateStr).split("-");
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    } catch {
        return dateStr;
    }
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    } catch {
        return dateStr;
    }
}
</script>

<template>
    <AppLayout
        title="Label"
        subtitle="Generator & Pencetakan Label Kemasan Box Makanan Resmi SPPG BGN"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Label" />

        <div class="space-y-6">
            <!-- 1. Header Control Panel (Disembunyikan saat Print) -->
            <div class="print:hidden space-y-6">
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Tag class="h-5 w-5 text-primary" />
                                    <span
                                        >Konfigurasi Label Resmi SPPG -
                                        BGN</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Format label kemasan box makanan bergizi
                                    standar Badan Gizi Nasional (BGN).
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                @click="handlePrint"
                                :disabled="printableKelompokList.length === 0"
                                className="h-10 px-5 bg-primary hover:bg-primary/90 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm cursor-pointer shrink-0"
                            >
                                <Printer class="h-4 w-4" />
                                <span
                                    >Cetak Label ({{
                                        printableKelompokList.length
                                    }}
                                    Kartu)</span
                                >
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-4">
                        <!-- Mode Switcher: Otomatis vs Manual -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-700"
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
                                                ? 'bg-white text-primary shadow-2xs border border-slate-200 font-extrabold'
                                                : 'text-slate-600 hover:text-slate-900 font-semibold',
                                        ]"
                                    >
                                        <Utensils class="h-3.5 w-3.5" />
                                        <span
                                            >⚡ Otomatis (Sesuai Work
                                            Order)</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        @click="setLabelMode('manual')"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                            labelMode === 'manual'
                                                ? 'bg-white text-amber-700 shadow-2xs border border-slate-200 font-extrabold'
                                                : 'text-slate-600 hover:text-slate-900 font-semibold',
                                        ]"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                        <span>✍️ Manual (Input Bebas)</span>
                                    </button>
                                </div>
                            </div>
                            <span class="text-[11px] text-slate-500 italic">
                                {{
                                    labelMode === "auto"
                                        ? "Data menu, tanggal, & sasaran tersinkronisasi otomatis dari Work Order."
                                        : "Anda bebas mengatur nama menu, tanggal, dan memilih sasaran tanpa data WO."
                                }}
                            </span>
                        </div>

                        <!-- Panel Mode Otomatis (WO Selector) -->
                        <div
                            v-if="labelMode === 'auto'"
                            class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200/80 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="h-8 w-8 rounded-lg bg-primary text-white flex items-center justify-center font-bold shrink-0 shadow-2xs"
                                >
                                    <Utensils class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <span
                                            class="font-black text-slate-900 text-xs sm:text-sm truncate"
                                        >
                                            {{
                                                activeWorkOrder
                                                    ? activeWorkOrder.nama
                                                    : "Belum Ada Work Order"
                                            }}
                                        </span>
                                        <span
                                            v-if="activeWorkOrder"
                                            class="font-mono font-bold text-primary bg-white px-2 py-0.5 rounded border border-blue-200 text-[10.5px]"
                                        >
                                            {{ activeWorkOrder.id }}
                                        </span>
                                        <Badge
                                            v-if="
                                                activeWorkOrder?.tanggal ===
                                                todayStr
                                            "
                                            class="bg-emerald-100 text-emerald-800 border-emerald-300 font-extrabold text-[10px]"
                                        >
                                            ⭐ Menu Hari Ini
                                        </Badge>
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 mt-0.5"
                                    >
                                        Total Distribusi:
                                        <strong
                                            >{{
                                                Number(
                                                    activeWorkOrder?.total_porsi ||
                                                        0,
                                                ).toLocaleString("id-ID")
                                            }}
                                            Porsi</strong
                                        >
                                        ({{ activeWorkOrder?.porsi_pk || 0 }} PK
                                        / {{ activeWorkOrder?.porsi_pb || 0 }}
                                        PB)
                                    </p>
                                </div>
                            </div>

                            <!-- Selector Dropdown WO -->
                            <div
                                class="flex items-center gap-2 shrink-0 self-start md:self-auto w-full md:w-auto"
                            >
                                <div
                                    v-if="workOrders.length > 1"
                                    class="relative flex-1 md:flex-none"
                                >
                                    <select
                                        v-model="selectedWoId"
                                        class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-1.5 pr-8 text-slate-700 hover:border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs transition-colors cursor-pointer"
                                    >
                                        <optgroup
                                            v-if="
                                                categorizedWorkOrders.today
                                                    .length > 0
                                            "
                                            label="⭐ Menu Hari Ini"
                                        >
                                            <option
                                                v-for="w in categorizedWorkOrders.today"
                                                :key="w.id"
                                                :value="w.id"
                                            >
                                                ⭐ Hari Ini • {{ w.id }} •
                                                {{ w.nama }}
                                            </option>
                                        </optgroup>

                                        <optgroup
                                            v-if="
                                                categorizedWorkOrders.upcoming
                                                    .length > 0
                                            "
                                            label="📅 Menu Rencana Mendatang"
                                        >
                                            <option
                                                v-for="w in categorizedWorkOrders.upcoming"
                                                :key="w.id"
                                                :value="w.id"
                                            >
                                                📅 {{ w.id }} • {{ w.nama }} ({{
                                                    formatTanggalIndo(
                                                        w.tanggal,
                                                    )
                                                }})
                                            </option>
                                        </optgroup>

                                        <optgroup
                                            v-if="
                                                categorizedWorkOrders.past
                                                    .length > 0
                                            "
                                            label="🕒 Riwayat Menu Terdahulu"
                                        >
                                            <option
                                                v-for="w in categorizedWorkOrders.past"
                                                :key="w.id"
                                                :value="w.id"
                                            >
                                                🕒 {{ w.id }} • {{ w.nama }} ({{
                                                    formatTanggalIndo(
                                                        w.tanggal,
                                                    )
                                                }})
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>

                                <button
                                    v-if="
                                        todayWorkOrder &&
                                        activeWorkOrder &&
                                        activeWorkOrder.id !== todayWorkOrder.id
                                    "
                                    type="button"
                                    @click="selectTodayWo"
                                    class="px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 font-bold text-xs flex items-center gap-1 shadow-2xs transition-colors cursor-pointer shrink-0"
                                    title="Pilih Menu Hari Ini"
                                >
                                    <Sparkles
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />
                                    <span>Hari Ini</span>
                                </button>
                            </div>
                        </div>

                        <!-- Panel Mode Manual -->
                        <div
                            v-else
                            class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80 flex items-center justify-between gap-3 text-xs"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-8 w-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold shrink-0 shadow-2xs"
                                >
                                    <Edit3 class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="font-bold text-amber-950">
                                        Mode Input Manual Bebas Aktif
                                    </p>
                                    <p
                                        class="text-[11px] text-amber-800 mt-0.5"
                                    >
                                        Ketik nama menu masakan, tanggal, jam
                                        masak, batas konsumsi, serta sesuaikan
                                        kandungan gizi dan sasaran.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Parameter Form Fields -->
                        <div
                            :class="[
                                'grid gap-4',
                                labelMode === 'auto'
                                    ? 'grid-cols-1 sm:grid-cols-2'
                                    : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                            ]"
                        >
                            <!-- Tanggal Produksi (Hanya di Mode Manual) -->
                            <div
                                v-if="labelMode === 'manual'"
                                class="space-y-1.5"
                            >
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Calendar
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <span>Tanggal Produksi:</span>
                                </label>
                                <input
                                    type="date"
                                    v-model="tanggalProduksi"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2"
                                />
                            </div>

                            <!-- Jam Produksi -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Clock class="h-3.5 w-3.5 text-primary" />
                                    <span>Jam Produksi:</span>
                                </label>
                                <input
                                    type="time"
                                    v-model="jamProduksi"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2 bg-white cursor-pointer"
                                />
                            </div>

                            <!-- Batas Konsumsi -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Hourglass
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />
                                    <span>Batas Konsumsi:</span>
                                </label>
                                <input
                                    type="time"
                                    v-model="batasKonsumsi"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2 text-amber-900 bg-amber-50/40 cursor-pointer"
                                />
                            </div>

                            <!-- Menu Makanan (Hanya di Mode Manual) -->
                            <div
                                v-if="labelMode === 'manual'"
                                class="space-y-1.5"
                            >
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Utensils
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />
                                    <span>Rincian Menu Makanan:</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="petunjukMenu"
                                    placeholder="Nasi Putih - Dori Finger with Yellow Mayonaise - Steam Tahu..."
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2"
                                />
                            </div>
                        </div>

                        <!-- Toggle Customizer Kandungan Gizi -->
                        <div class="pt-2">
                            <button
                                type="button"
                                @click="
                                    showGiziCustomizer = !showGiziCustomizer
                                "
                                class="text-xs font-bold text-primary hover:text-primary/80 flex items-center gap-1.5 cursor-pointer"
                            >
                                <Flame class="h-3.5 w-3.5" />
                                <span>{{
                                    showGiziCustomizer
                                        ? "Sembunyikan"
                                        : "Kustomisasi Nilai Kandungan Gizi (Energi, Karbo, Protein, Lemak, Serat)"
                                }}</span>
                                <ChevronDown
                                    class="h-3.5 w-3.5 transition-transform"
                                    :class="
                                        showGiziCustomizer ? 'rotate-180' : ''
                                    "
                                />
                            </button>

                            <div
                                v-if="showGiziCustomizer"
                                class="mt-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3"
                            >
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs"
                                >
                                    <div class="space-y-1">
                                        <span
                                            class="font-bold text-slate-700 block"
                                            >Energi (Kkal)</span
                                        >
                                        <div class="flex gap-1.5">
                                            <input
                                                v-model="giziData.energi_pk"
                                                placeholder="PK"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Kecil"
                                            />
                                            <input
                                                v-model="giziData.energi_pb"
                                                placeholder="PB"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Besar"
                                            />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="font-bold text-slate-700 block"
                                            >Karbohidrat (g)</span
                                        >
                                        <div class="flex gap-1.5">
                                            <input
                                                v-model="giziData.karbo_pk"
                                                placeholder="PK"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Kecil"
                                            />
                                            <input
                                                v-model="giziData.karbo_pb"
                                                placeholder="PB"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Besar"
                                            />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="font-bold text-slate-700 block"
                                            >Protein (g)</span
                                        >
                                        <div class="flex gap-1.5">
                                            <input
                                                v-model="giziData.protein_pk"
                                                placeholder="PK"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Kecil"
                                            />
                                            <input
                                                v-model="giziData.protein_pb"
                                                placeholder="PB"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Besar"
                                            />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="font-bold text-slate-700 block"
                                            >Lemak (g)</span
                                        >
                                        <div class="flex gap-1.5">
                                            <input
                                                v-model="giziData.lemak_pk"
                                                placeholder="PK"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Kecil"
                                            />
                                            <input
                                                v-model="giziData.lemak_pb"
                                                placeholder="PB"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Besar"
                                            />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="font-bold text-slate-700 block"
                                            >Serat (g)</span
                                        >
                                        <div class="flex gap-1.5">
                                            <input
                                                v-model="giziData.serat_pk"
                                                placeholder="PK"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Kecil"
                                            />
                                            <input
                                                v-model="giziData.serat_pb"
                                                placeholder="PB"
                                                class="w-full text-xs p-1.5 border rounded bg-white"
                                                title="Porsi Besar"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Kelompok Terpilih -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div
                                class="flex items-center justify-between flex-wrap gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                        >Pilih Sasaran Kelompok yang
                                        Dicetak:</span
                                    >
                                    <span class="text-[11px] text-slate-500">
                                        ({{ printableKelompokList.length }} dari
                                        {{ receivingKelompokList.length }}
                                        Menerima)
                                    </span>
                                </div>
                                <label
                                    v-if="receivingKelompokList.length > 0"
                                    class="flex items-center gap-1.5 text-xs font-bold text-primary cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="isAllSelected"
                                        class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                    />
                                    <span
                                        >Pilih Semua yang Menerima ({{
                                            receivingKelompokList.length
                                        }})</span
                                    >
                                </label>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template
                                    v-for="k in activeKelompokList"
                                    :key="k.id"
                                >
                                    <!-- Jika kelompok Tidak Menerima: Label disabled -->
                                    <button
                                        v-if="k.is_menerima === false"
                                        type="button"
                                        disabled
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold border bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-50 select-none flex items-center gap-1.5 line-through"
                                        title="Kelompok ini Tidak Menerima pada Work Order yang dipilih"
                                    >
                                        <UserX class="h-3 w-3 text-slate-400" />
                                        <span>{{ k.nama_kelompok }}</span>
                                        <span
                                            class="text-[9.5px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded"
                                        >
                                            Tidak Menerima (Non-Aktif)
                                        </span>
                                    </button>

                                    <!-- Jika kelompok Menerima: Tombol interaktif -->
                                    <button
                                        v-else
                                        type="button"
                                        @click="toggleKelompok(k)"
                                        :class="[
                                            'px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5',
                                            selectedKelompokIds.includes(k.id)
                                                ? 'bg-primary/10 text-primary border-primary/30 shadow-2xs font-bold'
                                                : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100',
                                        ]"
                                    >
                                        <Check
                                            v-if="
                                                selectedKelompokIds.includes(
                                                    k.id,
                                                )
                                            "
                                            class="h-3.5 w-3.5 text-primary"
                                        />
                                        <span>{{ k.nama_kelompok }}</span>
                                        <span
                                            class="text-[10px] text-slate-600 bg-white px-1.5 py-0.5 rounded border border-slate-200"
                                        >
                                            {{ k.total_penerima }} porsi
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- 2. Printable Label Grid Cards (PERSIS SESUAI TEMPLATE GAMBAR BGN) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between print:hidden">
                    <h3
                        class="text-sm font-bold text-slate-800 flex items-center gap-2"
                    >
                        <PackageCheck class="h-4 w-4 text-primary" />
                        <span
                            >Pratinjau Label Cetak ({{
                                printableKelompokList.length
                            }}
                            Kartu Label)</span
                        >
                    </h3>
                    <span class="text-xs text-slate-500">
                        Format Stiker Resmi SPPG BGN (Sesuai Desain Resmi)
                    </span>
                </div>

                <!-- Print Container Grid (2 Kolom Kartu) -->
                <div
                    class="grid grid-cols-1 lg:grid-cols-2 gap-6 print:grid-cols-1 print:gap-6"
                >
                    <div
                        v-for="k in printableKelompokList"
                        :key="k.id"
                        class="bgn-label-card bg-white rounded-2xl border-[3px] border-[#1E4B8B] p-5 sm:p-6 shadow-md relative overflow-hidden flex flex-col justify-between print:border-[3px] print:border-[#1E4B8B] print:shadow-none print:break-inside-avoid print:p-5 print:my-2"
                    >
                        <!-- ================= 1. HEADER LOGO & SPPG TITLE ================= -->
                        <div class="pb-2">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <!-- Logo Badan Gizi Nasional + Teks -->
                                <div class="flex items-center gap-2.5">
                                    <!-- Official BGN Emblem SVG -->
                                    <div
                                        class="h-12 w-12 rounded-full bg-[#1E3A8A] border-2 border-[#D4A017] p-0.5 flex items-center justify-center shrink-0 shadow-xs relative"
                                    >
                                        <div
                                            class="h-full w-full rounded-full bg-[#0D6538] border border-[#D4A017] flex items-center justify-center text-center"
                                        >
                                            <!-- Garuda / Star Emblem Graphic -->
                                            <span
                                                class="text-[#FBBF24] text-xs font-black"
                                                >★</span
                                            >
                                        </div>
                                    </div>
                                    <div class="leading-none">
                                        <span
                                            class="block font-black text-[#1E3A8A] text-[13px] tracking-tight"
                                            >BADAN</span
                                        >
                                        <span
                                            class="block font-black text-[#1E3A8A] text-[13px] tracking-tight"
                                            >GIZI</span
                                        >
                                        <span
                                            class="block font-black text-[#1E3A8A] text-[13px] tracking-tight"
                                            >NASIONAL</span
                                        >
                                    </div>
                                </div>

                                <!-- Satuan Pelayanan Pemenuhan Gizi -->
                                <div class="text-right">
                                    <span
                                        class="block text-[10px] sm:text-[11px] font-bold text-slate-500 uppercase tracking-wider"
                                    >
                                        SATUAN PELAYANAN PEMENUHAN GIZI
                                    </span>
                                    <h2
                                        class="font-black text-[#1E3A8A] text-sm sm:text-base md:text-[17px] leading-tight uppercase mt-0.5"
                                    >
                                        {{
                                            unitSppg?.nama
                                                ? unitSppg.nama.startsWith(
                                                      "SPPG",
                                                  )
                                                    ? unitSppg.nama
                                                    : "SPPG " + unitSppg.nama
                                                : "SPPG BULELENG SUKASADA TEGALLINGGAH"
                                        }}
                                    </h2>
                                </div>
                            </div>

                            <!-- Gold Accent Divider -->
                            <div
                                class="h-[2.5px] bg-[#C5921D] rounded-full mt-2.5 mb-3.5"
                            ></div>
                        </div>

                        <!-- ================= 2. BODY GRID (KIRI & KANAN) ================= -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-3.5"
                        >
                            <!-- ===== KOLOM KIRI ===== -->
                            <div
                                class="space-y-3 flex flex-col justify-between"
                            >
                                <!-- Badge Biru -->
                                <div>
                                    <div
                                        class="bg-[#4E88C7] text-white text-center py-1.5 px-3 rounded-lg font-black text-[11px] sm:text-xs uppercase tracking-wide shadow-2xs"
                                    >
                                        LABEL MAKANAN BERGIZI GRATIS
                                    </div>
                                </div>

                                <!-- Tanggal Produksi -->
                                <div>
                                    <label
                                        class="text-[11px] font-extrabold text-slate-800 flex items-center gap-1.5 mb-1"
                                    >
                                        <Calendar
                                            class="h-3.5 w-3.5 text-slate-800"
                                        />
                                        <span>Tanggal Produksi</span>
                                    </label>
                                    <div
                                        class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-2 px-3 text-center text-sm sm:text-base font-black text-slate-900 shadow-2xs"
                                    >
                                        {{ formatDateSlash(tanggalProduksi) }}
                                    </div>
                                </div>

                                <!-- Jam Produksi & Batas Konsumsi -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label
                                            class="text-[11px] font-extrabold text-slate-800 flex items-center gap-1 mb-1 truncate"
                                        >
                                            <Clock
                                                class="h-3.5 w-3.5 text-slate-800 shrink-0"
                                            />
                                            <span>Jam Produksi</span>
                                        </label>
                                        <div
                                            class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-2 px-2 text-center text-xs sm:text-sm font-black text-slate-900 shadow-2xs"
                                        >
                                            {{ formatJam(jamProduksi) }}
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[11px] font-extrabold text-slate-800 flex items-center gap-1 mb-1 truncate"
                                        >
                                            <Hourglass
                                                class="h-3.5 w-3.5 text-slate-800 shrink-0"
                                            />
                                            <span>Batas Konsumsi</span>
                                        </label>
                                        <div
                                            class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-2 px-2 text-center text-xs sm:text-sm font-black text-slate-900 shadow-2xs"
                                        >
                                            {{ formatJam(batasKonsumsi) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Tujuan Pengantaran -->
                                <div>
                                    <label
                                        class="text-[11px] font-extrabold text-slate-800 flex items-center gap-1.5 mb-1"
                                    >
                                        <MapPin
                                            class="h-3.5 w-3.5 text-slate-800"
                                        />
                                        <span>Tujuan Pengantaran</span>
                                    </label>
                                    <div
                                        class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-2.5 px-3 text-left text-xs sm:text-sm font-black text-[#1E3A8A] shadow-2xs leading-tight"
                                    >
                                        {{ k.nama_kelompok }}
                                    </div>
                                </div>
                            </div>

                            <!-- ===== KOLOM KANAN ===== -->
                            <div
                                class="space-y-2.5 flex flex-col justify-between"
                            >
                                <!-- Menu Box (Biru dengan Tab MENU di kiri) -->
                                <div
                                    class="bg-[#4E88C7] rounded-xl p-1 flex items-stretch gap-2 text-white shadow-2xs min-h-[58px]"
                                >
                                    <div
                                        class="bg-[#3D74B0] rounded-lg px-2.5 flex items-center justify-center font-black text-[11px] uppercase tracking-wider shrink-0"
                                    >
                                        MENU
                                    </div>
                                    <div
                                        class="flex items-center text-[10.5px] sm:text-[11px] font-bold leading-tight py-1 pr-1.5 text-white/95"
                                    >
                                        {{ petunjukMenu }}
                                    </div>
                                </div>

                                <!-- Kandungan Gizi Section -->
                                <div>
                                    <div
                                        class="flex items-center gap-1.5 text-[11px] font-extrabold text-slate-900 mb-1"
                                    >
                                        <Flame
                                            class="h-3.5 w-3.5 text-slate-800"
                                        />
                                        <span>Kandungan Gizi</span>
                                    </div>
                                    <!-- Dashed Line -->
                                    <div
                                        class="border-b border-dashed border-slate-300 mb-2"
                                    ></div>

                                    <!-- Matrix Kandungan Gizi -->
                                    <div class="space-y-1 text-xs">
                                        <!-- Row Energi -->
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-800"
                                            >
                                                Energi
                                                <span
                                                    class="text-[9.5px] text-slate-500 font-normal"
                                                    >(Kkal)</span
                                                >
                                            </span>
                                            <div
                                                class="flex items-center gap-1.5 shrink-0"
                                            >
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.energi_pk }}
                                                </div>
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.energi_pb }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row Karbohidrat -->
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-800"
                                            >
                                                Karbohidrat
                                                <span
                                                    class="text-[9.5px] text-slate-500 font-normal"
                                                    >(g)</span
                                                >
                                            </span>
                                            <div
                                                class="flex items-center gap-1.5 shrink-0"
                                            >
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.karbo_pk }}
                                                </div>
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.karbo_pb }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row Protein -->
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-800"
                                            >
                                                Protein
                                                <span
                                                    class="text-[9.5px] text-slate-500 font-normal"
                                                    >(g)</span
                                                >
                                            </span>
                                            <div
                                                class="flex items-center gap-1.5 shrink-0"
                                            >
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.protein_pk }}
                                                </div>
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.protein_pb }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row Lemak -->
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-800"
                                            >
                                                Lemak
                                                <span
                                                    class="text-[9.5px] text-slate-500 font-normal"
                                                    >(g)</span
                                                >
                                            </span>
                                            <div
                                                class="flex items-center gap-1.5 shrink-0"
                                            >
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.lemak_pk }}
                                                </div>
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.lemak_pb }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row Serat -->
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-800"
                                            >
                                                Serat
                                                <span
                                                    class="text-[9.5px] text-slate-500 font-normal"
                                                    >(g)</span
                                                >
                                            </span>
                                            <div
                                                class="flex items-center gap-1.5 shrink-0"
                                            >
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.serat_pk }}
                                                </div>
                                                <div
                                                    class="w-14 bg-[#EDF4FC] border border-[#BFD8F2] rounded-md py-0.5 text-center text-[11px] font-bold text-slate-900"
                                                >
                                                    {{ giziData.serat_pb }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Column Headers: Porsi Kecil & Porsi Besar (DI BAWAH KOTAK ANGKA) -->
                                        <div
                                            class="flex items-center justify-end gap-1.5 pt-1"
                                        >
                                            <div
                                                class="w-14 bg-[#5A92CF] text-white rounded-md py-1 text-center text-[9px] font-extrabold leading-tight"
                                            >
                                                Porsi<br />Kecil
                                            </div>
                                            <div
                                                class="w-14 bg-[#5A92CF] text-white rounded-md py-1 text-center text-[9px] font-extrabold leading-tight"
                                            >
                                                Porsi<br />Besar
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= 3. BOTTOM WARNING BANNER ================= -->
                        <div
                            class="bg-[#FFF5F5] border border-[#FCA5A5] rounded-xl p-3 flex items-center gap-3 relative overflow-hidden mt-2"
                        >
                            <!-- Red Left Stripe -->
                            <div
                                class="absolute left-0 top-0 bottom-0 w-2 bg-[#DC2626]"
                            ></div>

                            <!-- Triangle Warning Icon -->
                            <div class="pl-2 shrink-0">
                                <div
                                    class="h-9 w-9 rounded-lg flex items-center justify-center text-[#DC2626]"
                                >
                                    <AlertTriangle
                                        class="h-7 w-7"
                                        stroke-width="2.5"
                                    />
                                </div>
                            </div>

                            <!-- Warning Text -->
                            <div class="leading-snug">
                                <p
                                    class="text-[#DC2626] font-extrabold text-[11px] sm:text-xs tracking-tight uppercase"
                                >
                                    MAKANAN INI HANYA UNTUK DIKONSUMSI DI
                                    TEMPAT.
                                </p>
                                <p
                                    class="text-[#DC2626] font-black text-sm sm:text-base tracking-wide uppercase mt-0.5"
                                >
                                    DILARANG MEMBAWA PULANG!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="printableKelompokList.length === 0"
                    class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-400 font-semibold print:hidden"
                >
                    Tidak ada kelompok sasaran yang dipilih untuk dicetak.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Sembunyikan elemen layout yang tidak perlu saat cetak */
    body {
        background: #ffffff !important;
        font-family: inherit !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    aside,
    header,
    footer,
    .print\:hidden {
        display: none !important;
    }
    main,
    .max-w-7xl,
    .space-y-6 {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .bgn-label-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-bottom: 20px !important;
    }
}
</style>
