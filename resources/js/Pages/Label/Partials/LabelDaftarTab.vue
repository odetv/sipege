<script setup>
import { ref, computed } from "vue";
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
});

const emit = defineEmits(["go-to-buat", "edit-label"]);

// Search & Filter State
const searchQuery = ref("");
const filterTanggal = ref("");

const filteredLabels = computed(() => {
    let list = props.savedLabels || [];

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((item) => {
            const no = (item.nomor_label || "").toLowerCase();
            const nama = (item.nama_menu || "").toLowerCase();
            const petunjuk = (item.petunjuk_menu || "").toLowerCase();
            const tmpl = (item.template_name || "").toLowerCase();
            return (
                no.includes(q) ||
                nama.includes(q) ||
                petunjuk.includes(q) ||
                tmpl.includes(q)
            );
        });
    }

    if (filterTanggal.value) {
        list = list.filter((item) => {
            const tgl = (item.tanggal_produksi || "").substring(0, 10);
            return tgl === filterTanggal.value;
        });
    }

    return list;
});

// Stats
const totalLabelsCount = computed(() => (props.savedLabels || []).length);
const totalPorsiAll = computed(() =>
    (props.savedLabels || []).reduce(
        (sum, item) => sum + (Number(item.total_porsi) || 0),
        0,
    ),
);
const totalSasaranAll = computed(() =>
    (props.savedLabels || []).reduce(
        (sum, item) => sum + (Number(item.total_sasaran) || 0),
        0,
    ),
);

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
    <div class="space-y-6">
        <!-- 1. Top Summary Banner & Quick Action -->
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
        >
            <!-- Card 1: Total Label Tersimpan -->
            <Card className="bg-white border-slate-200/80 shadow-2xs">
                <CardContent className="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Label Tersimpan
                        </p>
                        <h3 class="text-2xl font-black text-slate-900 mt-1">
                            {{ totalLabelsCount }}
                            <span class="text-xs font-semibold text-slate-400 font-sans">Label</span>
                        </h3>
                    </div>
                    <div class="h-11 w-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <ClipboardList class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Card 2: Total Porsi Label -->
            <Card className="bg-white border-slate-200/80 shadow-2xs">
                <CardContent className="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Akumulasi Porsi
                        </p>
                        <h3 class="text-2xl font-black text-slate-900 mt-1">
                            {{ Number(totalPorsiAll).toLocaleString("id-ID") }}
                            <span class="text-xs font-semibold text-slate-400 font-sans">Porsi</span>
                        </h3>
                    </div>
                    <div class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <Utensils class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Card 3: Total Sasaran PM -->
            <Card className="bg-white border-slate-200/80 shadow-2xs">
                <CardContent className="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            Total Kelompok PM
                        </p>
                        <h3 class="text-2xl font-black text-slate-900 mt-1">
                            {{ totalSasaranAll }}
                            <span class="text-xs font-semibold text-slate-400 font-sans">Kelompok</span>
                        </h3>
                    </div>
                    <div class="h-11 w-11 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
                        <Users class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Card 4: Action Buat Label Baru -->
            <Card className="bg-gradient-to-br from-primary to-primary/90 text-white border-none shadow-xs flex flex-col justify-center">
                <CardContent className="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-white/80 uppercase tracking-wider">
                            Buat Label Baru
                        </p>
                        <p class="text-xs text-white/90 mt-0.5 font-medium">
                            Rancang & sesuaikan template
                        </p>
                    </div>
                    <Button
                        type="button"
                        @click="emit('go-to-buat')"
                        className="h-10 px-3.5 bg-white hover:bg-slate-100 text-primary font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer shrink-0"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Buat Label</span>
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- 2. Main Card List & Table -->
        <Card className="bg-white border-slate-200/80 shadow-xs">
            <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <CardTitle class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <ClipboardList class="h-5 w-5 text-primary" />
                            <span>Daftar Label Tersimpan</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm mt-0.5">
                            Kelola arsip label yang telah dibuat. Anda dapat langsung mengunduh PDF, mencetak, mengedit, atau menghapus data.
                        </CardDescription>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <div class="relative min-w-[220px] flex-1 sm:flex-initial">
                            <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama menu / nomor..."
                                class="w-full pl-9 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400 font-medium"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-600">
                            <Calendar class="h-3.5 w-3.5 text-slate-400" />
                            <input
                                v-model="filterTanggal"
                                type="date"
                                class="border-none bg-transparent p-0 text-xs font-semibold text-slate-700 outline-none cursor-pointer"
                            />
                            <button
                                v-if="filterTanggal"
                                @click="filterTanggal = ''"
                                class="text-slate-400 hover:text-slate-600 ml-1"
                                title="Reset filter tanggal"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </CardHeader>

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
                                searchQuery || filterTanggal
                                    ? "Label Tidak Ditemukan"
                                    : "Belum Ada Label yang Disimpan"
                            }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-1">
                            {{
                                searchQuery || filterTanggal
                                    ? "Coba sesuaikan kata kunci pencarian atau reset filter tanggal produksi."
                                    : "Buat dan simpan rancangan label kemasan makanan SPPG Anda melalui submenu Buat Label."
                            }}
                        </p>
                    </div>
                    <div v-if="!searchQuery && !filterTanggal" class="pt-2">
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

                <!-- Table View for Desktop & Responsive Card List for Mobile -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10.5px] tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Label & Menu</th>
                                <th class="py-3 px-4">Waktu Distribusi</th>
                                <th class="py-3 px-4">Sasaran & Porsi</th>
                                <th class="py-3 px-4">Template Label</th>
                                <th class="py-3 px-4 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, idx) in filteredLabels"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <!-- No -->
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-400">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Label & Menu -->
                                <td class="py-3.5 px-4 min-w-[220px]">
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
                                                <span>{{ item.jam_produksi || "07:00" }}</span>
                                            </span>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-amber-700 font-semibold">
                                                Maks: {{ item.batas_konsumsi || "09:00" }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Sasaran & Porsi -->
                                <td class="py-3.5 px-4 min-w-[160px] whitespace-nowrap">
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

                                <!-- Template Label -->
                                <td class="py-3.5 px-4 min-w-[160px]">
                                    <div class="flex flex-col items-start gap-1">
                                        <Badge class="bg-blue-50 text-blue-800 border-blue-200 font-bold text-[10.5px]">
                                            {{ item.template_name || "Standar BGN 4:3" }}
                                        </Badge>
                                        <span class="text-[10px] font-mono text-slate-400">
                                            Rasio {{ item.aspect_ratio || "4:3" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Download PDF 1/Hal -->
                                        <button
                                            type="button"
                                            @click="startDownloadSavedLabel(item, 'single')"
                                            class="h-8 px-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer"
                                            title="Download PDF 1 Label per Halaman"
                                        >
                                            <Download class="h-3.5 w-3.5" />
                                            <span class="hidden xl:inline">1 Hal</span>
                                        </button>

                                        <!-- Download PDF Lembaran A4 -->
                                        <button
                                            type="button"
                                            @click="startDownloadSavedLabel(item, 'a4_grid')"
                                            class="h-8 px-2.5 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white rounded-lg font-bold text-[11px] transition-all flex items-center gap-1 cursor-pointer"
                                            title="Download PDF Lembaran Kertas A4 (9 Label per Halaman)"
                                        >
                                            <FileText class="h-3.5 w-3.5" />
                                            <span class="hidden xl:inline">A4 (9/Hal)</span>
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="handleEdit(item)"
                                            class="h-8 w-8 rounded-lg bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                                            title="Edit Label ini di Submenu Buat Label"
                                        >
                                            <Edit3 class="h-3.5 w-3.5" />
                                        </button>

                                        <!-- Hapus -->
                                        <button
                                            type="button"
                                            @click="confirmDelete(item)"
                                            class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center transition-all cursor-pointer"
                                            title="Hapus Label dari Daftar"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
