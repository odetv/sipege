<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Label from "@/Components/ui/Label.vue";
import MapPicker from "@/Components/MapPicker.vue";
import {
    Users,
    Building2,
    School,
    User,
    Mail,
    Phone,
    MapPin,
    ArrowLeft,
    Save,
    Plus,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Info,
    Layers,
    Sparkles,
    Shield,
    RotateCcw,
    Loader2,
    Utensils,
} from "lucide-vue-next";
import {
    KATEGORI_OPTIONS,
    JENIS_KEPEMILIKAN_OPTIONS,
    TIPE_IDENTITAS_OPTIONS,
    JENIS_PORSI_OPTIONS,
    getSubKategoriByKategori,
    getJenisPorsiBySubKategori,
    sortRincianByKategori,
} from "@/Services/penerimaManfaatConfig";
import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
    formatWilayahName,
    formatKabupatenName,
} from "@/Services/wilayah";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
});

// View default ke area Pulau Bali tanpa pin
const defaultCenter = computed(() => {
    return [-8.409518, 115.188916];
});

// Form state - SEMUA FIELD KOSONG SECARA DEFAULT
const form = useForm({
    nama_kelompok: "",
    kategori: "",
    jenis_kepemilikan: "",
    tipe_identitas: "",
    kode_identitas: "",
    nama_kepala: "",
    email_kepala: "",
    telepon_kepala: "",
    nama_pic: "",
    email_pic: "",
    telepon_pic: "",
    provinsi: "",
    kabupaten: "",
    kecamatan: "",
    desa_kelurahan: "",
    kode_pos: "",
    alamat_lengkap: "",
    latitude: null,
    longitude: null,
    rincian: [],
});

// Region dropdowns state & Loading states
const provinceList = ref([]);
const regencyList = ref([]);
const districtList = ref([]);
const villageList = ref([]);

const isProvincesLoading = ref(false);
const isRegenciesLoading = ref(false);
const isDistrictsLoading = ref(false);
const isVillagesLoading = ref(false);

const selectedProvinceCode = ref("");
const selectedRegencyCode = ref("");
const selectedDistrictCode = ref("");
const selectedVillageCode = ref("");

// Helper phone inputs (tanpa 62)
const rawTeleponKepala = ref("");
const rawTeleponPIC = ref("");

function cleanPhone(newVal) {
    let cleaned = (newVal || "").toString().replace(/\D/g, "");
    if (cleaned.startsWith("0")) {
        cleaned = cleaned.substring(1);
    }
    if (cleaned.startsWith("62")) {
        cleaned = cleaned.substring(2);
    }
    return cleaned;
}

// Client-side Errors State
const clientErrors = ref({});

function getFieldError(fieldName) {
    return form.errors[fieldName] || clientErrors.value[fieldName] || "";
}

function clearFieldError(fieldName) {
    if (clientErrors.value[fieldName]) {
        delete clientErrors.value[fieldName];
    }
    if (form.errors[fieldName]) {
        form.clearErrors(fieldName);
    }
}

watch(rawTeleponKepala, (newVal) => {
    const cleaned = cleanPhone(newVal);
    rawTeleponKepala.value = cleaned;
    form.telepon_kepala = cleaned ? `62${cleaned}` : "";
    clearFieldError("telepon_kepala");
});

watch(rawTeleponPIC, (newVal) => {
    const cleaned = cleanPhone(newVal);
    rawTeleponPIC.value = cleaned;
    form.telepon_pic = cleaned ? `62${cleaned}` : "";
    clearFieldError("telepon_pic");
});

watch([() => form.latitude, () => form.longitude], ([lat, lng]) => {
    if (
        lat !== null &&
        lng !== null &&
        !isNaN(Number(lat)) &&
        !isNaN(Number(lng))
    ) {
        clearFieldError("latitude");
        clearFieldError("longitude");
    }
});

// Helper untuk membersihkan kata "Kabupaten / Kota" agar langsung menampilkan namanya
function cleanKabupatenName(name) {
    if (!name) return "";
    return name.replace(/^Kabupaten\s+/i, "").replace(/^Kota\s+/i, "");
}

// Subkategori list berdasarkan kategori yang dipilih
const currentCategorySubOptions = computed(() => {
    if (!form.kategori) return [];
    return getSubKategoriByKategori(form.kategori);
});

// Dapatkan opsi subkategori yang valid untuk baris tertentu (menghindari duplikasi)
function getAvailableSubKategoriForRow(currentRowIdx) {
    const allOptions = currentCategorySubOptions.value;
    const currentVal = form.rincian[currentRowIdx]?.sub_kategori;
    const selectedOthers = form.rincian
        .filter((_, idx) => idx !== currentRowIdx)
        .map((r) => r.sub_kategori)
        .filter(Boolean);

    return allOptions.filter(
        (opt) => opt === currentVal || !selectedOthers.includes(opt),
    );
}

// Setup subcategories saat user memilih kategori
function generateDefaultRincian(kategori) {
    if (!kategori) return [];
    const subList = getSubKategoriByKategori(kategori);
    return subList.map((sub) => ({
        sub_kategori: sub,
        jenis_porsi: getJenisPorsiBySubKategori(sub, kategori),
        jumlah_laki_laki: 0,
        jumlah_perempuan: 0,
    }));
}

watch(
    () => form.kategori,
    (newKategori) => {
        clearFieldError("kategori");
        if (newKategori) {
            form.rincian = generateDefaultRincian(newKategori);
        } else {
            form.rincian = [];
        }
    },
);

function onSubKategoriChange(item) {
    if (item.sub_kategori) {
        item.jenis_porsi = getJenisPorsiBySubKategori(
            item.sub_kategori,
            form.kategori,
        );
        form.rincian = sortRincianByKategori(form.rincian, form.kategori);
        clearFieldError("rincian");
    }
}

const isAllSubkategoriAdded = computed(() => {
    if (!form.kategori || currentCategorySubOptions.value.length === 0)
        return true;
    const selected = form.rincian.map((r) => r.sub_kategori).filter(Boolean);
    return currentCategorySubOptions.value.every((opt) =>
        selected.includes(opt),
    );
});

// Tambah baris sub kategori (ambil opsi pertama yang belum terpilih)
function addCustomSubkategori() {
    if (isAllSubkategoriAdded.value) return;
    const allOptions = currentCategorySubOptions.value;
    const selected = form.rincian.map((r) => r.sub_kategori);
    const firstUnused = allOptions.find((opt) => !selected.includes(opt)) || "";

    form.rincian.push({
        sub_kategori: firstUnused,
        jenis_porsi: getJenisPorsiBySubKategori(firstUnused, form.kategori),
        jumlah_laki_laki: 0,
        jumlah_perempuan: 0,
    });

    form.rincian = sortRincianByKategori(form.rincian, form.kategori);
    clearFieldError("rincian");
}

function removeSubkategori(index) {
    form.rincian.splice(index, 1);
}

function resetRincianToDefault() {
    if (form.kategori) {
        form.rincian = generateDefaultRincian(form.kategori);
        clearFieldError("rincian");
    } else {
        form.rincian = [];
    }
}

// Grand totals computed
const totalLakiLaki = computed(() => {
    return form.rincian.reduce(
        (sum, item) => sum + (Number(item.jumlah_laki_laki) || 0),
        0,
    );
});

const totalPerempuan = computed(() => {
    return form.rincian.reduce(
        (sum, item) => sum + (Number(item.jumlah_perempuan) || 0),
        0,
    );
});

const totalPorsiKecil = computed(() => {
    return form.rincian
        .filter(
            (item) =>
                (item.jenis_porsi ||
                    getJenisPorsiBySubKategori(
                        item.sub_kategori,
                        form.kategori,
                    )) === "Porsi Kecil",
        )
        .reduce(
            (sum, item) =>
                sum +
                (Number(item.jumlah_laki_laki) || 0) +
                (Number(item.jumlah_perempuan) || 0),
            0,
        );
});

const totalPorsiBesar = computed(() => {
    return form.rincian
        .filter(
            (item) =>
                (item.jenis_porsi ||
                    getJenisPorsiBySubKategori(
                        item.sub_kategori,
                        form.kategori,
                    )) === "Porsi Besar",
        )
        .reduce(
            (sum, item) =>
                sum +
                (Number(item.jumlah_laki_laki) || 0) +
                (Number(item.jumlah_perempuan) || 0),
            0,
        );
});

const grandTotal = computed(() => totalLakiLaki.value + totalPerempuan.value);

// Inisialisasi Wilayah
onMounted(async () => {
    isProvincesLoading.value = true;
    try {
        provinceList.value = await getProvinces();
    } catch (e) {
        console.error("Gagal memuat provinsi:", e);
    } finally {
        isProvincesLoading.value = false;
    }
});

async function onProvinceChange() {
    clearFieldError("provinsi");
    const prov = provinceList.value.find(
        (p) => p.code === selectedProvinceCode.value,
    );
    form.provinsi = prov ? prov.name : "";

    form.kabupaten = "";
    form.kecamatan = "";
    form.desa_kelurahan = "";
    form.kode_pos = "";
    selectedRegencyCode.value = "";
    selectedDistrictCode.value = "";
    selectedVillageCode.value = "";
    regencyList.value = [];
    districtList.value = [];
    villageList.value = [];

    if (selectedProvinceCode.value) {
        isRegenciesLoading.value = true;
        try {
            regencyList.value = await getRegencies(selectedProvinceCode.value);
        } catch (e) {
            console.error("Gagal memuat kabupaten:", e);
        } finally {
            isRegenciesLoading.value = false;
        }
    }
}

async function onRegencyChange() {
    clearFieldError("kabupaten");
    const reg = regencyList.value.find(
        (r) => r.code === selectedRegencyCode.value,
    );
    form.kabupaten = reg ? reg.name : "";

    form.kecamatan = "";
    form.desa_kelurahan = "";
    form.kode_pos = "";
    selectedDistrictCode.value = "";
    selectedVillageCode.value = "";
    districtList.value = [];
    villageList.value = [];

    if (selectedRegencyCode.value) {
        isDistrictsLoading.value = true;
        try {
            districtList.value = await getDistricts(selectedRegencyCode.value);
        } catch (e) {
            console.error("Gagal memuat kecamatan:", e);
        } finally {
            isDistrictsLoading.value = false;
        }
    }
}

async function onDistrictChange() {
    clearFieldError("kecamatan");
    const dist = districtList.value.find(
        (d) => d.code === selectedDistrictCode.value,
    );
    form.kecamatan = dist ? dist.name : "";

    form.desa_kelurahan = "";
    form.kode_pos = "";
    selectedVillageCode.value = "";
    villageList.value = [];

    if (selectedDistrictCode.value) {
        isVillagesLoading.value = true;
        try {
            villageList.value = await getVillages(selectedDistrictCode.value);
        } catch (e) {
            console.error("Gagal memuat desa:", e);
        } finally {
            isVillagesLoading.value = false;
        }
    }
}

function onVillageChange() {
    clearFieldError("desa_kelurahan");
    const vil = villageList.value.find(
        (v) => v.code === selectedVillageCode.value,
    );
    form.desa_kelurahan = vil ? vil.name : "";
    if (vil?.postal_code) {
        form.kode_pos = vil.postal_code;
        clearFieldError("kode_pos");
    }
}

// Client-side Validation Functions
function validateForm() {
    clientErrors.value = {};
    const errs = {};

    // 1. Identitas & Legalitas
    if (!form.nama_kelompok || !form.nama_kelompok.trim()) {
        errs.nama_kelompok = "Nama kelompok penerima manfaat wajib diisi.";
    } else if (form.nama_kelompok.trim().length < 3) {
        errs.nama_kelompok = "Nama kelompok minimal harus 3 karakter.";
    }

    if (!form.kategori) {
        errs.kategori = "Pilih kategori jenjang/lembaga.";
    }

    if (!form.jenis_kepemilikan) {
        errs.jenis_kepemilikan = "Pilih jenis kepemilikan.";
    }

    if (!form.tipe_identitas) {
        errs.tipe_identitas = "Pilih tipe nomor identitas.";
    }

    if (!form.kode_identitas || !form.kode_identitas.trim()) {
        errs.kode_identitas = "Nomor/kode identitas wajib diisi.";
    }

    // 2. Kontak Kepala Satuan
    if (!form.nama_kepala || !form.nama_kepala.trim()) {
        errs.nama_kepala = "Nama Kepala Satuan wajib diisi.";
    }

    if (!form.email_kepala || !form.email_kepala.trim()) {
        errs.email_kepala = "Email Kepala Satuan wajib diisi.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_kepala.trim())) {
        errs.email_kepala = "Format email Kepala Satuan tidak valid.";
    }

    if (!form.telepon_kepala) {
        errs.telepon_kepala = "Nomor Telp Kepala Satuan wajib diisi.";
    } else if (!/^62[0-9]{8,15}$/.test(form.telepon_kepala)) {
        errs.telepon_kepala =
            "Format nomor telepon tidak valid (contoh: +62 81234567890).";
    }

    // 3. Kontak PIC
    if (!form.nama_pic || !form.nama_pic.trim()) {
        errs.nama_pic = "Nama lengkap PIC wajib diisi.";
    }

    if (!form.email_pic || !form.email_pic.trim()) {
        errs.email_pic = "Email PIC wajib diisi.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_pic.trim())) {
        errs.email_pic = "Format email PIC tidak valid.";
    }

    if (!form.telepon_pic) {
        errs.telepon_pic = "Nomor Telp PIC wajib diisi.";
    } else if (!/^62[0-9]{8,15}$/.test(form.telepon_pic)) {
        errs.telepon_pic =
            "Format nomor telepon tidak valid (contoh: +62 81234567890).";
    }

    // 4. Wilayah & Alamat
    if (!form.provinsi) {
        errs.provinsi = "Pilih provinsi lokasi kelompok.";
    }
    if (!form.kabupaten) {
        errs.kabupaten = "Pilih kabupaten/kota lokasi kelompok.";
    }
    if (!form.kecamatan) {
        errs.kecamatan = "Pilih kecamatan lokasi kelompok.";
    }
    if (!form.desa_kelurahan) {
        errs.desa_kelurahan = "Pilih desa/kelurahan lokasi kelompok.";
    }

    if (!form.kode_pos || !form.kode_pos.toString().trim()) {
        errs.kode_pos = "Kode pos wajib diisi.";
    } else if (!/^[0-9]{5}$/.test(form.kode_pos.toString().trim())) {
        errs.kode_pos = "Kode pos harus berupa tepat 5 digit angka.";
    }

    if (!form.alamat_lengkap || !form.alamat_lengkap.trim()) {
        errs.alamat_lengkap = "Alamat lengkap kelompok wajib diisi.";
    }

    // 5. Titik Koordinat Peta
    if (
        form.latitude === null ||
        form.longitude === null ||
        isNaN(Number(form.latitude)) ||
        isNaN(Number(form.longitude))
    ) {
        errs.latitude = "Titik koordinat pada peta wajib ditentukan.";
        errs.longitude = "Titik koordinat pada peta wajib ditentukan.";
    }

    // 6. Rincian Penerima Manfaat
    if (!Array.isArray(form.rincian) || form.rincian.length === 0) {
        errs.rincian =
            "Rincian jumlah penerima manfaat wajib diisi minimal 1 subkategori.";
    } else {
        const hasInvalidSub = form.rincian.some((r) => !r.sub_kategori);
        if (hasInvalidSub) {
            errs.rincian =
                "Semua baris rincian harus memiliki subkategori yang valid.";
        }
    }

    clientErrors.value = errs;
    return Object.keys(errs).length === 0;
}

// Submit Form
function submitForm() {
    form.clearErrors();
    const isValid = validateForm();
    if (!isValid) {
        const firstErrorKey = Object.keys(clientErrors.value)[0];
        if (firstErrorKey) {
            const el = document.getElementById(firstErrorKey);
            if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
                el.focus();
            }
        }
        return;
    }

    form.rincian = sortRincianByKategori(form.rincian, form.kategori);

    form.post(route("penerima-manfaat.store"), {
        preserveScroll: true,
        onError: (errors) => {
            console.error("Validation error:", errors);
            const firstKey = Object.keys(errors)[0];
            if (firstKey) {
                const el = document.getElementById(firstKey);
                if (el) {
                    el.scrollIntoView({ behavior: "smooth", block: "center" });
                    el.focus();
                }
            }
        },
    });
}
</script>

<template>
    <AppLayout
        title="Tambah Kelompok Penerima Manfaat"
        subtitle="Formulir Pendaftaran Kelompok Penerima Manfaat"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Tambah Kelompok Penerima Manfaat" />

        <div class="max-w-6xl mx-auto space-y-6 pb-12">
            <!-- Top Navigation Bar -->
            <div class="flex items-center justify-between gap-4">
                <Link
                    :href="route('penerima-manfaat.index')"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-primary transition-colors cursor-pointer"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Kembali ke Daftar Kelompok</span>
                </Link>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="form.reset()"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-1.5 h-11 px-5 min-w-[120px] text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition-colors cursor-pointer shrink-0 whitespace-nowrap"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Reset Form</span>
                    </button>
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 h-11 px-6 min-w-[160px] text-xs font-bold rounded-lg bg-primary hover:bg-primary/90 text-white shadow-sm transition-all cursor-pointer disabled:opacity-50 shrink-0 whitespace-nowrap"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Data Kelompok"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Form Container -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- 1. IDENTITAS & LEGALITAS KELOMPOK -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="border-b border-slate-100 p-5 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                            >
                                <School class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle
                                    className="text-base font-bold text-slate-900"
                                >
                                    1. Identitas Kelompok
                                </CardTitle>
                                <CardDescription
                                    className="text-xs text-slate-500 mt-0.5"
                                >
                                    Informasi nama lembaga, jenjang kategori,
                                    status kepemilikan, dan informasi legalitas
                                    lainnya.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nama Kelompok -->
                            <div class="space-y-1.5 md:col-span-2">
                                <Label
                                    for="nama_kelompok"
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Nama Kelompok Penerima Manfaat
                                    <span class="text-rose-500">*</span>
                                </Label>
                                <input
                                    id="nama_kelompok"
                                    v-model="form.nama_kelompok"
                                    @input="clearFieldError('nama_kelompok')"
                                    type="text"
                                    placeholder="Contoh: SD Negeri 1 Singaraja / Posyandu Melati Indah"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                            getFieldError('nama_kelompok'),
                                    }"
                                    required
                                />
                                <p
                                    v-if="getFieldError('nama_kelompok')"
                                    class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        getFieldError("nama_kelompok")
                                    }}</span>
                                </p>
                            </div>

                            <!-- Kategori -->
                            <div class="space-y-1.5">
                                <Label
                                    for="kategori"
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Kategori Jenjang / Satuan
                                    <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="kategori"
                                    v-model="form.kategori"
                                    @change="clearFieldError('kategori')"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                            getFieldError('kategori'),
                                    }"
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih Kategori Jenjang...
                                    </option>
                                    <option
                                        v-for="kat in KATEGORI_OPTIONS"
                                        :key="kat.value"
                                        :value="kat.value"
                                    >
                                        {{ kat.value }}
                                    </option>
                                </select>
                                <p
                                    v-if="getFieldError('kategori')"
                                    class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{ getFieldError("kategori") }}</span>
                                </p>
                            </div>

                            <!-- Jenis Kepemilikan -->
                            <div class="space-y-1.5">
                                <Label
                                    for="jenis_kepemilikan"
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Jenis Kepemilikan
                                    <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="jenis_kepemilikan"
                                    v-model="form.jenis_kepemilikan"
                                    @change="
                                        clearFieldError('jenis_kepemilikan')
                                    "
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                            getFieldError('jenis_kepemilikan'),
                                    }"
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih Jenis Kepemilikan...
                                    </option>
                                    <option
                                        v-for="j in JENIS_KEPEMILIKAN_OPTIONS"
                                        :key="j.value"
                                        :value="j.value"
                                    >
                                        {{ j.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="getFieldError('jenis_kepemilikan')"
                                    class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        getFieldError("jenis_kepemilikan")
                                    }}</span>
                                </p>
                            </div>

                            <!-- Tipe Identitas -->
                            <div class="space-y-1.5">
                                <Label
                                    for="tipe_identitas"
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Tipe Nomor Identitas
                                    <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="tipe_identitas"
                                    v-model="form.tipe_identitas"
                                    @change="clearFieldError('tipe_identitas')"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                            getFieldError('tipe_identitas'),
                                    }"
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih Tipe Identitas...
                                    </option>
                                    <option
                                        v-for="t in TIPE_IDENTITAS_OPTIONS"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.value }}
                                    </option>
                                </select>
                                <p
                                    v-if="getFieldError('tipe_identitas')"
                                    class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        getFieldError("tipe_identitas")
                                    }}</span>
                                </p>
                            </div>

                            <!-- Kode Identitas -->
                            <div class="space-y-1.5">
                                <Label
                                    for="kode_identitas"
                                    class="text-xs font-semibold text-slate-700"
                                >
                                    Kode / Nomor Identitas ({{
                                        form.tipe_identitas || "-"
                                    }}) <span class="text-rose-500">*</span>
                                </Label>
                                <input
                                    id="kode_identitas"
                                    v-model="form.kode_identitas"
                                    @input="clearFieldError('kode_identitas')"
                                    type="text"
                                    placeholder="Contoh: 50101234 (Nomor NPSN / NSPP / NSM / Izin)"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                            getFieldError('kode_identitas'),
                                    }"
                                    required
                                />
                                <p
                                    v-if="getFieldError('kode_identitas')"
                                    class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    <span>{{
                                        getFieldError("kode_identitas")
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. KONTAK PENANGGUNG JAWAB (KS & PIC) -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="border-b border-slate-100 p-5 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="h-8 w-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"
                            >
                                <User class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle
                                    className="text-base font-bold text-slate-900"
                                >
                                    2. Kontak Satuan
                                </CardTitle>
                                <CardDescription
                                    className="text-xs text-slate-500 mt-0.5"
                                >
                                    Data narahubung resmi Kepala Satuan dan PIC.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-6">
                        <!-- KS Section -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                >
                                    A. Data Kepala / Pimpinan Satuan
                                </span>
                                <div
                                    class="flex-1 border-t border-slate-200"
                                ></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <Label
                                        for="nama_kepala"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Nama Kepala Satuan
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="nama_kepala"
                                        v-model="form.nama_kepala"
                                        @input="clearFieldError('nama_kepala')"
                                        type="text"
                                        placeholder="Nama Lengkap Kepala Satuan"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{
                                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                getFieldError('nama_kepala'),
                                        }"
                                        required
                                    />
                                    <p
                                        v-if="getFieldError('nama_kepala')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("nama_kepala")
                                        }}</span>
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="email_kepala"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Email Kepala Satuan
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="email_kepala"
                                        v-model="form.email_kepala"
                                        @input="clearFieldError('email_kepala')"
                                        type="email"
                                        placeholder="kepala@domain.com"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{
                                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                getFieldError('email_kepala'),
                                        }"
                                        required
                                    />
                                    <p
                                        v-if="getFieldError('email_kepala')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("email_kepala")
                                        }}</span>
                                    </p>
                                </div>

                                <!-- WhatsApp KS (Format Register +62) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="telepon_kepala"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Nomor Telp Kepala Satuan
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <div class="flex rounded-lg shadow-2xs">
                                        <span
                                            class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none"
                                        >
                                            +62
                                        </span>
                                        <input
                                            id="telepon_kepala"
                                            v-model="rawTeleponKepala"
                                            type="text"
                                            placeholder="81234567890 (tanpa 0 di depan)"
                                            class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError(
                                                        'telepon_kepala',
                                                    ),
                                            }"
                                            required
                                        />
                                    </div>
                                    <p
                                        v-if="getFieldError('telepon_kepala')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("telepon_kepala")
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- PIC Section -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-bold uppercase tracking-wider text-slate-700"
                                >
                                    B. Data Person In Charge (PIC) / Narahubung
                                </span>
                                <div
                                    class="flex-1 border-t border-slate-200"
                                ></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <Label
                                        for="nama_pic"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Nama PIC
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="nama_pic"
                                        v-model="form.nama_pic"
                                        @input="clearFieldError('nama_pic')"
                                        type="text"
                                        placeholder="Nama Lengkap PIC"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{
                                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                getFieldError('nama_pic'),
                                        }"
                                        required
                                    />
                                    <p
                                        v-if="getFieldError('nama_pic')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("nama_pic")
                                        }}</span>
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="email_pic"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Email PIC
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="email_pic"
                                        v-model="form.email_pic"
                                        @input="clearFieldError('email_pic')"
                                        type="email"
                                        placeholder="pic@domain.com"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{
                                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                getFieldError('email_pic'),
                                        }"
                                        required
                                    />
                                    <p
                                        v-if="getFieldError('email_pic')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("email_pic")
                                        }}</span>
                                    </p>
                                </div>

                                <!-- WhatsApp PIC (Format Register +62) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="telepon_pic"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Nomor Telp PIC
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <div class="flex rounded-lg shadow-2xs">
                                        <span
                                            class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none"
                                        >
                                            +62
                                        </span>
                                        <input
                                            id="telepon_pic"
                                            v-model="rawTeleponPIC"
                                            type="text"
                                            placeholder="81234567890 (tanpa 0 di depan)"
                                            class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError(
                                                        'telepon_pic',
                                                    ),
                                            }"
                                            required
                                        />
                                    </div>
                                    <p
                                        v-if="getFieldError('telepon_pic')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("telepon_pic")
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 3. WILAYAH & TITIK LOKASI PETA (SPLIT 2 BAGIAN) -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="border-b border-slate-100 p-5 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"
                            >
                                <MapPin class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle
                                    className="text-base font-bold text-slate-900"
                                >
                                    3. Wilayah & Titik Lokasi Peta
                                </CardTitle>
                                <CardDescription
                                    className="text-xs text-slate-500 mt-0.5"
                                >
                                    Alamat administrasi wilayah dan penentuan
                                    titik koordinat geografis di peta.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6">
                        <div
                            class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
                        >
                            <!-- SISI KIRI: Form Input Wilayah & Alamat Lengkap (6 Kolom) -->
                            <div class="lg:col-span-6 space-y-4">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                >
                                    <!-- Provinsi -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="provinsi"
                                            class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                                        >
                                            <span>Provinsi</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2
                                                v-if="isProvincesLoading"
                                                class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                            />
                                        </Label>
                                        <select
                                            id="provinsi"
                                            v-model="selectedProvinceCode"
                                            @change="onProvinceChange"
                                            :disabled="isProvincesLoading"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer disabled:bg-slate-50 disabled:text-slate-400"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError('provinsi'),
                                            }"
                                            required
                                        >
                                            <option value="">
                                                {{
                                                    isProvincesLoading
                                                        ? "Memuat Provinsi..."
                                                        : "Pilih Provinsi..."
                                                }}
                                            </option>
                                            <option
                                                v-for="prov in provinceList"
                                                :key="prov.code"
                                                :value="prov.code"
                                            >
                                                {{ prov.name }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="getFieldError('provinsi')"
                                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>{{
                                                getFieldError("provinsi")
                                            }}</span>
                                        </p>
                                    </div>

                                    <!-- Kabupaten/Kota (Hanya Menampilkan Nama Langsung) -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="kabupaten"
                                            class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                                        >
                                            <span>Kabupaten / Kota</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2
                                                v-if="isRegenciesLoading"
                                                class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                            />
                                        </Label>
                                        <select
                                            id="kabupaten"
                                            v-model="selectedRegencyCode"
                                            @change="onRegencyChange"
                                            :disabled="
                                                !selectedProvinceCode ||
                                                isRegenciesLoading ||
                                                regencyList.length === 0
                                            "
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError('kabupaten'),
                                            }"
                                            required
                                        >
                                            <option value="">
                                                {{
                                                    isRegenciesLoading
                                                        ? "Memuat Kabupaten..."
                                                        : "Pilih Kabupaten/Kota..."
                                                }}
                                            </option>
                                            <option
                                                v-for="reg in regencyList"
                                                :key="reg.code"
                                                :value="reg.code"
                                            >
                                                {{
                                                    cleanKabupatenName(reg.name)
                                                }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="getFieldError('kabupaten')"
                                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>{{
                                                getFieldError("kabupaten")
                                            }}</span>
                                        </p>
                                    </div>

                                    <!-- Kecamatan -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="kecamatan"
                                            class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                                        >
                                            <span>Kecamatan</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2
                                                v-if="isDistrictsLoading"
                                                class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                            />
                                        </Label>
                                        <select
                                            id="kecamatan"
                                            v-model="selectedDistrictCode"
                                            @change="onDistrictChange"
                                            :disabled="
                                                !selectedRegencyCode ||
                                                isDistrictsLoading ||
                                                districtList.length === 0
                                            "
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError('kecamatan'),
                                            }"
                                            required
                                        >
                                            <option value="">
                                                {{
                                                    isDistrictsLoading
                                                        ? "Memuat Kecamatan..."
                                                        : "Pilih Kecamatan..."
                                                }}
                                            </option>
                                            <option
                                                v-for="dist in districtList"
                                                :key="dist.code"
                                                :value="dist.code"
                                            >
                                                {{ dist.name }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="getFieldError('kecamatan')"
                                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>{{
                                                getFieldError("kecamatan")
                                            }}</span>
                                        </p>
                                    </div>

                                    <!-- Desa/Kelurahan -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="desa_kelurahan"
                                            class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                                        >
                                            <span>Desa / Kelurahan</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2
                                                v-if="isVillagesLoading"
                                                class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                            />
                                        </Label>
                                        <select
                                            id="desa_kelurahan"
                                            v-model="selectedVillageCode"
                                            @change="onVillageChange"
                                            :disabled="
                                                !selectedDistrictCode ||
                                                isVillagesLoading ||
                                                villageList.length === 0
                                            "
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError(
                                                        'desa_kelurahan',
                                                    ),
                                            }"
                                            required
                                        >
                                            <option value="">
                                                {{
                                                    isVillagesLoading
                                                        ? "Memuat Desa..."
                                                        : "Pilih Desa/Kelurahan..."
                                                }}
                                            </option>
                                            <option
                                                v-for="vil in villageList"
                                                :key="vil.code"
                                                :value="vil.code"
                                            >
                                                {{ vil.name }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="
                                                getFieldError('desa_kelurahan')
                                            "
                                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>{{
                                                getFieldError("desa_kelurahan")
                                            }}</span>
                                        </p>
                                    </div>

                                    <!-- Kode Pos -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="kode_pos"
                                            class="text-xs font-semibold text-slate-700"
                                        >
                                            Kode Pos
                                            <span class="text-rose-500">*</span>
                                        </Label>
                                        <input
                                            id="kode_pos"
                                            v-model="form.kode_pos"
                                            @input="clearFieldError('kode_pos')"
                                            type="text"
                                            maxlength="5"
                                            placeholder="5 digit angka"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                            :class="{
                                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                    getFieldError('kode_pos'),
                                            }"
                                            required
                                        />
                                        <p
                                            v-if="getFieldError('kode_pos')"
                                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            <span>{{
                                                getFieldError("kode_pos")
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Alamat Lengkap -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="alamat_lengkap"
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        Alamat Lengkap (Jalan / Dusun / RT / RW)
                                        <span class="text-rose-500">*</span>
                                    </Label>
                                    <textarea
                                        id="alamat_lengkap"
                                        v-model="form.alamat_lengkap"
                                        @input="
                                            clearFieldError('alamat_lengkap')
                                        "
                                        rows="3"
                                        placeholder="Contoh: Jl. Mayor Metra No. 12, Banjar Dinas Anyar"
                                        class="w-full p-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 leading-relaxed"
                                        :class="{
                                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                                getFieldError('alamat_lengkap'),
                                        }"
                                        required
                                    ></textarea>
                                    <p
                                        v-if="getFieldError('alamat_lengkap')"
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("alamat_lengkap")
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- SISI KANAN: Map Picker Komponen (6 Kolom) -->
                            <div class="lg:col-span-6 space-y-2">
                                <div id="latitude"></div>
                                <MapPicker
                                    v-model:latitude="form.latitude"
                                    v-model:longitude="form.longitude"
                                    :default-center="defaultCenter"
                                    :default-zoom="10"
                                    label="Titik Koordinat Lokasi Kelompok *"
                                    height="370px"
                                />
                                <div
                                    v-if="
                                        getFieldError('latitude') ||
                                        getFieldError('longitude')
                                    "
                                >
                                    <p
                                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        <span>{{
                                            getFieldError("latitude") ||
                                            getFieldError("longitude")
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 4. RINCIAN JUMLAH PENERIMA MANFAAT -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="border-b border-slate-100 p-5 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"
                            >
                                <Users class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle
                                    className="text-base font-bold text-slate-900"
                                >
                                    4. Rincian Jumlah Penerima Manfaat
                                </CardTitle>
                                <CardDescription
                                    className="text-xs text-slate-500 mt-0.5"
                                >
                                    Pemetaan jumlah penerima manfaat ({{
                                        form.kategori ||
                                        "Pilih kategori terlebih dahulu"
                                    }}).
                                </CardDescription>
                            </div>
                        </div>

                        <!-- Summary Badges (Termasuk Porsi Kecil & Porsi Besar) -->
                        <div
                            class="flex flex-wrap items-center gap-2 self-start sm:self-auto text-xs"
                        >
                            <span
                                class="px-2.5 py-1 font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-200"
                            >
                                Porsi Kecil: {{ totalPorsiKecil }}
                            </span>
                            <span
                                class="px-2.5 py-1 font-bold rounded-lg bg-blue-50 text-blue-800 border border-blue-200"
                            >
                                Porsi Besar: {{ totalPorsiBesar }}
                            </span>
                            <span
                                class="px-2.5 py-1 font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-200"
                            >
                                L: {{ totalLakiLaki }}
                            </span>
                            <span
                                class="px-2.5 py-1 font-bold rounded-lg bg-pink-50 text-pink-700 border border-pink-200"
                            >
                                P: {{ totalPerempuan }}
                            </span>
                            <span
                                class="px-3.5 py-1 font-extrabold rounded-lg bg-primary text-white shadow-xs"
                            >
                                Total: {{ grandTotal }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-5">
                        <!-- Error Rincian -->
                        <div
                            v-if="getFieldError('rincian')"
                            class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-2.5 text-xs font-semibold"
                        >
                            <AlertCircle
                                class="h-4 w-4 shrink-0 text-rose-600"
                            />
                            <span>{{ getFieldError("rincian") }}</span>
                        </div>

                        <!-- Notice jika belum memilih kategori -->
                        <div
                            v-if="!form.kategori"
                            class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center gap-3 text-xs"
                        >
                            <Info class="h-5 w-5 text-blue-600 shrink-0" />
                            <span
                                >Silakan pilih
                                <strong>Kategori Jenjang / Lembaga</strong> di
                                bagian 1 terlebih dahulu untuk memunculkan
                                pilihan subkategori.</span
                            >
                        </div>

                        <div
                            v-else
                            class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs"
                        >
                            <table
                                class="w-full text-left text-xs border-collapse"
                            >
                                <thead>
                                    <tr
                                        class="h-14 bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider"
                                    >
                                        <th class="px-4 py-4 w-12 text-center">
                                            No
                                        </th>
                                        <th class="px-5 py-4 min-w-[200px]">
                                            Sub Kategori Penerima
                                        </th>
                                        <th class="px-4 py-4 w-36 text-center">
                                            Jenis Porsi
                                        </th>
                                        <th class="px-5 py-4 text-center w-36">
                                            Laki-Laki (L)
                                        </th>
                                        <th class="px-5 py-4 text-center w-36">
                                            Perempuan (P)
                                        </th>
                                        <th class="px-4 py-4 text-center w-28">
                                            Subtotal
                                        </th>
                                        <th
                                            class="px-4 py-4 text-center w-12"
                                        ></th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 bg-white"
                                >
                                    <tr
                                        v-for="(item, idx) in form.rincian"
                                        :key="idx"
                                        class="hover:bg-slate-50/60 transition-colors h-16"
                                    >
                                        <td
                                            class="px-4 py-4 text-center font-medium text-slate-400"
                                        >
                                            {{ idx + 1 }}
                                        </td>

                                        <!-- Select Sub Kategori -->
                                        <td class="px-5 py-4">
                                            <select
                                                v-model="item.sub_kategori"
                                                @change="
                                                    onSubKategoriChange(item)
                                                "
                                                class="w-full h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-slate-800 cursor-pointer"
                                                required
                                            >
                                                <option value="" disabled>
                                                    Pilih Sub Kategori...
                                                </option>
                                                <option
                                                    v-for="opt in getAvailableSubKategoriForRow(
                                                        idx,
                                                    )"
                                                    :key="opt"
                                                    :value="opt"
                                                >
                                                    {{ opt }}
                                                </option>
                                            </select>
                                        </td>

                                        <!-- Jenis Porsi (Fixed Badge Berdasarkan Subkategori) -->
                                        <td class="px-4 py-4 text-center">
                                            <span
                                                v-if="
                                                    getJenisPorsiBySubKategori(
                                                        item.sub_kategori,
                                                        form.kategori,
                                                    ) === 'Porsi Kecil'
                                                "
                                                class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs select-none"
                                            >
                                                Porsi Kecil
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs select-none"
                                            >
                                                Porsi Besar
                                            </span>
                                        </td>

                                        <!-- Input Laki-Laki -->
                                        <td class="px-5 py-4 text-center">
                                            <input
                                                v-model.number="
                                                    item.jumlah_laki_laki
                                                "
                                                type="number"
                                                min="0"
                                                placeholder="0"
                                                class="w-full h-10 text-center px-3 text-xs font-bold rounded-lg border border-sky-200 bg-sky-50/40 text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                            />
                                        </td>

                                        <!-- Input Perempuan -->
                                        <td class="px-5 py-4 text-center">
                                            <input
                                                v-model.number="
                                                    item.jumlah_perempuan
                                                "
                                                type="number"
                                                min="0"
                                                placeholder="0"
                                                class="w-full h-10 text-center px-3 text-xs font-bold rounded-lg border border-pink-200 bg-pink-50/40 text-pink-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500"
                                            />
                                        </td>

                                        <!-- Subtotal -->
                                        <td
                                            class="px-4 py-4 text-center font-extrabold text-xs text-slate-800"
                                        >
                                            {{
                                                (Number(
                                                    item.jumlah_laki_laki,
                                                ) || 0) +
                                                (Number(
                                                    item.jumlah_perempuan,
                                                ) || 0)
                                            }}
                                        </td>

                                        <!-- Hapus Baris -->
                                        <td class="px-4 py-4 text-center">
                                            <button
                                                type="button"
                                                @click="removeSubkategori(idx)"
                                                class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer"
                                                title="Hapus baris ini"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="form.rincian.length === 0">
                                        <td
                                            colspan="7"
                                            class="py-10 text-center text-slate-400 text-xs"
                                        >
                                            Belum ada baris subkategori. Klik
                                            tombol di bawah untuk menambah
                                            baris.
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot>
                                    <tr
                                        class="h-14 bg-slate-50 font-bold border-t border-slate-200 text-xs"
                                    >
                                        <td
                                            colspan="3"
                                            class="px-6 py-4 text-right uppercase tracking-wider text-slate-600"
                                        >
                                            Total Penerima Manfaat
                                        </td>
                                        <td
                                            class="px-5 py-4 text-center text-sky-700 font-bold"
                                        >
                                            {{ totalLakiLaki }}
                                        </td>
                                        <td
                                            class="px-5 py-4 text-center text-pink-700 font-bold"
                                        >
                                            {{ totalPerempuan }}
                                        </td>
                                        <td
                                            class="px-4 py-4 text-center text-primary font-black text-sm"
                                        >
                                            {{ grandTotal }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Action Buttons di Bawah Tabel -->
                        <div
                            v-if="form.kategori"
                            class="flex flex-wrap items-center justify-between gap-3 pt-2"
                        >
                            <button
                                type="button"
                                @click="addCustomSubkategori"
                                :disabled="isAllSubkategoriAdded"
                                class="inline-flex items-center justify-center gap-2 h-11 px-5 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition-colors cursor-pointer shrink-0 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
                                :title="
                                    isAllSubkategoriAdded
                                        ? 'Semua sub kategori untuk jenjang ini telah ditambahkan'
                                        : 'Tambah baris sub kategori lainnya'
                                "
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Tambah Baris Sub Kategori Lainnya</span>
                            </button>

                            <button
                                type="button"
                                @click="resetRincianToDefault"
                                class="inline-flex items-center justify-center gap-1.5 h-11 px-4 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors cursor-pointer shrink-0 whitespace-nowrap"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                <span
                                    >Reset Rincian ke Default ({{
                                        form.kategori
                                    }})</span
                                >
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bottom Submit Button -->
                <div
                    class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200"
                >
                    <Link
                        :href="route('penerima-manfaat.index')"
                        class="inline-flex items-center justify-center h-11 px-6 min-w-[100px] text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition-colors cursor-pointer shrink-0 whitespace-nowrap"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 h-11 px-6 min-w-[180px] text-xs font-bold rounded-lg bg-primary hover:bg-primary/90 text-white shadow-sm transition-all cursor-pointer disabled:opacity-50 shrink-0 whitespace-nowrap"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Data Kelompok Penerima Manfaat"
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
