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
    kelompok: {
        type: Object,
        required: true,
    },
});

const defaultCenter = computed(() => {
    if (props.kelompok?.latitude && props.kelompok?.longitude) {
        return [Number(props.kelompok.latitude), Number(props.kelompok.longitude)];
    }
    return [-8.409518, 115.188916];
});

// Form state
const form = useForm({
    nama_kelompok: props.kelompok.nama_kelompok || "",
    kategori: props.kelompok.kategori || "SD",
    jenis_kepemilikan: props.kelompok.jenis_kepemilikan || "Negeri",
    tipe_identitas: props.kelompok.tipe_identitas || "NPSN",
    kode_identitas: props.kelompok.kode_identitas || "",
    nama_kepala_sekolah: props.kelompok.nama_kepala_sekolah || "",
    email_kepala_sekolah: props.kelompok.email_kepala_sekolah || "",
    telepon_kepala_sekolah: props.kelompok.telepon_kepala_sekolah || "",
    nama_pic: props.kelompok.nama_pic || "",
    email_pic: props.kelompok.email_pic || "",
    telepon_pic: props.kelompok.telepon_pic || "",
    provinsi: props.kelompok.provinsi || "",
    kabupaten: props.kelompok.kabupaten || "",
    kecamatan: props.kelompok.kecamatan || "",
    desa_kelurahan: props.kelompok.desa_kelurahan || "",
    kode_pos: props.kelompok.kode_pos || "",
    alamat_lengkap: props.kelompok.alamat_lengkap || "",
    latitude: props.kelompok.latitude ? Number(props.kelompok.latitude) : null,
    longitude: props.kelompok.longitude ? Number(props.kelompok.longitude) : null,
    rincian: Array.isArray(props.kelompok.rincian)
        ? props.kelompok.rincian.map((r) => ({
              id: r.id,
              sub_kategori: r.sub_kategori,
              jenis_porsi: r.jenis_porsi || getJenisPorsiBySubKategori(r.sub_kategori, props.kelompok.kategori),
              jumlah_laki_laki: Number(r.jumlah_laki_laki) || 0,
              jumlah_perempuan: Number(r.jumlah_perempuan) || 0,
          }))
        : [],
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
const rawTeleponKS = ref(
    form.telepon_kepala_sekolah
        ? form.telepon_kepala_sekolah.replace(/^62/, "")
        : ""
);
const rawTeleponPIC = ref(
    form.telepon_pic ? form.telepon_pic.replace(/^62/, "") : ""
);

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

watch(rawTeleponKS, (newVal) => {
    const cleaned = cleanPhone(newVal);
    rawTeleponKS.value = cleaned;
    form.telepon_kepala_sekolah = cleaned ? `62${cleaned}` : "";
});

watch(rawTeleponPIC, (newVal) => {
    const cleaned = cleanPhone(newVal);
    rawTeleponPIC.value = cleaned;
    form.telepon_pic = cleaned ? `62${cleaned}` : "";
});

function cleanKabupatenName(name) {
    if (!name) return "";
    return name.replace(/^Kabupaten\s+/i, "").replace(/^Kota\s+/i, "");
}

// Subkategori options
const currentCategorySubOptions = computed(() => {
    if (!form.kategori) return [];
    return getSubKategoriByKategori(form.kategori);
});

function getAvailableSubKategoriForRow(currentRowIdx) {
    const allOptions = currentCategorySubOptions.value;
    const currentVal = form.rincian[currentRowIdx]?.sub_kategori;
    const selectedOthers = form.rincian
        .filter((_, idx) => idx !== currentRowIdx)
        .map((r) => r.sub_kategori)
        .filter(Boolean);

    return allOptions.filter(
        (opt) => opt === currentVal || !selectedOthers.includes(opt)
    );
}

function onSubKategoriChange(item) {
    if (item.sub_kategori) {
        item.jenis_porsi = getJenisPorsiBySubKategori(item.sub_kategori, form.kategori);
    }
}

const isAllSubkategoriAdded = computed(() => {
    if (!form.kategori || currentCategorySubOptions.value.length === 0) return true;
    const selected = form.rincian.map((r) => r.sub_kategori).filter(Boolean);
    return currentCategorySubOptions.value.every((opt) => selected.includes(opt));
});

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
}

function removeSubkategori(index) {
    form.rincian.splice(index, 1);
}

function resetSubkategoriToCategoryDefaults() {
    const subList = getSubKategoriByKategori(form.kategori);
    form.rincian = subList.map((sub) => {
        const existing = form.rincian.find((r) => r.sub_kategori === sub);
        return {
            sub_kategori: sub,
            jenis_porsi: existing?.jenis_porsi || getJenisPorsiBySubKategori(sub, form.kategori),
            jumlah_laki_laki: existing ? existing.jumlah_laki_laki : 0,
            jumlah_perempuan: existing ? existing.jumlah_perempuan : 0,
        };
    });
}

// Grand totals computed
const totalLakiLaki = computed(() => {
    return form.rincian.reduce(
        (sum, item) => sum + (Number(item.jumlah_laki_laki) || 0),
        0
    );
});

const totalPerempuan = computed(() => {
    return form.rincian.reduce(
        (sum, item) => sum + (Number(item.jumlah_perempuan) || 0),
        0
    );
});

const totalPorsiKecil = computed(() => {
    return form.rincian
        .filter((item) => (item.jenis_porsi || getJenisPorsiBySubKategori(item.sub_kategori, form.kategori)) === 'Porsi Kecil')
        .reduce((sum, item) => sum + (Number(item.jumlah_laki_laki) || 0) + (Number(item.jumlah_perempuan) || 0), 0);
});

const totalPorsiBesar = computed(() => {
    return form.rincian
        .filter((item) => (item.jenis_porsi || getJenisPorsiBySubKategori(item.sub_kategori, form.kategori)) === 'Porsi Besar')
        .reduce((sum, item) => sum + (Number(item.jumlah_laki_laki) || 0) + (Number(item.jumlah_perempuan) || 0), 0);
});

const grandTotal = computed(() => totalLakiLaki.value + totalPerempuan.value);

// Inisialisasi Wilayah
onMounted(async () => {
    isProvincesLoading.value = true;
    try {
        provinceList.value = await getProvinces();

        if (form.provinsi) {
            const prov = provinceList.value.find(
                (p) =>
                    p.name.toLowerCase() === form.provinsi.toLowerCase() ||
                    p.name.toLowerCase().includes(form.provinsi.toLowerCase())
            );
            if (prov) {
                selectedProvinceCode.value = prov.code;
                isRegenciesLoading.value = true;
                regencyList.value = await getRegencies(prov.code);
                isRegenciesLoading.value = false;

                if (form.kabupaten) {
                    const reg = regencyList.value.find(
                        (r) =>
                            r.name
                                .toLowerCase()
                                .includes(form.kabupaten.toLowerCase()) ||
                            form.kabupaten
                                .toLowerCase()
                                .includes(r.name.toLowerCase())
                    );
                    if (reg) {
                        selectedRegencyCode.value = reg.code;
                        isDistrictsLoading.value = true;
                        districtList.value = await getDistricts(reg.code);
                        isDistrictsLoading.value = false;

                        if (form.kecamatan) {
                            const dist = districtList.value.find(
                                (d) =>
                                    d.name
                                        .toLowerCase()
                                        .includes(
                                            form.kecamatan.toLowerCase()
                                        ) ||
                                    form.kecamatan
                                        .toLowerCase()
                                        .includes(d.name.toLowerCase())
                            );
                            if (dist) {
                                selectedDistrictCode.value = dist.code;
                                isVillagesLoading.value = true;
                                villageList.value = await getVillages(dist.code);
                                isVillagesLoading.value = false;

                                if (form.desa_kelurahan) {
                                    const vil = villageList.value.find(
                                        (v) =>
                                            v.name
                                                .toLowerCase()
                                                .includes(
                                                    form.desa_kelurahan.toLowerCase()
                                                ) ||
                                            form.desa_kelurahan
                                                .toLowerCase()
                                                .includes(v.name.toLowerCase())
                                    );
                                    if (vil) {
                                        selectedVillageCode.value = vil.code;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    } catch (e) {
        console.error("Gagal inisialisasi wilayah:", e);
    } finally {
        isProvincesLoading.value = false;
    }
});

async function onProvinceChange() {
    const prov = provinceList.value.find(
        (p) => p.code === selectedProvinceCode.value
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
    const reg = regencyList.value.find(
        (r) => r.code === selectedRegencyCode.value
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
    const dist = districtList.value.find(
        (d) => d.code === selectedDistrictCode.value
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
    const vil = villageList.value.find(
        (v) => v.code === selectedVillageCode.value
    );
    form.desa_kelurahan = vil ? vil.name : "";
    if (vil?.postal_code) {
        form.kode_pos = vil.postal_code;
    }
}

// Submit Update Form
function submitForm() {
    form.put(route("penerima-manfaat.update", props.kelompok.uid || props.kelompok.id), {
        preserveScroll: true,
        onError: (errors) => {
            console.error("Validation error:", errors);
        },
    });
}
</script>

<template>
    <AppLayout
        title="Edit Kelompok Penerima Manfaat"
        subtitle="Perbarui data legalitas, narahubung, dan rincian penerima manfaat"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head :title="`Edit ${form.nama_kelompok} - SIPEGE`" />

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
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 h-11 px-6 min-w-[160px] text-xs font-bold rounded-lg bg-primary hover:bg-primary/90 text-white shadow-sm transition-all cursor-pointer disabled:opacity-50 shrink-0 whitespace-nowrap"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{ form.processing ? "Menyimpan Perubahan..." : "Simpan Perubahan" }}</span>
                    </button>
                </div>
            </div>

            <!-- Form Container -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- 1. IDENTITAS & LEGALITAS KELOMPOK -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader className="border-b border-slate-100 p-5 bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <School class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">
                                    1. Identitas & Legalitas Kelompok
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Informasi nama lembaga, jenjang kategori, status kepemilikan, dan nomor kode legalitas.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nama Kelompok -->
                            <div class="space-y-1.5 md:col-span-2">
                                <Label for="nama_kelompok" class="text-xs font-semibold text-slate-700">
                                    Nama Kelompok Penerima Manfaat <span class="text-rose-500">*</span>
                                </Label>
                                <input
                                    id="nama_kelompok"
                                    v-model="form.nama_kelompok"
                                    type="text"
                                    placeholder="Contoh: SD Negeri 1 Singaraja / Posyandu Melati Indah"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                    :class="{ 'border-rose-400': form.errors.nama_kelompok }"
                                    required
                                />
                                <p v-if="form.errors.nama_kelompok" class="text-xs text-rose-500 font-medium">
                                    {{ form.errors.nama_kelompok }}
                                </p>
                            </div>

                            <!-- Kategori -->
                            <div class="space-y-1.5">
                                <Label for="kategori" class="text-xs font-semibold text-slate-700">
                                    Kategori Jenjang / Lembaga <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="kategori"
                                    v-model="form.kategori"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{ 'border-rose-400': form.errors.kategori }"
                                    required
                                >
                                    <option value="" disabled>Pilih Kategori Jenjang...</option>
                                    <option
                                        v-for="kat in KATEGORI_OPTIONS"
                                        :key="kat.value"
                                        :value="kat.value"
                                    >
                                        {{ kat.value }}
                                    </option>
                                </select>
                                <p v-if="form.errors.kategori" class="text-xs text-rose-500 font-medium">
                                    {{ form.errors.kategori }}
                                </p>
                            </div>

                            <!-- Jenis Kepemilikan -->
                            <div class="space-y-1.5">
                                <Label for="jenis_kepemilikan" class="text-xs font-semibold text-slate-700">
                                    Jenis Kepemilikan <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="jenis_kepemilikan"
                                    v-model="form.jenis_kepemilikan"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{ 'border-rose-400': form.errors.jenis_kepemilikan }"
                                    required
                                >
                                    <option value="" disabled>Pilih Jenis Kepemilikan...</option>
                                    <option
                                        v-for="j in JENIS_KEPEMILIKAN_OPTIONS"
                                        :key="j.value"
                                        :value="j.value"
                                    >
                                        {{ j.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.jenis_kepemilikan" class="text-xs text-rose-500 font-medium">
                                    {{ form.errors.jenis_kepemilikan }}
                                </p>
                            </div>

                            <!-- Tipe Identitas -->
                            <div class="space-y-1.5">
                                <Label for="tipe_identitas" class="text-xs font-semibold text-slate-700">
                                    Tipe Nomor Identitas <span class="text-rose-500">*</span>
                                </Label>
                                <select
                                    id="tipe_identitas"
                                    v-model="form.tipe_identitas"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                                    :class="{ 'border-rose-400': form.errors.tipe_identitas }"
                                    required
                                >
                                    <option value="" disabled>Pilih Tipe Identitas...</option>
                                    <option
                                        v-for="t in TIPE_IDENTITAS_OPTIONS"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.value }}
                                    </option>
                                </select>
                                <p v-if="form.errors.tipe_identitas" class="text-xs text-rose-500 font-medium">
                                    {{ form.errors.tipe_identitas }}
                                </p>
                            </div>

                            <!-- Kode Identitas -->
                            <div class="space-y-1.5">
                                <Label for="kode_identitas" class="text-xs font-semibold text-slate-700">
                                    Kode / Nomor Identitas ({{ form.tipe_identitas || "Legalitas" }}) <span class="text-rose-500">*</span>
                                </Label>
                                <input
                                    id="kode_identitas"
                                    v-model="form.kode_identitas"
                                    type="text"
                                    placeholder="Contoh: 50101234 (Nomor NPSN / NSPP / NSM / Izin)"
                                    class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 font-mono"
                                    :class="{ 'border-rose-400': form.errors.kode_identitas }"
                                    required
                                />
                                <p v-if="form.errors.kode_identitas" class="text-xs text-rose-500 font-medium">
                                    {{ form.errors.kode_identitas }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. KONTAK PENANGGUNG JAWAB (KS & PIC) -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader className="border-b border-slate-100 p-5 bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <User class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">
                                    2. Kontak Penanggung Jawab (KS & PIC)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Data narahubung resmi Kepala Sekolah / Pimpinan dan Person In Charge (PIC) lapangan.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-6">
                        <!-- KS Section -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                    A. Data Kepala Sekolah / Pimpinan Lembaga
                                </span>
                                <div class="flex-1 border-t border-slate-200"></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="nama_kepala_sekolah" class="text-xs font-semibold text-slate-700">
                                        Nama Kepala Sekolah <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="nama_kepala_sekolah"
                                        v-model="form.nama_kepala_sekolah"
                                        type="text"
                                        placeholder="Nama Kepala Sekolah beserta gelar"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{ 'border-rose-400': form.errors.nama_kepala_sekolah }"
                                        required
                                    />
                                    <p v-if="form.errors.nama_kepala_sekolah" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.nama_kepala_sekolah }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="email_kepala_sekolah" class="text-xs font-semibold text-slate-700">
                                        Email Kepala Sekolah <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="email_kepala_sekolah"
                                        v-model="form.email_kepala_sekolah"
                                        type="email"
                                        placeholder="kepsek@sekolah.sch.id"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{ 'border-rose-400': form.errors.email_kepala_sekolah }"
                                        required
                                    />
                                    <p v-if="form.errors.email_kepala_sekolah" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.email_kepala_sekolah }}
                                    </p>
                                </div>

                                <!-- WhatsApp KS (Format +62) -->
                                <div class="space-y-1.5">
                                    <Label for="telepon_kepala_sekolah" class="text-xs font-semibold text-slate-700">
                                        Nomor WhatsApp KS <span class="text-rose-500">*</span>
                                    </Label>
                                    <div class="flex rounded-lg shadow-2xs">
                                        <span class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none">
                                            +62
                                        </span>
                                        <input
                                            id="telepon_kepala_sekolah"
                                            v-model="rawTeleponKS"
                                            type="text"
                                            placeholder="81234567890 (tanpa 0 di depan)"
                                            class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 font-mono"
                                            :class="{ 'border-rose-400': form.errors.telepon_kepala_sekolah }"
                                            required
                                        />
                                    </div>
                                    <p v-if="form.errors.telepon_kepala_sekolah" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.telepon_kepala_sekolah }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- PIC Section -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                                    B. Data Person In Charge (PIC) / Narahubung
                                </span>
                                <div class="flex-1 border-t border-slate-200"></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="nama_pic" class="text-xs font-semibold text-slate-700">
                                        Nama Lengkap PIC <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="nama_pic"
                                        v-model="form.nama_pic"
                                        type="text"
                                        placeholder="Nama PIC penanggung jawab"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{ 'border-rose-400': form.errors.nama_pic }"
                                        required
                                    />
                                    <p v-if="form.errors.nama_pic" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.nama_pic }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="email_pic" class="text-xs font-semibold text-slate-700">
                                        Email PIC <span class="text-rose-500">*</span>
                                    </Label>
                                    <input
                                        id="email_pic"
                                        v-model="form.email_pic"
                                        type="email"
                                        placeholder="pic@domain.com"
                                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                        :class="{ 'border-rose-400': form.errors.email_pic }"
                                        required
                                    />
                                    <p v-if="form.errors.email_pic" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.email_pic }}
                                    </p>
                                </div>

                                <!-- WhatsApp PIC (Format +62) -->
                                <div class="space-y-1.5">
                                    <Label for="telepon_pic" class="text-xs font-semibold text-slate-700">
                                        Nomor WhatsApp PIC <span class="text-rose-500">*</span>
                                    </Label>
                                    <div class="flex rounded-lg shadow-2xs">
                                        <span class="inline-flex items-center px-3.5 rounded-l-lg border border-r-0 border-slate-200 bg-slate-100 text-slate-700 font-bold text-xs select-none">
                                            +62
                                        </span>
                                        <input
                                            id="telepon_pic"
                                            v-model="rawTeleponPIC"
                                            type="text"
                                            placeholder="81234567890 (tanpa 0 di depan)"
                                            class="flex-1 h-11 px-3 text-xs rounded-r-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 font-mono"
                                            :class="{ 'border-rose-400': form.errors.telepon_pic }"
                                            required
                                        />
                                    </div>
                                    <p v-if="form.errors.telepon_pic" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.telepon_pic }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 3. WILAYAH & TITIK LOKASI PETA (SPLIT 2 BAGIAN) -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader className="border-b border-slate-100 p-5 bg-slate-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                                <MapPin class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">
                                    3. Wilayah & Titik Lokasi Peta
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Alamat administrasi wilayah dan penentuan titik koordinat geografis di peta.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <!-- SISI KIRI: Form Input Wilayah & Alamat Lengkap (6 Kolom) -->
                            <div class="lg:col-span-6 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Provinsi -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label for="provinsi" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                            <span>Provinsi</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2 v-if="isProvincesLoading" class="h-3.5 w-3.5 animate-spin text-primary ml-1" />
                                        </Label>
                                        <select
                                            id="provinsi"
                                            v-model="selectedProvinceCode"
                                            @change="onProvinceChange"
                                            :disabled="isProvincesLoading"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer disabled:bg-slate-50 disabled:text-slate-400"
                                            required
                                        >
                                            <option value="">{{ isProvincesLoading ? 'Memuat Provinsi...' : 'Pilih Provinsi...' }}</option>
                                            <option
                                                v-for="prov in provinceList"
                                                :key="prov.code"
                                                :value="prov.code"
                                            >
                                                {{ prov.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.provinsi" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.provinsi }}
                                        </p>
                                    </div>

                                    <!-- Kabupaten/Kota (Hanya Menampilkan Nama Langsung) -->
                                    <div class="space-y-1.5">
                                        <Label for="kabupaten" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                            <span>Kabupaten / Kota</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2 v-if="isRegenciesLoading" class="h-3.5 w-3.5 animate-spin text-primary ml-1" />
                                        </Label>
                                        <select
                                            id="kabupaten"
                                            v-model="selectedRegencyCode"
                                            @change="onRegencyChange"
                                            :disabled="!selectedProvinceCode || isRegenciesLoading || regencyList.length === 0"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            required
                                        >
                                            <option value="">{{ isRegenciesLoading ? 'Memuat Kabupaten...' : 'Pilih Kabupaten/Kota...' }}</option>
                                            <option
                                                v-for="reg in regencyList"
                                                :key="reg.code"
                                                :value="reg.code"
                                            >
                                                {{ cleanKabupatenName(reg.name) }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.kabupaten" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.kabupaten }}
                                        </p>
                                    </div>

                                    <!-- Kecamatan -->
                                    <div class="space-y-1.5">
                                        <Label for="kecamatan" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                            <span>Kecamatan</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2 v-if="isDistrictsLoading" class="h-3.5 w-3.5 animate-spin text-primary ml-1" />
                                        </Label>
                                        <select
                                            id="kecamatan"
                                            v-model="selectedDistrictCode"
                                            @change="onDistrictChange"
                                            :disabled="!selectedRegencyCode || isDistrictsLoading || districtList.length === 0"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            required
                                        >
                                            <option value="">{{ isDistrictsLoading ? 'Memuat Kecamatan...' : 'Pilih Kecamatan...' }}</option>
                                            <option
                                                v-for="dist in districtList"
                                                :key="dist.code"
                                                :value="dist.code"
                                            >
                                                {{ dist.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.kecamatan" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.kecamatan }}
                                        </p>
                                    </div>

                                    <!-- Desa/Kelurahan -->
                                    <div class="space-y-1.5">
                                        <Label for="desa_kelurahan" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                            <span>Desa / Kelurahan</span>
                                            <span class="text-rose-500">*</span>
                                            <Loader2 v-if="isVillagesLoading" class="h-3.5 w-3.5 animate-spin text-primary ml-1" />
                                        </Label>
                                        <select
                                            id="desa_kelurahan"
                                            v-model="selectedVillageCode"
                                            @change="onVillageChange"
                                            :disabled="!selectedDistrictCode || isVillagesLoading || villageList.length === 0"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                            required
                                        >
                                            <option value="">{{ isVillagesLoading ? 'Memuat Desa...' : 'Pilih Desa/Kelurahan...' }}</option>
                                            <option
                                                v-for="vil in villageList"
                                                :key="vil.code"
                                                :value="vil.code"
                                            >
                                                {{ vil.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.desa_kelurahan" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.desa_kelurahan }}
                                        </p>
                                    </div>

                                    <!-- Kode Pos -->
                                    <div class="space-y-1.5">
                                        <Label for="kode_pos" class="text-xs font-semibold text-slate-700">
                                            Kode Pos <span class="text-rose-500">*</span>
                                        </Label>
                                        <input
                                            id="kode_pos"
                                            v-model="form.kode_pos"
                                            type="text"
                                            maxlength="5"
                                            placeholder="5 digit angka"
                                            class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 font-mono"
                                            :class="{ 'border-rose-400': form.errors.kode_pos }"
                                            required
                                        />
                                        <p v-if="form.errors.kode_pos" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.kode_pos }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Alamat Lengkap -->
                                <div class="space-y-1.5">
                                    <Label for="alamat_lengkap" class="text-xs font-semibold text-slate-700">
                                        Alamat Lengkap (Jalan / Dusun / RT / RW) <span class="text-rose-500">*</span>
                                    </Label>
                                    <textarea
                                        id="alamat_lengkap"
                                        v-model="form.alamat_lengkap"
                                        rows="3"
                                        placeholder="Contoh: Jl. Mayor Metra No. 12, Banjar Dinas Anyar"
                                        class="w-full p-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 leading-relaxed"
                                        :class="{ 'border-rose-400': form.errors.alamat_lengkap }"
                                        required
                                    ></textarea>
                                    <p v-if="form.errors.alamat_lengkap" class="text-xs text-rose-500 font-medium">
                                        {{ form.errors.alamat_lengkap }}
                                    </p>
                                </div>
                            </div>

                            <!-- SISI KANAN: Map Picker Komponen (6 Kolom) -->
                            <div class="lg:col-span-6 space-y-2">
                                <MapPicker
                                    v-model:latitude="form.latitude"
                                    v-model:longitude="form.longitude"
                                    :default-center="defaultCenter"
                                    :default-zoom="form.latitude && form.longitude ? 14 : 10"
                                    label="Titik Koordinat Lokasi Kelompok"
                                    height="370px"
                                />
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p v-if="form.errors.latitude" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.latitude }}
                                        </p>
                                    </div>
                                    <div>
                                        <p v-if="form.errors.longitude" class="text-xs text-rose-500 font-medium">
                                            {{ form.errors.longitude }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 4. RINCIAN JUMLAH PENERIMA MANFAAT -->
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader className="border-b border-slate-100 p-5 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <Users class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle className="text-base font-bold text-slate-900">
                                    4. Rincian Jumlah Penerima Manfaat
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Pemetaan jumlah penerima manfaat Laki-Laki & Perempuan serta jenis porsi ({{ form.kategori }}).
                                </CardDescription>
                            </div>
                        </div>

                        <!-- Summary Badges (Termasuk Porsi Kecil & Porsi Besar) -->
                        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto font-mono text-xs">
                            <span class="px-2.5 py-1 font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
                                Porsi Kecil: {{ totalPorsiKecil }}
                            </span>
                            <span class="px-2.5 py-1 font-bold rounded-lg bg-blue-50 text-blue-800 border border-blue-200">
                                Porsi Besar: {{ totalPorsiBesar }}
                            </span>
                            <span class="px-2.5 py-1 font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-200">
                                L: {{ totalLakiLaki }}
                            </span>
                            <span class="px-2.5 py-1 font-bold rounded-lg bg-pink-50 text-pink-700 border border-pink-200">
                                P: {{ totalPerempuan }}
                            </span>
                            <span class="px-3.5 py-1 font-extrabold rounded-lg bg-primary text-white shadow-xs">
                                Total: {{ grandTotal }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="p-5 sm:p-6 space-y-5">
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="h-14 bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                        <th class="px-4 py-4 w-12 text-center">No</th>
                                        <th class="px-5 py-4 min-w-[200px]">Sub Kategori Penerima</th>
                                        <th class="px-4 py-4 w-36 text-center">Jenis Porsi</th>
                                        <th class="px-5 py-4 text-center w-36">Laki-Laki (L)</th>
                                        <th class="px-5 py-4 text-center w-36">Perempuan (P)</th>
                                        <th class="px-4 py-4 text-center w-28">Subtotal</th>
                                        <th class="px-4 py-4 text-center w-12"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="(item, idx) in form.rincian"
                                        :key="idx"
                                        class="hover:bg-slate-50/60 transition-colors h-16"
                                    >
                                        <td class="px-4 py-4 text-center font-medium text-slate-400">
                                            {{ idx + 1 }}
                                        </td>

                                        <!-- Select Sub Kategori -->
                                        <td class="px-5 py-4">
                                            <select
                                                v-model="item.sub_kategori"
                                                @change="onSubKategoriChange(item)"
                                                class="w-full h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-slate-800 cursor-pointer"
                                                required
                                            >
                                                <option value="" disabled>Pilih Sub Kategori...</option>
                                                <option
                                                    v-for="opt in getAvailableSubKategoriForRow(idx)"
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
                                                v-if="getJenisPorsiBySubKategori(item.sub_kategori, form.kategori) === 'Porsi Kecil'"
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
                                                v-model.number="item.jumlah_laki_laki"
                                                type="number"
                                                min="0"
                                                placeholder="0"
                                                class="w-full h-10 text-center px-3 text-xs font-mono font-bold rounded-lg border border-sky-200 bg-sky-50/40 text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                            />
                                        </td>

                                        <!-- Input Perempuan -->
                                        <td class="px-5 py-4 text-center">
                                            <input
                                                v-model.number="item.jumlah_perempuan"
                                                type="number"
                                                min="0"
                                                placeholder="0"
                                                class="w-full h-10 text-center px-3 text-xs font-mono font-bold rounded-lg border border-pink-200 bg-pink-50/40 text-pink-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500"
                                            />
                                        </td>

                                        <!-- Subtotal -->
                                        <td class="px-4 py-4 text-center font-mono font-extrabold text-xs text-slate-800">
                                            {{ (Number(item.jumlah_laki_laki) || 0) + (Number(item.jumlah_perempuan) || 0) }}
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
                                        <td colspan="7" class="py-10 text-center text-slate-400 text-xs">
                                            Belum ada baris subkategori. Klik tombol di bawah untuk menambah baris.
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot>
                                    <tr class="h-14 bg-slate-50 font-bold border-t border-slate-200 text-xs">
                                        <td colspan="3" class="px-6 py-4 text-right uppercase tracking-wider text-slate-600">
                                            Grand Total Penerima
                                        </td>
                                        <td class="px-5 py-4 text-center font-mono text-sky-700 font-bold">
                                            {{ totalLakiLaki }}
                                        </td>
                                        <td class="px-5 py-4 text-center font-mono text-pink-700 font-bold">
                                            {{ totalPerempuan }}
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono text-primary font-black text-sm">
                                            {{ grandTotal }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Action Buttons di Bawah Tabel -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <button
                                type="button"
                                @click="addCustomSubkategori"
                                :disabled="isAllSubkategoriAdded"
                                class="inline-flex items-center justify-center gap-2 h-11 px-5 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 shadow-2xs transition-colors cursor-pointer shrink-0 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
                                :title="isAllSubkategoriAdded ? 'Semua sub kategori untuk jenjang ini telah ditambahkan' : 'Tambah baris sub kategori lainnya'"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Tambah Baris Sub Kategori Lainnya</span>
                            </button>

                            <button
                                type="button"
                                @click="resetSubkategoriToCategoryDefaults"
                                class="inline-flex items-center justify-center gap-1.5 h-11 px-4 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors cursor-pointer shrink-0 whitespace-nowrap"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                <span>Reset Rincian ke Default ({{ form.kategori }})</span>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bottom Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
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
                        <span>{{ form.processing ? "Menyimpan..." : "Simpan Perubahan" }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
