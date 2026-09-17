<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    BookOpen,
    FileCheck2,
    FileText,
    BookMarked,
    Search,
    Filter,
    Eye,
    Download,
    ExternalLink,
    X,
    ChevronRight,
    ChevronLeft,
    CheckCircle2,
    ShieldCheck,
    Layers,
    LayoutGrid,
    List,
    Maximize2,
    Minimize2,
    Sparkles,
    Utensils,
    Package,
    Truck,
    Info,
    Check,
} from "lucide-vue-next";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    documents: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    activeType: {
        type: String,
        default: "all", // 'all' | 'sop' | 'juknis' | 'pedoman'
    },
    selectedId: {
        type: String,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const unitSppg = computed(() => page.props.unitSppg || null);

// ─── Filter & Search State ───────────────────────────────────────────────────
const selectedType = ref(props.activeType || "all");
const searchQuery = ref("");
const selectedCategory = ref("all");
const viewMode = ref("grid"); // 'grid' | 'table'
const activeDoc = ref(null);
const isPreviewModalOpen = ref(false);
const isFullscreen = ref(false);

watch(
    () => props.activeType,
    (newVal) => {
        if (newVal) selectedType.value = newVal;
    }
);

// ─── Types Tab Options ───────────────────────────────────────────────────────
const typeTabs = computed(() => [
    {
        id: "all",
        label: "Semua Petunjuk",
        count: props.documents.length,
        icon: BookOpen,
    },
    {
        id: "sop",
        label: "SOP",
        count: props.summary.total_sop || props.documents.filter((d) => d.type === "sop").length,
        icon: FileCheck2,
    },
    {
        id: "juknis",
        label: "Juknis",
        count: props.summary.total_juknis || props.documents.filter((d) => d.type === "juknis").length,
        icon: FileText,
    },
    {
        id: "pedoman",
        label: "Pedoman",
        count: props.summary.total_pedoman || props.documents.filter((d) => d.type === "pedoman").length,
        icon: BookMarked,
    },
]);

function setTypeTab(typeId) {
    selectedType.value = typeId;
    selectedCategory.value = "all";
}

// ─── Category Options for Active Type ────────────────────────────────────────
const availableCategories = computed(() => {
    const docs = selectedType.value === "all"
        ? props.documents
        : props.documents.filter((d) => d.type === selectedType.value);

    const map = { all: docs.length };
    docs.forEach((d) => {
        const cat = d.category || "Umum";
        map[cat] = (map[cat] || 0) + 1;
    });
    return map;
});

const categoryColors = {
    "Perencanaan & Pengadaan": {
        bg: "bg-indigo-50 text-indigo-700 border-indigo-200",
        dot: "bg-indigo-500",
    },
    "Penerimaan & Penyimpanan Bahan": {
        bg: "bg-amber-50 text-amber-700 border-amber-200",
        dot: "bg-amber-500",
    },
    "Persiapan & Pengolahan Makanan": {
        bg: "bg-emerald-50 text-emerald-700 border-emerald-200",
        dot: "bg-emerald-500",
    },
    "Pemorsian & Distribusi": {
        bg: "bg-blue-50 text-blue-700 border-blue-200",
        dot: "bg-blue-500",
    },
    "Pengendalian Mutu & Keamanan Pangan": {
        bg: "bg-rose-50 text-rose-700 border-rose-200",
        dot: "bg-rose-500",
    },
    "Sanitasi, Higiene & Pemeliharaan": {
        bg: "bg-purple-50 text-purple-700 border-purple-200",
        dot: "bg-purple-500",
    },
    "Petunjuk Teknis (Juknis)": {
        bg: "bg-cyan-50 text-cyan-700 border-cyan-200",
        dot: "bg-cyan-500",
    },
    "Pedoman Operasional": {
        bg: "bg-teal-50 text-teal-700 border-teal-200",
        dot: "bg-teal-500",
    },
};

function getCategoryStyle(catName) {
    return (
        categoryColors[catName] || {
            bg: "bg-slate-50 text-slate-700 border-slate-200",
            dot: "bg-slate-500",
        }
    );
}

function getTypeBadgeStyle(type) {
    if (type === "sop") {
        return "bg-slate-900 text-white";
    } else if (type === "juknis") {
        return "bg-cyan-700 text-white";
    } else if (type === "pedoman") {
        return "bg-teal-700 text-white";
    }
    return "bg-slate-800 text-white";
}

// ─── Filtered Documents ──────────────────────────────────────────────────────
const filteredDocuments = computed(() => {
    return props.documents.filter((doc) => {
        // Filter Tipe Dokumen (Semua / SOP / Juknis / Pedoman)
        const matchesType =
            selectedType.value === "all" || doc.type === selectedType.value;

        // Filter Kategori
        const matchesCategory =
            selectedCategory.value === "all" ||
            doc.category === selectedCategory.value;

        // Filter Pencarian
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !q ||
            doc.title.toLowerCase().includes(q) ||
            doc.code.toLowerCase().includes(q) ||
            (doc.short_title && doc.short_title.toLowerCase().includes(q)) ||
            (doc.description && doc.description.toLowerCase().includes(q)) ||
            (doc.category && doc.category.toLowerCase().includes(q)) ||
            String(doc.number).includes(q);

        return matchesType && matchesCategory && matchesQuery;
    });
});

// ─── Preview Modal Actions ───────────────────────────────────────────────────
function openPreview(doc) {
    activeDoc.value = doc;
    isPreviewModalOpen.value = true;
    document.body.style.overflow = "hidden";
}

function closePreview() {
    isPreviewModalOpen.value = false;
    activeDoc.value = null;
    isFullscreen.value = false;
    document.body.style.overflow = "";
}

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
}

function navigateDoc(direction) {
    if (!activeDoc.value) return;
    const currentList = filteredDocuments.value;
    const currentIndex = currentList.findIndex((d) => d.id === activeDoc.value.id);
    if (currentIndex === -1) return;

    let targetIndex = currentIndex + direction;
    if (targetIndex < 0) targetIndex = currentList.length - 1;
    if (targetIndex >= currentList.length) targetIndex = 0;

    activeDoc.value = currentList[targetIndex];
}

function handleKeydown(e) {
    if (!isPreviewModalOpen.value) return;
    if (e.key === "Escape") {
        closePreview();
    } else if (e.key === "ArrowLeft") {
        navigateDoc(-1);
    } else if (e.key === "ArrowRight") {
        navigateDoc(1);
    }
}

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    if (props.selectedId) {
        const found = props.documents.find((d) => d.id === props.selectedId);
        if (found) openPreview(found);
    }
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
    <AppLayout
        title="Petunjuk Operasional SPPG"
        subtitle="Pedoman, Petunjuk Teknis (Juknis), dan Standar Operasional Prosedur (SOP) Penyelenggaraan Makan Bergizi Gratis (MBG)"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Petunjuk (SOP, Juknis & Pedoman)" />

        <div class="space-y-6 pb-16">
            <!-- ─── HEADER & CTA ────────────────────────────────────────────── -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0 shadow-xs"
                    >
                        <BookOpen class="w-6 h-6" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2"
                        >
                            Petunjuk Operasional SPPG
                            <span
                                class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold border border-slate-200"
                            >
                                {{ documents.length }} Dokumen
                            </span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Pusat regulasi teknis, pedoman kerja, juknis tata kelola, dan 42 standar operasional prosedur unit SPPG.
                        </p>
                    </div>
                </div>

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
                            title="Tampilan Kotak"
                        >
                            <LayoutGrid class="w-4 h-4" />
                            <span class="hidden sm:inline">Kartu</span>
                        </button>

                        <button
                            type="button"
                            @click="viewMode = 'table'"
                            :class="[
                                'p-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
                                viewMode === 'table'
                                    ? 'bg-white text-primary shadow-xs font-bold'
                                    : 'text-slate-600 hover:text-slate-900',
                            ]"
                            title="Tampilan Tabel"
                        >
                            <List class="w-4 h-4" />
                            <span class="hidden sm:inline">Tabel</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─── DOCUMENT TYPE TABS (SEMUA / SOP / JUKNIS / PEDOMAN) ─────── -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.id"
                    type="button"
                    @click="setTypeTab(tab.id)"
                    :class="[
                        'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap transition-all duration-150 flex items-center gap-2 cursor-pointer border shadow-2xs',
                        selectedType === tab.id
                            ? 'bg-primary text-white border-primary shadow-sm shadow-primary/25 scale-[1.01]'
                            : 'bg-white text-slate-600 border-slate-200/80 hover:bg-slate-50 hover:text-slate-900',
                    ]"
                >
                    <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ tab.label }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 text-[11px] font-extrabold rounded-full',
                            selectedType === tab.id
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-600 border border-slate-200',
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- ─── METRIC CARDS ────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Petunjuk -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Total Petunjuk
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100"
                        >
                            <BookOpen class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-slate-900">
                            {{ summary.total_petunjuk || documents.length }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Dokumen acuan operasional</p>
                    </div>
                </div>

                <!-- Card 2: Standar Operasional Prosedur (SOP) -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Standar Mutu (SOP)
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100"
                        >
                            <FileCheck2 class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-indigo-600">
                            {{ summary.total_sop || 42 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">42 instruksi kerja teknis</p>
                    </div>
                </div>

                <!-- Card 3: Petunjuk Teknis (Juknis) -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Juknis Tata Kelola
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center border border-cyan-100"
                        >
                            <FileText class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-cyan-600">
                            {{ summary.total_juknis || 1 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">SK BGN Tata Kelola 2026</p>
                    </div>
                </div>

                <!-- Card 4: Pedoman SPPG -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Pedoman SPPG
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100"
                        >
                            <BookMarked class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-teal-600">
                            {{ summary.total_pedoman || 1 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Buku panduan unit SPPG</p>
                    </div>
                </div>
            </div>

            <!-- ─── FILTER & SEARCH BAR ──────────────────────────────────────── -->
            <div
                class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4"
            >
                <!-- Search Input -->
                <div class="relative w-full md:w-80">
                    <Search
                        class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari petunjuk, juknis, SOP..."
                        class="w-full pl-10 pr-9 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Category Filter & Counter -->
                <div
                    class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-between md:justify-end"
                >
                    <!-- Category Select -->
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-slate-400 shrink-0" />
                        <select
                            v-model="selectedCategory"
                            class="bg-slate-50 border border-slate-200 text-slate-700 text-xs sm:text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium"
                        >
                            <option value="all">
                                Semua Klaster ({{ availableCategories.all || 0 }})
                            </option>
                            <option
                                v-for="(count, catName) in availableCategories"
                                :key="catName"
                                v-show="catName !== 'all'"
                                :value="catName"
                            >
                                {{ catName }} ({{ count }})
                            </option>
                        </select>
                    </div>

                    <span class="text-xs text-slate-500 font-medium">
                        Menampilkan
                        <strong class="text-slate-800">{{ filteredDocuments.length }}</strong>
                        dari {{ documents.length }} Dokumen
                    </span>
                </div>
            </div>

            <!-- ─── EMPTY STATE ──────────────────────────────────────────────── -->
            <div
                v-if="filteredDocuments.length === 0"
                class="bg-white rounded-2xl border border-slate-100 p-12 text-center max-w-md mx-auto shadow-sm"
            >
                <div
                    class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400"
                >
                    <FileText class="w-6 h-6" />
                </div>
                <h3 class="text-base font-bold text-slate-800">Tidak ada dokumen ditemukan</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Coba ubah kata kunci pencarian atau ganti tab/kategori.
                </p>
                <button
                    type="button"
                    @click="
                        searchQuery = '';
                        selectedCategory = 'all';
                        selectedType = 'all';
                    "
                    class="mt-4 px-4 py-2 bg-primary text-white text-xs font-semibold rounded-xl hover:bg-primary/95 shadow-sm transition-all cursor-pointer"
                >
                    Reset Filter
                </button>
            </div>

            <!-- ─── VIEW 1: GRID VIEW ────────────────────────────────────────── -->
            <div
                v-else-if="viewMode === 'grid'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
            >
                <div
                    v-for="doc in filteredDocuments"
                    :key="doc.id"
                    class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group"
                >
                    <!-- Card Top -->
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <!-- Code & Category Badge -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    :class="[
                                        'text-xs font-black px-2.5 py-0.5 rounded-lg tracking-wider whitespace-nowrap shadow-2xs',
                                        getTypeBadgeStyle(doc.type),
                                    ]"
                                >
                                    {{ doc.code }}
                                </span>
                                <span
                                    :class="[
                                        'text-[11px] font-semibold px-2.5 py-0.5 rounded-full border inline-flex items-center gap-1 whitespace-nowrap',
                                        getCategoryStyle(doc.category).bg,
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full shrink-0',
                                            getCategoryStyle(doc.category).dot,
                                        ]"
                                    ></span>
                                    <span>{{ doc.category }}</span>
                                </span>
                            </div>

                            <!-- File Size -->
                            <span class="text-[11px] text-slate-400 font-semibold shrink-0">
                                {{ doc.size_formatted }}
                            </span>
                        </div>

                        <!-- Title & Description -->
                        <div class="pt-1">
                            <h3
                                class="font-bold text-slate-900 text-sm sm:text-base leading-snug group-hover:text-primary transition-colors line-clamp-2"
                                :title="doc.title"
                            >
                                {{ doc.title }}
                            </h3>
                            <p class="text-xs text-slate-500 font-normal leading-relaxed mt-1.5 line-clamp-2">
                                {{ doc.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Card Bottom Actions -->
                    <div
                        class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between gap-2"
                    >
                        <button
                            type="button"
                            @click="openPreview(doc)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-primary hover:bg-primary/95 text-white text-xs font-semibold rounded-xl shadow-xs transition-all cursor-pointer active:scale-98"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            <span>Preview Dokumen</span>
                        </button>

                        <a
                            :href="doc.stream_url"
                            target="_blank"
                            class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors"
                            title="Buka di Tab Baru"
                        >
                            <ExternalLink class="w-3.5 h-3.5" />
                        </a>

                        <a
                            :href="doc.download_url"
                            class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl border border-slate-200 transition-colors"
                            title="Unduh Berkas PDF"
                        >
                            <Download class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- ─── VIEW 2: TABLE VIEW ───────────────────────────────────────── -->
            <div
                v-else-if="viewMode === 'table'"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead
                            class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase tracking-wider text-[11px]"
                        >
                            <tr>
                                <th class="py-3.5 px-4 text-center w-28 whitespace-nowrap">Kode</th>
                                <th class="py-3.5 px-4 min-w-[280px] whitespace-nowrap">Nama Dokumen</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Jenis / Klaster</th>
                                <th class="py-3.5 px-4 whitespace-nowrap">Ukuran</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="doc in filteredDocuments"
                                :key="doc.id"
                                class="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                                @click="openPreview(doc)"
                            >
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'font-extrabold px-2.5 py-1 rounded-lg text-xs whitespace-nowrap inline-block tracking-wider',
                                            getTypeBadgeStyle(doc.type),
                                        ]"
                                    >
                                        {{ doc.code }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 group-hover:text-primary transition-colors">
                                    <div class="flex items-center gap-2">
                                        <FileText class="w-4 h-4 text-slate-400 shrink-0" />
                                        <span class="leading-tight">{{ doc.title }}</span>
                                    </div>
                                    <p class="text-xs font-normal text-slate-500 mt-0.5 line-clamp-1 max-w-xl">
                                        {{ doc.description }}
                                    </p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'text-[11px] font-semibold px-2.5 py-0.5 rounded-full border inline-flex items-center gap-1 whitespace-nowrap',
                                            getCategoryStyle(doc.category).bg,
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full shrink-0',
                                                getCategoryStyle(doc.category).dot,
                                            ]"
                                        ></span>
                                        <span>{{ doc.category }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 font-medium whitespace-nowrap">
                                    {{ doc.size_formatted }}
                                </td>
                                <td class="py-3.5 px-4 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openPreview(doc)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-xl text-xs font-bold transition-all cursor-pointer"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="doc.stream_url"
                                            target="_blank"
                                            class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors"
                                            title="Buka di Tab Baru"
                                        >
                                            <ExternalLink class="w-4 h-4" />
                                        </a>
                                        <a
                                            :href="doc.download_url"
                                            class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors"
                                            title="Unduh PDF"
                                        >
                                            <Download class="w-4 h-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── MODAL PRATINJAU PDF BESAR (FULLSCREEN VIEWER) ─────────────── -->
        <Teleport to="body">
            <div
                v-if="isPreviewModalOpen && activeDoc"
                class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-1 sm:p-3 md:p-4 animate-in fade-in duration-150"
            >
                <div
                    :class="[
                        'bg-white shadow-2xl flex flex-col overflow-hidden border border-slate-200 transition-all duration-150',
                        isFullscreen
                            ? 'fixed inset-0 rounded-none w-screen h-screen'
                            : 'w-[98vw] max-w-[1700px] h-[96vh] rounded-2xl',
                    ]"
                >
                    <!-- Modal Header -->
                    <div
                        class="px-4 sm:px-6 py-3 bg-slate-900 text-white flex items-center justify-between gap-3 shrink-0"
                    >
                        <!-- Title & Info -->
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <span
                                :class="[
                                    'px-2.5 py-1 text-xs font-black rounded-lg tracking-wide shrink-0 shadow-xs whitespace-nowrap',
                                    getTypeBadgeStyle(activeDoc.type),
                                ]"
                            >
                                {{ activeDoc.code }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-extrabold text-sm sm:text-base text-white truncate leading-tight">
                                    {{ activeDoc.title }}
                                </h3>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ activeDoc.category }} &bull; {{ activeDoc.size_formatted }}
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Prev / Next Navigation -->
                            <div
                                class="hidden sm:flex items-center bg-slate-800 rounded-xl p-0.5 border border-slate-700 mr-2"
                            >
                                <button
                                    type="button"
                                    @click="navigateDoc(-1)"
                                    class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-700 transition-colors"
                                    title="Dokumen Sebelumnya (Panah Kiri ←)"
                                >
                                    <ChevronLeft class="w-4 h-4" />
                                </button>
                                <span class="px-2 text-xs font-bold text-slate-400">
                                    {{ filteredDocuments.findIndex(d => d.id === activeDoc.id) + 1 }} / {{ filteredDocuments.length }}
                                </span>
                                <button
                                    type="button"
                                    @click="navigateDoc(1)"
                                    class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-700 transition-colors"
                                    title="Dokumen Selanjutnya (Panah Kanan →)"
                                >
                                    <ChevronRight class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Open in Tab Baru -->
                            <a
                                :href="activeDoc.stream_url"
                                target="_blank"
                                class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition-colors"
                                title="Buka di Tab Baru"
                            >
                                <ExternalLink class="w-4 h-4" />
                            </a>

                            <!-- Download Button -->
                            <a
                                :href="activeDoc.download_url"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary/90 transition-all"
                                title="Unduh Dokumen PDF"
                            >
                                <Download class="w-3.5 h-3.5" />
                                <span class="hidden sm:inline">Unduh PDF</span>
                            </a>

                            <!-- Fullscreen Toggle -->
                            <button
                                type="button"
                                @click="toggleFullscreen"
                                class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition-colors"
                                :title="isFullscreen ? 'Kecilkan' : 'Layar Penuh'"
                            >
                                <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                                <Maximize2 v-else class="w-4 h-4" />
                            </button>

                            <!-- Close Button -->
                            <button
                                type="button"
                                @click="closePreview"
                                class="p-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white transition-colors"
                                title="Tutup (Esc)"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (PDF Full-Bleed Embed) -->
                    <div class="flex-1 w-full h-full bg-slate-200 relative overflow-hidden">
                        <iframe
                            :key="activeDoc.id"
                            :src="activeDoc.stream_url"
                            class="w-full h-full border-0 absolute inset-0"
                            title="Pratinjau Dokumen Petunjuk"
                        ></iframe>
                    </div>

                    <!-- Modal Footer Note -->
                    <div
                        class="px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 shrink-0"
                    >
                        <div class="flex items-center gap-2">
                            <Info class="w-3.5 h-3.5 text-slate-400" />
                            <span class="hidden sm:inline">
                                Navigasi cepat: gunakan tombol panah <kbd class="px-1 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-mono">←</kbd> dan <kbd class="px-1 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-mono">→</kbd> pada keyboard.
                            </span>
                            <span class="sm:hidden">{{ activeDoc.code }}: {{ activeDoc.short_title }}</span>
                        </div>
                        <span class="text-slate-400 font-mono text-[11px]">{{ activeDoc.filename }}</span>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
