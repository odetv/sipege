<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import {
    Sparkles,
    Bot,
    MessageSquare,
    FileSpreadsheet,
    UploadCloud,
    Trash2,
    Plus,
    CheckCircle2,
    AlertCircle,
    Loader2,
    X,
    ShieldAlert,
    ShieldCheck,
    Info,
    Cpu,
    Zap,
    Clock,
    Layers,
    ArrowUpDown,
    ArrowRightLeft,
    Calendar,
    Files,
} from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "saved"]);

// ─── TAB SELECTION & STATE ───
const activeTab = ref("chat"); // 'chat' | 'file'
const chatPrompt = ref("");
const selectedFiles = ref([]);
const fileInputRef = ref(null);
const isDragging = ref(false);

const isAnalyzing = ref(false);
const isSaving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

// ─── METADATA EKSEKUSI AI & TOKEN USAGE ───
const executionMeta = ref(null);

// ─── HASIL EKSTRAKSI AI UNTUK DI-REVIEW ───
const extractedItems = ref([]);

const SATUAN_OPTIONS = ["Kg", "L"];
const JENIS_OPTIONS = ["Organik", "Anorganik"];
const SUMBER_OPTIONS = ["Persiapan", "Pengolahan", "Ompreng", "Lainnya"];
const METODE_OPTIONS = ["Donasi", "Kompos", "Daur Ulang", "Pembuangan"];
const STATUS_OPTIONS = ["Tidak Berbahaya", "Berbahaya"];

const QUICK_PROMPTS = [
    {
        label: "Sisa Sayur & Nasi",
        text: "Tadi pagi dari persiapan ada sisa potongan sayur dan kulit labu seberat 4.5 kg dibuat kompos. Dari sisa ompreng makan siang ada sisa nasi 3.2 kg didonasikan ke peternak.",
    },
    {
        label: "Minyak Jelantah & Kerak",
        text: "Di pengolahan ada minyak jelantah sisa menggoreng sebanyak 2.5 liter disalurkan untuk daur ulang, dan kerak wajan 0.8 kg dibuang.",
    },
    {
        label: "Kardus & Plastik Luar",
        text: "Dari pembongkaran kardus bahan supplier ada kardus kering seberat 6 kg dan botol plastik kemasan 1.5 kg disortir untuk daur ulang.",
    },
];

function applyQuickPrompt(promptText) {
    chatPrompt.value = promptText;
}

// ─── MULTIPLE FILE HANDLING & DRAG-DROP ───
function handleFileChange(event) {
    const files = Array.from(event.target.files || []);
    if (files.length > 0) {
        files.forEach((f) => {
            // Cegah duplikasi file berdasarkan nama dan ukuran
            if (
                !selectedFiles.value.some(
                    (existing) =>
                        existing.name === f.name && existing.size === f.size,
                )
            ) {
                selectedFiles.value.push(f);
            }
        });
        errorMessage.value = "";
    }
    if (event.target) {
        event.target.value = "";
    }
}

function handleFileDrop(event) {
    isDragging.value = false;
    const files = Array.from(event.dataTransfer.files || []);
    if (files.length > 0) {
        files.forEach((f) => {
            if (
                !selectedFiles.value.some(
                    (existing) =>
                        existing.name === f.name && existing.size === f.size,
                )
            ) {
                selectedFiles.value.push(f);
            }
        });
        errorMessage.value = "";
    }
}

function removeSelectedFile(index) {
    selectedFiles.value.splice(index, 1);
}

function clearAllFiles() {
    selectedFiles.value = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
}

function formatFileSize(bytes) {
    if (!bytes) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

const totalFilesSize = computed(() => {
    const totalBytes = selectedFiles.value.reduce(
        (acc, f) => acc + (f.size || 0),
        0,
    );
    return formatFileSize(totalBytes);
});

// ─── HELPER SANITASI TANGGAL (MENCEGAH BULAN 00 SEPERTI 2026-00-24) ───
function sanitizeDate(dateStr) {
    if (!dateStr || typeof dateStr !== "string") {
        return new Date().toISOString().split("T")[0];
    }
    let trimmed = dateStr.trim();
    // Jika terdapat format bulan 00 (misal 2026-00-24), otomatis koreksi ke bulan saat ini
    const currentMonth = String(new Date().getMonth() + 1).padStart(2, "0");
    trimmed = trimmed.replace(
        /^(\d{4})-00-(\d{1,2})$/,
        `$1-${currentMonth}-$2`,
    );

    // Validasi format baku YYYY-MM-DD
    if (/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/.test(trimmed)) {
        return trimmed;
    }
    return new Date().toISOString().split("T")[0];
}

// ─── WAJIB: PENGURUTAN BERDASARKAN TANGGAL KRONOLOGIS (ASCENDING) ───
function sortExtractedByDate() {
    extractedItems.value.sort((a, b) => {
        const tA = sanitizeDate(a.tanggal);
        const tB = sanitizeDate(b.tanggal);
        return tA.localeCompare(tB);
    });
}

// ─── PROSES ANALISIS & EKSTRAKSI AI ───
async function runAiExtraction() {
    errorMessage.value = "";
    successMessage.value = "";

    if (activeTab.value === "chat" && !chatPrompt.value.trim()) {
        errorMessage.value =
            "Silakan ketik pesan singkat atau catatan limbah terlebih dahulu.";
        return;
    }

    if (activeTab.value === "file" && selectedFiles.value.length === 0) {
        errorMessage.value =
            "Silakan pilih atau unggah minimal satu file dokumen terlebih dahulu.";
        return;
    }

    isAnalyzing.value = true;

    try {
        const formData = new FormData();
        const apiMode = activeTab.value === "chat" ? "text" : "file";
        formData.append("mode", apiMode);

        if (activeTab.value === "chat") {
            formData.append("prompt", chatPrompt.value.trim());
        } else {
            // Kirim semua file yang dipilih (Multiple Upload)
            selectedFiles.value.forEach((file) => {
                formData.append("files[]", file);
            });
        }

        const res = await axios.post(route("residu.ai-parse"), formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        if (res.data && res.data.success && Array.isArray(res.data.data)) {
            extractedItems.value = res.data.data.map((item) => ({
                tanggal: sanitizeDate(item.tanggal),
                nama_limbah: item.nama_limbah || "",
                jumlah: parseFloat(item.jumlah) || 1.0,
                satuan: item.satuan || "Kg",
                jenis_limbah: item.jenis_limbah || "Organik",
                sumber_limbah: item.sumber_limbah || "Persiapan",
                metode_penanganan: item.metode_penanganan || "Kompos",
                status_berbahaya: item.status_berbahaya || "Tidak Berbahaya",
                keterangan: item.keterangan || "",
            }));

            // Selalu urutkan berdasarkan tanggal kronologis
            sortExtractedByDate();

            executionMeta.value = res.data.execution || null;

            const fileCountInfo =
                selectedFiles.value.length > 1
                    ? ` dari ${selectedFiles.value.length} file dokumen`
                    : "";
            successMessage.value = `Berhasil mengekstrak ${extractedItems.value.length} catatan limbah${fileCountInfo} (terurut kronologis berdasarkan tanggal). Silakan periksa kembali sebelum menyimpan.`;
        } else {
            throw new Error(
                res.data.message || "Gagal mengekstrak data dari input.",
            );
        }
    } catch (err) {
        console.error("AI Parse Error:", err);
        errorMessage.value =
            err.response?.data?.message ||
            err.message ||
            "Terjadi kesalahan saat mengekstrak data dengan AI.";
    } finally {
        isAnalyzing.value = false;
    }
}

// ─── EDIT & KOREKSI HASIL EKSTRAKSI ───
function removeItem(index) {
    extractedItems.value.splice(index, 1);
}

function addItemRow() {
    extractedItems.value.push({
        tanggal: new Date().toISOString().split("T")[0],
        nama_limbah: "",
        jumlah: 1.0,
        satuan: "Kg",
        jenis_limbah: "Organik",
        sumber_limbah: "Persiapan",
        metode_penanganan: "Kompos",
        status_berbahaya: "Tidak Berbahaya",
        keterangan: "Input tambahan manual",
    });
}

function resetExtraction() {
    extractedItems.value = [];
    executionMeta.value = null;
    errorMessage.value = "";
    successMessage.value = "";
}

// ─── SIMPAN BATCH KE DATABASE ───
async function saveAllToDatabase() {
    if (extractedItems.value.length === 0) return;

    // Pastikan data selalu terurut berdasarkan tanggal sebelum disimpan
    sortExtractedByDate();

    // Validasi sederhana di sisi klien dan sanitasi tanggal
    for (let i = 0; i < extractedItems.value.length; i++) {
        const it = extractedItems.value[i];
        it.tanggal = sanitizeDate(it.tanggal);

        if (!it.nama_limbah || !it.nama_limbah.trim()) {
            errorMessage.value = `Baris ke-${i + 1}: Nama limbah wajib diisi.`;
            return;
        }
        if (!it.jumlah || parseFloat(it.jumlah) <= 0) {
            errorMessage.value = `Baris ke-${i + 1}: Jumlah limbah harus lebih dari 0.`;
            return;
        }
    }

    isSaving.value = true;
    errorMessage.value = "";

    try {
        const res = await axios.post(route("residu.bulk-store"), {
            items: extractedItems.value,
        });

        if (res.data && res.data.success) {
            closeModal();
            emit("saved", res.data.count || extractedItems.value.length);
        } else {
            throw new Error(
                res.data.message || "Gagal menyimpan catatan limbah.",
            );
        }
    } catch (err) {
        console.error("Bulk Store Error:", err);
        const serverErrors = err.response?.data?.errors;
        let detailedMsg = "";
        if (serverErrors && typeof serverErrors === "object") {
            const firstKey = Object.keys(serverErrors)[0];
            if (
                firstKey &&
                serverErrors[firstKey] &&
                serverErrors[firstKey][0]
            ) {
                detailedMsg = serverErrors[firstKey][0];
            }
        }
        errorMessage.value =
            detailedMsg ||
            err.response?.data?.message ||
            err.message ||
            "Gagal menyimpan data ke database.";
    } finally {
        isSaving.value = false;
    }
}

function closeModal() {
    emit("update:modelValue", false);
    resetExtraction();
}
</script>

<template>
    <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-5 overflow-y-auto"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"
            @click="closeModal"
        ></div>

        <!-- Modal Dialog: Proporsional (max-w-2xl) saat input, Melebar Luas (max-w-7xl) saat preview tabel -->
        <div
            class="relative w-full bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[94vh] sm:max-h-[92vh] z-10 animate-in fade-in zoom-in-95 duration-200 transition-all"
            :class="extractedItems.length > 0 ? 'max-w-7xl 2xl:max-w-[95vw]' : 'max-w-2xl'"
        >
            <!-- ─── Modal Header Semantik SIPEGE ─── -->
            <div
                class="px-4 sm:px-6 py-3.5 sm:py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800"
            >
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div
                        class="p-2 sm:p-2.5 rounded-xl bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 shrink-0"
                    >
                        <Sparkles class="h-4 sm:h-5 w-4 sm:w-5 text-indigo-400" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <h2 class="text-xs sm:text-base font-bold text-white truncate">
                                Catat Pintar Residu & Limbah
                            </h2>
                            <span
                                class="hidden xs:inline-flex sm:inline-flex px-1.5 sm:px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 shrink-0"
                            >
                                AI Engine
                            </span>
                        </div>
                        <p class="text-[10.5px] sm:text-xs text-slate-400 mt-0.5 line-clamp-1 sm:line-clamp-none">
                            Ekstraksi otomatis logbook limbah dari pesan singkat atau dokumen file.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="closeModal"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer shrink-0 ml-1"
                    title="Tutup Modal"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- ─── Body Modal (Scrollable) ─── -->
            <div class="p-3.5 sm:p-6 overflow-y-auto space-y-3.5 sm:space-y-4 flex-1 bg-slate-50/50">
                <!-- Tahap 1: Formulir Input (Belum ada hasil ekstraksi) -->
                <div v-if="extractedItems.length === 0" class="space-y-3.5 sm:space-y-4">
                    <!-- Segmented Tabs Selector -->
                    <div
                        class="p-1 bg-slate-200/80 rounded-xl border border-slate-300/60 flex items-center gap-1 w-full"
                    >
                        <button
                            type="button"
                            @click="activeTab = 'chat'"
                            class="flex-1 py-2 px-2.5 sm:px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 sm:gap-2 cursor-pointer select-none whitespace-nowrap"
                            :class="
                                activeTab === 'chat'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
                            "
                        >
                            <MessageSquare class="h-4 w-4 text-indigo-600 shrink-0" />
                            <span>Pesan Singkat</span>
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'file'"
                            class="flex-1 py-2 px-2.5 sm:px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 sm:gap-2 cursor-pointer select-none whitespace-nowrap"
                            :class="
                                activeTab === 'file'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/40'
                            "
                        >
                            <FileSpreadsheet class="h-4 w-4 text-emerald-600 shrink-0" />
                            <span>Upload Dokumen</span>
                        </button>
                    </div>

                    <!-- TAB 1: Chat / Teks Catatan -->
                    <div v-if="activeTab === 'chat'" class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 leading-snug">
                            Masukkan Teks Catatan Limbah Harian (Bisa bahasa santai / pesan WA):
                        </label>
                        <textarea
                            v-model="chatPrompt"
                            rows="5"
                            placeholder="Contoh: Tadi siang tgl 29-10-2026 sisa sayur sop 4.2 kg di persiapan kita buat kompos. Terus nasi sisa ompreng 3 kg didonasiin ke warga. Minyak jelantah 2 liter dari pengolahan didaur ulang..."
                            class="w-full px-3.5 py-2.5 sm:py-3 rounded-xl border border-slate-300 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 focus:outline-none transition-all resize-y bg-white leading-relaxed"
                        ></textarea>

                        <!-- Quick Prompts Chips -->
                        <div class="space-y-1.5 pt-0.5">
                            <span class="text-[11px] font-semibold text-slate-500 block">Contoh Cepat:</span>
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar sm:flex-wrap">
                                <button
                                    v-for="(qp, qpIdx) in QUICK_PROMPTS"
                                    :key="qpIdx"
                                    type="button"
                                    @click="applyQuickPrompt(qp.text)"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white hover:bg-indigo-50 text-slate-700 hover:text-indigo-800 border border-slate-200 hover:border-indigo-300 shadow-2xs transition-all cursor-pointer whitespace-nowrap shrink-0 sm:shrink"
                                >
                                    + {{ qp.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Upload File Dokumen (Multiple Files) -->
                    <div v-else class="space-y-3">
                        <div
                            class="relative border-2 border-dashed rounded-2xl p-4 sm:p-6 text-center transition-all cursor-pointer"
                            :class="
                                isDragging
                                    ? 'border-indigo-500 bg-indigo-50/50 shadow-inner'
                                    : 'border-slate-300 hover:border-indigo-400 bg-white hover:bg-indigo-50/20 shadow-2xs'
                            "
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleFileDrop"
                            @click="$refs.fileInputRef && $refs.fileInputRef.click()"
                        >
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                accept=".xlsx, .xls, .csv, .txt, .json, .pdf, .docx, .doc, .pptx, .ppt, .rtf, .odt, .ods, .odp"
                                class="hidden"
                                @change="handleFileChange"
                            />

                            <!-- State: Belum ada file -->
                            <div
                                v-if="selectedFiles.length === 0"
                                class="space-y-2 py-1 sm:py-2"
                            >
                                <div
                                    class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto border border-indigo-100"
                                >
                                    <UploadCloud class="h-5 sm:h-6 w-5 sm:w-6" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800">
                                        Pilih file dokumen atau seret ke area ini
                                    </p>
                                    <p class="text-[10.5px] sm:text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        Mendukung Word, Excel, PowerPoint, PDF, TXT, CSV, dll. (Bisa Multiple File)
                                    </p>
                                </div>
                            </div>

                            <!-- State: File Terpilih (Responsif di HP) -->
                            <div v-else class="space-y-2.5 text-left" @click.stop>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-200"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div
                                            class="p-2 rounded-xl bg-emerald-100 text-emerald-800 shrink-0"
                                        >
                                            <Files class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate">
                                                {{ selectedFiles.length }} File Terpilih
                                                <span class="text-[11px] font-normal text-slate-500">
                                                    ({{ totalFilesSize }})
                                                </span>
                                            </p>
                                            <p class="text-[10.5px] text-slate-500 line-clamp-1 sm:line-clamp-none">
                                                Seluruh data limbah akan diurutkan otomatis berdasarkan tanggal.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 sm:flex items-center gap-2 w-full sm:w-auto">
                                        <button
                                            type="button"
                                            @click="$refs.fileInputRef && $refs.fileInputRef.click()"
                                            class="px-2.5 sm:px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 cursor-pointer flex items-center justify-center gap-1.5 whitespace-nowrap transition-colors"
                                        >
                                            <Plus class="h-3.5 w-3.5 shrink-0" />
                                            <span>Tambah File</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="clearAllFiles"
                                            class="px-2.5 sm:px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 cursor-pointer text-center whitespace-nowrap transition-colors"
                                        >
                                            Hapus Semua
                                        </button>
                                    </div>
                                </div>

                                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-1">
                                    <div
                                        v-for="(f, fIdx) in selectedFiles"
                                        :key="fIdx"
                                        class="flex items-center justify-between bg-white px-3 py-2 rounded-xl border border-slate-200 text-xs shadow-2xs gap-2"
                                    >
                                        <div class="flex items-center gap-2 min-w-0">
                                            <FileSpreadsheet class="h-4 w-4 text-emerald-600 shrink-0" />
                                            <span class="font-medium text-slate-800 truncate">
                                                {{ f.name }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 shrink-0">
                                                ({{ formatFileSize(f.size) }})
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click.stop="removeSelectedFile(fIdx)"
                                            class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0 cursor-pointer"
                                            title="Hapus file ini"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="p-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-[11px] flex items-start gap-2"
                        >
                            <Info class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                            <p class="leading-relaxed">
                                Sistem akan membaca seluruh file sekaligus dan <strong>secara otomatis mengurutkannya berdasarkan tanggal (dari awal ke akhir)</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tahap 2: Tinjau & Koreksi Hasil Ekstraksi (Tabel Luas) -->
                <div v-else class="space-y-3.5">
                    <!-- ─── PANEL STATUS EKSEKUSI AI & TOKEN (PERMINTAAN USER) ─── -->
                    <div
                        v-if="executionMeta"
                        class="p-3.5 rounded-xl border text-xs space-y-2.5 transition-all shadow-2xs"
                        :class="
                            executionMeta.is_ai
                                ? 'bg-gradient-to-r from-indigo-50/90 via-violet-50/80 to-purple-50/90 border-indigo-200 text-slate-900'
                                : executionMeta.provider === 'excel_parser'
                                  ? 'bg-gradient-to-r from-emerald-50/90 via-teal-50/80 to-cyan-50/90 border-emerald-200 text-slate-900'
                                  : 'bg-amber-50/90 border-amber-200 text-amber-950'
                        "
                    >
                        <!-- Baris Atas: Status Mesin & Waktu -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-2"
                            :class="
                                executionMeta.is_ai
                                    ? 'border-indigo-200/60'
                                    : executionMeta.provider === 'excel_parser'
                                      ? 'border-emerald-200/60'
                                      : 'border-amber-200/60'
                            "
                        >
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- FLAG RINGKAS: AI vs Non-AI (Permintaan User) -->
                                <span
                                    v-if="executionMeta.is_ai"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-600 text-white font-black text-xs shadow-xs tracking-wide"
                                >
                                    <Sparkles class="h-3.5 w-3.5" />
                                    <span>AI</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-700 text-white font-black text-xs shadow-xs tracking-wide"
                                >
                                    <Cpu class="h-3.5 w-3.5" />
                                    <span>Non-AI</span>
                                </span>

                                <span class="font-bold text-slate-700 text-xs">
                                    {{ executionMeta.provider_name }}
                                </span>
                            </div>

                            <div
                                class="flex items-center gap-1 text-[11px] text-slate-500 shrink-0"
                            >
                                <Clock class="h-3.5 w-3.5 text-slate-400" />
                                <span
                                    >Waktu Proses:
                                    <strong
                                        >{{
                                            (
                                                (executionMeta.duration_ms ||
                                                    0) / 1000
                                            ).toFixed(2)
                                        }}
                                        dtk</strong
                                    ></span
                                >
                            </div>
                        </div>

                        <!-- 3 Kartu Metrik Ringkas: Model AI, Token, dan Jumlah Catatan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <!-- Kartu Model AI -->
                            <div
                                class="p-2 rounded-lg bg-white/90 border border-slate-200/80 shadow-2xs"
                            >
                                <p
                                    class="text-[10px] text-slate-500 font-medium uppercase tracking-wider flex items-center justify-between"
                                >
                                    <span>{{
                                        executionMeta.is_ai
                                            ? "Model AI Digunakan"
                                            : "Metode Pemrosesan"
                                    }}</span>
                                    <Layers class="h-3 w-3 text-indigo-500" />
                                </p>
                                <p
                                    class="text-xs font-mono font-bold truncate mt-0.5"
                                    :class="
                                        executionMeta.is_ai
                                            ? 'text-indigo-700'
                                            : 'text-teal-700'
                                    "
                                    :title="executionMeta.model"
                                >
                                    {{
                                        executionMeta.model ||
                                        "Ekstraksi Kolom Otomatis"
                                    }}
                                </p>
                            </div>

                            <!-- Kartu Konsumsi Token -->
                            <div
                                class="p-2 rounded-lg bg-white/90 border border-slate-200/80 shadow-2xs"
                            >
                                <p
                                    class="text-[10px] text-slate-500 font-medium uppercase tracking-wider flex items-center justify-between"
                                >
                                    <span>Konsumsi Token</span>
                                    <Zap class="h-3 w-3 text-amber-500" />
                                </p>
                                <div
                                    v-if="executionMeta.is_ai"
                                    class="flex items-baseline gap-1 mt-0.5"
                                >
                                    <span
                                        class="text-xs font-mono font-bold text-indigo-900"
                                    >
                                        {{
                                            executionMeta.tokens
                                                ?.total_tokens || 0
                                        }}
                                    </span>
                                    <span
                                        class="text-[10px] text-slate-500 font-normal"
                                        >Token</span
                                    >
                                    <span
                                        class="text-[9.5px] text-slate-400 font-mono ml-1"
                                    >
                                        (In:
                                        {{
                                            executionMeta.tokens
                                                ?.prompt_tokens || 0
                                        }}
                                        | Out:
                                        {{
                                            executionMeta.tokens
                                                ?.completion_tokens || 0
                                        }})
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="text-xs font-bold text-teal-800 mt-0.5"
                                >
                                    0 Token (Instan / Gratis)
                                </div>
                            </div>

                            <!-- Kartu Hasil Ekstraksi -->
                            <div
                                class="p-2 rounded-lg bg-white/90 border border-slate-200/80 shadow-2xs"
                            >
                                <p
                                    class="text-[10px] text-slate-500 font-medium uppercase tracking-wider flex items-center justify-between"
                                >
                                    <span>Hasil Ekstraksi</span>
                                    <CheckCircle2
                                        class="h-3 w-3 text-emerald-500"
                                    />
                                </p>
                                <p
                                    class="text-xs font-bold text-emerald-700 mt-0.5"
                                >
                                    {{ extractedItems.length }} Catatan Berhasil
                                    Dimuat
                                </p>
                            </div>
                        </div>

                        <!-- Catatan jika Fallback tanpa AI -->
                        <div
                            v-if="
                                !executionMeta.is_ai &&
                                executionMeta.provider !== 'excel_parser' &&
                                executionMeta.fallback_reason
                            "
                            class="p-2 rounded-lg bg-amber-100/70 border border-amber-300 text-amber-900 text-[11px] flex items-start gap-2"
                        >
                            <Info
                                class="h-4 w-4 text-amber-700 shrink-0 mt-0.5"
                            />
                            <div class="min-w-0">
                                <span class="font-bold">Keterangan: </span>
                                <span>{{
                                    executionMeta.fallback_reason
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar Tabel Aksi (Tambah Baris, Urutkan Tanggal & Ulangi Input) -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200"
                    >
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold text-slate-800">
                                Data Catatan Limbah Siap Review ({{
                                    extractedItems.length
                                }} baris)
                            </span>
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 whitespace-nowrap"
                            >
                                <Calendar class="h-3 w-3" />
                                <span>Terurut Tanggal</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 flex-wrap sm:flex-nowrap">
                            <!-- Tombol Urutkan Tanggal Manual -->
                            <button
                                type="button"
                                @click="sortExtractedByDate"
                                class="flex-1 sm:flex-none px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer whitespace-nowrap"
                                title="Susun ulang data secara kronologis dari tanggal terlama ke terbaru"
                            >
                                <ArrowUpDown
                                    class="h-3.5 w-3.5 text-slate-600 shrink-0"
                                />
                                <span>Urutkan</span>
                            </button>

                            <!-- Tombol Tambah Baris -->
                            <button
                                type="button"
                                @click="addItemRow"
                                class="flex-1 sm:flex-none px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-800 text-xs font-bold flex items-center justify-center gap-1 transition-colors cursor-pointer whitespace-nowrap"
                            >
                                <Plus class="h-3.5 w-3.5 text-indigo-600 shrink-0" />
                                <span>Tambah Baris</span>
                            </button>

                            <!-- Tombol Ulangi Input -->
                            <button
                                type="button"
                                @click="resetExtraction"
                                class="px-2.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition-colors cursor-pointer whitespace-nowrap"
                            >
                                Ulangi
                            </button>
                        </div>
                    </div>

                    <!-- Indikator Scroll di Layar Kecil -->
                    <div class="sm:hidden flex items-center justify-between text-[11px] text-slate-500 px-1 font-medium">
                        <span>👈 Geser tabel untuk melihat seluruh 10 kolom 👉</span>
                    </div>

                    <!-- Tabel Preview Interaktif (Lega & Tidak Terpotong) -->
                    <div
                        class="border border-slate-200 rounded-xl overflow-x-auto shadow-2xs"
                    >
                        <table class="w-full text-xs text-left min-w-[1050px]">
                            <thead
                                class="bg-slate-100/90 border-b border-slate-200 text-slate-700 font-bold uppercase text-[10.5px] tracking-wider"
                            >
                                <tr>
                                    <th class="px-3 py-2.5 text-center w-12">
                                        No
                                    </th>
                                    <th class="px-3 py-2.5 w-36">Tanggal</th>
                                    <th class="px-3 py-2.5 min-w-[180px]">
                                        Nama Limbah
                                    </th>
                                    <th class="px-3 py-2.5 w-32 text-center">
                                        Jumlah & Satuan
                                    </th>
                                    <th class="px-3 py-2.5 w-32">Jenis</th>
                                    <th class="px-3 py-2.5 w-32">Sumber</th>
                                    <th class="px-3 py-2.5 w-36">Penanganan</th>
                                    <th class="px-3 py-2.5 w-36">
                                        Status Bahaya
                                    </th>
                                    <th class="px-3 py-2.5 min-w-[160px]">
                                        Keterangan
                                    </th>
                                    <th class="px-2.5 py-2.5 text-center w-12">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(item, idx) in extractedItems"
                                    :key="idx"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td
                                        class="px-2.5 py-2 text-center text-slate-500 font-medium"
                                    >
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Tanggal -->
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="item.tanggal"
                                            type="date"
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                        />
                                    </td>

                                    <!-- Nama Limbah -->
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="item.nama_limbah"
                                            type="text"
                                            placeholder="Nama limbah..."
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                        />
                                    </td>

                                    <!-- Jumlah & Satuan -->
                                    <td class="px-2 py-1.5">
                                        <div class="flex items-center gap-1">
                                            <input
                                                v-model="item.jumlah"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                class="w-16 px-1.5 py-1 rounded-md border border-slate-200 text-xs text-right font-bold focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                            />
                                            <select
                                                v-model="item.satuan"
                                                class="px-1.5 py-1 rounded-md border border-slate-200 text-xs font-bold bg-white focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                            >
                                                <option
                                                    v-for="s in SATUAN_OPTIONS"
                                                    :key="s"
                                                    :value="s"
                                                >
                                                    {{ s }}
                                                </option>
                                            </select>
                                        </div>
                                    </td>

                                    <!-- Jenis Limbah -->
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="item.jenis_limbah"
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs font-semibold focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                            :class="
                                                item.jenis_limbah === 'Organik'
                                                    ? 'bg-emerald-50 text-emerald-800'
                                                    : 'bg-amber-50 text-amber-800'
                                            "
                                        >
                                            <option
                                                v-for="j in JENIS_OPTIONS"
                                                :key="j"
                                                :value="j"
                                            >
                                                {{ j }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- Sumber Limbah -->
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="item.sumber_limbah"
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                        >
                                            <option
                                                v-for="sb in SUMBER_OPTIONS"
                                                :key="sb"
                                                :value="sb"
                                            >
                                                {{ sb }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- Metode Penanganan -->
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="item.metode_penanganan"
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                        >
                                            <option
                                                v-for="m in METODE_OPTIONS"
                                                :key="m"
                                                :value="m"
                                            >
                                                {{ m }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- Status Berbahaya -->
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="item.status_berbahaya"
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs font-bold focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                            :class="
                                                item.status_berbahaya ===
                                                'Berbahaya'
                                                    ? 'bg-rose-50 text-rose-800'
                                                    : 'bg-slate-50 text-slate-700'
                                            "
                                        >
                                            <option
                                                v-for="st in STATUS_OPTIONS"
                                                :key="st"
                                                :value="st"
                                            >
                                                {{ st }}
                                            </option>
                                        </select>
                                    </td>

                                    <!-- Keterangan -->
                                    <td class="px-2 py-1.5">
                                        <input
                                            v-model="item.keterangan"
                                            type="text"
                                            placeholder="Catatan..."
                                            class="w-full px-2 py-1 rounded-md border border-slate-200 text-xs text-slate-600 focus:ring-1 focus:ring-violet-500 focus:outline-none"
                                        />
                                    </td>

                                    <!-- Aksi Hapus -->
                                    <td class="px-2 py-1.5 text-center">
                                        <button
                                            type="button"
                                            @click="removeItem(idx)"
                                            class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus baris ini"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pesan Error Global -->
                <div
                    v-if="errorMessage"
                    class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2"
                >
                    <AlertCircle class="h-4 w-4 text-rose-600 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>
            </div>

            <!-- ─── Footer Modal Semantik ─── -->
            <div
                class="px-4 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0"
            >
                <div class="text-xs text-slate-500 w-full sm:w-auto text-center sm:text-left">
                    <span
                        v-if="extractedItems.length > 0"
                        class="font-semibold text-slate-800"
                    >
                        Total siap disimpan: {{ extractedItems.length }} Catatan Limbah
                    </span>
                </div>

                <div class="w-full sm:w-auto flex items-center justify-end">
                    <!-- State 1: Saat Input Form -->
                    <div v-if="extractedItems.length === 0" class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors cursor-pointer text-center"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            @click="runAiExtraction"
                            :disabled="isAnalyzing"
                            class="px-4 sm:px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap min-w-0"
                        >
                            <Loader2 v-if="isAnalyzing" class="h-4 w-4 animate-spin text-white shrink-0" />
                            <Sparkles v-else class="h-4 w-4 text-amber-300 shrink-0" />
                            <span>{{
                                isAnalyzing
                                    ? "Menganalisis..."
                                    : "Analisis"
                            }}</span>
                        </button>
                    </div>

                    <!-- State 2: Saat Hasil Ekstraksi Tabel Siap -->
                    <div v-else class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto">
                        <button
                            type="button"
                            @click="resetExtraction"
                            class="px-3.5 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold transition-colors cursor-pointer text-center whitespace-nowrap"
                        >
                            Input Ulang
                        </button>

                        <button
                            type="button"
                            @click="closeModal"
                            class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-500 text-xs font-medium transition-colors cursor-pointer text-center whitespace-nowrap"
                        >
                            Tutup
                        </button>

                        <button
                            type="button"
                            @click="saveAllToDatabase"
                            :disabled="isSaving"
                            class="col-span-2 sm:col-span-1 px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 whitespace-nowrap"
                        >
                            <Loader2 v-if="isSaving" class="h-4 w-4 animate-spin shrink-0" />
                            <CheckCircle2 v-else class="h-4 w-4 shrink-0" />
                            <span>{{
                                isSaving
                                    ? "Menyimpan ke Database..."
                                    : `Simpan Semua (${extractedItems.length} Catatan)`
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
