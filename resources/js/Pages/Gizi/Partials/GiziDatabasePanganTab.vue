<script setup>
import { ref, computed, watch } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Database,
    Search,
    ChevronLeft,
    ChevronRight,
    RotateCcw,
    AlertTriangle,
    Loader2,
    Globe,
    Layers,
    X,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    tkpiList: {
        type: Array,
        default: () => [],
    },
    tkpiDatasets: {
        type: Object,
        default: () => ({
            fta: [],
            csv: [],
            tkpi2020: [],
            fatsecret: [],
        }),
    },
    selectedSource: {
        type: String,
        default: "tkpi2020",
    },
    nutrisurveyFiles: {
        type: Array,
        default: () => [],
    },
    selectedFta: {
        type: String,
        default: "indo.fta",
    },
});

const emit = defineEmits(["update-source", "update-fta"]);

// State FTA NutriSurvey Dinamis (Multi-Negara)
const activeFtaFile = ref(props.selectedFta || "indo.fta");
const customFtaItemsMap = ref({});
const isFtaLoading = ref(false);
const isFtaModalOpen = ref(false);

watch(
    () => props.selectedFta,
    (val) => {
        if (val && val !== activeFtaFile.value) {
            activeFtaFile.value = val;
        }
    },
);

// Detail metadata FTA yang sedang aktif
const activeFtaInfo = computed(() => {
    const list = props.nutrisurveyFiles || [];
    const found = list.find((f) => f.filename === activeFtaFile.value);
    return (
        found || {
            filename: activeFtaFile.value,
            country: "Indonesia",
            label: "Indonesia (DKBM / TKPI)",
            flag: "🇮🇩",
            count: props.tkpiDatasets?.fta?.length || 1105,
        }
    );
});

// Daftar item FTA yang aktif
const activeFtaItems = computed(() => {
    if (customFtaItemsMap.value[activeFtaFile.value]) {
        return customFtaItemsMap.value[activeFtaFile.value];
    }
    if (
        activeFtaFile.value === (props.selectedFta || "indo.fta") &&
        props.tkpiDatasets?.fta &&
        props.tkpiDatasets.fta.length > 0
    ) {
        return props.tkpiDatasets.fta;
    }
    return props.tkpiDatasets?.fta || [];
});

async function selectFtaFile(filename) {
    if (!filename) return;
    activeFtaFile.value = filename;
    tkpiCurrentPage.value = 1;

    // Jika belum di-cache secara lokal di vue state, panggil API
    if (!customFtaItemsMap.value[filename]) {
        if (
            filename === (props.selectedFta || "indo.fta") &&
            props.tkpiDatasets?.fta &&
            props.tkpiDatasets.fta.length > 0
        ) {
            customFtaItemsMap.value[filename] = props.tkpiDatasets.fta;
        } else {
            isFtaLoading.value = true;
            try {
                const res = await fetch(
                    `/gizi/api/nutrisurvey/data?file=${encodeURIComponent(filename)}`,
                );
                const json = await res.json();
                if (json.success && Array.isArray(json.data)) {
                    customFtaItemsMap.value[filename] = json.data;
                }
            } catch (err) {
                console.error("Gagal memuat file FTA:", err);
            } finally {
                isFtaLoading.value = false;
            }
        }
    }

    emit("update-fta", filename, activeFtaItems.value);
}

// State Pencarian Live FatSecret API
const fatsecretSearchResults = ref([]);
const isFatsecretSearching = ref(false);
const fatsecretApiError = ref(null);

const tkpiItems = computed(() => {
    if (props.selectedSource === "fta") {
        return activeFtaItems.value;
    }
    if (props.selectedSource === "fatsecret") {
        if (fatsecretSearchResults.value && fatsecretSearchResults.value.length > 0) {
            return fatsecretSearchResults.value;
        }
        return props.tkpiDatasets?.fatsecret || props.tkpiList || [];
    }
    return props.tkpiList || [];
});

// State & Logika Database Pangan (Paginasi & Filter)
const tkpiSearchQuery = ref("");
const tkpiCategoryFilter = ref("Semua");

const tkpiCurrentPage = ref(1);
const tkpiPerPage = ref(15);

// Watcher untuk query pencarian FatSecret API secara live
let searchDebounceTimer = null;
watch(tkpiSearchQuery, (newVal) => {
    tkpiCurrentPage.value = 1;
    if (props.selectedSource === "fatsecret") {
        clearTimeout(searchDebounceTimer);
        const q = newVal.trim();
        if (!q) {
            fatsecretSearchResults.value = [];
            fatsecretApiError.value = null;
            return;
        }
        isFatsecretSearching.value = true;
        searchDebounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`/gizi/api/fatsecret/search?q=${encodeURIComponent(q)}`);
                const json = await res.json();
                if (json.success) {
                    fatsecretSearchResults.value = json.data || [];
                    fatsecretApiError.value = null;
                } else {
                    fatsecretApiError.value = {
                        code: json.error_code,
                        message: json.error_message,
                        ip: json.ip_detected || '103.175.82.250',
                    };
                    if (json.data && json.data.length > 0) {
                        fatsecretSearchResults.value = json.data;
                    } else {
                        fatsecretSearchResults.value = [];
                    }
                }
            } catch (err) {
                console.error("FatSecret search error:", err);
            } finally {
                isFatsecretSearching.value = false;
            }
        }, 400);
    }
});

watch(() => props.selectedSource, (newSource) => {
    tkpiCurrentPage.value = 1;
    fatsecretApiError.value = null;
    if (newSource !== "fatsecret") {
        fatsecretSearchResults.value = [];
    }
});

const filteredTkpiList = computed(() => {
    return tkpiItems.value.filter((item) => {
        const matchesCategory =
            tkpiCategoryFilter.value === "Semua" ||
            item.kategori === tkpiCategoryFilter.value;
        const query = tkpiSearchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (item.nama && item.nama.toLowerCase().includes(query)) ||
            (item.id && item.id.toLowerCase().includes(query)) ||
            (item.kategori && item.kategori.toLowerCase().includes(query));
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

const avgBdd = computed(() => {
    if (!tkpiItems.value || tkpiItems.value.length === 0) return "0.0%";
    const totalBdd = tkpiItems.value.reduce(
        (acc, item) => acc + (Number(item.bdd) || 0),
        0,
    );
    return (totalBdd / tkpiItems.value.length).toFixed(1) + "%";
});

// Kalkulasi total bahan dari seluruh sumber database yang tersedia
// Total kumulatif seluruh database NutriSurvey internasional (17 file .fta / .dat)
const totalNutrisurveyBahan = computed(() => {
    if (props.nutrisurveyFiles && props.nutrisurveyFiles.length > 0) {
        return props.nutrisurveyFiles.reduce((acc, f) => acc + (Number(f.count) || 0), 0);
    }
    return 29447;
});

const countFta = computed(() => {
    if (activeFtaItems.value && activeFtaItems.value.length > 0) {
        return activeFtaItems.value.length;
    }
    return activeFtaInfo.value?.count || 1105;
});
const countCsv = computed(() => {
    return props.tkpiDatasets?.csv?.length || (props.selectedSource === "csv" ? tkpiItems.value.length : 1066);
});
const countTkpi2020 = computed(() => {
    return props.tkpiDatasets?.tkpi2020?.length || props.tkpiDatasets?.xlsx?.length || (props.selectedSource === "tkpi2020" || props.selectedSource === "xlsx" ? tkpiItems.value.length : 1158);
});
const countFatsecret = computed(() => {
    return props.tkpiDatasets?.fatsecret?.length || (props.selectedSource === "fatsecret" ? tkpiItems.value.length : 1100);
});

// Total seluruh database pangan yang ada (Kumulatif NutriSurvey Global + Kemenkes CSV + Modifikasi XLSX + FatSecret)
const totalBahanAllDatabases = computed(() => {
    return totalNutrisurveyBahan.value + countCsv.value + countTkpi2020.value + countFatsecret.value;
});

function formatRupiah(val) {
    if (!val && val !== 0) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatVal(val) {
    if (val === null || val === undefined || val === "" || val === "-") return "-";
    if (typeof val === "number") {
        if (Number.isNaN(val)) return "-";
        return Number.isInteger(val)
            ? val.toString()
            : val.toLocaleString("id-ID", { maximumFractionDigits: 2 });
    }
    return val;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header Info & Actions -->
        <Card className="bg-white border-slate-200 shadow-xs">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <div
                    class="flex flex-col xl:flex-row xl:items-center justify-between gap-3.5"
                >
                    <div class="min-w-0 flex-1">
                        <CardTitle
                            class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                        >
                            <Database class="h-5 w-5 text-primary shrink-0" />
                            <span>Master Database Pangan</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm mt-1 leading-relaxed text-slate-500">
                            Pilih sumber database acuan untuk seluruh modul
                            perencanaan & rancang formula menu:
                            <strong class="text-slate-800 font-semibold">{{
                                selectedSource === "fta"
                                    ? `NutriSurvey (${activeFtaInfo.country} - ${activeFtaFile} - ${countFta.toLocaleString('id-ID')} Bahan)`
                                    : (selectedSource === "tkpi2020" || selectedSource === "xlsx"
                                        ? `Modifikasi (tkpi2020.xlsx - ${tkpiDatasets.tkpi2020?.length || tkpiDatasets.xlsx?.length || (selectedSource === "tkpi2020" ? tkpiItems.length : 1158)} Bahan)`
                                        : (selectedSource === "fatsecret"
                                            ? `FatSecret (fatsecret.com - ${tkpiDatasets.fatsecret?.length || (selectedSource === "fatsecret" ? tkpiItems.length : 1100)} Bahan)`
                                            : `Kemenkes (tkpi2020.csv - ${tkpiDatasets.csv?.length || (selectedSource === "csv" ? tkpiItems.length : 1066)} Bahan)`))
                            }}</strong>.
                        </CardDescription>
                    </div>

                    <!-- Source Dataset Switcher (4 Pilihan Database Pangan) -->
                    <div
                        class="flex items-center gap-1.5 bg-slate-100/90 p-1 rounded-xl border border-slate-200 overflow-x-auto max-w-full shrink-0 self-start xl:self-center scrollbar-thin"
                    >
                        <button
                            type="button"
                            @click="emit('update-source', 'fta')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0',
                                selectedSource === 'fta'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Gunakan Database NutriSurvey Internasional"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="
                                    selectedSource === 'fta'
                                        ? 'bg-primary'
                                        : 'bg-slate-300'
                                "
                            ></span>
                            <span>NutriSurvey (Global)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="
                                    selectedSource === 'fta'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{ totalNutrisurveyBahan.toLocaleString('id-ID') }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="emit('update-source', 'csv')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0',
                                selectedSource === 'csv'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Gunakan Database Kemenkes (tkpi2020.csv)"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="
                                    selectedSource === 'csv'
                                        ? 'bg-primary'
                                        : 'bg-slate-300'
                                "
                            ></span>
                            <span>Kemenkes (tkpi2020.csv)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="
                                    selectedSource === 'csv'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{
                                    tkpiDatasets.csv?.length ||
                                    (selectedSource === "csv"
                                        ? tkpiItems.length
                                        : 1066)
                                }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="emit('update-source', 'tkpi2020')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0',
                                selectedSource === 'tkpi2020' || selectedSource === 'xlsx'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Gunakan Database Modifikasi (tkpi2020.xlsx)"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="
                                    selectedSource === 'tkpi2020' || selectedSource === 'xlsx'
                                        ? 'bg-primary'
                                        : 'bg-slate-300'
                                "
                            ></span>
                            <span>Modifikasi (tkpi2020.xlsx)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="
                                    selectedSource === 'tkpi2020' || selectedSource === 'xlsx'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{
                                    tkpiDatasets.tkpi2020?.length ||
                                    tkpiDatasets.xlsx?.length ||
                                    (selectedSource === "tkpi2020" || selectedSource === "xlsx"
                                        ? tkpiItems.length
                                        : 1158)
                                }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="emit('update-source', 'fatsecret')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap shrink-0',
                                selectedSource === 'fatsecret'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Gunakan Database FatSecret (fatsecret.com)"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="
                                    selectedSource === 'fatsecret'
                                        ? 'bg-emerald-500'
                                        : 'bg-slate-300'
                                "
                            ></span>
                            <span>FatSecret (fatsecret.com)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="
                                    selectedSource === 'fatsecret'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{
                                    tkpiDatasets.fatsecret?.length ||
                                    (selectedSource === "fatsecret"
                                        ? tkpiItems.length
                                        : 1100)
                                }}
                            </span>
                        </button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-5 space-y-4">
                <!-- Control Bar Khusus Database NutriSurvey Multi-Negara -->
                <div
                    v-if="selectedSource === 'fta'"
                    class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50/50 to-slate-50 border border-emerald-200 shadow-2xs space-y-2.5 overflow-hidden"
                >
                    <div
                        class="flex flex-col lg:flex-row lg:items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                            >
                                <Globe class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <div
                                    class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-2 flex-wrap"
                                >
                                    <span>Database NutriSurvey Internasional</span>
                                    <span
                                        class="text-[10px] px-2 py-0.5 rounded-md bg-emerald-600 text-white font-bold shrink-0"
                                    >
                                        {{ nutrisurveyFiles.length || 17 }} Negara Tersedia
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 mt-0.5">
                                    Pilih database komposisi pangan negara acuan (.fta / .dat):
                                </div>
                            </div>
                        </div>

                        <!-- Selector Dropdown & Tombol Katalog -->
                        <div class="flex items-center gap-2 w-full lg:w-auto min-w-0">
                            <select
                                :value="activeFtaFile"
                                @change="(e) => selectFtaFile(e.target.value)"
                                :disabled="isFtaLoading"
                                class="h-10 px-3.5 text-xs font-bold rounded-xl border border-emerald-300 hover:border-emerald-500 text-slate-800 bg-white shadow-2xs outline-none cursor-pointer focus:ring-2 focus:ring-emerald-500/20 flex-1 lg:flex-initial lg:w-[320px] min-w-0 truncate"
                            >
                                <option
                                    v-for="f in nutrisurveyFiles"
                                    :key="f.filename"
                                    :value="f.filename"
                                >
                                    {{ f.flag }} {{ f.country }} — {{ f.filename }} ({{ f.count.toLocaleString('id-ID') }} Bahan)
                                </option>
                            </select>

                            <button
                                type="button"
                                @click="isFtaModalOpen = true"
                                class="h-10 px-3.5 rounded-xl border border-emerald-300 hover:border-emerald-500 bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-bold transition-all shadow-2xs cursor-pointer flex items-center justify-center gap-1.5 shrink-0 whitespace-nowrap"
                                title="Lihat Daftar Lengkap Seluruh Database FTA"
                            >
                                <Layers class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span>Lihat Semua</span>
                            </button>
                        </div>
                    </div>

                    <!-- Loading Indicator saat ganti FTA -->
                    <div
                        v-if="isFtaLoading"
                        class="flex items-center gap-2 text-xs font-bold text-emerald-700 animate-pulse pt-1"
                    >
                        <Loader2 class="w-3.5 h-3.5 animate-spin" />
                        <span
                            >Memuat dan mem-parsing database {{ activeFtaInfo.country }} ({{
                                activeFtaFile
                            }})...</span
                        >
                    </div>
                </div>

                <!-- Alert Banner Khusus FatSecret IP Whitelist -->
                <div
                    v-if="selectedSource === 'fatsecret' && fatsecretApiError"
                    class="p-4 rounded-xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs space-y-2 shadow-2xs"
                >
                    <div class="flex items-center gap-2 font-bold text-sm text-amber-950">
                        <AlertTriangle class="h-4 w-4 text-amber-600 shrink-0" />
                        <span>Perhatian: IP Address Belum Terdaftar di FatSecret API</span>
                    </div>
                    <p class="leading-relaxed">
                        FatSecret membatasi akses API hanya dari IP yang telah terdaftar di menu <strong>IP Restrictions</strong> akun FatSecret Platform Anda.
                    </p>
                    <div class="p-2.5 bg-white rounded-lg border border-amber-200 font-mono text-[11px] text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                        <span>IP Terdeteksi: <strong class="text-rose-600 font-black">{{ fatsecretApiError.ip || '103.175.82.250' }}</strong></span>
                        <span class="text-slate-500 font-sans text-[10px]">Daftarkan IP ini atau gunakan 0.0.0.0/0 di platform.fatsecret.com</span>
                    </div>
                    <p class="text-[10.5px] text-amber-800">
                        *Catatan: Sementara menunggu whitelist di FatSecret selesai (biasanya ~1 jam), sistem telah menyediakan daftar bahan pangan terverifikasi di bawah ini yang tetap dapat digunakan.
                    </p>
                </div>

                <!-- Stat Summary Grid (Sejajar 4 Card) -->
                <div
                    class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5"
                >
                    <div
                        class="p-3 sm:p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 text-center flex flex-col justify-center"
                        :title="`Total Kumulatif Seluruh Database: ${totalBahanAllDatabases.toLocaleString('id-ID')} Bahan (${countTkpi2020} Modifikasi + ${totalNutrisurveyBahan} NutriSurvey Global + ${countCsv} Kemenkes + ${countFatsecret} FatSecret)`"
                    >
                        <p
                            class="text-[9.5px] sm:text-[10px] font-bold text-blue-700 uppercase tracking-wider truncate"
                        >
                            TOTAL BAHAN TERDAFTAR
                        </p>
                        <h4 class="text-lg sm:text-xl font-black text-blue-950 mt-1">
                            {{ totalBahanAllDatabases.toLocaleString("id-ID") }}
                            <span class="text-xs font-medium text-slate-500"
                                >Bahan</span
                            >
                        </h4>
                        <p class="text-[9.5px] sm:text-[10px] text-blue-600/90 font-medium mt-0.5 truncate">
                            Total dari Seluruh Database ({{ tkpiItems.length.toLocaleString("id-ID") }} Aktif)
                        </p>
                    </div>
                    <div
                        class="p-3 sm:p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[9.5px] sm:text-[10px] font-bold text-emerald-700 uppercase tracking-wider truncate"
                        >
                            KATEGORI PANGAN
                        </p>
                        <h4 class="text-lg sm:text-xl font-black text-emerald-950 mt-1">
                            {{ tkpiCategoryList.length - 1 }}
                            <span class="text-xs font-medium text-slate-500"
                                >Kelompok</span
                            >
                        </h4>
                        <p class="text-[9.5px] sm:text-[10px] text-emerald-600/90 font-medium mt-0.5 truncate">
                            Pada Database Terpilih
                        </p>
                    </div>
                    <div
                        class="p-3 sm:p-3.5 bg-amber-50/60 rounded-xl border border-amber-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[9.5px] sm:text-[10px] font-bold text-amber-700 uppercase tracking-wider truncate"
                        >
                            RATA-RATA BDD
                        </p>
                        <h4 class="text-lg sm:text-xl font-black text-amber-950 mt-1">
                            {{ avgBdd }}
                            <span class="text-xs font-medium text-slate-500"
                                >Dapat Dimakan</span
                            >
                        </h4>
                        <p class="text-[9.5px] sm:text-[10px] text-amber-600/90 font-medium mt-0.5 truncate">
                            Berat Bersih Konsumsi
                        </p>
                    </div>
                    <div
                        class="p-3 sm:p-3.5 bg-purple-50/60 rounded-xl border border-purple-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[9.5px] sm:text-[10px] font-bold text-purple-700 uppercase tracking-wider truncate"
                        >
                            SUMBER DATA
                        </p>
                        <h4
                            class="text-xs sm:text-sm font-black text-purple-950 mt-1 truncate"
                            :title="
                                selectedSource === 'fta'
                                    ? `NutriSurvey (${activeFtaInfo.country})`
                                    : (selectedSource === 'tkpi2020' || selectedSource === 'xlsx'
                                        ? 'Modifikasi (tkpi2020.xlsx)'
                                        : (selectedSource === 'fatsecret'
                                            ? 'FatSecret (fatsecret.com)'
                                            : 'Kemenkes (tkpi2020.csv)'))
                            "
                        >
                            {{
                                selectedSource === "fta"
                                    ? `NutriSurvey (${activeFtaInfo.country})`
                                    : (selectedSource === "tkpi2020" || selectedSource === "xlsx"
                                        ? "Modifikasi (tkpi2020.xlsx)"
                                        : (selectedSource === "fatsecret"
                                            ? "FatSecret (fatsecret.com)"
                                            : "Kemenkes (tkpi2020.csv)"))
                            }}
                        </h4>
                        <p class="text-[9.5px] sm:text-[10px] text-purple-600/90 font-medium mt-0.5 truncate">
                            {{ tkpiItems.length.toLocaleString("id-ID") }} Bahan Digunakan
                        </p>
                    </div>
                </div>

                <!-- Search & Filter Controls -->
                <div class="pt-2 space-y-3">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="relative w-full sm:w-96 shrink-0">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none w-3.5 h-3.5">
                                <Search
                                    v-if="!isFatsecretSearching"
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                                <Loader2
                                    v-else
                                    class="h-3.5 w-3.5 text-primary animate-spin"
                                />
                            </div>
                            <input
                                type="text"
                                v-model="tkpiSearchQuery"
                                :placeholder="
                                    selectedSource === 'fatsecret'
                                        ? 'Cari online via FatSecret API (ayam, tempe, telur)...'
                                        : 'Cari bahan makanan / kode...'
                                "
                                class="w-full pl-9 pr-3 py-1.5 text-xs font-medium rounded-lg border-slate-300 focus:ring-primary focus:border-primary"
                            />
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            Filter Kategori ({{ tkpiCategoryList.length - 1 }}
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

        <!-- Tabel Data Bahan Pangan -->
        <div
            class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
        >
            <div class="overflow-x-auto max-w-full">
                <table
                    class="w-full min-w-[2400px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="bg-slate-100/90 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none sticky top-0 z-20"
                        >
                            <th class="py-3.5 px-3 sticky left-0 bg-slate-100 z-30 min-w-[90px] border-r border-slate-200/60 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)]">
                                Kode
                            </th>
                            <th class="py-3.5 px-3 sticky left-[90px] bg-slate-100 z-30 min-w-[240px] border-r border-slate-200/60 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)]">
                                Nama Bahan Pangan
                            </th>
                            <th class="py-3.5 px-3 min-w-[160px]">Kategori</th>
                            <th class="py-3.5 px-3 min-w-[120px]">Sumber</th>
                            <th class="py-3.5 px-3 text-right min-w-[80px]">Air (g)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px] text-amber-900 bg-amber-50/50">
                                Energi (Kkal)
                            </th>
                            <th class="py-3.5 px-3 text-right min-w-[85px] text-blue-900 bg-blue-50/50">
                                Protein (g)
                            </th>
                            <th class="py-3.5 px-3 text-right min-w-[80px]">Lemak (g)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">
                                Karbohidrat (g)
                            </th>
                            <th class="py-3.5 px-3 text-right min-w-[80px]">Serat (g)</th>
                            <th class="py-3.5 px-3 text-right min-w-[75px]">Abu (g)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">Kalsium / Ca (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">Fosfor / P (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[85px]">Besi / Fe (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">Natrium / Na (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">Kalium / K (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[90px]">Tembaga / Cu (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[85px]">Seng / Zn (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[90px]">Retinol (mcg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">β-Karoten (mcg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[110px]">Karoten Total (mcg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[90px]">Tiamin / B1 (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[95px]">Riboflavin / B2 (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[90px]">Niasin / B3 (mg)</th>
                            <th class="py-3.5 px-3 text-right min-w-[90px]">Vitamin C (mg)</th>
                            <th class="py-3.5 px-3 text-center min-w-[80px] text-emerald-900 bg-emerald-50/50">
                                BDD (%)
                            </th>
                            <th class="py-3.5 px-3 text-center min-w-[120px]">Alergen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(item, idx) in paginatedTkpiList"
                            :key="item.id ? `${item.id}-${idx}` : idx"
                            class="hover:bg-slate-50/80 transition-colors group"
                        >
                            <td
                                class="p-3 font-mono font-bold text-slate-500 text-[11px] sticky left-0 bg-white group-hover:bg-slate-50/90 z-10 border-r border-slate-200/50 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)]"
                            >
                                {{ item.code || item.id }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 sticky left-[90px] bg-white group-hover:bg-slate-50/90 z-10 border-r border-slate-200/50 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)]">
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
                            <td class="p-3">
                                <span class="text-[10.5px] text-slate-500 font-mono">
                                    {{ formatVal(item.sumber) }}
                                </span>
                            </td>
                            <td class="p-3 text-right text-slate-600">
                                {{ formatVal(item.air) }}
                            </td>
                            <td class="p-3 text-right font-bold text-amber-800 bg-amber-50/30">
                                {{ formatVal(item.energi) }}
                            </td>
                            <td
                                class="p-3 text-right font-semibold text-blue-800 bg-blue-50/30"
                            >
                                {{ formatVal(item.protein) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.lemak) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.karbohidrat) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.serat) }}
                            </td>
                            <td class="p-3 text-right text-slate-600">
                                {{ formatVal(item.abu) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.kalsium) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.fosfor) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.besi) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.natrium) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.kalium) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.tembaga) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.seng) }}
                            </td>
                            <td class="p-3 text-right text-slate-600">
                                {{ formatVal(item.retinol) }}
                            </td>
                            <td class="p-3 text-right text-slate-600">
                                {{ formatVal(item.b_karoten) }}
                            </td>
                            <td class="p-3 text-right text-slate-600">
                                {{ formatVal(item.karoten_total) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.tiamin) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.riboflavin) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.niasin) }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ formatVal(item.vitamin_c) }}
                            </td>
                            <td
                                class="p-3 text-center font-bold text-emerald-800 bg-emerald-50/30"
                            >
                                {{ item.bdd !== null && item.bdd !== undefined ? item.bdd + '%' : '-' }}
                            </td>
                            <td class="p-3 text-center">
                                <span
                                    v-if="item.alergen"
                                    class="text-[10.5px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 whitespace-nowrap"
                                >
                                    {{ item.alergen }}
                                </span>
                                <span v-else class="text-slate-400">-</span>
                            </td>
                        </tr>
                        <tr v-if="filteredTkpiList.length === 0">
                            <td
                                colspan="27"
                                class="p-8 text-center text-slate-400 font-semibold"
                            >
                                Tidak ada data bahan pangan yang sesuai dengan
                                pencarian.
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
                    <button
                        type="button"
                        @click="prevTkpiPage"
                        :disabled="tkpiCurrentPage === 1"
                        class="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed font-medium cursor-pointer"
                    >
                        Sebelumnya
                    </button>
                    <span class="font-bold text-slate-800">
                        Hal {{ tkpiCurrentPage }} / {{ tkpiTotalPages }}
                    </span>
                    <button
                        type="button"
                        @click="nextTkpiPage"
                        :disabled="tkpiCurrentPage >= tkpiTotalPages"
                        class="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed font-medium cursor-pointer"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL RINCIAN SELURUH DATABASE FTA NUTRISURVEY -->
        <Teleport to="body">
            <div
                v-if="isFtaModalOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
                @click.self="isFtaModalOpen = false"
            >
                <div
                    class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden my-auto"
                >
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <Globe class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                Katalog Database NutriSurvey Internasional
                            </h3>
                            <p class="text-xs text-slate-500">
                                Tersedia {{ nutrisurveyFiles.length || 17 }} basis data komposisi pangan dari berbagai negara
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isFtaModalOpen = false"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 sm:p-5 overflow-y-auto space-y-3">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5"
                    >
                        <div
                            v-for="item in nutrisurveyFiles"
                            :key="item.filename"
                            @click="
                                selectFtaFile(item.filename);
                                isFtaModalOpen = false;
                            "
                            :class="[
                                'p-3 rounded-xl border text-left transition-all cursor-pointer relative group flex flex-col justify-between',
                                activeFtaFile === item.filename
                                    ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs'
                                    : 'bg-white border-slate-200 hover:border-emerald-300 hover:bg-slate-50/80',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ item.flag }}</span>
                                    <div>
                                        <div
                                            class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors"
                                        >
                                            {{ item.country }}
                                        </div>
                                        <div
                                            class="text-[10px] text-slate-500 font-mono"
                                        >
                                            {{ item.filename }}
                                        </div>
                                    </div>
                                </div>
                                <span
                                    v-if="activeFtaFile === item.filename"
                                    class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                                >
                                    <Check class="w-3 h-3" />
                                </span>
                            </div>

                            <div
                                class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10.5px]"
                            >
                                <span class="font-bold text-slate-700">
                                    {{ item.count.toLocaleString("id-ID") }} Bahan
                                </span>
                                <span class="text-slate-400 font-mono text-[10px]">
                                    {{ item.size_formatted }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="p-3.5 sm:p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500"
                >
                    <span class="text-[11px] text-slate-500">
                        *Klik pada salah satu negara untuk mengaktifkan database komposisi pangannya.
                    </span>
                    <button
                        type="button"
                        @click="isFtaModalOpen = false"
                        class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold transition-all cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
        </Teleport>
    </div>
</template>
