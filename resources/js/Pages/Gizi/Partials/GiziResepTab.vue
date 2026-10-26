<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
    BookOpen,
    Search,
    Filter,
    Plus,
    Utensils,
    Package,
    Sparkles,
    Trash2,
    Eye,
    Calendar,
    ChevronDown,
    ChevronUp,
    CheckCircle2,
    Layers,
    FileText,
    ArrowRight,
    Scale,
    Flame,
    Beef,
    Wheat,
    HeartPulse,
    Info,
    AlertCircle,
    Copy,
} from "lucide-vue-next";

const props = defineProps({
    resepsList: {
        type: Array,
        default: () => [],
    },
    tkpiList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["open-rancang-menu"]);

// State Pencarian & Filter
const searchQuery = ref("");
const selectedTipeFilter = ref("all"); // 'all' | 'sub_menu' | 'paket_menu'
const selectedKategoriFilter = ref("all");
const expandedRecipeId = ref(null);

// Modal Detail Resep
const activeModalResep = ref(null);
const showDetailModal = ref(false);

// State Hapus Resep
const isDeleting = ref(false);
const recipeToDelete = ref(null);
const showDeleteConfirmModal = ref(false);
const notificationMessage = ref(null);
const notificationType = ref("success"); // 'success' | 'error'

function showNotification(msg, type = "success") {
    notificationMessage.value = msg;
    notificationType.value = type;
    setTimeout(() => {
        notificationMessage.value = null;
    }, 4000);
}

// Statistik Resep
const stats = computed(() => {
    const list = props.resepsList || [];
    const total = list.length;
    const subMenuCount = list.filter((r) => r.tipe === "sub_menu").length;
    const paketCount = list.filter((r) => r.tipe === "paket_menu").length;
    return {
        total,
        subMenuCount,
        paketCount,
    };
});

// Daftar Kategori Unik
const availableCategories = computed(() => {
    const set = new Set();
    (props.resepsList || []).forEach((r) => {
        if (r.kategori) set.add(r.kategori);
    });
    return Array.from(set).sort();
});

// Filtered List
const filteredReseps = computed(() => {
    let list = props.resepsList || [];

    // Filter Tipe
    if (selectedTipeFilter.value !== "all") {
        list = list.filter((r) => r.tipe === selectedTipeFilter.value);
    }

    // Filter Kategori
    if (selectedKategoriFilter.value !== "all") {
        list = list.filter((r) => r.kategori === selectedKategoriFilter.value);
    }

    // Filter Pencarian Teks
    if (searchQuery.value && searchQuery.value.trim() !== "") {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((r) => {
            const nama = (r.nama_resep || "").toLowerCase();
            const asal = (r.asal_menu || "").toLowerCase();
            const kat = (r.kategori || "").toLowerCase();
            const desk = (r.deskripsi || "").toLowerCase();

            // Cek juga nama-nama bahan di dalam resep
            let bahanMatch = false;
            const items = getRecipeItems(r);
            if (Array.isArray(items)) {
                bahanMatch = items.some((it) =>
                    (it.nama || "").toLowerCase().includes(q),
                );
            }

            return (
                nama.includes(q) ||
                asal.includes(q) ||
                kat.includes(q) ||
                desk.includes(q) ||
                bahanMatch
            );
        });
    }

    return list;
});

// Helper untuk mengambil items bahan dari resep
function getRecipeItems(resep) {
    if (!resep || !resep.data_resep) return [];
    if (resep.tipe === "sub_menu") {
        return resep.data_resep.items || [];
    }
    // Jika paket menu, gabungkan seluruh items
    if (resep.tipe === "paket_menu") {
        return resep.data_resep.items || [];
    }
    return [];
}

// Helper untuk mengambil ringkasan nutrisi
function getRecipeNutrition(resep) {
    if (!resep || !resep.data_resep) {
        return {
            pk: { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 },
            pb: { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 },
        };
    }
    return {
        pk: resep.data_resep.total_nutrisi_pk || {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        },
        pb: resep.data_resep.total_nutrisi_pb || {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        },
    };
}

// Format Tanggal
function formatDate(dateStr) {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function formatRupiah(val) {
    const num = Number(val) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

function toggleExpand(id) {
    if (expandedRecipeId.value === id) {
        expandedRecipeId.value = null;
    } else {
        expandedRecipeId.value = id;
    }
}

function openDetail(resep) {
    activeModalResep.value = resep;
    showDetailModal.value = true;
}

function closeDetail() {
    activeModalResep.value = null;
    showDetailModal.value = false;
}

function confirmDelete(resep) {
    recipeToDelete.value = resep;
    showDeleteConfirmModal.value = true;
}

async function handleDelete() {
    if (!recipeToDelete.value) return;
    isDeleting.value = true;
    try {
        const id = recipeToDelete.value.id || recipeToDelete.value.uuid;
        const res = await fetch(`/gizi/api/resep/${id}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
                Accept: "application/json",
            },
        });
        const json = await res.json();
        if (json.success) {
            showNotification("Resep berhasil dihapus dari koleksi.");
            showDeleteConfirmModal.value = false;
            recipeToDelete.value = null;
            // Reload props Inertia secara partial
            router.reload({ only: ["resepsList"] });
        } else {
            showNotification(json.message || "Gagal menghapus resep.", "error");
        }
    } catch (err) {
        showNotification("Terjadi kesalahan jaringan saat menghapus resep.", "error");
    } finally {
        isDeleting.value = false;
    }
}

function gunakanResepDiRancangMenu(resep) {
    // Navigasi ke Rancang Menu dengan membawa parameter resep
    emit("open-rancang-menu");
    // Simpan target resep ke sessionStorage agar langsung otomatis diaplikasikan di Rancang Menu
    if (typeof window !== "undefined") {
        sessionStorage.setItem("sipege_apply_resep", JSON.stringify(resep));
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Toast Notification -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="notificationMessage"
                class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-xs font-bold text-white"
                :class="
                    notificationType === 'success' ? 'bg-emerald-600' : 'bg-rose-600'
                "
            >
                <CheckCircle2
                    v-if="notificationType === 'success'"
                    class="h-4 w-4 shrink-0"
                />
                <AlertCircle v-else class="h-4 w-4 shrink-0" />
                <span>{{ notificationMessage }}</span>
            </div>
        </transition>

        <!-- Header Card Banner -->
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl border border-indigo-900/50"
        >
            <!-- Background Glow Accent -->
            <div
                class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"
            ></div>
            <div
                class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-emerald-500/15 blur-3xl pointer-events-none"
            ></div>

            <div
                class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6"
            >
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold backdrop-blur-xs">
                        <BookOpen class="h-3.5 w-3.5 text-indigo-400" />
                        <span>Koleksi & Standarisasi Resep MBG</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                        Bank Resep Sub Menu & Paket Gizi
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        Kelola formula resep terstandarisasi untuk komponen menu (sub-menu) maupun paket menu harian lengkap. Resep yang tersimpan dapat langsung diaplikasikan satu-klik saat merancang menu produksi.
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <Button
                        type="button"
                        @click="emit('open-rancang-menu')"
                        className="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black px-5 py-2.5 rounded-xl text-xs sm:text-sm flex items-center gap-2 shadow-lg shadow-emerald-500/20 cursor-pointer transition-all hover:scale-[1.02]"
                    >
                        <Utensils class="h-4 w-4" />
                        <span>Rancang Menu Baru</span>
                    </Button>
                </div>
            </div>

            <!-- Quick Stats Bar -->
            <div
                class="relative z-10 mt-6 pt-6 border-t border-indigo-800/40 grid grid-cols-1 sm:grid-cols-3 gap-4"
            >
                <div class="flex items-center gap-3 bg-white/5 rounded-2xl p-3.5 border border-white/10 backdrop-blur-xs">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Total Resep Tersimpan</div>
                        <div class="text-xl font-black text-white">{{ stats.total }} <span class="text-xs font-normal text-slate-400">Resep</span></div>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white/5 rounded-2xl p-3.5 border border-white/10 backdrop-blur-xs">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Resep Sub Menu (Komponen)</div>
                        <div class="text-xl font-black text-white">{{ stats.subMenuCount }} <span class="text-xs font-normal text-slate-400">Komponen</span></div>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white/5 rounded-2xl p-3.5 border border-white/10 backdrop-blur-xs">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Paket Menu Lengkap</div>
                        <div class="text-xl font-black text-white">{{ stats.paketCount }} <span class="text-xs font-normal text-slate-400">Paket</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar Pencarian & Filter -->
        <div
            class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4"
        >
            <div class="relative flex-1">
                <Search
                    class="h-4 w-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari berdasarkan nama resep, asal menu, kategori, atau nama bahan..."
                    class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all text-slate-800 placeholder-slate-400"
                />
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Filter Tipe -->
                <div class="flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200 text-xs">
                    <button
                        type="button"
                        @click="selectedTipeFilter = 'all'"
                        :class="[
                            'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer text-xs',
                            selectedTipeFilter === 'all'
                                ? 'bg-white text-slate-900 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900',
                        ]"
                    >
                        Semua Tipe
                    </button>
                    <button
                        type="button"
                        @click="selectedTipeFilter = 'sub_menu'"
                        :class="[
                            'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer text-xs',
                            selectedTipeFilter === 'sub_menu'
                                ? 'bg-white text-purple-700 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900',
                        ]"
                    >
                        Sub Menu
                    </button>
                    <button
                        type="button"
                        @click="selectedTipeFilter = 'paket_menu'"
                        :class="[
                            'px-3 py-1 rounded-lg font-bold transition-all cursor-pointer text-xs',
                            selectedTipeFilter === 'paket_menu'
                                ? 'bg-white text-emerald-700 shadow-2xs'
                                : 'text-slate-500 hover:text-slate-900',
                        ]"
                    >
                        Paket Lengkap
                    </button>
                </div>

                <!-- Filter Kategori -->
                <select
                    v-if="availableCategories.length > 0"
                    v-model="selectedKategoriFilter"
                    class="text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white font-medium text-slate-700 focus:outline-hidden focus:border-indigo-500"
                >
                    <option value="all">Semua Kategori</option>
                    <option
                        v-for="cat in availableCategories"
                        :key="cat"
                        :value="cat"
                    >
                        {{ cat }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Daftar Resep Grid -->
        <div v-if="filteredReseps.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
                v-for="resep in filteredReseps"
                :key="resep.id || resep.uuid"
                class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group hover:border-indigo-300"
            >
                <div class="p-5 space-y-4">
                    <!-- Badges Header -->
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10.5px] font-black uppercase tracking-wider border',
                                resep.tipe === 'paket_menu'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-purple-50 text-purple-800 border-purple-200',
                            ]"
                        >
                            {{ resep.tipe === "paket_menu" ? "📦 Paket Menu Lengkap" : "🍳 Sub Menu" }}
                        </span>

                        <span
                            v-if="resep.kategori"
                            class="px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200"
                        >
                            {{ resep.kategori }}
                        </span>
                    </div>

                    <!-- Judul & Asal Menu -->
                    <div>
                        <h3 class="text-base font-black text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1">
                            {{ resep.nama_resep }}
                        </h3>
                        <p
                            v-if="resep.asal_menu"
                            class="text-[11.5px] text-slate-500 flex items-center gap-1 mt-0.5 line-clamp-1"
                        >
                            <span class="text-slate-400">Dari Menu:</span>
                            <strong class="text-slate-700 font-semibold">{{ resep.asal_menu }}</strong>
                        </p>
                        <p
                            v-if="resep.deskripsi"
                            class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed"
                        >
                            {{ resep.deskripsi }}
                        </p>
                    </div>

                    <!-- Nutrisi Acuan Mini Cards (PK & PB) -->
                    <div class="rounded-xl bg-slate-50 p-2.5 border border-slate-150 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-[10.5px] font-black uppercase tracking-wider text-slate-500 border-b border-slate-200/60 pb-1">
                            <span>Estimasi Zat Gizi</span>
                            <span>PK • PB</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <!-- Energi -->
                            <div class="flex items-center justify-between">
                                <span class="text-amber-800 font-medium flex items-center gap-1">
                                    <Flame class="h-3 w-3 text-amber-500" />
                                    Energi:
                                </span>
                                <span class="font-black text-slate-800">
                                    {{ getRecipeNutrition(resep).pk.energi }} / {{ getRecipeNutrition(resep).pb.energi }} <span class="text-[9px] text-slate-400 font-normal">kkal</span>
                                </span>
                            </div>
                            <!-- Protein -->
                            <div class="flex items-center justify-between">
                                <span class="text-blue-800 font-medium flex items-center gap-1">
                                    <Beef class="h-3 w-3 text-blue-500" />
                                    Protein:
                                </span>
                                <span class="font-black text-slate-800">
                                    {{ getRecipeNutrition(resep).pk.protein }} / {{ getRecipeNutrition(resep).pb.protein }} <span class="text-[9px] text-slate-400 font-normal">g</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Bahan Makanan (Maks 3 bahan + count) -->
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-bold">
                                Komposisi Bahan:
                            </span>
                            <span class="text-[10.5px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                {{ getRecipeItems(resep).length }} Bahan Baku
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div
                                v-for="(b, bIdx) in getRecipeItems(resep).slice(0, 3)"
                                :key="bIdx"
                                class="flex items-center justify-between text-[11px] bg-slate-50/70 px-2.5 py-1 rounded-lg border border-slate-100"
                            >
                                <span class="text-slate-800 font-medium truncate max-w-[160px]">
                                    • {{ b.nama }}
                                </span>
                                <span class="text-slate-500 font-mono text-[10px] shrink-0">
                                    PK:{{ b.gram_pk || 0 }}g | PB:{{ b.gram_pb || 0 }}g
                                </span>
                            </div>
                            <div
                                v-if="getRecipeItems(resep).length > 3"
                                class="text-[10.5px] text-indigo-600 font-bold text-center pt-0.5"
                            >
                                + {{ getRecipeItems(resep).length - 3 }} bahan lainnya...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Actions -->
                <div
                    class="bg-slate-50/90 px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-2"
                >
                    <div class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                        <Calendar class="h-3 w-3" />
                        <span>{{ formatDate(resep.created_at) }}</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="openDetail(resep)"
                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition-colors cursor-pointer"
                            title="Lihat Detail Resep"
                        >
                            <Eye class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete(resep)"
                            class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer"
                            title="Hapus Resep"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click="gunakanResepDiRancangMenu(resep)"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-2.5 py-1.5 rounded-xl flex items-center gap-1 cursor-pointer transition shadow-2xs"
                        >
                            <span>Gunakan</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="bg-white rounded-3xl p-10 sm:p-14 border border-dashed border-slate-300 text-center space-y-4 shadow-xs"
        >
            <div
                class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center mx-auto shadow-inner"
            >
                <BookOpen class="h-8 w-8" />
            </div>
            <div class="space-y-1 max-w-md mx-auto">
                <h3 class="text-base sm:text-lg font-black text-slate-900">
                    Belum Ada Resep Tersimpan
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Anda belum memiliki resep yang tersimpan di koleksi ini. Mulailah merancang menu dan klik tombol <strong>"Simpan sebagai Resep"</strong> pada masing-masing sub menu atau simpan <strong>1 keseluruhan menu</strong> untuk digunakan kembali kapan saja.
                </p>
            </div>
            <div class="pt-2">
                <Button
                    type="button"
                    @click="emit('open-rancang-menu')"
                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl text-xs inline-flex items-center gap-2 cursor-pointer shadow-sm transition"
                >
                    <Utensils class="h-4 w-4" />
                    <span>Mulai Rancang Menu</span>
                </Button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL DETAIL RESEP LENGKAP -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div
                v-if="showDetailModal && activeModalResep"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200"
            >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200"
            >
                <!-- Header Modal -->
                <div
                    class="p-5 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-indigo-50/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <BookOpen class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border',
                                        activeModalResep.tipe === 'paket_menu'
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                            : 'bg-purple-50 text-purple-800 border-purple-200',
                                    ]"
                                >
                                    {{ activeModalResep.tipe === 'paket_menu' ? 'Paket Menu Lengkap' : 'Resep Sub Menu' }}
                                </span>
                                <span
                                    v-if="activeModalResep.kategori"
                                    class="text-[10px] bg-slate-200/70 text-slate-700 px-2 py-0.5 rounded font-bold"
                                >
                                    {{ activeModalResep.kategori }}
                                </span>
                            </div>
                            <h3 class="text-base font-black text-slate-900 mt-0.5">
                                {{ activeModalResep.nama_resep }}
                            </h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeDetail"
                        class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-white border border-transparent hover:border-slate-200 transition cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Body Modal (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-5 text-slate-800 text-xs">
                    <!-- Meta info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <div>
                            <span class="text-slate-400 text-[11px] block">Asal Sumber Menu:</span>
                            <span class="font-bold text-slate-900 text-xs">{{ activeModalResep.asal_menu || "-" }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[11px] block">Waktu Tersimpan:</span>
                            <span class="font-bold text-slate-900 text-xs">{{ formatDate(activeModalResep.created_at) }}</span>
                        </div>
                        <div v-if="activeModalResep.deskripsi" class="sm:col-span-2 pt-1 border-t border-slate-200/60">
                            <span class="text-slate-400 text-[11px] block">Catatan / Petunjuk Masak:</span>
                            <p class="text-slate-700 text-xs leading-relaxed mt-0.5">{{ activeModalResep.deskripsi }}</p>
                        </div>
                    </div>

                    <!-- Nutrisi PK & PB Details -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <HeartPulse class="h-4 w-4 text-rose-500" />
                            <span>Kandungan Gizi Standar (per Porsi)</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Card Porsi Kecil -->
                            <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200 space-y-2">
                                <span class="font-black text-amber-950 block border-b border-amber-200 pb-1">
                                    Porsi Kecil (PK):
                                </span>
                                <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                    <div>Energi: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pk.energi }} kkal</strong></div>
                                    <div>Protein: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pk.protein }} g</strong></div>
                                    <div>Lemak: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pk.lemak }} g</strong></div>
                                    <div>Karbo: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pk.karbohidrat }} g</strong></div>
                                    <div>Serat: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pk.serat }} g</strong></div>
                                </div>
                            </div>
                            <!-- Card Porsi Besar -->
                            <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200 space-y-2">
                                <span class="font-black text-blue-950 block border-b border-blue-200 pb-1">
                                    Porsi Besar (PB):
                                </span>
                                <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                    <div>Energi: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pb.energi }} kkal</strong></div>
                                    <div>Protein: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pb.protein }} g</strong></div>
                                    <div>Lemak: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pb.lemak }} g</strong></div>
                                    <div>Karbo: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pb.karbohidrat }} g</strong></div>
                                    <div>Serat: <strong class="text-slate-900">{{ getRecipeNutrition(activeModalResep).pb.serat }} g</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Komposisi Bahan Pangan -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                <Scale class="h-4 w-4 text-indigo-500" />
                                <span>Daftar Bahan Baku, Harga & Catatan</span>
                            </h4>
                            <span class="text-[10.5px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                {{ getRecipeItems(activeModalResep).length }} Bahan Terdaftar
                            </span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-100 text-[10px] font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="p-2.5 w-8 text-center">No</th>
                                        <th class="p-2.5">Bahan Pangan & Nama PO</th>
                                        <th class="p-2.5 text-center">Sub Menu</th>
                                        <th class="p-2.5 text-right">Harga / Satuan</th>
                                        <th class="p-2.5 text-center">Gram PK</th>
                                        <th class="p-2.5 text-center">Gram PB</th>
                                        <th class="p-2.5 text-center">BDD / Buffer</th>
                                        <th class="p-2.5">Catatan / Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(it, idx) in getRecipeItems(activeModalResep)"
                                        :key="idx"
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <td class="p-2.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                        <td class="p-2.5">
                                            <div class="font-bold text-slate-900 leading-tight">{{ it.nama }}</div>
                                            <div v-if="it.nama_po" class="text-[10px] font-semibold text-slate-500 mt-0.5">
                                                PO: <span class="text-slate-800">{{ it.nama_po }}</span>
                                            </div>
                                            <div v-if="it.alergen" class="text-[9.5px] text-amber-700 font-medium">Alergen: {{ it.alergen }}</div>
                                        </td>
                                        <td class="p-2.5 text-center text-slate-600 text-[11px] font-medium">{{ it.nama_sub_menu || "-" }}</td>
                                        <td class="p-2.5 text-right font-black text-slate-900 text-[11px] shrink-0">
                                            <div>{{ formatRupiah(it.harga_master || it.harga_aktual || 0) }}</div>
                                            <div class="text-[9.5px] text-slate-400 font-medium">/ {{ it.satuan || "Kg" }}</div>
                                        </td>
                                        <td class="p-2.5 text-center font-bold text-slate-800 bg-amber-50/30">{{ it.gram_pk || 0 }}g</td>
                                        <td class="p-2.5 text-center font-bold text-slate-800 bg-blue-50/30">{{ it.gram_pb || 0 }}g</td>
                                        <td class="p-2.5 text-center text-[10.5px] text-slate-600">
                                            <div>BDD: {{ it.bdd || 100 }}%</div>
                                            <div class="text-[9.5px] text-slate-400">+{{ it.buffer || 0 }}%</div>
                                        </td>
                                        <td class="p-2.5 max-w-[180px]">
                                            <span
                                                v-if="it.keterangan && it.keterangan !== '-'"
                                                class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-amber-50 text-amber-900 border border-amber-200 inline-block leading-tight"
                                            >
                                                {{ it.keterangan }}
                                            </span>
                                            <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
                    <Button
                        type="button"
                        @click="closeDetail"
                        variant="secondary"
                        className="text-xs font-bold px-4 py-2"
                    >
                        Tutup
                    </Button>
                    <Button
                        type="button"
                        @click="() => { closeDetail(); gunakanResepDiRancangMenu(activeModalResep); }"
                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 flex items-center gap-1.5 cursor-pointer shadow-sm"
                    >
                        <span>Gunakan Resep Ini di Rancang Menu</span>
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
        </Teleport>

        <!-- ========================================================================= -->
        <!-- MODAL KONFIRMASI HAPUS RESEP -->
        <!-- ========================================================================= -->
        <Teleport to="body">
            <div
                v-if="showDeleteConfirmModal && recipeToDelete"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200"
            >
                <div
                    class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md p-6 space-y-4 animate-in zoom-in-95 duration-200"
                >
                    <div
                        class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto"
                    >
                        <Trash2 class="h-6 w-6" />
                    </div>
                    <div class="text-center space-y-1">
                        <h3 class="text-base font-black text-slate-900">
                            Hapus Resep dari Koleksi?
                        </h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Anda akan menghapus resep <strong class="text-slate-800 font-bold">"{{ recipeToDelete.nama_resep }}"</strong>. Resep yang sudah dihapus tidak dapat dikembalikan.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <Button
                            type="button"
                            @click="showDeleteConfirmModal = false; recipeToDelete = null;"
                            variant="secondary"
                            className="flex-1 text-xs font-bold py-2.5"
                            :disabled="isDeleting"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            @click="handleDelete"
                            className="flex-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold py-2.5 flex items-center justify-center gap-1.5 cursor-pointer"
                            :disabled="isDeleting"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span>{{ isDeleting ? "Menghapus..." : "Ya, Hapus" }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
