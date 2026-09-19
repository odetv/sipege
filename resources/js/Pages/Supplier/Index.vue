<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Store,
    Plus,
    Pencil,
    Trash2,
    Search,
    Filter,
    Building2,
    Phone,
    User,
    MapPin,
    Tag,
    X,
    Check,
    AlertCircle,
    CheckCircle2,
    Boxes,
    ShoppingBag,
    HelpCircle,
    ExternalLink,
    RefreshCw,
    Briefcase,
    FileText,
    ChevronDown,
    Loader2,
} from "lucide-vue-next";
import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
} from "@/Services/wilayah";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    unitSppg: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ─── Helper Pembersihan Nama Kabupaten / Kota ─────────────────────────────────
function cleanKabupatenName(name) {
    if (!name) return "";
    return name.replace(/^(Kabupaten|Kab\.|Kota)\s+/i, "").trim();
}

// ─── Filter & Search State ───────────────────────────────────────────────────
const searchQuery = ref("");
const selectedJenis = ref("all");
const selectedKomoditasFilter = ref("all");

const jenisOptions = [
    { value: "KDMP", label: "KDMP" },
    { value: "Koperasi", label: "Koperasi" },
    { value: "Bumdes", label: "BUMDes" },
    { value: "Bumdesma", label: "BUMDesma" },
    { value: "UMKM", label: "UMKM" },
    { value: "UD", label: "UD" },
    { value: "CV", label: "CV" },
    { value: "PT", label: "PT" },
    { value: "Lainnya", label: "Lainnya" },
];

const jenisColors = {
    KDMP: {
        bg: "bg-emerald-50 text-emerald-700 border-emerald-200",
        dot: "bg-emerald-500",
    },
    Koperasi: {
        bg: "bg-blue-50 text-blue-700 border-blue-200",
        dot: "bg-blue-500",
    },
    Bumdes: {
        bg: "bg-indigo-50 text-indigo-700 border-indigo-200",
        dot: "bg-indigo-500",
    },
    Bumdesma: {
        bg: "bg-violet-50 text-violet-700 border-violet-200",
        dot: "bg-violet-500",
    },
    UMKM: {
        bg: "bg-amber-50 text-amber-700 border-amber-200",
        dot: "bg-amber-500",
    },
    UD: { bg: "bg-sky-50 text-sky-700 border-sky-200", dot: "bg-sky-500" },
    CV: {
        bg: "bg-purple-50 text-purple-700 border-purple-200",
        dot: "bg-purple-500",
    },
    PT: { bg: "bg-teal-50 text-teal-700 border-teal-200", dot: "bg-teal-500" },
    Lainnya: {
        bg: "bg-slate-50 text-slate-700 border-slate-200",
        dot: "bg-slate-500",
    },
};

// Rekap list seluruh komoditas unik untuk filter
const allKomoditasList = computed(() => {
    const set = new Set();
    props.suppliers.forEach((s) => {
        if (Array.isArray(s.komoditas)) {
            s.komoditas.forEach((k) => {
                if (k && String(k).trim()) {
                    set.add(String(k).trim());
                }
            });
        }
    });
    return Array.from(set).sort();
});

// Filtered Suppliers
const filteredSuppliers = computed(() => {
    return props.suppliers.filter((s) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !query ||
            (s.nama_usaha && s.nama_usaha.toLowerCase().includes(query)) ||
            (s.nama_pemilik && s.nama_pemilik.toLowerCase().includes(query)) ||
            (s.no_telp && s.no_telp.toLowerCase().includes(query)) ||
            (s.kabupaten && s.kabupaten.toLowerCase().includes(query)) ||
            (s.kecamatan && s.kecamatan.toLowerCase().includes(query)) ||
            (s.kelurahan && s.kelurahan.toLowerCase().includes(query)) ||
            (s.alamat_lengkap &&
                s.alamat_lengkap.toLowerCase().includes(query)) ||
            (Array.isArray(s.komoditas) &&
                s.komoditas.some((k) =>
                    String(k).toLowerCase().includes(query),
                ));

        const matchesJenis =
            selectedJenis.value === "all" ||
            s.jenis_supplier === selectedJenis.value;

        const matchesKomoditas =
            selectedKomoditasFilter.value === "all" ||
            (Array.isArray(s.komoditas) &&
                s.komoditas.includes(selectedKomoditasFilter.value));

        return matchesQuery && matchesJenis && matchesKomoditas;
    });
});

// ─── Wilayah Cascade State for Modal Form ─────────────────────────────────────
const provinces = ref([]);
const regencies = ref([]);
const districts = ref([]);
const villages = ref([]);

const isLoadingProvinces = ref(false);
const isLoadingRegencies = ref(false);
const isLoadingDistricts = ref(false);
const isLoadingVillages = ref(false);

const selectedProvinceCode = ref("");
const selectedRegencyCode = ref("");
const selectedDistrictCode = ref("");
const selectedVillageCode = ref("");

// Quick suggestions for komoditas
const komoditasSuggestions = [
    "Beras Premium",
    "Daging Ayam Segar",
    "Daging Sapi",
    "Telur Ayam Ras",
    "Sayuran Hijau",
    "Wortel & Kentang",
    "Buah-buahan Segar",
    "Ikan Segar",
    "Tempe & Tahu",
    "Susu Murni / UHT",
    "Bumbu Dapur",
    "Minyak Goreng",
    "Gas LPG",
    "Packaging / Kotak Makan",
];

// ─── Modal Form State (Tambah / Edit) ─────────────────────────────────────────
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const currentKomoditasInput = ref("");
const rawPhone = ref("");

const form = useForm({
    jenis_supplier: "",
    nama_usaha: "",
    nama_pemilik: "",
    no_telp: "",
    komoditas: [],
    provinsi: "",
    kabupaten: "",
    kecamatan: "",
    kelurahan: "",
    alamat_lengkap: "",
    kode_pos: "",
});

// Phone Handler
function handlePhoneInput(e) {
    let val = e.target.value.replace(/\D/g, "");
    if (val.startsWith("62")) {
        val = val.substring(2);
    }
    if (val.startsWith("0")) {
        val = val.substring(1);
    }
    rawPhone.value = val;
    form.no_telp = val ? `62${val}` : "";
}

// Inisialisasi Wilayah
onMounted(async () => {
    isLoadingProvinces.value = true;
    try {
        provinces.value = await getProvinces();
    } catch (e) {
        console.error("Gagal load provinsi:", e);
    } finally {
        isLoadingProvinces.value = false;
    }
});

// Wilayah Handlers
async function onProvinceChange(provName) {
    form.provinsi = provName;
    form.kabupaten = "";
    form.kecamatan = "";
    form.kelurahan = "";
    regencies.value = [];
    districts.value = [];
    villages.value = [];

    if (!provName) return;

    const found = provinces.value.find((p) => p.name === provName);
    if (found) {
        selectedProvinceCode.value = found.code;
        isLoadingRegencies.value = true;
        try {
            const rawReg = await getRegencies(found.code);
            // Bersihkan nama kabupaten / kota (hilangkan kata "Kabupaten" / "Kota")
            regencies.value = (rawReg || []).map((r) => ({
                ...r,
                displayName: cleanKabupatenName(r.name),
            }));
        } finally {
            isLoadingRegencies.value = false;
        }
    }
}

async function onRegencyChange(regCleanName) {
    form.kabupaten = regCleanName;
    form.kecamatan = "";
    form.kelurahan = "";
    districts.value = [];
    villages.value = [];

    if (!regCleanName) return;

    const found = regencies.value.find(
        (r) =>
            r.displayName === regCleanName ||
            cleanKabupatenName(r.name) === regCleanName ||
            r.name === regCleanName,
    );
    if (found) {
        selectedRegencyCode.value = found.code;
        isLoadingDistricts.value = true;
        try {
            districts.value = await getDistricts(found.code);
        } finally {
            isLoadingDistricts.value = false;
        }
    }
}

async function onDistrictChange(distName) {
    form.kecamatan = distName;
    form.kelurahan = "";
    villages.value = [];

    if (!distName) return;

    const found = districts.value.find((d) => d.name === distName);
    if (found) {
        selectedDistrictCode.value = found.code;
        isLoadingVillages.value = true;
        try {
            villages.value = await getVillages(found.code);
        } finally {
            isLoadingVillages.value = false;
        }
    }
}

function onVillageChange(vilName) {
    form.kelurahan = vilName;
}

// ─── Komoditas Tags Management ────────────────────────────────────────────────
function addKomoditas(item) {
    const val = (item || currentKomoditasInput.value).trim();
    if (val && !form.komoditas.includes(val)) {
        form.komoditas.push(val);
    }
    currentKomoditasInput.value = "";
}

function removeKomoditas(index) {
    form.komoditas.splice(index, 1);
}

// ─── Modal Openers ────────────────────────────────────────────────────────────
function openCreateModal() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    rawPhone.value = "";
    currentKomoditasInput.value = "";

    // Default semua field KOSONG 100%
    form.jenis_supplier = "";
    form.nama_usaha = "";
    form.nama_pemilik = "";
    form.no_telp = "";
    form.komoditas = [];
    form.provinsi = "";
    form.kabupaten = "";
    form.kecamatan = "";
    form.kelurahan = "";
    form.alamat_lengkap = "";
    form.kode_pos = "";

    selectedProvinceCode.value = "";
    selectedRegencyCode.value = "";
    selectedDistrictCode.value = "";
    selectedVillageCode.value = "";

    regencies.value = [];
    districts.value = [];
    villages.value = [];

    isModalOpen.value = true;
}

async function openEditModal(supplier) {
    isEditing.value = true;
    editingId.value = supplier.id;
    form.clearErrors();

    form.jenis_supplier = supplier.jenis_supplier || "KDMP";
    form.nama_usaha = supplier.nama_usaha || "";
    form.nama_pemilik = supplier.nama_pemilik || "";
    form.komoditas = Array.isArray(supplier.komoditas)
        ? [...supplier.komoditas]
        : [];
    form.provinsi = supplier.provinsi || "";
    form.kabupaten = cleanKabupatenName(supplier.kabupaten || "");
    form.kecamatan = supplier.kecamatan || "";
    form.kelurahan = supplier.kelurahan || "";
    form.alamat_lengkap = supplier.alamat_lengkap || "";
    form.kode_pos = supplier.kode_pos || "";

    // Format phone untuk display
    let p = String(supplier.no_telp || "").replace(/\D/g, "");
    if (p.startsWith("62")) p = p.substring(2);
    else if (p.startsWith("0")) p = p.substring(1);
    rawPhone.value = p;
    form.no_telp = p ? `62${p}` : "";

    // Load cascade wilayah jika ada data
    if (form.provinsi) {
        const prov = provinces.value.find((pr) => pr.name === form.provinsi);
        if (prov) {
            selectedProvinceCode.value = prov.code;
            isLoadingRegencies.value = true;
            try {
                const rawReg = await getRegencies(prov.code);
                regencies.value = (rawReg || []).map((r) => ({
                    ...r,
                    displayName: cleanKabupatenName(r.name),
                }));
            } finally {
                isLoadingRegencies.value = false;
            }

            if (form.kabupaten) {
                const reg = regencies.value.find(
                    (r) =>
                        r.displayName === form.kabupaten ||
                        cleanKabupatenName(r.name) === form.kabupaten,
                );
                if (reg) {
                    selectedRegencyCode.value = reg.code;
                    isLoadingDistricts.value = true;
                    try {
                        districts.value = await getDistricts(reg.code);
                    } finally {
                        isLoadingDistricts.value = false;
                    }

                    if (form.kecamatan) {
                        const dist = districts.value.find(
                            (d) => d.name === form.kecamatan,
                        );
                        if (dist) {
                            selectedDistrictCode.value = dist.code;
                            isLoadingVillages.value = true;
                            try {
                                villages.value = await getVillages(dist.code);
                            } finally {
                                isLoadingVillages.value = false;
                            }
                        }
                    }
                }
            }
        }
    }

    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
    rawPhone.value = "";
}

function submitForm() {
    form.no_telp = rawPhone.value ? `62${rawPhone.value}` : "";

    if (isEditing.value) {
        form.put(route("supplier.update", editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route("supplier.store"), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
}

// ─── Modal Delete Confirmation ───────────────────────────────────────────────
const isDeleteModalOpen = ref(false);
const supplierToDelete = ref(null);

function confirmDelete(supplier) {
    supplierToDelete.value = supplier;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    supplierToDelete.value = null;
}

function submitDelete() {
    if (!supplierToDelete.value) return;
    router.delete(route("supplier.destroy", supplierToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
    });
}
</script>

<template>
    <AppLayout
        title="Supplier Rekanan SPPG"
        subtitle="Kelola Data Vendor, Penyedia Bahan Baku & Operasional SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Supplier Rekanan SPPG" />

        <div class="space-y-6 pb-16">
            <!-- ─── FLASH ALERTS ────────────────────────────────────────────── -->
            <div
                v-if="flash.success"
                class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-sm animate-in fade-in"
            >
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <p class="text-sm font-medium">{{ flash.success }}</p>
            </div>
            <div
                v-if="flash.error"
                class="flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm animate-in fade-in"
            >
                <AlertCircle class="w-5 h-5 text-rose-600 shrink-0" />
                <p class="text-sm font-medium">{{ flash.error }}</p>
            </div>

            <!-- ─── HEADER & CTA ────────────────────────────────────────────── -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0 shadow-xs"
                    >
                        <Store class="w-6 h-6" />
                    </div>
                    <div>
                        <h1
                            class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2"
                        >
                            Daftar Supplier & Rekanan SPPG
                            <span
                                class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold border border-slate-200"
                            >
                                {{ suppliers.length }} Vendor
                            </span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Penyedia bahan baku pangan lokal (KDMP, Koperasi,
                            BUMDes, UMKM) & vendor operasional unit SPPG.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary/95 text-white text-sm font-semibold shadow-sm transition-all duration-150 cursor-pointer active:scale-98"
                    >
                        <Plus class="w-4 h-4" />
                        Tambah Supplier Baru
                    </button>
                </div>
            </div>

            <!-- ─── METRIC CARDS ────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Supplier -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Total Supplier</span
                        >
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100"
                        >
                            <Store class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-slate-900">
                            {{ summary.total_supplier || 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Rekanan terdaftar di sistem
                        </p>
                    </div>
                </div>

                <!-- Card 2: KDMP, Koperasi & BUMDes -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >KDMP, Koperasi & BUMDes</span
                        >
                        <div
                            class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100"
                        >
                            <Building2 class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-emerald-600">
                            {{ summary.total_kdmp_koperasi || 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Lembaga ekonomi desa & kolektif
                        </p>
                    </div>
                </div>

                <!-- Card 3: UMKM & Swasta -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Pelaku Usaha UMKM</span
                        >
                        <div
                            class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100"
                        >
                            <Briefcase class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-amber-600">
                            {{ summary.total_umkm || 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Usaha mikro & toko lokal
                        </p>
                    </div>
                </div>

                <!-- Card 4: Ragam Komoditas -->
                <div
                    class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Ragam Komoditas</span
                        >
                        <div
                            class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center border border-violet-100"
                        >
                            <Boxes class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-2xl font-black text-violet-600">
                            {{ summary.total_komoditas || 0 }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Jenis bahan baku dipasok
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── TOOLBAR & FILTERS ───────────────────────────────────────── -->
            <div
                class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col lg:flex-row gap-3 items-center justify-between"
            >
                <!-- Search Box -->
                <div class="relative w-full lg:w-96">
                    <Search
                        class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama toko, pemilik, no. telp, wilayah..."
                        class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Filter Dropdowns -->
                <div
                    class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto"
                >
                    <!-- Filter Jenis Supplier -->
                    <div class="flex items-center gap-1.5">
                        <span
                            class="text-xs font-semibold text-slate-500 hidden sm:inline"
                            >Jenis:</span
                        >
                        <select
                            v-model="selectedJenis"
                            class="text-xs sm:text-sm font-medium rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-700"
                        >
                            <option value="all">Semua Jenis</option>
                            <option
                                v-for="opt in jenisOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Komoditas -->
                    <div
                        v-if="allKomoditasList.length > 0"
                        class="flex items-center gap-1.5"
                    >
                        <span
                            class="text-xs font-semibold text-slate-500 hidden sm:inline"
                            >Komoditas:</span
                        >
                        <select
                            v-model="selectedKomoditasFilter"
                            class="text-xs sm:text-sm font-medium rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-700 max-w-[180px] truncate"
                        >
                            <option value="all">Semua Komoditas</option>
                            <option
                                v-for="k in allKomoditasList"
                                :key="k"
                                :value="k"
                            >
                                {{ k }}
                            </option>
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <button
                        v-if="
                            searchQuery ||
                            selectedJenis !== 'all' ||
                            selectedKomoditasFilter !== 'all'
                        "
                        @click="
                            searchQuery = '';
                            selectedJenis = 'all';
                            selectedKomoditasFilter = 'all';
                        "
                        class="text-xs font-semibold text-rose-600 hover:text-rose-700 px-2.5 py-1.5 rounded-lg hover:bg-rose-50 transition-colors"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- ─── SUPPLIERS TABLE ─────────────────────────────────────────── -->
            <div
                class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-slate-100 bg-slate-50/60 text-slate-600 text-xs font-bold uppercase tracking-wider"
                            >
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4 min-w-[200px]">
                                    Toko / Usaha & Pemilik
                                </th>
                                <th class="py-3.5 px-4 w-36">Jenis Supplier</th>
                                <th class="py-3.5 px-4 min-w-[220px]">
                                    Komoditas Bahan Baku
                                </th>
                                <th class="py-3.5 px-4 min-w-[220px]">
                                    Alamat & Wilayah
                                </th>
                                <th class="py-3.5 px-4 w-24 text-center">
                                    Jml PO
                                </th>
                                <th class="py-3.5 px-4 w-28 text-right">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 text-xs sm:text-sm"
                        >
                            <tr
                                v-for="(sup, idx) in filteredSuppliers"
                                :key="sup.id"
                                class="hover:bg-slate-50/70 transition-colors group"
                            >
                                <!-- No -->
                                <td
                                    class="py-4 px-4 text-center font-medium text-slate-400"
                                >
                                    {{ idx + 1 }}
                                </td>

                                <!-- Nama Toko / Usaha & Pemilik -->
                                <td class="py-4 px-4">
                                    <div
                                        class="font-bold text-slate-900 text-sm flex items-center gap-1.5"
                                    >
                                        <Store
                                            class="w-4 h-4 text-primary shrink-0"
                                        />
                                        <span>{{ sup.nama_usaha }}</span>
                                    </div>
                                    <div
                                        class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1"
                                    >
                                        <span
                                            v-if="sup.nama_pemilik"
                                            class="flex items-center gap-1"
                                        >
                                            <User
                                                class="w-3.5 h-3.5 text-slate-400"
                                            />
                                            {{ sup.nama_pemilik }}
                                        </span>
                                        <a
                                            v-if="sup.no_telp"
                                            :href="`https://wa.me/${sup.no_telp.replace(/[^0-9]/g, '')}`"
                                            target="_blank"
                                            class="flex items-center gap-1 text-primary hover:underline font-semibold"
                                            title="Hubungi via WhatsApp"
                                        >
                                            <Phone
                                                class="w-3.5 h-3.5 text-emerald-500"
                                            />
                                            +{{ sup.no_telp }}
                                        </a>
                                    </div>
                                </td>

                                <!-- Jenis Supplier Badge -->
                                <td class="py-4 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border',
                                            jenisColors[sup.jenis_supplier]
                                                ?.bg ||
                                                'bg-slate-50 text-slate-700 border-slate-200',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                jenisColors[sup.jenis_supplier]
                                                    ?.dot || 'bg-slate-400',
                                            ]"
                                        ></span>
                                        {{ sup.jenis_supplier }}
                                    </span>
                                </td>

                                <!-- Komoditas Bahan -->
                                <td class="py-4 px-4">
                                    <div
                                        v-if="
                                            Array.isArray(sup.komoditas) &&
                                            sup.komoditas.length > 0
                                        "
                                        class="flex flex-wrap gap-1.5 max-w-md"
                                    >
                                        <span
                                            v-for="(kom, kIdx) in sup.komoditas"
                                            :key="kIdx"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                        >
                                            {{ kom }}
                                        </span>
                                    </div>
                                    <span
                                        v-else
                                        class="text-xs text-slate-400 italic"
                                        >Belum ada komoditas</span
                                    >
                                </td>

                                <!-- Alamat & Wilayah -->
                                <td class="py-4 px-4">
                                    <div
                                        class="text-xs text-slate-700 leading-relaxed font-medium"
                                    >
                                        <span v-if="sup.alamat_lengkap"
                                            >{{ sup.alamat_lengkap }},
                                        </span>
                                        <span v-if="sup.kelurahan"
                                            >{{ sup.kelurahan }},
                                        </span>
                                        <span v-if="sup.kecamatan"
                                            >Kec. {{ sup.kecamatan }},
                                        </span>
                                        <span v-if="sup.kabupaten">{{
                                            cleanKabupatenName(sup.kabupaten)
                                        }}</span>
                                    </div>
                                    <div
                                        class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1"
                                    >
                                        <span v-if="sup.provinsi">{{
                                            sup.provinsi
                                        }}</span>
                                        <span v-if="sup.kode_pos">
                                            • Kode Pos: {{ sup.kode_pos }}</span
                                        >
                                    </div>
                                </td>

                                <!-- Jml PO -->
                                <td class="py-4 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700"
                                    >
                                        {{ sup.purchase_orders_count || 0 }} PO
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <button
                                            type="button"
                                            @click="openEditModal(sup)"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/10 transition-colors"
                                            title="Edit Supplier"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(sup)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus Supplier"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredSuppliers.length === 0">
                                <td colspan="7" class="py-12 px-4 text-center">
                                    <div
                                        class="max-w-sm mx-auto flex flex-col items-center"
                                    >
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3"
                                        >
                                            <Store class="w-6 h-6" />
                                        </div>
                                        <h3
                                            class="text-sm font-bold text-slate-800"
                                        >
                                            Tidak ada supplier ditemukan
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1">
                                            {{
                                                searchQuery ||
                                                selectedJenis !== "all"
                                                    ? "Coba ubah kata kunci pencarian atau filter Anda."
                                                    : "Belum ada data supplier rekanan. Tambahkan supplier baru sekarang."
                                            }}
                                        </p>
                                        <button
                                            v-if="
                                                !searchQuery &&
                                                selectedJenis === 'all'
                                            "
                                            type="button"
                                            @click="openCreateModal"
                                            class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-white text-xs font-semibold shadow-sm hover:bg-primary/95 transition-all"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                            Tambah Supplier Pertama
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── MODAL FORM TAMBAH / EDIT SUPPLIER ───────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
            >
                <div
                    class="bg-white rounded-2xl shadow-xl border border-slate-100 w-full max-w-3xl overflow-hidden my-8 max-h-[90vh] flex flex-col animate-in zoom-in-95 duration-150"
                >
                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60 shrink-0"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold"
                            >
                                <Store class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    {{
                                        isEditing
                                            ? "Edit Data Supplier Rekanan"
                                            : "Tambah Supplier Rekanan Baru"
                                    }}
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Lengkapi profil toko, penyedia bahan baku
                                    pangan lokal, atau vendor operasional SPPG.
                                </p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            type="button"
                            class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div
                        class="p-6 overflow-y-auto space-y-6 text-xs sm:text-sm"
                    >
                        <!-- SECTION 1: INFORMASI USAHA & KONTAK -->
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 pb-1 border-b border-slate-100"
                            >
                                <Briefcase class="w-4 h-4 text-primary" />
                                <span>1. Profil Toko & Usaha Supplier</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Jenis Supplier -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        Jenis Supplier
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.jenis_supplier"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-xs sm:text-sm font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                    >
                                        <option value="" disabled>
                                            -- Pilih Jenis Supplier --
                                        </option>
                                        <option value="KDMP">
                                            KDMP (Kelompok Dapur Mandiri Pangan)
                                        </option>
                                        <option value="Koperasi">
                                            Koperasi
                                        </option>
                                        <option value="Bumdes">
                                            BUMDes (Badan Usaha Milik Desa)
                                        </option>
                                        <option value="Bumdesma">
                                            BUMDesma (BUMDes Bersama)
                                        </option>
                                        <option value="UMKM">
                                            UMKM (Usaha Mikro, Kecil, Menengah)
                                        </option>
                                        <option value="UD">
                                            UD (Usaha Dagang)
                                        </option>
                                        <option value="CV">
                                            CV (Commanditaire Vennootschap)
                                        </option>
                                        <option value="PT">
                                            PT (Perseroan Terbatas)
                                        </option>
                                        <option value="Lainnya">
                                            Lainnya / Vendor Swasta
                                        </option>
                                    </select>
                                    <p
                                        v-if="form.errors.jenis_supplier"
                                        class="text-[11px] text-rose-600"
                                    >
                                        {{ form.errors.jenis_supplier }}
                                    </p>
                                </div>

                                <!-- Nama Dagang Usaha / Toko -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        Nama Dagang Toko / Usaha
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nama_usaha"
                                        type="text"
                                        placeholder="Contoh: Toko Tani Berkah / KDMP Sukamaju"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                    />
                                    <p
                                        v-if="form.errors.nama_usaha"
                                        class="text-[11px] text-rose-600"
                                    >
                                        {{ form.errors.nama_usaha }}
                                    </p>
                                </div>

                                <!-- Nama Pemilik Usaha -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        Nama Pemilik Usaha / Penanggung Jawab
                                    </label>
                                    <input
                                        v-model="form.nama_pemilik"
                                        type="text"
                                        placeholder="Contoh: H. Ahmad Subarjo"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                    />
                                    <p
                                        v-if="form.errors.nama_pemilik"
                                        class="text-[11px] text-rose-600"
                                    >
                                        {{ form.errors.nama_pemilik }}
                                    </p>
                                </div>

                                <!-- No. Telepon / WhatsApp dengan Panduan +62 -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                    >
                                        No. Telp / WhatsApp
                                    </label>
                                    <div
                                        class="flex items-stretch rounded-xl overflow-hidden border border-slate-200 bg-slate-50/50 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary transition-all"
                                    >
                                        <div
                                            class="flex items-center justify-center px-3.5 bg-slate-100/80 border-r border-slate-200 text-slate-700 font-bold text-xs select-none tracking-wide shrink-0"
                                        >
                                            +62
                                        </div>
                                        <input
                                            :value="rawPhone"
                                            @input="handlePhoneInput"
                                            type="tel"
                                            placeholder="85738291029 (tanpa 0 atau +62)"
                                            class="w-full min-w-0 bg-transparent py-2.5 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:outline-none placeholder:text-slate-400"
                                        />
                                    </div>
                                    <p
                                        class="text-[10.5px] text-slate-400 mt-0.5"
                                    >
                                        Mulai langsung dengan angka 8...
                                        Disimpan di sistem sebagai
                                        <span class="font-mono text-slate-600"
                                            >62{{ rawPhone || "8..." }}</span
                                        >
                                    </p>
                                    <p
                                        v-if="form.errors.no_telp"
                                        class="text-[11px] text-rose-600"
                                    >
                                        {{ form.errors.no_telp }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: KOMODITAS BAHAN YANG DISEDIAKAN -->
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 pb-1 border-b border-slate-100"
                            >
                                <Boxes class="w-4 h-4 text-primary" />
                                <span
                                    >2. Komoditas Bahan Yang Disediakan Supplier
                                    (Bisa Lebih Dari 1)</span
                                >
                            </div>

                            <!-- Input komoditas custom -->
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <input
                                        v-model="currentKomoditasInput"
                                        @keydown.enter.prevent="addKomoditas()"
                                        type="text"
                                        placeholder="Ketik komoditas (misal: Beras Premium, Daging Ayam, Sayuran) lalu tekan Enter / Tambah"
                                        class="flex-1 rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                    />
                                    <button
                                        type="button"
                                        @click="addKomoditas()"
                                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-colors cursor-pointer shrink-0"
                                    >
                                        + Tambah
                                    </button>
                                </div>

                                <!-- List Komoditas Terpilih -->
                                <div
                                    v-if="form.komoditas.length > 0"
                                    class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 flex flex-wrap gap-2"
                                >
                                    <span
                                        v-for="(item, idx) in form.komoditas"
                                        :key="idx"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 animate-in fade-in"
                                    >
                                        {{ item }}
                                        <button
                                            type="button"
                                            @click="removeKomoditas(idx)"
                                            class="hover:text-rose-600 transition-colors p-0.5 rounded-full cursor-pointer"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </span>
                                </div>

                                <!-- Quick Suggestions -->
                                <div>
                                    <span
                                        class="text-[11px] font-semibold text-slate-400 block mb-1.5"
                                        >Saran Cepat:</span
                                    >
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="sug in komoditasSuggestions"
                                            :key="sug"
                                            type="button"
                                            @click="addKomoditas(sug)"
                                            :disabled="
                                                form.komoditas.includes(sug)
                                            "
                                            :class="[
                                                'text-[11px] px-2 py-0.5 rounded-md border font-medium transition-colors cursor-pointer',
                                                form.komoditas.includes(sug)
                                                    ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                                                    : 'bg-white hover:bg-primary/10 hover:text-primary hover:border-primary/30 text-slate-600 border-slate-200',
                                            ]"
                                        >
                                            + {{ sug }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: WILAYAH ADMINISTRASI & ALAMAT -->
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700 pb-1 border-b border-slate-100"
                            >
                                <MapPin class="w-4 h-4 text-primary" />
                                <span
                                    >3. Wilayah Administrasi & Alamat
                                    Lengkap</span
                                >
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Provinsi -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                        >Provinsi</label
                                    >
                                    <div class="relative">
                                        <select
                                            :value="form.provinsi"
                                            @change="
                                                onProvinceChange(
                                                    $event.target.value,
                                                )
                                            "
                                            :disabled="isLoadingProvinces"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 pr-8 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-slate-100"
                                        >
                                            <option value="">
                                                -- Pilih Provinsi --
                                            </option>
                                            <option
                                                v-for="prov in provinces"
                                                :key="prov.code"
                                                :value="prov.name"
                                            >
                                                {{ prov.name }}
                                            </option>
                                        </select>
                                        <Loader2
                                            v-if="isLoadingProvinces"
                                            class="w-4 h-4 text-slate-400 animate-spin absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                        />
                                    </div>
                                </div>

                                <!-- Kabupaten / Kota -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                        >Kabupaten / Kota</label
                                    >
                                    <div class="relative">
                                        <select
                                            :value="form.kabupaten"
                                            @change="
                                                onRegencyChange(
                                                    $event.target.value,
                                                )
                                            "
                                            :disabled="
                                                isLoadingRegencies ||
                                                !form.provinsi
                                            "
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 pr-8 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-slate-100"
                                        >
                                            <option value="">
                                                -- Pilih Kab/Kota --
                                            </option>
                                            <option
                                                v-for="reg in regencies"
                                                :key="reg.code"
                                                :value="reg.displayName"
                                            >
                                                {{ reg.displayName }}
                                            </option>
                                        </select>
                                        <Loader2
                                            v-if="isLoadingRegencies"
                                            class="w-4 h-4 text-slate-400 animate-spin absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                        />
                                    </div>
                                </div>

                                <!-- Kecamatan -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                        >Kecamatan</label
                                    >
                                    <div class="relative">
                                        <select
                                            :value="form.kecamatan"
                                            @change="
                                                onDistrictChange(
                                                    $event.target.value,
                                                )
                                            "
                                            :disabled="
                                                isLoadingDistricts ||
                                                !form.kabupaten
                                            "
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 pr-8 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-slate-100"
                                        >
                                            <option value="">
                                                -- Pilih Kecamatan --
                                            </option>
                                            <option
                                                v-for="dist in districts"
                                                :key="dist.code"
                                                :value="dist.name"
                                            >
                                                {{ dist.name }}
                                            </option>
                                        </select>
                                        <Loader2
                                            v-if="isLoadingDistricts"
                                            class="w-4 h-4 text-slate-400 animate-spin absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                        />
                                    </div>
                                </div>

                                <!-- Kelurahan / Desa -->
                                <div class="space-y-1">
                                    <label
                                        class="block text-xs font-bold text-slate-700"
                                        >Kelurahan / Desa</label
                                    >
                                    <div class="relative">
                                        <select
                                            :value="form.kelurahan"
                                            @change="
                                                onVillageChange(
                                                    $event.target.value,
                                                )
                                            "
                                            :disabled="
                                                isLoadingVillages ||
                                                !form.kecamatan
                                            "
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 pr-8 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:bg-slate-100"
                                        >
                                            <option value="">
                                                -- Pilih Kelurahan/Desa --
                                            </option>
                                            <option
                                                v-for="vil in villages"
                                                :key="vil.code"
                                                :value="vil.name"
                                            >
                                                {{ vil.name }}
                                            </option>
                                        </select>
                                        <Loader2
                                            v-if="isLoadingVillages"
                                            class="w-4 h-4 text-slate-400 animate-spin absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Alamat Lengkap -->
                            <div class="space-y-1">
                                <label
                                    class="block text-xs font-bold text-slate-700"
                                    >Alamat Lengkap / Jalan / RT RW</label
                                >
                                <textarea
                                    v-model="form.alamat_lengkap"
                                    rows="2"
                                    placeholder="Contoh: Jl. Diponegoro No. 12, RT 02 / RW 04"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                ></textarea>
                            </div>

                            <!-- Kode Pos -->
                            <div class="space-y-1">
                                <label
                                    class="block text-xs font-bold text-slate-700"
                                    >Kode Pos</label
                                >
                                <input
                                    v-model="form.kode_pos"
                                    type="text"
                                    placeholder="Contoh: 81119"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-xs sm:text-sm font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3 shrink-0"
                    >
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary/95 text-white text-xs sm:text-sm font-bold shadow-sm transition-all cursor-pointer disabled:opacity-50"
                        >
                            <Check class="w-4 h-4" />
                            {{
                                form.processing
                                    ? "Menyimpan..."
                                    : isEditing
                                      ? "Simpan Perubahan"
                                      : "Tambah Supplier"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── MODAL CONFIRM DELETE ────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="isDeleteModalOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
            >
                <div
                    class="bg-white rounded-2xl shadow-xl border border-slate-100 w-full max-w-md p-6 animate-in zoom-in-95 duration-150"
                >
                    <div
                        class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 border border-rose-100"
                    >
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900">
                        Hapus Data Supplier Rekanan?
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2">
                        Apakah Anda yakin ingin menghapus data supplier
                        <span class="font-bold text-slate-800"
                            >"{{ supplierToDelete?.nama_usaha }}"</span
                        >? Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs sm:text-sm font-semibold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDelete"
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs sm:text-sm font-bold shadow-sm transition-colors cursor-pointer"
                        >
                            Ya, Hapus Data
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
