<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
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
    kops: {
        type: Array,
        default: () => [],
    },
    customAssets: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const unit = computed(() => props.unitSppg || page.props.unitSppg || null);

const currentTab = ref(props.activeTab || "logo");
const searchQuery = ref("");
const viewMode = ref("grid"); // 'grid' | 'list'

const typeTabs = computed(() => [
    {
        id: "logo",
        label: "Logo",
        count: props.stats?.total_logo || props.logos.length,
        icon: ImageIcon,
        description: "Logo resmi BGN & SPPG",
        color: "blue",
    },
    {
        id: "denah",
        label: "Denah",
        count: props.stats?.total_denah || props.denahs.length,
        icon: MapIcon,
        description: "Denah tata ruang & zonasi dapur",
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

// Filtered lists
const filteredLogos = computed(() => {
    if (!searchQuery.value.trim()) return props.logos;
    const q = searchQuery.value.toLowerCase();
    return props.logos.filter((l) => l.title?.toLowerCase().includes(q));
});

const filteredDenahs = computed(() => {
    if (!searchQuery.value.trim()) return props.denahs;
    const q = searchQuery.value.toLowerCase();
    return props.denahs.filter((d) => d.title?.toLowerCase().includes(q));
});

const filteredStrukturs = computed(() => {
    if (!searchQuery.value.trim()) return props.strukturs;
    const q = searchQuery.value.toLowerCase();
    return props.strukturs.filter((s) => s.title?.toLowerCase().includes(q));
});

const filteredPlangs = computed(() => {
    if (!searchQuery.value.trim()) return props.plangs;
    const q = searchQuery.value.toLowerCase();
    return props.plangs.filter((p) => p.title?.toLowerCase().includes(q));
});

const filteredPosters = computed(() => {
    if (!searchQuery.value.trim()) return props.posters;
    const q = searchQuery.value.toLowerCase();
    return props.posters.filter((p) => p.title?.toLowerCase().includes(q));
});

const filteredKops = computed(() => {
    if (!searchQuery.value.trim()) return props.kops;
    const q = searchQuery.value.toLowerCase();
    return props.kops.filter((k) => k.title?.toLowerCase().includes(q));
});

const activeTabInfo = computed(() => {
    return (
        typeTabs.value.find((t) => t.id === currentTab.value) ||
        typeTabs.value[0]
    );
});
</script>

<template>
    <AppLayout
        title="Aset Digital SPPG"
        subtitle="Katalog dan repositori aset digital resmi: Logo, Denah, Struktur, Plang Ruangan, Poster, serta Kop Dokumen"
        :user="user"
        :unit-sppg="unit"
    >
        <Head title="Aset Digital SPPG" />

        <div class="space-y-6 pb-16">
            <!-- ─── 1. HEADER SECTION (CLEAN WHITE THEME) ────────────────────────── -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0 shadow-xs"
                    >
                        <Layers class="w-6 h-6" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2"
                        >
                            Aset Digital SPPG
                            <span
                                class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold border border-slate-200"
                            >
                                {{ stats.total_all || 0 }} Aset
                            </span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Pusat penyimpanan dan katalog aset visual standar: Logo resmi, Denah operasional dapur, Struktur organisasi, Plang penamaan ruangan, Poster SOP, dan Template Kop Dokumen.
                        </p>
                    </div>
                </div>

                <!-- View Toggle Buttons -->
                <div class="flex items-center gap-3 shrink-0">
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
                            title="Tampilan Grid"
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
                            title="Tampilan List"
                        >
                            <List class="w-4 h-4" />
                            <span class="hidden sm:inline">List</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── 2. SUB-MENU TABS (MATCHING STANDARD PETUNJUK STYLE) ───────────── -->
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

            <!-- ─── 3. METRIC CARDS (CLEAN 6 COLUMNS) ────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
                <!-- Card 1: Logo -->
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

                <!-- Card 2: Denah -->
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
                        <p class="text-[11px] text-slate-500 mt-0.5">Layout & alur dapur</p>
                    </div>
                </div>

                <!-- Card 3: Struktur -->
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

                <!-- Card 4: Plang Ruangan -->
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

                <!-- Card 5: Poster -->
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
                        <p class="text-[11px] text-slate-500 mt-0.5">Poster edukasi SOP</p>
                    </div>
                </div>

                <!-- Card 6: Kop Dokumen -->
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
                        <p class="text-[11px] text-slate-500 mt-0.5">Template surat dinas</p>
                    </div>
                </div>
            </div>

            <!-- ─── 4. SEARCH & FILTER TOOLBAR ───────────────────────────────────── -->
            <div
                class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm"
            >
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                        <component :is="activeTabInfo.icon" class="w-4 h-4 text-primary" />
                        <span>Kategori: {{ activeTabInfo.label }}</span>
                    </div>
                </div>

                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="`Cari berkas ${activeTabInfo.label.toLowerCase()}...`"
                        class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    />
                </div>
            </div>

            <!-- ─── 5. CONTENT SECTION PER TAB ──────────────────────────────────── -->

            <!-- ── Tab 1: Logo ── -->
            <div v-if="currentTab === 'logo'" class="space-y-6">
                <div v-if="filteredLogos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="logo in filteredLogos"
                        :key="logo.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm hover:shadow-md transition-all space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ logo.title }}</h3>
                        <p class="text-xs text-slate-500">{{ logo.filename }} ({{ logo.size }})</p>
                        <a
                            v-if="logo.download_url"
                            :href="logo.download_url"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-primary hover:bg-primary/90 transition-colors shadow-2xs"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>Unduh Berkas</span>
                        </a>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-blue-50 to-blue-100/60 text-blue-600 flex items-center justify-center border border-blue-200/60 shadow-xs">
                        <ImageIcon class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Aset Logo</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder aset logo saat ini masih kosong. Berkas visual atau lambang resmi yang ditambahkan ke sub-menu logo akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        <span>Format didukung: PNG, JPG, SVG, WebP</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 2: Denah ── -->
            <div v-if="currentTab === 'denah'" class="space-y-6">
                <div v-if="filteredDenahs.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div
                        v-for="denah in filteredDenahs"
                        :key="denah.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ denah.title }}</h3>
                        <p class="text-xs text-slate-500">{{ denah.filename }} ({{ denah.size }})</p>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-emerald-50 to-emerald-100/60 text-emerald-600 flex items-center justify-center border border-emerald-200/60 shadow-xs">
                        <MapIcon class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Aset Denah</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder aset denah saat ini masih kosong. Berkas denah tata ruang atau layout zonasi dapur yang ditambahkan akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Format didukung: PDF, PNG, JPG, CAD/DWG</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 3: Struktur (BARU) ── -->
            <div v-if="currentTab === 'struktur'" class="space-y-6">
                <div v-if="filteredStrukturs.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div
                        v-for="struktur in filteredStrukturs"
                        :key="struktur.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ struktur.title }}</h3>
                        <p class="text-xs text-slate-500">{{ struktur.filename }} ({{ struktur.size }})</p>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-indigo-50 to-indigo-100/60 text-indigo-600 flex items-center justify-center border border-indigo-200/60 shadow-xs">
                        <Network class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Aset Struktur Organisasi</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder aset struktur organisasi saat ini masih kosong. Bagan struktur tata kelola atau susunan pengurus SPPG yang ditambahkan akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span>Format didukung: PDF, PNG, JPG, PPTX</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 4: Plang Ruangan ── -->
            <div v-if="currentTab === 'plang-ruangan'" class="space-y-6">
                <div v-if="filteredPlangs.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div
                        v-for="plang in filteredPlangs"
                        :key="plang.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ plang.title }}</h3>
                        <p class="text-xs text-slate-500">{{ plang.filename }} ({{ plang.size }})</p>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-amber-50 to-amber-100/60 text-amber-600 flex items-center justify-center border border-amber-200/60 shadow-xs">
                        <Signpost class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Aset Plang Ruangan</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder aset plang ruangan saat ini masih kosong. Desain plang nama ruangan atau signage fasilitas yang ditambahkan akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Format didukung: PDF, CDR, AI, PNG</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 5: Poster ── -->
            <div v-if="currentTab === 'poster'" class="space-y-6">
                <div v-if="filteredPosters.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div
                        v-for="poster in filteredPosters"
                        :key="poster.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ poster.title }}</h3>
                        <p class="text-xs text-slate-500">{{ poster.filename }} ({{ poster.size }})</p>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-purple-50 to-purple-100/60 text-purple-600 flex items-center justify-center border border-purple-200/60 shadow-xs">
                        <FileImage class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Aset Poster</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder aset poster saat ini masih kosong. Berkas poster edukasi, standar higienitas atau SOP yang ditambahkan akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                        <span>Format didukung: PDF, PNG, JPG (Ukuran A3/A2)</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 6: Kop Dokumen ── -->
            <div v-if="currentTab === 'kop-dokumen'" class="space-y-6">
                <div v-if="filteredKops.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div
                        v-for="kop in filteredKops"
                        :key="kop.id"
                        class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm space-y-4"
                    >
                        <h3 class="text-sm font-bold text-slate-900">{{ kop.title }}</h3>
                        <p class="text-xs text-slate-500">{{ kop.filename }} ({{ kop.size }})</p>
                    </div>
                </div>

                <div
                    v-else
                    class="py-20 px-8 text-center rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center space-y-5"
                >
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-b from-rose-50 to-rose-100/60 text-rose-600 flex items-center justify-center border border-rose-200/60 shadow-xs">
                        <FileText class="w-10 h-10" />
                    </div>
                    <div class="space-y-2 max-w-md mx-auto">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Belum Ada Template Kop Dokumen</h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Folder template kop dokumen saat ini masih kosong. Berkas template kop surat atau formulir resmi yang ditambahkan akan otomatis tampil di sini.
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 text-slate-500 text-xs font-medium border border-slate-200/60">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        <span>Format didukung: DOCX, PDF, XLSX, ODT</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
