<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import AsetDigitalKopDokumenTab from "./Partials/AsetDigitalKopDokumenTab.vue";
import {
    Layers,
    Image as ImageIcon,
    Map as MapIcon,
    Network,
    Signpost,
    FileImage,
    FileText,
    Download,
    Eye,
    Search,
    Inbox,
    Sparkles,
    Check,
    X,
    LayoutGrid,
    List,
    ExternalLink,
    Maximize2,
} from "lucide-vue-next";

const props = defineProps({
    activeTab: {
        type: String,
        default: "logo",
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    logos: {
        type: Array,
        default: () => [],
    },
    denahs: {
        type: Array,
        default: () => [],
    },
    strukturs: {
        type: Array,
        default: () => [],
    },
    plangs: {
        type: Array,
        default: () => [],
    },
    posters: {
        type: Array,
        default: () => [],
    },
    kopConfig: {
        type: Object,
        default: null,
    },
    kops: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_logo: 0,
            total_denah: 0,
            total_struktur: 0,
            total_plang: 0,
            total_poster: 0,
            total_kop: 0,
            total_all: 0,
        }),
    },
});

const currentTab = ref(props.activeTab || "logo");
const searchQuery = ref("");
const viewMode = ref("grid"); // 'grid' | 'list'
const selectedPreviewItem = ref(null);

const typeTabs = computed(() => [
    {
        id: "logo",
        label: "Logo",
        count: props.stats?.total_logo || props.logos.length,
        icon: ImageIcon,
        description: "Varian logo resmi BGN & SPPG",
        color: "blue",
    },
    {
        id: "denah",
        label: "Denah",
        count: props.stats?.total_denah || props.denahs.length,
        icon: MapIcon,
        description: "Denah & alur operasional dapur",
        color: "emerald",
    },
    {
        id: "struktur",
        label: "Struktur",
        count: props.stats?.total_struktur || props.strukturs.length,
        icon: Network,
        description: "Struktur organisasi unit SPPG",
        color: "indigo",
    },
    {
        id: "plang-ruangan",
        label: "Plang Ruangan",
        count: props.stats?.total_plang || props.plangs.length,
        icon: Signpost,
        description: "Plang nama ruangan & signage",
        color: "amber",
    },
    {
        id: "poster",
        label: "Poster",
        count: props.stats?.total_poster || props.posters.length,
        icon: FileImage,
        description: "Poster SOP, higienitas & K3",
        color: "purple",
    },
    {
        id: "kop-dokumen",
        label: "Kop Dokumen",
        count: props.stats?.total_kop || props.kops.length,
        icon: FileText,
        description: "Template kop surat & formulir",
        color: "rose",
    },
]);

function switchTab(tabKey) {
    currentTab.value = tabKey;
    searchQuery.value = "";
    window.history.replaceState(
        {},
        "",
        route("aset-digital." + (tabKey === "all" ? "index" : tabKey)),
    );
}

// Filtered items computed
const filteredLogos = computed(() => {
    if (!searchQuery.value.trim()) return props.logos;
    const q = searchQuery.value.toLowerCase();
    return props.logos.filter((l) => l.title?.toLowerCase().includes(q) || l.filename?.toLowerCase().includes(q));
});

const filteredDenahs = computed(() => {
    if (!searchQuery.value.trim()) return props.denahs;
    const q = searchQuery.value.toLowerCase();
    return props.denahs.filter((d) => d.title?.toLowerCase().includes(q) || d.filename?.toLowerCase().includes(q));
});

const filteredStrukturs = computed(() => {
    if (!searchQuery.value.trim()) return props.strukturs;
    const q = searchQuery.value.toLowerCase();
    return props.strukturs.filter((s) => s.title?.toLowerCase().includes(q) || s.filename?.toLowerCase().includes(q));
});

const filteredPlangs = computed(() => {
    if (!searchQuery.value.trim()) return props.plangs;
    const q = searchQuery.value.toLowerCase();
    return props.plangs.filter((p) => p.title?.toLowerCase().includes(q) || p.filename?.toLowerCase().includes(q));
});

const filteredPosters = computed(() => {
    if (!searchQuery.value.trim()) return props.posters;
    const q = searchQuery.value.toLowerCase();
    return props.posters.filter((p) => p.title?.toLowerCase().includes(q) || p.filename?.toLowerCase().includes(q));
});

const activeTabInfo = computed(() => {
    return (
        typeTabs.value.find((t) => t.id === currentTab.value) ||
        typeTabs.value[0]
    );
});

const currentItems = computed(() => {
    switch (currentTab.value) {
        case "logo":
            return filteredLogos.value;
        case "denah":
            return filteredDenahs.value;
        case "struktur":
            return filteredStrukturs.value;
        case "plang-ruangan":
            return filteredPlangs.value;
        case "poster":
            return filteredPosters.value;
        default:
            return [];
    }
});

function openPreview(item) {
    selectedPreviewItem.value = item;
}

function closePreview() {
    selectedPreviewItem.value = null;
}

watch(selectedPreviewItem, (val) => {
    if (val) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "";
    }
});

function handleKeydown(e) {
    if (e.key === "Escape" && selectedPreviewItem.value) {
        closePreview();
    }
}

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
    <AppLayout
        title="Aset Digital SPPG"
        subtitle="Katalog dan repositori aset digital resmi: Logo, Denah, Struktur, Plang Ruangan, Poster, serta Kop Dokumen"
        :user="$page.props.auth?.user"
        :unit-sppg="unitSppg"
    >
        <Head title="Aset Digital SPPG" />

        <div class="space-y-5">
            <!-- ─── 1. HERO HEADER BANNER ────────────────────────────────────────── -->
            <div
                class="p-6 sm:p-7 rounded-3xl bg-white border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden"
            >
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-bold border border-primary/20">
                        <Layers class="w-3.5 h-3.5" />
                        <span>Katalog Aset Visual Standar</span>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">
                            Aset Digital SPPG
                        </h1>
                        <span
                            class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold border border-slate-200"
                        >
                            {{ stats.total_all || 0 }} Berkas
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        Pusat repositori aset grafis dan visual standar operasional: Logo resmi, Denah alur dapur, Struktur organisasi, Signage plang ruangan, Poster edukasi & SOP, serta Template Kop Dokumen.
                    </p>
                </div>

                <!-- View Toggle Buttons (Only when browsing assets) -->
                <div v-if="currentTab !== 'kop-dokumen'" class="flex items-center gap-3 shrink-0">
                    <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200/80">
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            :class="[
                                'p-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'grid'
                                    ? 'bg-white text-primary shadow-xs font-bold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Tampilan Grid Galeri"
                        >
                            <LayoutGrid class="w-4 h-4" />
                            <span class="hidden sm:inline">Grid</span>
                        </button>

                        <button
                            type="button"
                            @click="viewMode = 'list'"
                            :class="[
                                'p-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'list'
                                    ? 'bg-white text-primary shadow-xs font-bold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Tampilan Daftar Tabel"
                        >
                            <List class="w-4 h-4" />
                            <span class="hidden sm:inline">List</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── 2. SUB-MENU TABS ─────────────────────────────────────────────── -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.id"
                    type="button"
                    @click="switchTab(tab.id)"
                    :class="[
                        'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition-all duration-150 flex items-center gap-2 cursor-pointer border shadow-2xs',
                        currentTab === tab.id
                            ? 'bg-primary text-white border-primary shadow-sm shadow-primary/25 scale-[1.01]'
                            : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50 hover:text-slate-900',
                    ]"
                >
                    <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ tab.label }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-[11px] font-extrabold rounded-full',
                            currentTab === tab.id
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-600 border border-slate-200',
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- ─── 3. METRIC CARDS (Hidden on Kop Dokumen) ───────────────────────── -->
            <div v-if="currentTab !== 'kop-dokumen'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
                <div
                    @click="switchTab('logo')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'logo'
                            ? 'bg-blue-50/60 border-blue-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Logo</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                            <ImageIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_logo || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Varian logo resmi</p>
                    </div>
                </div>

                <div
                    @click="switchTab('denah')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'denah'
                            ? 'bg-emerald-50/60 border-emerald-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Denah</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <MapIcon class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_denah || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Layout dapur</p>
                    </div>
                </div>

                <div
                    @click="switchTab('struktur')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'struktur'
                            ? 'bg-indigo-50/60 border-indigo-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Struktur</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                            <Network class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_struktur || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Bagan organisasi</p>
                    </div>
                </div>

                <div
                    @click="switchTab('plang-ruangan')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'plang-ruangan'
                            ? 'bg-amber-50/60 border-amber-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Plang</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                            <Signpost class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_plang || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Signage ruangan</p>
                    </div>
                </div>

                <div
                    @click="switchTab('poster')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'poster'
                            ? 'bg-purple-50/60 border-purple-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Poster</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                            <FileImage class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_poster || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Poster & SOP</p>
                    </div>
                </div>

                <div
                    @click="switchTab('kop-dokumen')"
                    :class="[
                        'p-4 sm:p-5 rounded-2xl border transition-all cursor-pointer relative overflow-hidden flex flex-col justify-between',
                        currentTab === 'kop-dokumen'
                            ? 'bg-rose-50/60 border-rose-200 shadow-xs'
                            : 'bg-white border-slate-100 hover:border-slate-200 shadow-sm',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kop Dokumen</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                            <FileText class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-xl sm:text-2xl font-black text-slate-900">
                            {{ stats.total_kop || 0 }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Template dinas</p>
                    </div>
                </div>
            </div>

            <!-- ─── 4. SEARCH & FILTER TOOLBAR (Hidden on Kop Dokumen) ─────────────── -->
            <div
                v-if="currentTab !== 'kop-dokumen'"
                class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm"
            >
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            :placeholder="`Cari berkas ${activeTabInfo.label.toLowerCase()}...`"
                            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3 text-xs text-slate-500 w-full sm:w-auto justify-between sm:justify-end">
                    <span class="font-medium">
                        Menampilkan <strong>{{ currentItems.length }}</strong> dari <strong>{{ activeTabInfo.count }}</strong> berkas
                    </span>
                </div>
            </div>

            <!-- ─── 5. ASSET ITEMS GALLERY (When currentTab is NOT kop-dokumen) ────── -->
            <div v-if="currentTab !== 'kop-dokumen'">
                <!-- GRID VIEW -->
                <div
                    v-if="viewMode === 'grid' && currentItems.length > 0"
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5"
                >
                    <div
                        v-for="item in currentItems"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group"
                    >
                        <!-- Visual Preview Area -->
                        <div
                            class="h-44 sm:h-48 bg-slate-50 border-b border-slate-100 flex items-center justify-center p-3 relative overflow-hidden select-none cursor-pointer"
                            @click="openPreview(item)"
                        >
                            <!-- Checkerboard for Transparent Images -->
                            <div
                                v-if="item.is_image"
                                class="absolute inset-0 opacity-[0.03] bg-[radial-gradient(#000_1px,transparent_1px)] [background-size:12px_12px]"
                            ></div>

                            <img
                                v-if="item.is_image"
                                :src="item.url"
                                :alt="item.title"
                                class="max-h-full max-w-full object-contain mx-auto transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            />

                            <div
                                v-else-if="item.is_pdf"
                                class="flex flex-col items-center justify-center text-rose-500 space-y-2"
                            >
                                <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center border border-rose-100 shadow-2xs">
                                    <FileText class="w-8 h-8 text-rose-600" />
                                </div>
                                <span class="text-xs font-bold text-slate-700">Berkas Dokumen PDF</span>
                            </div>

                            <div
                                v-else
                                class="flex flex-col items-center justify-center text-slate-400 space-y-2"
                            >
                                <FileText class="w-12 h-12" />
                                <span class="text-xs font-semibold">{{ item.extension }}</span>
                            </div>

                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 rounded-lg bg-white/90 backdrop-blur-xs text-slate-900 text-xs font-bold shadow-sm hover:bg-white flex items-center gap-1 cursor-pointer"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    <span>Lihat</span>
                                </button>
                            </div>

                            <!-- Format Badge Top-Right -->
                            <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-md bg-white/90 backdrop-blur-xs text-slate-700 text-[10px] font-extrabold border border-slate-200/80 shadow-2xs">
                                {{ item.extension }}
                            </span>
                        </div>

                        <!-- Card Info & Download -->
                        <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
                            <div class="space-y-1">
                                <h3
                                    class="text-xs sm:text-sm font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-primary transition-colors cursor-pointer"
                                    :title="item.title"
                                    @click="openPreview(item)"
                                >
                                    {{ item.title }}
                                </h3>
                                <p class="text-[11px] text-slate-400 truncate" :title="item.filename">
                                    {{ item.filename }}
                                </p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold text-slate-500">
                                    {{ item.size }}
                                </span>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openPreview(item)"
                                        class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer"
                                        title="Pratinjau detail"
                                    >
                                        <Maximize2 class="w-3.5 h-3.5" />
                                    </button>

                                    <a
                                        :href="item.url"
                                        :download="item.filename"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary hover:bg-primary/90 transition shadow-2xs cursor-pointer"
                                        title="Unduh berkas asli"
                                    >
                                        <Download class="w-3.5 h-3.5" />
                                        <span>Unduh</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIST VIEW -->
                <div
                    v-else-if="viewMode === 'list' && currentItems.length > 0"
                    class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                                    <th class="py-3 px-4 w-12 text-center">Preview</th>
                                    <th class="py-3 px-4">Judul & Nama Berkas</th>
                                    <th class="py-3 px-4 w-24">Format</th>
                                    <th class="py-3 px-4 w-28">Ukuran</th>
                                    <th class="py-3 px-4 w-36 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="item in currentItems"
                                    :key="item.id"
                                    class="hover:bg-slate-50/70 transition"
                                >
                                    <td class="py-2.5 px-4 text-center cursor-pointer" @click="openPreview(item)">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden mx-auto">
                                            <img
                                                v-if="item.is_image"
                                                :src="item.url"
                                                :alt="item.title"
                                                class="max-h-full max-w-full object-contain"
                                            />
                                            <FileText v-else class="w-5 h-5 text-slate-500" />
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-4">
                                        <div
                                            class="font-bold text-slate-900 hover:text-primary cursor-pointer transition"
                                            @click="openPreview(item)"
                                        >
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">{{ item.filename }}</div>
                                    </td>
                                    <td class="py-2.5 px-4">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px] border border-slate-200">
                                            {{ item.extension }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 font-medium text-slate-600">
                                        {{ item.size }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                @click="openPreview(item)"
                                                class="px-2.5 py-1 rounded-lg text-slate-700 hover:bg-slate-100 border border-slate-200 text-xs font-semibold cursor-pointer"
                                            >
                                                Lihat
                                            </button>
                                            <a
                                                :href="item.url"
                                                :download="item.filename"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-primary hover:bg-primary/90 transition shadow-2xs cursor-pointer"
                                            >
                                                <Download class="w-3 h-3" />
                                                <span>Unduh</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- EMPTY STATE -->
                <div
                    v-else
                    class="py-16 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-4"
                >
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center border border-slate-200/80">
                        <component :is="activeTabInfo.icon" class="w-8 h-8" />
                    </div>
                    <div class="space-y-1 max-w-md mx-auto">
                        <h3 class="text-base sm:text-lg font-bold text-slate-900">
                            {{ searchQuery ? 'Berkas Tidak Ditemukan' : `Belum Ada Aset ${activeTabInfo.label}` }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ searchQuery ? `Tidak ada berkas yang sesuai dengan kata kunci "${searchQuery}".` : activeTabInfo.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── 6. KOP DOKUMEN TAB COMPONENT ─────────────────────────────────── -->
            <div v-if="currentTab === 'kop-dokumen'">
                <AsetDigitalKopDokumenTab
                    :kop-config="props.kopConfig"
                    :unit-sppg="unitSppg"
                />
            </div>
        </div>

        <!-- ─── LIGHTBOX PREVIEW MODAL ───────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="selectedPreviewItem"
                    class="fixed inset-0 z-[999999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
                    @click.self="closePreview"
                >
                    <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
                        <!-- Modal Header -->
                        <div class="p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex items-center justify-between gap-4 bg-slate-50/70">
                            <div class="min-w-0">
                                <h3 class="font-bold text-sm sm:text-base text-slate-900 truncate">
                                    {{ selectedPreviewItem.title }}
                                </h3>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ selectedPreviewItem.filename }} • {{ selectedPreviewItem.size }} • {{ selectedPreviewItem.extension }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="selectedPreviewItem.url"
                                    :download="selectedPreviewItem.filename"
                                    class="px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary/90 text-white text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>Unduh</span>
                                </a>
                                <button
                                    type="button"
                                    @click="closePreview"
                                    class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer"
                                >
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body (Preview Content) -->
                        <div class="p-4 sm:p-6 overflow-y-auto flex-1 flex items-center justify-center bg-slate-900/5 min-h-[300px]">
                            <img
                                v-if="selectedPreviewItem.is_image"
                                :src="selectedPreviewItem.url"
                                :alt="selectedPreviewItem.title"
                                class="max-h-[65vh] max-w-full object-contain rounded-xl shadow-sm"
                            />

                            <div
                                v-else-if="selectedPreviewItem.is_pdf"
                                class="w-full h-[65vh] rounded-xl overflow-hidden border border-slate-200 bg-white"
                            >
                                <iframe
                                    :src="selectedPreviewItem.url"
                                    class="w-full h-full border-none"
                                ></iframe>
                            </div>

                            <div v-else class="text-center p-8 text-slate-500">
                                <FileText class="w-16 h-16 mx-auto mb-3 text-slate-400" />
                                <p class="font-bold text-sm text-slate-800">{{ selectedPreviewItem.filename }}</p>
                                <p class="text-xs text-slate-500 mt-1">Pratinjau langsung tidak didukung untuk format ini.</p>
                                <a
                                    :href="selectedPreviewItem.url"
                                    :download="selectedPreviewItem.filename"
                                    class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold shadow-xs"
                                >
                                    <Download class="w-4 h-4" />
                                    <span>Unduh Berkas Sekarang</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
