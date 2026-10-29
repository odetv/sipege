<script setup>
import { ref, computed } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Tag,
    Plus,
    RotateCcw,
    Search,
    Edit3,
    Trash2,
    DollarSign,
    CheckCircle2,
    AlertCircle,
    Utensils,
    Layers,
    X,
    Sparkles,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    kategoriList: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
});

const searchQuery = ref("");
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const isResetModalOpen = ref(false);
const isResetting = ref(false);

const isDeleteModalOpen = ref(false);
const deletingItem = ref(null);
const isDeleting = ref(false);

const form = useForm({
    nama: "",
    pagu_per_porsi: 10000,
    jenis_porsi: "Porsi Besar",
    keterangan: "",
    status: "Aktif",
});

function formatRupiah(val) {
    const n = Number(val) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(n);
}

function formatRibuan(val) {
    const n = Number(val) || 0;
    return new Intl.NumberFormat("id-ID").format(n);
}

// Filter data
const filteredKategori = computed(() => {
    if (!searchQuery.value) return props.kategoriList;
    const q = searchQuery.value.toLowerCase();
    return props.kategoriList.filter((k) => {
        return (
            k.nama.toLowerCase().includes(q) ||
            String(k.pagu_per_porsi).includes(q) ||
            (k.keterangan && k.keterangan.toLowerCase().includes(q))
        );
    });
});

// Modal Tambah / Edit
function openAddModal() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.pagu_per_porsi = 10000;
    form.jenis_porsi = "Porsi Besar";
    form.status = "Aktif";
    isModalOpen.value = true;
}

function openEditModal(item) {
    isEditing.value = true;
    editingId.value = item.id;
    form.clearErrors();
    form.nama = item.nama;
    form.pagu_per_porsi = item.pagu_per_porsi;
    form.jenis_porsi = item.jenis_porsi || "Porsi Besar";
    form.keterangan = item.keterangan || "";
    form.status = item.status || "Aktif";
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    isEditing.value = false;
    editingId.value = null;
    form.reset();
}

function submitForm() {
    const updateRouteName = route().has("penerima-manfaat.kategori-kpm.update")
        ? "penerima-manfaat.kategori-kpm.update"
        : "pengaturan.kategori-kpm.update";
    const storeRouteName = route().has("penerima-manfaat.kategori-kpm.store")
        ? "penerima-manfaat.kategori-kpm.store"
        : "pengaturan.kategori-kpm.store";

    if (isEditing.value) {
        form.put(route(updateRouteName, editingId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route(storeRouteName), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

// Modal Hapus
function openDeleteModal(item) {
    deletingItem.value = item;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    deletingItem.value = null;
}

function confirmDelete() {
    if (!deletingItem.value) return;
    isDeleting.value = true;
    const destroyRouteName = route().has("penerima-manfaat.kategori-kpm.destroy")
        ? "penerima-manfaat.kategori-kpm.destroy"
        : "pengaturan.kategori-kpm.destroy";

    router.delete(route(destroyRouteName, deletingItem.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

// Modal Reset ke Default
function openResetModal() {
    isResetModalOpen.value = true;
}

function closeResetModal() {
    isResetModalOpen.value = false;
}

function confirmReset() {
    isResetting.value = true;
    const resetRouteName = route().has("penerima-manfaat.kategori-kpm.reset")
        ? "penerima-manfaat.kategori-kpm.reset"
        : "pengaturan.kategori-kpm.reset";

    router.post(route(resetRouteName), {}, {
        preserveScroll: true,
        onSuccess: () => closeResetModal(),
        onFinish: () => {
            isResetting.value = false;
        },
    });
}

function getPaguBadgeClass(pagu) {
    if (pagu === 8000) {
        return "bg-teal-50 text-teal-800 border-teal-200 ring-1 ring-teal-500/20";
    }
    if (pagu === 10000) {
        return "bg-emerald-50 text-emerald-800 border-emerald-200 ring-1 ring-emerald-500/20";
    }
    return "bg-indigo-50 text-indigo-800 border-indigo-200 ring-1 ring-indigo-500/20";
}
</script>

<template>
    <Head title="Kategori KPM - SIPEGE" />

    <AppLayout>
        <div class="space-y-6 pb-16">
            <!-- ─── HEADER UTAMA ─── -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-600 text-white shadow-xs">
                            <Tag class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                Kategori Kelompok Penerima Manfaat (KPM)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    Pagu Standar BGN
                                </span>
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-500">
                                Kelola kategori sasaran penerima manfaat beserta pagu anggaran per porsi operasional SPPG.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="openResetModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 sm:py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-2xs transition-colors cursor-pointer"
                        title="Kembalikan ke 11 Kategori Standar Default BGN"
                    >
                        <RotateCcw class="h-4 w-4 text-slate-500" />
                        <span>Reset ke Default</span>
                    </button>

                    <button
                        type="button"
                        @click="openAddModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold shadow-2xs transition-colors cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>

            <!-- ─── KPI SUMMARY CARDS ─── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Card 1: Total Kategori -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-slate-100 text-slate-700">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Total Kategori</p>
                        <p class="text-base font-bold text-slate-900 leading-tight">
                            {{ summary.total_kategori ?? 0 }} <span class="text-xs font-normal text-slate-500">kelompok</span>
                        </p>
                    </div>
                </div>

                <!-- Card 2: Pagu Rp 8.000 -->
                <div class="p-3.5 rounded-xl bg-teal-50/60 border border-teal-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-teal-600 text-white shadow-xs">
                        <Utensils class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold text-teal-800 uppercase tracking-wider">Porsi Rp 8.000</p>
                        <p class="text-base font-bold text-teal-950 leading-tight">
                            {{ summary.total_pagu_8000 ?? 0 }} <span class="text-xs font-normal text-teal-700">kategori</span>
                        </p>
                        <p class="text-[9.5px] text-teal-600 mt-0.5">PAUD, Balita & SD Bawah</p>
                    </div>
                </div>

                <!-- Card 3: Pagu Rp 10.000 -->
                <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-emerald-600 text-white shadow-xs">
                        <DollarSign class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Porsi Rp 10.000</p>
                        <p class="text-base font-bold text-emerald-950 leading-tight">
                            {{ summary.total_pagu_10000 ?? 0 }} <span class="text-xs font-normal text-emerald-700">kategori</span>
                        </p>
                        <p class="text-[9.5px] text-emerald-600 mt-0.5">SD Atas, SMP, SMA, Bumil, dll</p>
                    </div>
                </div>

                <!-- Card 4: Rata-rata Pagu -->
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Rata-rata Pagu</p>
                        <p class="text-base font-bold text-amber-900 leading-tight truncate">
                            {{ formatRupiah(summary.rata_rata_pagu ?? 0) }}
                        </p>
                        <p class="text-[9.5px] text-slate-400 mt-0.5">Per porsi makanan</p>
                    </div>
                </div>
            </div>

            <!-- ─── BARIS PENCARIAN ─── -->
            <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kategori KPM atau nominal pagu..."
                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary"
                    />
                </div>

                <div class="text-xs text-slate-500">
                    Total: <strong class="text-slate-800">{{ filteredKategori.length }}</strong> kategori sasaran
                </div>
            </div>

            <!-- ─── TABEL KATEGORI KPM ─── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/90 text-slate-700 border-b border-slate-200 font-bold">
                                <th class="py-2.5 px-3 text-center w-12 border-r border-slate-200">No</th>
                                <th class="py-2.5 px-3 min-w-[220px] border-r border-slate-200">Nama Kategori KPM</th>
                                <th class="py-2.5 px-3 text-right min-w-[150px] border-r border-slate-200">Pagu per Porsi</th>
                                <th class="py-2.5 px-3 min-w-[130px] border-r border-slate-200">Tipe Porsi</th>
                                <th class="py-2.5 px-3 min-w-[260px] border-r border-slate-200">Deskripsi / Sasaran</th>
                                <th class="py-2.5 px-2 text-center w-20 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-2 text-center w-24">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(k, idx) in filteredKategori"
                                :key="k.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-2.5 px-3 text-center font-medium text-slate-500 border-r border-slate-200">
                                    {{ idx + 1 }}
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <p class="font-bold text-slate-900 leading-snug">{{ k.nama }}</p>
                                </td>

                                <td class="py-2.5 px-3 text-right border-r border-slate-200">
                                    <span :class="['inline-block px-2.5 py-1 rounded-lg text-xs font-mono font-bold border', getPaguBadgeClass(k.pagu_per_porsi)]">
                                        {{ formatRupiah(k.pagu_per_porsi) }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ k.jenis_porsi || 'Porsi Besar' }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-3 text-slate-600 border-r border-slate-200 text-[11px]">
                                    {{ k.keterangan || '-' }}
                                </td>

                                <td class="py-2.5 px-2 text-center border-r border-slate-200">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                            k.status === 'Aktif'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                : 'bg-slate-100 text-slate-500 border-slate-200',
                                        ]"
                                    >
                                        {{ k.status }}
                                    </span>
                                </td>

                                <td class="py-2.5 px-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="openEditModal(k)"
                                            class="p-1 rounded-md text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer"
                                            title="Ubah Kategori"
                                        >
                                            <Edit3 class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(k)"
                                            class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Kategori"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredKategori.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-500">
                                    <Tag class="h-8 w-8 mx-auto text-slate-300 mb-2" />
                                    <p class="text-xs font-semibold">Tidak ada kategori KPM yang cocok.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Standar Nasional BGN: <span class="font-bold text-slate-700">Rp 8.000</span> (PAUD/SD Bawah/Balita) & <span class="font-bold text-slate-700">Rp 10.000</span> (SD Atas/SMP/SMA/Bumil/Menyusui)
                    </div>
                </div>
            </div>

            <!-- ─── MODAL TAMBAH / EDIT KATEGORI ─── -->
            <Teleport to="body">
                <div
                    v-if="isModalOpen"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Tag class="h-5 w-5 text-emerald-200" />
                                <div>
                                    <h3 class="text-sm font-bold">
                                        {{ isEditing ? 'Ubah Kategori KPM' : 'Tambah Kategori KPM Baru' }}
                                    </h3>
                                    <p class="text-[11px] text-emerald-100">Pagu anggaran dan tipe porsi sasaran</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeModal"
                                class="p-1 rounded-lg text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="p-5 space-y-4">
                            <!-- Nama Kategori -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nama Kategori KPM <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.nama"
                                    type="text"
                                    required
                                    placeholder="Contoh: PAUD/TK/RA, SD/MI Kelas 1–3, SMP/MTs"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-medium focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                <p v-if="form.errors.nama" class="text-[11px] text-rose-600 font-medium mt-1">
                                    {{ form.errors.nama }}
                                </p>
                            </div>

                            <!-- Pagu per Porsi -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Pagu Anggaran per Porsi (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="form.pagu_per_porsi"
                                        type="number"
                                        min="0"
                                        step="100"
                                        required
                                        placeholder="10000"
                                        class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs font-mono font-semibold focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    />
                                </div>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <button
                                        type="button"
                                        @click="form.pagu_per_porsi = 8000; form.jenis_porsi = 'Porsi Kecil'"
                                        class="px-2 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-100 transition-colors"
                                    >
                                        Set Rp 8.000 (Porsi Kecil)
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.pagu_per_porsi = 10000; form.jenis_porsi = 'Porsi Besar'"
                                        class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                    >
                                        Set Rp 10.000 (Porsi Besar)
                                    </button>
                                </div>
                                <p v-if="form.errors.pagu_per_porsi" class="text-[11px] text-rose-600 font-medium mt-1">
                                    {{ form.errors.pagu_per_porsi }}
                                </p>
                            </div>

                            <!-- Tipe Porsi & Status -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tipe Porsi
                                    </label>
                                    <select
                                        v-model="form.jenis_porsi"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    >
                                        <option value="Porsi Kecil">Porsi Kecil</option>
                                        <option value="Porsi Besar">Porsi Besar</option>
                                        <option value="Khusus">Khusus</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        v-model="form.status"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    >
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Keterangan / Sasaran -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Keterangan / Sasaran
                                </label>
                                <textarea
                                    v-model="form.keterangan"
                                    rows="2"
                                    placeholder="Rincian jenjang penerima manfaat atau catatan..."
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambahkan Kategori') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- ─── MODAL RESET KE DEFAULT ─── -->
            <Teleport to="body">
                <div
                    v-if="isResetModalOpen"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="p-5 text-center space-y-3">
                            <div class="h-12 w-12 rounded-full bg-amber-50 text-amber-600 mx-auto flex items-center justify-center border border-amber-200">
                                <RotateCcw class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Reset ke Default BGN?</h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    Daftar kategori KPM akan dikembalikan ke 11 standar resmi BGN (PAUD s.d. Balita dengan pagu Rp 8.000 & Rp 10.000).
                                </p>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeResetModal"
                                class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="confirmReset"
                                :disabled="isResetting"
                                class="px-4 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                            >
                                {{ isResetting ? 'Mereset...' : 'Ya, Reset ke Default' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ─── MODAL HAPUS KATEGORI ─── -->
            <Teleport to="body">
                <div
                    v-if="isDeleteModalOpen && deletingItem"
                    class="fixed inset-0 z-[99999] min-h-screen w-screen overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
                >
                    <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-200">
                        <div class="p-5 text-center space-y-3">
                            <div class="h-12 w-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center border border-rose-200">
                                <Trash2 class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Hapus Kategori KPM?</h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    Kategori <strong>{{ deletingItem.nama }}</strong> dengan pagu {{ formatRupiah(deletingItem.pagu_per_porsi) }} akan dihapus dari daftar.
                                </p>
                            </div>
                        </div>

                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeDeleteModal"
                                class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="confirmDelete"
                                :disabled="isDeleting"
                                class="px-4 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors cursor-pointer disabled:opacity-50"
                            >
                                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
