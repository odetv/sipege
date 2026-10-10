<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import LabelCardItem from "./LabelCardItem.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import {
    ClipboardList,
    Search,
    Calendar,
    Clock,
    Tag,
    Printer,
    Download,
    FileText,
    Edit3,
    Trash2,
    PlusCircle,
    Eye,
    Users,
    Utensils,
    Layers,
    AlertCircle,
    CheckCircle2,
    Loader2,
    X,
    Filter,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Sparkles,
    RotateCcw,
} from "lucide-vue-next";
import {
    downloadPdfSingleMode,
    downloadPdfA4GridMode,
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
    savedLabels: {
        type: Array,
        default: () => [],
    },
    templates: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["go-to-buat", "edit-label"]);

// ─── Filter & Search State ──────────────────────────────────────────────────
const searchQuery = ref("");
const tanggalMulai = ref("");
const tanggalSelesai = ref("");
const filterDateMode = ref("");
const selectedPeriodeId = ref("");
const isFilterAllTime = ref(false);

// ─── Pagination State (Default 10 item per halaman) ───────────────────────────
const currentPage = ref(1);
const perPage = ref(10);

const filteredLabels = computed(() => {
    let list = props.savedLabels || [];

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((item) => {
            const no = (item.nomor_label || "").toLowerCase();
            const nama = (item.nama_menu || "").toLowerCase();
            const petunjuk = (item.petunjuk_menu || "").toLowerCase();
            return (
                no.includes(q) ||
                nama.includes(q) ||
                petunjuk.includes(q)
            );
        });
    }

    if (!isFilterAllTime.value) {
        if (tanggalMulai.value) {
            list = list.filter((item) => {
                const tgl = (item.tanggal_produksi || "").substring(0, 10);
                return tgl >= tanggalMulai.value;
            });
        }

        if (tanggalSelesai.value) {
            list = list.filter((item) => {
                const tgl = (item.tanggal_produksi || "").substring(0, 10);
                return tgl <= tanggalSelesai.value;
            });
        }
    }

    return list;
});

const totalItems = computed(() => filteredLabels.value.length);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalItems.value / perPage.value)),
);

const paginatedLabels = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredLabels.value.slice(start, start + perPage.value);
});

function goToPage(p) {
    if (p >= 1 && p <= totalPages.value) {
        currentPage.value = p;
    }
}

watch([searchQuery, tanggalMulai, tanggalSelesai], () => {
    currentPage.value = 1;
});

// Detail Modal State
const selectedDetailLabel = ref(null);
const isDetailOpen = ref(false);

function openDetail(item) {
    selectedDetailLabel.value = item;
    isDetailOpen.value = true;
}

function closeDetail() {
    isDetailOpen.value = false;
    selectedDetailLabel.value = null;
}

// Delete Confirmation Modal State
const labelToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(item) {
    labelToDelete.value = item;
}

function cancelDelete() {
    labelToDelete.value = null;
}

function executeDelete() {
    if (!labelToDelete.value) return;
    isDeleting.value = true;
    router.delete(route("label.destroy", labelToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            labelToDelete.value = null;
        },
    });
}

// Download PDF Handler for Saved Label
const isDownloading = ref(false);
const downloadType = ref("");
const downloadProgress = ref({
    current: 0,
    total: 0,
    percentage: 0,
    message: "",
});
const downloadError = ref("");
const downloadSuccess = ref(false);

// Sandbox isolated container
const sandboxLabelData = ref(null);
const sandboxKelompok = ref(null);
const sandboxCardRef = ref(null);

async function getSandboxSavedCardElement(kelompok) {
    sandboxKelompok.value = kelompok;
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 25));
    return (
        sandboxCardRef.value?.querySelector(".bgn-label-card") ||
        sandboxCardRef.value
    );
}

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

async function handleDownload(item, type) {
    const kelompokList = item.kelompok_list || [];
    if (kelompokList.length === 0) {
        alert("Tidak ada kelompok sasaran pada data label ini.");
        return;
    }

    sandboxLabelData.value = item;

    isDownloading.value = true;
    isDownloadCancelled.value = false;
    downloadType.value = type;
    downloadError.value = "";
    downloadSuccess.value = false;
    downloadProgress.value = {
        current: 0,
        total: kelompokList.length,
        percentage: 0,
        message: "Menyiapkan elemen kartu label tersimpan...",
    };

    try {
        const dateSuffix = (
            (item.tanggal_produksi || "").substring(0, 10) || "label"
        ).replace(/-/g, "");

        if (type === "single") {
            const filename = `Label_${item.nomor_label || "BGN"}_1PerHal_${dateSuffix}.pdf`;
            await downloadPdfSingleMode({
                printableKelompokList: kelompokList,
                getRenderElement: getSandboxSavedCardElement,
                filename,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = p;
                },
            });
        } else if (type === "a4_grid") {
            const filename = `Label_${item.nomor_label || "BGN"}_LembarA4_${dateSuffix}.pdf`;
            await downloadPdfA4GridMode({
                printableKelompokList: kelompokList,
                getRenderElement: getSandboxSavedCardElement,
                filename,
                isCancelled: () => isDownloadCancelled.value,
                onProgress: (p) => {
                    downloadProgress.value = p;
                },
            });
        }

        if (!isDownloadCancelled.value) {
            downloadSuccess.value = true;
            setTimeout(() => {
                if (downloadSuccess.value) {
                    isDownloading.value = false;
                }
            }, 1500);
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

function handleEdit(item) {
    emit("edit-label", item);
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const parts = String(dateStr).substring(0, 10).split("-");
        if (parts.length === 3) {
            const months = [
                "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                "Juli", "Agustus", "September", "Oktober", "November", "Desember"
            ];
            const d = parseInt(parts[2], 10);
            const m = months[parseInt(parts[1], 10) - 1];
            const y = parts[0];
            return `${d} ${m} ${y}`;
        }
        const d = new Date(dateStr);
        return d.toLocaleDateString("id-ID", {
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
    <div class="space-y-4 sm:space-y-5">
        <!-- ─── Control Bar Card: Header, Filter & Rentang Kalender ─────────────────── -->
        <Card className="bg-white border-slate-200/80 shadow-xs relative z-30">
            <CardHeader className="p-4 sm:p-5 space-y-3.5">
                <!-- Baris 1: Judul & Tombol Buat Label Baru -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <CardTitle class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <ClipboardList class="h-5 w-5 text-primary" />
                            <span>Daftar Label Tersimpan</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm mt-0.5">
                            Kelola arsip data label kemasan makanan yang telah dibuat.
                        </CardDescription>
                    </div>

                    <!-- Tombol Cepat Buat Label Baru -->
                    <Button
                        type="button"
                        @click="emit('go-to-buat')"
                        className="h-8.5 sm:h-9 px-3.5 bg-primary hover:bg-primary/90 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs cursor-pointer rounded-xl self-start sm:self-auto shrink-0"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Buat Label Baru</span>
                    </Button>
                </div>

                <!-- Baris 2: PeriodDateFilterBar Reusable -->
                <PeriodDateFilterBar
                    v-model:startDate="tanggalMulai"
                    v-model:endDate="tanggalSelesai"
                    v-model:mode="filterDateMode"
                    v-model:periodeId="selectedPeriodeId"
                    v-model:isAllTime="isFilterAllTime"
                    :periodes="periodes"
                />

                <!-- Baris 3: Toolbar Pencarian & Info Arsip -->
                <div class="pt-3 border-t border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <span>Menampilkan <strong class="text-slate-900 font-extrabold">{{ filteredLabels.length }}</strong> label</span>
                        <span v-if="isFilterAllTime" class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 text-[11px]">Semua Arsip</span>
                    </div>

                    <!-- Right: Search Input -->
                    <div class="relative w-full sm:w-80">
                        <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama menu / nomor label..."
                            class="w-full pl-8 pr-7 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400 font-medium"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5"
                        >
                            <X class="h-3 w-3" />
                        </button>
                    </div>
                </div>
            </CardHeader>
        </Card>

        <!-- ─── Table Card: Daftar Label & Paginasi ─────────────────────── -->
        <Card className="bg-white border-slate-200/80 shadow-xs overflow-hidden relative z-10">
            <CardContent className="p-0">
                <!-- Empty State -->
                <div
                    v-if="filteredLabels.length === 0"
                    class="py-16 px-4 text-center space-y-3"
                >
                    <div class="h-16 w-16 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400">
                        <ClipboardList class="h-8 w-8" />
                    </div>
                    <div class="max-w-md mx-auto">
                        <h4 class="text-sm sm:text-base font-bold text-slate-800">
                            {{
                                searchQuery || tanggalMulai || tanggalSelesai
                                    ? "Label Tidak Ditemukan"
                                    : "Belum Ada Label yang Disimpan"
                            }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1">
                            {{
                                searchQuery || tanggalMulai || tanggalSelesai
                                    ? "Coba sesuaikan kata kunci pencarian atau reset filter rentang tanggal."
                                    : "Buat dan simpan rancangan label kemasan makanan SPPG Anda melalui submenu Buat Label."
                            }}
                        </p>
                    </div>
                    <div v-if="!searchQuery && !tanggalMulai && !tanggalSelesai" class="pt-2">
                        <Button
                            type="button"
                            @click="emit('go-to-buat')"
                            className="h-9 px-4 bg-primary hover:bg-primary/90 text-white font-bold text-xs inline-flex items-center gap-2 shadow-xs cursor-pointer"
                        >
                            <PlusCircle class="h-4 w-4" />
                            <span>Mulai Buat Label Sekarang</span>
                        </Button>
                    </div>
                </div>

                <div v-else>
                    <!-- ================= MOBILE CARD VIEW (< md) ================= -->
                    <div class="block md:hidden divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in paginatedLabels"
                            :key="item.id"
                            class="p-4 space-y-3 hover:bg-slate-50/60 transition-colors"
                        >
                            <!-- Card Header: Nomor Label Badge & Index -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[11px] font-mono font-bold text-slate-400">#{{ (currentPage - 1) * perPage + idx + 1 }}</span>
                                    <span class="font-mono font-extrabold text-primary bg-primary/10 px-2 py-0.5 rounded-md border border-primary/20 text-xs">
                                        {{ item.nomor_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Menu Name & Description -->
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm leading-snug">
                                    {{ item.nama_menu }}
                                </h4>
                                <p v-if="item.petunjuk_menu && item.petunjuk_menu !== item.nama_menu" class="text-xs text-slate-500 mt-1 line-clamp-2 italic">
                                    {{ item.petunjuk_menu }}
                                </p>
                            </div>

                            <!-- Info Badges & Metadata -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-slate-700 bg-slate-50 p-2 rounded-xl border border-slate-100">
                                    <Calendar class="h-3.5 w-3.5 text-primary shrink-0" />
                                    <div class="overflow-hidden">
                                        <p class="text-[9.5px] text-slate-400 font-bold uppercase leading-none">Distribusi</p>
                                        <p class="font-bold text-slate-800 text-[11px] truncate mt-0.5">{{ formatTanggalIndo(item.tanggal_produksi) }}</p>
                                        <p class="text-[10px] text-slate-500 font-medium">{{ item.jam_produksi || "12:14" }} • Maks {{ item.batas_konsumsi || "12:14" }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-700 bg-slate-50 p-2 rounded-xl border border-slate-100">
                                    <Utensils class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    <div class="overflow-hidden">
                                        <p class="text-[9.5px] text-slate-400 font-bold uppercase leading-none">Porsi & Sasaran</p>
                                        <p class="font-extrabold text-slate-900 text-[11px] truncate mt-0.5">{{ Number(item.total_porsi || 0).toLocaleString("id-ID") }} Porsi</p>
                                        <p class="text-[10px] text-slate-500 font-medium truncate">{{ item.total_sasaran || (item.kelompoks_snapshot?.length || 0) }} PM • {{ item.total_pk || 0 }} PK / {{ item.total_pb || 0 }} PB</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Action Buttons -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="handleEdit(item)"
                                    class="flex-1 h-9 px-3 rounded-xl bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white flex items-center justify-center gap-1.5 transition-all border border-amber-200/80 cursor-pointer shadow-2xs active:scale-95 text-xs font-bold"
                                    title="Edit Label"
                                >
                                    <Edit3 class="h-4 w-4" />
                                    <span>Edit Label</span>
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete(item)"
                                    class="h-9 px-3.5 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center gap-1.5 transition-all border border-rose-200/80 cursor-pointer shadow-2xs active:scale-95 text-xs font-bold"
                                    title="Hapus Label"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ================= DESKTOP TABLE VIEW (>= md) ================= -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10.5px] tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Label & Menu</th>
                                    <th class="py-3 px-4">Waktu Distribusi</th>
                                    <th class="py-3 px-4">Sasaran & Porsi</th>
                                    <th class="py-3 px-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(item, idx) in paginatedLabels"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 transition-colors group"
                                >
                                    <!-- No -->
                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-400">
                                        {{ (currentPage - 1) * perPage + idx + 1 }}
                                    </td>

                                    <!-- Label & Menu -->
                                    <td class="py-3.5 px-4 min-w-[240px]">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-extrabold text-primary bg-primary/10 px-2 py-0.5 rounded border border-primary/20 text-[11px]">
                                                    {{ item.nomor_label }}
                                                </span>
                                            </div>
                                            <div class="font-bold text-slate-900 text-xs sm:text-sm">
                                                {{ item.nama_menu }}
                                            </div>
                                            <p v-if="item.petunjuk_menu && item.petunjuk_menu !== item.nama_menu" class="text-[11px] text-slate-500 line-clamp-1 max-w-sm italic">
                                                {{ item.petunjuk_menu }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Tanggal & Jam Produksi -->
                                    <td class="py-3.5 px-4 min-w-[170px] whitespace-nowrap">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                                <Calendar class="h-3.5 w-3.5 text-primary" />
                                                <span>{{ formatTanggalIndo(item.tanggal_produksi) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                                                <span class="flex items-center gap-1">
                                                    <Clock class="h-3 w-3 text-slate-400" />
                                                    <span>{{ item.jam_produksi || "12:14" }}</span>
                                                </span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-amber-700 font-semibold">
                                                    Maks: {{ item.batas_konsumsi || "12:14" }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Sasaran & Porsi -->
                                    <td class="py-3.5 px-4 min-w-[170px] whitespace-nowrap">
                                        <div class="space-y-1">
                                            <div class="font-extrabold text-slate-900 flex items-center gap-1.5">
                                                <Utensils class="h-3.5 w-3.5 text-emerald-600" />
                                                <span>{{ Number(item.total_porsi || 0).toLocaleString("id-ID") }} Porsi</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[10.5px] text-slate-500">
                                                <span>{{ item.total_sasaran || (item.kelompoks_snapshot?.length || 0) }} Sasaran PM</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="font-mono">{{ item.total_pk || 0 }} PK / {{ item.total_pb || 0 }} PB</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Aksi: Edit & Hapus Saja -->
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Edit -->
                                            <button
                                                type="button"
                                                @click="handleEdit(item)"
                                                class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white flex items-center justify-center transition-colors border border-amber-200/80 cursor-pointer shadow-2xs active:scale-95"
                                                title="Edit Label ini di Submenu Buat Label"
                                            >
                                                <Edit3 class="h-4 w-4" />
                                            </button>

                                            <!-- Hapus -->
                                            <button
                                                type="button"
                                                @click="confirmDelete(item)"
                                                class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center transition-colors border border-rose-200/80 cursor-pointer shadow-2xs active:scale-95"
                                                title="Hapus Label dari Daftar"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ================= PAGINATION CONTROLS FOOTER ================= -->
                    <div
                        v-if="filteredLabels.length > 0"
                        class="p-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-2 text-slate-500 flex-wrap justify-center sm:justify-start">
                            <span>Menampilkan</span>
                            <span class="font-bold text-slate-800">
                                {{ Math.min((currentPage - 1) * perPage + 1, totalItems) }} -
                                {{ Math.min(currentPage * perPage, totalItems) }}
                            </span>
                            <span>dari</span>
                            <span class="font-bold text-slate-800">{{ totalItems }}</span>
                            <span>label</span>

                            <span class="text-slate-300 mx-1">•</span>

                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Baris:</span>
                                <select
                                    v-model.number="perPage"
                                    @change="currentPage = 1"
                                    class="bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 outline-none cursor-pointer focus:ring-1 focus:ring-primary"
                                >
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <!-- Page Navigation Buttons -->
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-bold transition-colors cursor-pointer"
                                title="Halaman Sebelumnya"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </button>

                            <template v-for="page in totalPages" :key="page">
                                <button
                                    v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                                    type="button"
                                    @click="goToPage(page)"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer',
                                        currentPage === page
                                            ? 'bg-primary text-white shadow-2xs font-extrabold'
                                            : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else-if="(page === currentPage - 2 && page > 1) || (page === currentPage + 2 && page < totalPages)"
                                    class="px-1 text-slate-400 font-bold"
                                >
                                    ...
                                </span>
                            </template>

                            <button
                                type="button"
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed font-bold transition-colors cursor-pointer"
                                title="Halaman Selanjutnya"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 3. Modal Konfirmasi Hapus Label -->
        <Teleport to="body">
            <div
                v-if="labelToDelete"
                class="fixed inset-0 z-[99999] bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in"
            >
                <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 space-y-4 animate-in zoom-in-95">
                    <div class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                        <Trash2 class="h-6 w-6" />
                    </div>

                    <div class="text-center space-y-1">
                        <h4 class="text-base font-black text-slate-900">
                            Hapus Label Tersimpan?
                        </h4>
                        <p class="text-xs text-slate-500">
                            Apakah Anda yakin ingin menghapus label
                            <strong class="text-slate-800">{{ labelToDelete.nomor_label }}</strong>
                            (<span class="italic">{{ labelToDelete.nama_menu }}</span>)?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <Button
                            type="button"
                            @click="cancelDelete"
                            :disabled="isDeleting"
                            className="h-9 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            @click="executeDelete"
                            :disabled="isDeleting"
                            className="h-9 px-4 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer"
                        >
                            <Loader2 v-if="isDeleting" class="h-3.5 w-3.5 animate-spin" />
                            <Trash2 v-else class="h-3.5 w-3.5" />
                            <span>{{ isDeleting ? "Menghapus..." : "Ya, Hapus Label" }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- 4. Download Progress Modal Dialog -->
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
                            class="text-base sm:text-lg font-black text-slate-900"
                        >
                            {{
                                downloadSuccess
                                    ? "File PDF Berhasil Dibuat & Diunduh!"
                                    : downloadError
                                      ? "Gagal Membuat PDF"
                                      : downloadType === "single"
                                        ? "Membuat PDF Tunggal (1 Label / Halaman)..."
                                        : "Membuat PDF Lembar A4 (9 Label / Halaman)..."
                            }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1">
                            {{
                                downloadError
                                    ? downloadError
                                    : downloadProgress.message ||
                                      "Sedang merender halaman stiker label..."
                            }}
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="!downloadError" class="space-y-1.5 pt-1">
                        <div
                            class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 p-0.5"
                        >
                            <div
                                class="h-full transition-all duration-300 rounded-full"
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
                            class="flex items-center justify-between text-[11px] font-mono font-bold text-slate-500 px-1"
                        >
                            <span
                                >{{ downloadProgress.current }} dari
                                {{ downloadProgress.total }} Label</span
                            >
                            <span>{{ downloadProgress.percentage }}%</span>
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
                            title="Hentikan dan batalkan proses download"
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
                    v-if="sandboxLabelData"
                    :unit-sppg="unitSppg"
                    :nama-sppg="sandboxLabelData.nama_sppg || unitSppg?.nama"
                    :zona-waktu="sandboxLabelData.zona_waktu || 'WITA'"
                    :tanggal-produksi="(sandboxLabelData.tanggal_produksi || '').substring(0, 10)"
                    :jam-produksi="sandboxLabelData.jam_produksi || '12:14'"
                    :tanggal-expired="(sandboxLabelData.tanggal_produksi || '').substring(0, 10)"
                    :jam-expired="sandboxLabelData.batas_konsumsi || '12:14'"
                    :menu-items="sandboxLabelData.petunjuk_menu ? sandboxLabelData.petunjuk_menu.split('\n') : [sandboxLabelData.nama_menu]"
                    :gizi-data="sandboxLabelData.gizi_data || {}"
                    :harga-items="sandboxLabelData.harga_items || []"
                    :kelompok="sandboxKelompok"
                />
            </div>
        </div>
    </div>
</template>
