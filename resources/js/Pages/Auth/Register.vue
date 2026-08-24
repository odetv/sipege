<script setup>
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Select from "@/Components/ui/Select.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardFooter from "@/Components/ui/CardFooter.vue";
import Badge from "@/Components/ui/Badge.vue";
import Separator from "@/Components/ui/Separator.vue";
import MapPicker from "@/Components/MapPicker.vue";
import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
    formatWilayahName,
    formatKabupatenName,
    formatNamaLengkap,
} from "@/Services/wilayah";
import { formatTanggalIndo } from "@/lib/utils";
import {
    Building2,
    User,
    MapPin,
    FileText,
    CheckCircle,
    ArrowRight,
    ArrowLeft,
    Sparkles,
    Phone,
    Mail,
    Lock,
    AlertCircle,
    Copy,
    Loader2,
    Eye,
    EyeOff,
} from "lucide-vue-next";

const currentStep = ref(1);
const clientErrors = ref({});
const isAgreed = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const domisiliMapRef = ref(null);
const unitMapRef = ref(null);

// Raw phone input without +62 prefix
const rawPhone = ref("");

const form = useForm({
    // 1.2.1 Data Profil Pengguna (Kepala SPPG)
    nik: "",
    nip: "",
    nama: "",
    gelar_depan: "",
    gelar_belakang: "",
    agama: "",
    jenis_kelamin: "",
    tempat_lahir: "",
    tanggal_lahir: "",
    jenjang_pendidikan: "",
    bidang_pendidikan: "",
    status_kawin: "",
    provinsi_ktp: "",
    kabupaten_ktp: "",
    kecamatan_ktp: "",
    desa_kelurahan_ktp: "",
    kode_pos_ktp: "",
    alamat_lengkap_ktp: "",
    provinsi_domisili: "",
    kabupaten_domisili: "",
    kecamatan_domisili: "",
    desa_kelurahan_domisili: "",
    kode_pos_domisili: "",
    alamat_lengkap_domisili: "",
    latitude_domisili: null,
    longitude_domisili: null,
    telepon: "",
    email: "",
    password: "",
    password_confirmation: "",

    // 1.2.2 Data Unit SPPG
    id_sppg: "",
    kode_sppg: "",
    unit_nama: "",
    status: "Belum Operasional",
    tanggal_operasional: "",
    unit_provinsi: "",
    unit_kabupaten: "",
    unit_kecamatan: "",
    unit_desa_kelurahan: "",
    unit_latitude: null,
    unit_longitude: null,
    kode_pos: "",
    alamat_lengkap: "",
});

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

// Refresh maps on step change
function triggerMapRefresh() {
    nextTick(() => {
        setTimeout(() => {
            window.dispatchEvent(new Event("resize"));
            if (domisiliMapRef.value?.refresh) domisiliMapRef.value.refresh();
            if (unitMapRef.value?.refresh) unitMapRef.value.refresh();
        }, 150);
        setTimeout(() => {
            if (domisiliMapRef.value?.refresh) domisiliMapRef.value.refresh();
            if (unitMapRef.value?.refresh) unitMapRef.value.refresh();
        }, 400);
    });
}

// Format and sync phone
watch(rawPhone, (newVal) => {
    let cleaned = newVal.replace(/\D/g, "");
    if (cleaned.startsWith("0")) {
        cleaned = cleaned.substring(1);
    }
    if (cleaned.startsWith("62")) {
        cleaned = cleaned.substring(2);
    }
    rawPhone.value = cleaned;
    form.telepon = cleaned ? `62${cleaned}` : "";
    clearFieldError("telepon");
});

// Wilayah Loading States
const isProvincesLoading = ref(false);

const isKtpRegLoading = ref(false);
const isKtpDistLoading = ref(false);
const isKtpVillLoading = ref(false);

const isDomRegLoading = ref(false);
const isDomDistLoading = ref(false);
const isDomVillLoading = ref(false);

const isUnitRegLoading = ref(false);
const isUnitDistLoading = ref(false);
const isUnitVillLoading = ref(false);

// Wilayah Data States
const provincesList = ref([]);

// KTP
const selectedKtpProvId = ref("");
const selectedKtpRegId = ref("");
const selectedKtpDistId = ref("");
const ktpRegencies = ref([]);
const ktpDistricts = ref([]);
const ktpVillages = ref([]);

// Domisili
const selectedDomProvId = ref("");
const selectedDomRegId = ref("");
const selectedDomDistId = ref("");
const domRegencies = ref([]);
const domDistricts = ref([]);
const domVillages = ref([]);

// Unit SPPG
const selectedUnitProvId = ref("");
const selectedUnitRegId = ref("");
const selectedUnitDistId = ref("");
const unitRegencies = ref([]);
const unitDistricts = ref([]);
const unitVillages = ref([]);

onMounted(async () => {
    isProvincesLoading.value = true;
    try {
        provincesList.value = await getProvinces();
    } finally {
        isProvincesLoading.value = false;
    }
});

// KTP handlers
async function onKtpProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedKtpProvId.value,
    );
    form.provinsi_ktp = prov ? formatWilayahName(prov.name) : "";
    selectedKtpRegId.value = "";
    selectedKtpDistId.value = "";
    form.kabupaten_ktp = "";
    form.kecamatan_ktp = "";
    form.desa_kelurahan_ktp = "";
    ktpDistricts.value = [];
    ktpVillages.value = [];
    clearFieldError("provinsi_ktp");
    if (selectedKtpProvId.value) {
        isKtpRegLoading.value = true;
        try {
            ktpRegencies.value = await getRegencies(selectedKtpProvId.value);
        } finally {
            isKtpRegLoading.value = false;
        }
    } else {
        ktpRegencies.value = [];
    }
}

async function onKtpRegChange() {
    const reg = ktpRegencies.value.find((r) => r.id === selectedKtpRegId.value);
    // Simpan HANYA nama kabupaten/kota tanpa kata "Kabupaten" atau "Kota"
    form.kabupaten_ktp = reg ? formatKabupatenName(reg.name) : "";
    selectedKtpDistId.value = "";
    form.kecamatan_ktp = "";
    form.desa_kelurahan_ktp = "";
    ktpVillages.value = [];
    clearFieldError("kabupaten_ktp");
    if (selectedKtpRegId.value) {
        isKtpDistLoading.value = true;
        try {
            ktpDistricts.value = await getDistricts(selectedKtpRegId.value);
        } finally {
            isKtpDistLoading.value = false;
        }
    } else {
        ktpDistricts.value = [];
    }
}

async function onKtpDistChange() {
    const dist = ktpDistricts.value.find(
        (d) => d.id === selectedKtpDistId.value,
    );
    form.kecamatan_ktp = dist ? formatWilayahName(dist.name) : "";
    form.desa_kelurahan_ktp = "";
    clearFieldError("kecamatan_ktp");
    if (selectedKtpDistId.value) {
        isKtpVillLoading.value = true;
        try {
            ktpVillages.value = await getVillages(selectedKtpDistId.value);
        } finally {
            isKtpVillLoading.value = false;
        }
    } else {
        ktpVillages.value = [];
    }
}

function onKtpVillChange(val) {
    form.desa_kelurahan_ktp = formatWilayahName(val);
    clearFieldError("desa_kelurahan_ktp");
}

// Domisili handlers
async function onDomProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedDomProvId.value,
    );
    form.provinsi_domisili = prov ? formatWilayahName(prov.name) : "";
    selectedDomRegId.value = "";
    selectedDomDistId.value = "";
    form.kabupaten_domisili = "";
    form.kecamatan_domisili = "";
    form.desa_kelurahan_domisili = "";
    domDistricts.value = [];
    domVillages.value = [];
    clearFieldError("provinsi_domisili");
    if (selectedDomProvId.value) {
        isDomRegLoading.value = true;
        try {
            domRegencies.value = await getRegencies(selectedDomProvId.value);
        } finally {
            isDomRegLoading.value = false;
        }
    } else {
        domRegencies.value = [];
    }
}

async function onDomRegChange() {
    const reg = domRegencies.value.find((r) => r.id === selectedDomRegId.value);
    // Simpan HANYA nama kabupaten/kota tanpa kata "Kabupaten" atau "Kota"
    form.kabupaten_domisili = reg ? formatKabupatenName(reg.name) : "";
    selectedDomDistId.value = "";
    form.kecamatan_domisili = "";
    form.desa_kelurahan_domisili = "";
    domVillages.value = [];
    clearFieldError("kabupaten_domisili");
    if (selectedDomRegId.value) {
        isDomDistLoading.value = true;
        try {
            domDistricts.value = await getDistricts(selectedDomRegId.value);
        } finally {
            isDomDistLoading.value = false;
        }
    } else {
        domDistricts.value = [];
    }
}

async function onDomDistChange() {
    const dist = domDistricts.value.find(
        (d) => d.id === selectedDomDistId.value,
    );
    form.kecamatan_domisili = dist ? formatWilayahName(dist.name) : "";
    form.desa_kelurahan_domisili = "";
    clearFieldError("kecamatan_domisili");
    if (selectedDomDistId.value) {
        isDomVillLoading.value = true;
        try {
            domVillages.value = await getVillages(selectedDomDistId.value);
        } finally {
            isDomVillLoading.value = false;
        }
    } else {
        domVillages.value = [];
    }
}

function onDomVillChange(val) {
    form.desa_kelurahan_domisili = formatWilayahName(val);
    clearFieldError("desa_kelurahan_domisili");
}

// Copy KTP to Domisili
async function copyKtpToDomisili() {
    if (!selectedKtpProvId.value) return;
    selectedDomProvId.value = selectedKtpProvId.value;
    await onDomProvChange();

    if (selectedKtpRegId.value) {
        selectedDomRegId.value = selectedKtpRegId.value;
        await onDomRegChange();
    }

    if (selectedKtpDistId.value) {
        selectedDomDistId.value = selectedKtpDistId.value;
        await onDomDistChange();
    }

    if (form.desa_kelurahan_ktp) {
        form.desa_kelurahan_domisili = form.desa_kelurahan_ktp;
    }

    if (form.kode_pos_ktp) {
        form.kode_pos_domisili = form.kode_pos_ktp;
    }

    if (form.alamat_lengkap_ktp) {
        form.alamat_lengkap_domisili = form.alamat_lengkap_ktp;
        clearFieldError("alamat_lengkap_domisili");
    }
}

// Unit SPPG handlers
async function onUnitProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedUnitProvId.value,
    );
    form.unit_provinsi = prov ? formatWilayahName(prov.name) : "";
    selectedUnitRegId.value = "";
    selectedUnitDistId.value = "";
    form.unit_kabupaten = "";
    form.unit_kecamatan = "";
    form.unit_desa_kelurahan = "";
    unitDistricts.value = [];
    unitVillages.value = [];
    clearFieldError("unit_provinsi");
    if (selectedUnitProvId.value) {
        isUnitRegLoading.value = true;
        try {
            unitRegencies.value = await getRegencies(selectedUnitProvId.value);
        } finally {
            isUnitRegLoading.value = false;
        }
    } else {
        unitRegencies.value = [];
    }
}

async function onUnitRegChange() {
    const reg = unitRegencies.value.find(
        (r) => r.id === selectedUnitRegId.value,
    );
    // Simpan HANYA nama kabupaten/kota tanpa kata "Kabupaten" atau "Kota"
    form.unit_kabupaten = reg ? formatKabupatenName(reg.name) : "";
    selectedUnitDistId.value = "";
    form.unit_kecamatan = "";
    form.unit_desa_kelurahan = "";
    unitVillages.value = [];
    clearFieldError("unit_kabupaten");
    if (selectedUnitRegId.value) {
        isUnitDistLoading.value = true;
        try {
            unitDistricts.value = await getDistricts(selectedUnitRegId.value);
        } finally {
            isUnitDistLoading.value = false;
        }
    } else {
        unitDistricts.value = [];
    }
}

async function onUnitDistChange() {
    const dist = unitDistricts.value.find(
        (d) => d.id === selectedUnitDistId.value,
    );
    form.unit_kecamatan = dist ? formatWilayahName(dist.name) : "";
    form.unit_desa_kelurahan = "";
    clearFieldError("unit_kecamatan");
    if (selectedUnitDistId.value) {
        isUnitVillLoading.value = true;
        try {
            unitVillages.value = await getVillages(selectedUnitDistId.value);
        } finally {
            isUnitVillLoading.value = false;
        }
    } else {
        unitVillages.value = [];
    }
}

function onUnitVillChange(val) {
    form.unit_desa_kelurahan = formatWilayahName(val);
    clearFieldError("unit_desa_kelurahan");
}

function scrollToFirstError(errorsObj) {
    nextTick(() => {
        const errorKeys = Object.keys(errorsObj);
        if (errorKeys.length === 0) return;
        const firstKey = errorKeys[0];
        const el =
            document.getElementById(firstKey) ||
            document.querySelector(`[name="${firstKey}"]`);
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "center" });
            if (typeof el.focus === "function") el.focus();
        }
    });
}

// Client validation Step 1 (Profil Kepala SPPG)
function validateStep1() {
    const errors = {};

    if (!form.nik) {
        errors.nik = "NIK wajib diisi.";
    } else if (!/^\d{16}$/.test(form.nik)) {
        errors.nik = "NIK harus berupa 16 digit angka.";
    }

    if (!form.nama || !form.nama.trim()) {
        errors.nama = "Nama lengkap wajib diisi.";
    }

    if (!form.agama) {
        errors.agama = "Pilih salah satu agama.";
    }

    if (!form.jenis_kelamin) {
        errors.jenis_kelamin = "Pilih jenis kelamin (L/P).";
    }

    if (!form.tempat_lahir || !form.tempat_lahir.trim()) {
        errors.tempat_lahir = "Tempat lahir wajib diisi.";
    }

    if (!form.tanggal_lahir) {
        errors.tanggal_lahir = "Tanggal lahir wajib diisi.";
    }

    if (!form.jenjang_pendidikan) {
        errors.jenjang_pendidikan = "Pilih jenjang pendidikan.";
    }

    if (!form.bidang_pendidikan || !form.bidang_pendidikan.trim()) {
        errors.bidang_pendidikan = "Bidang pendidikan / jurusan wajib diisi.";
    }

    if (!form.status_kawin) {
        errors.status_kawin = "Pilih status perkawinan.";
    }

    if (!form.provinsi_ktp) {
        errors.provinsi_ktp = "Pilih provinsi sesuai KTP.";
    }
    if (!form.kabupaten_ktp) {
        errors.kabupaten_ktp = "Pilih kabupaten/kota sesuai KTP.";
    }
    if (!form.kecamatan_ktp) {
        errors.kecamatan_ktp = "Pilih kecamatan sesuai KTP.";
    }
    if (!form.desa_kelurahan_ktp) {
        errors.desa_kelurahan_ktp = "Pilih desa/kelurahan sesuai KTP.";
    }
    if (!form.kode_pos_ktp) {
        errors.kode_pos_ktp = "Kode pos KTP wajib diisi.";
    } else if (!/^\d{5}$/.test(form.kode_pos_ktp)) {
        errors.kode_pos_ktp = "Kode pos KTP harus berupa 5 digit angka.";
    }
    if (!form.alamat_lengkap_ktp || !form.alamat_lengkap_ktp.trim()) {
        errors.alamat_lengkap_ktp = "Alamat lengkap sesuai KTP wajib diisi.";
    }

    if (!form.provinsi_domisili) {
        errors.provinsi_domisili = "Pilih provinsi domisili.";
    }
    if (!form.kabupaten_domisili) {
        errors.kabupaten_domisili = "Pilih kabupaten/kota domisili.";
    }
    if (!form.kecamatan_domisili) {
        errors.kecamatan_domisili = "Pilih kecamatan domisili.";
    }
    if (!form.desa_kelurahan_domisili) {
        errors.desa_kelurahan_domisili = "Pilih desa/kelurahan domisili.";
    }
    if (!form.kode_pos_domisili) {
        errors.kode_pos_domisili = "Kode pos domisili wajib diisi.";
    } else if (!/^\d{5}$/.test(form.kode_pos_domisili)) {
        errors.kode_pos_domisili =
            "Kode pos domisili harus berupa 5 digit angka.";
    }
    if (!form.alamat_lengkap_domisili || !form.alamat_lengkap_domisili.trim()) {
        errors.alamat_lengkap_domisili = "Alamat lengkap domisili wajib diisi.";
    }

    if (!form.latitude_domisili || !form.longitude_domisili) {
        errors.latitude_domisili =
            "Titik koordinat domisili wajib ditentukan pada peta.";
    }

    if (!form.telepon) {
        errors.telepon = "Nomor telepon wajib diisi.";
    } else if (!/^62\d{8,15}$/.test(form.telepon)) {
        errors.telepon =
            "Format telepon tidak valid (minimal 8-15 digit angka setelah +62).";
    }

    if (!form.email) {
        errors.email = "Email wajib diisi.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        errors.email = "Format email tidak valid.";
    }

    if (!form.password) {
        errors.password = "Password wajib diisi.";
    } else if (form.password.length < 8) {
        errors.password = "Password minimal 8 karakter.";
    }

    if (!form.password_confirmation) {
        errors.password_confirmation = "Konfirmasi password wajib diisi.";
    } else if (form.password !== form.password_confirmation) {
        errors.password_confirmation =
            "Konfirmasi password tidak cocok dengan password.";
    }

    clientErrors.value = errors;
    if (Object.keys(errors).length > 0) {
        scrollToFirstError(errors);
        return false;
    }
    return true;
}

// Client validation Step 2 (Data Unit SPPG)
function validateStep2() {
    const errors = {};

    if (!form.id_sppg) {
        errors.id_sppg = "ID SPPG wajib diisi.";
    } else if (
        form.id_sppg.length !== 8 ||
        !/^[A-Z0-9]{8}$/i.test(form.id_sppg)
    ) {
        errors.id_sppg = "ID SPPG harus tepat 8 karakter huruf dan angka.";
    }

    if (!form.kode_sppg || !form.kode_sppg.trim()) {
        errors.kode_sppg = "Kode unit SPPG wajib diisi.";
    }

    if (!form.unit_nama || !form.unit_nama.trim()) {
        errors.unit_nama = "Nama unit SPPG wajib diisi.";
    }

    if (!form.status) {
        errors.status = "Pilih status operasional unit SPPG.";
    }

    if (!form.unit_provinsi) {
        errors.unit_provinsi = "Pilih provinsi unit SPPG.";
    }
    if (!form.unit_kabupaten) {
        errors.unit_kabupaten = "Pilih kabupaten/kota unit SPPG.";
    }
    if (!form.unit_kecamatan) {
        errors.unit_kecamatan = "Pilih kecamatan unit SPPG.";
    }
    if (!form.unit_desa_kelurahan) {
        errors.unit_desa_kelurahan = "Pilih desa/kelurahan unit SPPG.";
    }

    if (!form.kode_pos) {
        errors.kode_pos = "Kode pos wajib diisi.";
    } else if (!/^\d+$/.test(form.kode_pos)) {
        errors.kode_pos = "Kode pos hanya berupa angka.";
    }

    if (!form.alamat_lengkap || !form.alamat_lengkap.trim()) {
        errors.alamat_lengkap = "Alamat lengkap unit SPPG wajib diisi.";
    }

    if (!form.unit_latitude || !form.unit_longitude) {
        errors.unit_latitude =
            "Titik koordinat lokasi unit SPPG wajib ditentukan pada peta.";
    }

    clientErrors.value = errors;
    if (Object.keys(errors).length > 0) {
        scrollToFirstError(errors);
        return false;
    }
    return true;
}

function goToStep(step) {
    if (step === 1) {
        currentStep.value = 1;
        triggerMapRefresh();
    } else if (step === 2 && currentStep.value > 2) {
        currentStep.value = 2;
        triggerMapRefresh();
    }
}

function nextStep() {
    if (currentStep.value === 1) {
        if (validateStep1()) {
            clientErrors.value = {};
            currentStep.value = 2;
            triggerMapRefresh();
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    } else if (currentStep.value === 2) {
        if (validateStep2()) {
            clientErrors.value = {};
            currentStep.value = 3;
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value -= 1;
        triggerMapRefresh();
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
}

function submit() {
    if (!isAgreed.value) {
        return;
    }
    form.post(route("register"), {
        onError: (errors) => {
            scrollToFirstError(errors);
        },
        onFinish: () => form.reset("password", "password_confirmation"),
    });
}
</script>

<template>
    <Head title="Daftar ke Sistem" />

    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 via-slate-100/60 to-blue-50/40 py-8 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <Link
                    :href="'/'"
                    class="inline-flex items-center gap-2.5 mb-2 group"
                >
                    <div
                        class="h-10 w-10 rounded-xl bg-primary text-white flex items-center justify-center shadow-md shadow-primary/25"
                    >
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="text-left">
                        <span
                            class="font-extrabold text-xl tracking-tight text-slate-900 leading-tight block"
                            >SIPEGE</span
                        >
                        <span
                            class="text-xs text-slate-500 font-medium leading-none"
                            >Sistem Pengelolaan SPPG</span
                        >
                    </div>
                </Link>
                <h1
                    class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight"
                >
                    Formulir Pendaftaran
                </h1>
                <p class="text-sm text-slate-500 max-w-lg mx-auto mt-1">
                    Lengkapi data pengguna dan data unit operasional untuk
                    verifikasi sistem
                </p>
            </div>

            <!-- Stepper / Indicator -->
            <div class="mb-8 max-w-2xl mx-auto">
                <div class="grid grid-cols-3 gap-2 sm:gap-4 relative">
                    <!-- Step 1 -->
                    <div
                        @click="currentStep > 1 ? goToStep(1) : null"
                        :class="[
                            'flex flex-col sm:flex-row items-center gap-2 p-3 rounded-xl border text-left transition-all duration-200',
                            currentStep === 1
                                ? 'bg-white border-primary shadow-sm ring-2 ring-primary/10'
                                : currentStep > 1
                                  ? 'bg-emerald-50/60 border-emerald-200 text-emerald-900 cursor-pointer'
                                  : 'bg-white/60 border-slate-200 opacity-60',
                        ]"
                    >
                        <div
                            :class="[
                                'h-8 w-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0',
                                currentStep === 1
                                    ? 'bg-primary text-white'
                                    : currentStep > 1
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-slate-200 text-slate-600',
                            ]"
                        >
                            <CheckCircle
                                v-if="currentStep > 1"
                                class="h-4 w-4"
                            />
                            <span v-else>1</span>
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="text-xs font-bold leading-tight">
                                Data Pengguna
                            </p>
                            <p
                                class="text-[10px] text-slate-500 hidden sm:block"
                            >
                                Informasi Identitas Diri
                            </p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div
                        @click="currentStep > 2 ? goToStep(2) : null"
                        :class="[
                            'flex flex-col sm:flex-row items-center gap-2 p-3 rounded-xl border text-left transition-all duration-200',
                            currentStep === 2
                                ? 'bg-white border-primary shadow-sm ring-2 ring-primary/10'
                                : currentStep > 2
                                  ? 'bg-emerald-50/60 border-emerald-200 text-emerald-900 cursor-pointer'
                                  : 'bg-white/60 border-slate-200 opacity-60',
                        ]"
                    >
                        <div
                            :class="[
                                'h-8 w-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0',
                                currentStep === 2
                                    ? 'bg-primary text-white'
                                    : currentStep > 2
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-slate-200 text-slate-600',
                            ]"
                        >
                            <CheckCircle
                                v-if="currentStep > 2"
                                class="h-4 w-4"
                            />
                            <span v-else>2</span>
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="text-xs font-bold leading-tight">
                                Data SPPG
                            </p>
                            <p
                                class="text-[10px] text-slate-500 hidden sm:block"
                            >
                                Informasi Unit SPPG
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div
                        :class="[
                            'flex flex-col sm:flex-row items-center gap-2 p-3 rounded-xl border text-left transition-all duration-200',
                            currentStep === 3
                                ? 'bg-white border-primary shadow-sm ring-2 ring-primary/10'
                                : 'bg-white/60 border-slate-200 opacity-60',
                        ]"
                    >
                        <div
                            :class="[
                                'h-8 w-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0',
                                currentStep === 3
                                    ? 'bg-primary text-white'
                                    : 'bg-slate-200 text-slate-600',
                            ]"
                        >
                            3
                        </div>
                        <div class="text-center sm:text-left">
                            <p class="text-xs font-bold leading-tight">
                                Konfirmasi
                            </p>
                            <p
                                class="text-[10px] text-slate-500 hidden sm:block"
                            >
                                Review & Kirim
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <Card className="shadow-xl border-slate-200/90 bg-white">
                <form @submit.prevent="submit">
                    <!-- ================= STEP 1: PROFIL PENGGUNA (KEPALA SPPG) ================= -->
                    <div
                        v-show="currentStep === 1"
                        class="p-6 sm:p-8 space-y-8"
                    >
                        <!-- Section Header -->
                        <div class="border-b border-slate-100 pb-4">
                            <Badge variant="secondary" className="mb-1 text-xs"
                                >Langkah 1 dari 3</Badge
                            >
                            <h2
                                class="text-xl font-bold text-slate-900 flex items-center gap-2"
                            >
                                <User class="h-5 w-5 text-primary" />
                                <span>Data Pengguna</span>
                            </h2>
                            <p class="text-xs text-slate-500">
                                Informasi identitas pribadi dan domisili
                                penanggung jawab unit SPPG
                            </p>
                        </div>

                        <!-- Sub: Identitas Utama -->
                        <div class="space-y-4">
                            <h3
                                class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px] bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                A. Identitas Pribadi
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- NIK (16 digit angka, wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="nik" :required="true"
                                        >NIK (Nomor Induk Kependudukan)</Label
                                    >
                                    <Input
                                        id="nik"
                                        v-model="form.nik"
                                        maxlength="16"
                                        placeholder="16 digit angka NIK KTP"
                                        required
                                        :className="
                                            getFieldError('nik')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.nik = form.nik
                                                .replace(/\D/g, '')
                                                .slice(0, 16);
                                            clearFieldError('nik');
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Tepat 16 digit angka ({{
                                            form.nik.length
                                        }}/16)
                                    </p>
                                    <p
                                        v-if="getFieldError('nik')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("nik") }}
                                    </p>
                                </div>

                                <!-- NIP (angka, opsional) -->
                                <div class="space-y-1.5">
                                    <Label for="nip"
                                        >NIP (Nomor Induk Pegawai)</Label
                                    >
                                    <Input
                                        id="nip"
                                        v-model="form.nip"
                                        placeholder="Contoh: 199005152015031001"
                                        :className="
                                            getFieldError('nip')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.nip = form.nip.replace(
                                                /\D/g,
                                                '',
                                            );
                                            clearFieldError('nip');
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Opsional, hanya angka jika ada
                                    </p>
                                    <p
                                        v-if="getFieldError('nip')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("nip") }}
                                    </p>
                                </div>

                                <!-- Nama Lengkap (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="nama" :required="true"
                                        >Nama Lengkap (tanpa gelar)</Label
                                    >
                                    <Input
                                        id="nama"
                                        v-model="form.nama"
                                        placeholder="Nama lengkap sesuai KTP"
                                        required
                                        :className="
                                            getFieldError('nama')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="clearFieldError('nama')"
                                    />
                                    <p
                                        v-if="getFieldError('nama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("nama") }}
                                    </p>
                                </div>

                                <!-- Gelar Depan (opsional) -->
                                <div class="space-y-1.5">
                                    <Label for="gelar_depan">Gelar Depan</Label>
                                    <Input
                                        id="gelar_depan"
                                        v-model="form.gelar_depan"
                                        placeholder="Contoh: Ir., Dr., Drs."
                                    />
                                </div>

                                <!-- Gelar Belakang (opsional) -->
                                <div class="space-y-1.5">
                                    <Label for="gelar_belakang"
                                        >Gelar Belakang</Label
                                    >
                                    <Input
                                        id="gelar_belakang"
                                        v-model="form.gelar_belakang"
                                        placeholder="Contoh: S.T., M.T., M.Kom."
                                    />
                                </div>

                                <!-- Agama (wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="agama" :required="true"
                                        >Agama</Label
                                    >
                                    <Select
                                        id="agama"
                                        v-model="form.agama"
                                        :required="true"
                                        placeholder="Pilih Agama"
                                        :className="
                                            getFieldError('agama')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @update:modelValue="
                                            clearFieldError('agama')
                                        "
                                    >
                                        <option value="Islam">Islam</option>
                                        <option value="Kristen Protestan">
                                            Kristen Protestan
                                        </option>
                                        <option value="Katolik">Katolik</option>
                                        <option value="Hindu">Hindu</option>
                                        <option value="Buddha">Buddha</option>
                                        <option value="Khonghucu">
                                            Khonghucu
                                        </option>
                                        <option value="Lainnya">Lainnya</option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('agama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("agama") }}
                                    </p>
                                </div>

                                <!-- Jenis Kelamin (L/P, wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="jenis_kelamin" :required="true"
                                        >Jenis Kelamin</Label
                                    >
                                    <Select
                                        id="jenis_kelamin"
                                        v-model="form.jenis_kelamin"
                                        :required="true"
                                        placeholder="Pilih Jenis Kelamin"
                                        :className="
                                            getFieldError('jenis_kelamin')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @update:modelValue="
                                            clearFieldError('jenis_kelamin')
                                        "
                                    >
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('jenis_kelamin')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("jenis_kelamin") }}
                                    </p>
                                </div>

                                <!-- Tempat Lahir (hanya huruf, wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="tempat_lahir" :required="true"
                                        >Tempat Lahir</Label
                                    >
                                    <Input
                                        id="tempat_lahir"
                                        v-model="form.tempat_lahir"
                                        placeholder="Contoh: Denpasar"
                                        required
                                        :className="
                                            getFieldError('tempat_lahir')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.tempat_lahir =
                                                form.tempat_lahir.replace(
                                                    /[^a-zA-Z\s\.\,\-]/g,
                                                    '',
                                                );
                                            clearFieldError('tempat_lahir');
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Hanya huruf
                                    </p>
                                    <p
                                        v-if="getFieldError('tempat_lahir')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("tempat_lahir") }}
                                    </p>
                                </div>

                                <!-- Tanggal Lahir (wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="tanggal_lahir" :required="true"
                                        >Tanggal Lahir</Label
                                    >
                                    <Input
                                        id="tanggal_lahir"
                                        type="date"
                                        v-model="form.tanggal_lahir"
                                        required
                                        :className="
                                            getFieldError('tanggal_lahir')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            clearFieldError('tanggal_lahir')
                                        "
                                    />
                                    <p
                                        v-if="getFieldError('tanggal_lahir')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("tanggal_lahir") }}
                                    </p>
                                </div>

                                <!-- Jenjang Pendidikan (wajib) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="jenjang_pendidikan"
                                        :required="true"
                                        >Jenjang Pendidikan</Label
                                    >
                                    <Select
                                        id="jenjang_pendidikan"
                                        v-model="form.jenjang_pendidikan"
                                        :required="true"
                                        placeholder="Pilih Jenjang"
                                        :className="
                                            getFieldError('jenjang_pendidikan')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @update:modelValue="
                                            clearFieldError(
                                                'jenjang_pendidikan',
                                            )
                                        "
                                    >
                                        <option value="SMA/SMK">
                                            SMA / SMK / Sederajat
                                        </option>
                                        <option value="D-III">
                                            D-III (Diploma Tiga)
                                        </option>
                                        <option value="D-IV">
                                            D-IV (Diploma Empat)
                                        </option>
                                        <option value="S-I">
                                            S-I (Sarjana)
                                        </option>
                                        <option value="S-II">
                                            S-II (Magister)
                                        </option>
                                        <option value="S-III">
                                            S-III (Doktor)
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('jenjang_pendidikan')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("jenjang_pendidikan")
                                        }}
                                    </p>
                                </div>

                                <!-- Bidang Pendidikan (wajib) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="bidang_pendidikan"
                                        :required="true"
                                        >Bidang Pendidikan / Jurusan</Label
                                    >
                                    <Input
                                        id="bidang_pendidikan"
                                        v-model="form.bidang_pendidikan"
                                        placeholder="Contoh: Teknik Informatika / Manajemen"
                                        required
                                        :className="
                                            getFieldError('bidang_pendidikan')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            clearFieldError('bidang_pendidikan')
                                        "
                                    />
                                    <p
                                        v-if="
                                            getFieldError('bidang_pendidikan')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("bidang_pendidikan") }}
                                    </p>
                                </div>

                                <!-- Status Kawin (Belum Menikah, Menikah, Janda, Duda, wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="status_kawin" :required="true"
                                        >Status Perkawinan</Label
                                    >
                                    <Select
                                        id="status_kawin"
                                        v-model="form.status_kawin"
                                        :required="true"
                                        placeholder="Pilih Status Perkawinan"
                                        :className="
                                            getFieldError('status_kawin')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @update:modelValue="
                                            clearFieldError('status_kawin')
                                        "
                                    >
                                        <option value="Belum Menikah">
                                            Belum Menikah
                                        </option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Janda">Janda</option>
                                        <option value="Duda">Duda</option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('status_kawin')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("status_kawin") }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sub: Alamat KTP -->
                        <div class="space-y-4 pt-2">
                            <h3
                                class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px] bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                B. Alamat Sesuai KTP
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Provinsi KTP -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="provinsi_ktp"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Provinsi KTP</span>
                                        <Loader2
                                            v-if="isProvincesLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="provinsi_ktp"
                                        v-model="selectedKtpProvId"
                                        @update:modelValue="onKtpProvChange"
                                        :disabled="isProvincesLoading"
                                        :required="true"
                                        :placeholder="
                                            isProvincesLoading
                                                ? 'Memuat data provinsi...'
                                                : 'Pilih Provinsi'
                                        "
                                        :className="
                                            getFieldError('provinsi_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="prov in provincesList"
                                            :key="prov.id"
                                            :value="prov.id"
                                        >
                                            {{ formatWilayahName(prov.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('provinsi_ktp')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("provinsi_ktp") }}
                                    </p>
                                </div>

                                <!-- Kabupaten KTP (Hanya nama langsung tanpa kata 'Kabupaten' / 'Kota') -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="kabupaten_ktp"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kabupaten / Kota KTP</span>
                                        <Loader2
                                            v-if="isKtpRegLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="kabupaten_ktp"
                                        v-model="selectedKtpRegId"
                                        @update:modelValue="onKtpRegChange"
                                        :disabled="
                                            !selectedKtpProvId ||
                                            isKtpRegLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isKtpRegLoading
                                                ? 'Memuat kabupaten/kota...'
                                                : 'Pilih Kabupaten/Kota'
                                        "
                                        :className="
                                            getFieldError('kabupaten_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="reg in ktpRegencies"
                                            :key="reg.id"
                                            :value="reg.id"
                                        >
                                            {{ formatKabupatenName(reg.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('kabupaten_ktp')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kabupaten_ktp") }}
                                    </p>
                                </div>

                                <!-- Kecamatan KTP -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="kecamatan_ktp"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kecamatan KTP</span>
                                        <Loader2
                                            v-if="isKtpDistLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="kecamatan_ktp"
                                        v-model="selectedKtpDistId"
                                        @update:modelValue="onKtpDistChange"
                                        :disabled="
                                            !selectedKtpRegId ||
                                            isKtpDistLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isKtpDistLoading
                                                ? 'Memuat kecamatan...'
                                                : 'Pilih Kecamatan'
                                        "
                                        :className="
                                            getFieldError('kecamatan_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="dist in ktpDistricts"
                                            :key="dist.id"
                                            :value="dist.id"
                                        >
                                            {{ formatWilayahName(dist.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('kecamatan_ktp')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kecamatan_ktp") }}
                                    </p>
                                </div>

                                <!-- Desa / Kelurahan KTP -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="desa_kelurahan_ktp"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Desa / Kelurahan KTP</span>
                                        <Loader2
                                            v-if="isKtpVillLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="desa_kelurahan_ktp"
                                        v-model="form.desa_kelurahan_ktp"
                                        @update:modelValue="onKtpVillChange"
                                        :disabled="
                                            !selectedKtpDistId ||
                                            isKtpVillLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isKtpVillLoading
                                                ? 'Memuat desa/kelurahan...'
                                                : 'Pilih Desa/Kelurahan'
                                        "
                                        :className="
                                            getFieldError('desa_kelurahan_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="vill in ktpVillages"
                                            :key="vill.id"
                                            :value="
                                                formatWilayahName(vill.name)
                                            "
                                        >
                                            {{ formatWilayahName(vill.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('desa_kelurahan_ktp')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("desa_kelurahan_ktp")
                                        }}
                                    </p>
                                </div>

                                <!-- Kode Pos KTP (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="kode_pos_ktp" :required="true"
                                        >Kode Pos KTP</Label
                                    >
                                    <Input
                                        id="kode_pos_ktp"
                                        v-model="form.kode_pos_ktp"
                                        placeholder="5 digit kode pos (contoh: 81161)"
                                        required
                                        :className="
                                            getFieldError('kode_pos_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.kode_pos_ktp =
                                                form.kode_pos_ktp
                                                    .replace(/\D/g, '')
                                                    .slice(0, 5);
                                            clearFieldError('kode_pos_ktp');
                                        "
                                    />
                                    <p
                                        v-if="getFieldError('kode_pos_ktp')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kode_pos_ktp") }}
                                    </p>
                                </div>

                                <!-- Alamat Lengkap KTP (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label
                                        for="alamat_lengkap_ktp"
                                        :required="true"
                                        >Alamat Lengkap Sesuai KTP</Label
                                    >
                                    <textarea
                                        id="alamat_lengkap_ktp"
                                        v-model="form.alamat_lengkap_ktp"
                                        rows="2"
                                        placeholder="Nama jalan, nomor rumah, RT/RW, gang..."
                                        required
                                        :class="
                                            'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                            (getFieldError('alamat_lengkap_ktp')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : 'border-input')
                                        "
                                        @input="
                                            clearFieldError(
                                                'alamat_lengkap_ktp',
                                            )
                                        "
                                    ></textarea>
                                    <p
                                        v-if="
                                            getFieldError('alamat_lengkap_ktp')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("alamat_lengkap_ktp")
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sub: Alamat Domisili & Peta -->
                        <div class="space-y-4 pt-2">
                            <div
                                class="flex items-center justify-between bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                <h3
                                    class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px]"
                                >
                                    C. Alamat Domisili & Titik Peta
                                </h3>
                                <button
                                    type="button"
                                    @click="copyKtpToDomisili"
                                    :disabled="!selectedKtpProvId"
                                    class="text-[11px] font-semibold text-primary hover:underline flex items-center gap-1 cursor-pointer disabled:opacity-40"
                                >
                                    <Copy class="h-3 w-3" />
                                    <span>Sama dengan KTP</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Provinsi Domisili -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="provinsi_domisili"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Provinsi Domisili</span>
                                        <Loader2
                                            v-if="isProvincesLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="provinsi_domisili"
                                        v-model="selectedDomProvId"
                                        @update:modelValue="onDomProvChange"
                                        :disabled="isProvincesLoading"
                                        :required="true"
                                        :placeholder="
                                            isProvincesLoading
                                                ? 'Memuat data provinsi...'
                                                : 'Pilih Provinsi'
                                        "
                                        :className="
                                            getFieldError('provinsi_domisili')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="prov in provincesList"
                                            :key="prov.id"
                                            :value="prov.id"
                                        >
                                            {{ formatWilayahName(prov.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('provinsi_domisili')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("provinsi_domisili") }}
                                    </p>
                                </div>

                                <!-- Kabupaten Domisili (Hanya nama langsung tanpa kata 'Kabupaten' / 'Kota') -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="kabupaten_domisili"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kabupaten / Kota Domisili</span>
                                        <Loader2
                                            v-if="isDomRegLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="kabupaten_domisili"
                                        v-model="selectedDomRegId"
                                        @update:modelValue="onDomRegChange"
                                        :disabled="
                                            !selectedDomProvId ||
                                            isDomRegLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isDomRegLoading
                                                ? 'Memuat kabupaten/kota...'
                                                : 'Pilih Kabupaten/Kota'
                                        "
                                        :className="
                                            getFieldError('kabupaten_domisili')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="reg in domRegencies"
                                            :key="reg.id"
                                            :value="reg.id"
                                        >
                                            {{ formatKabupatenName(reg.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('kabupaten_domisili')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("kabupaten_domisili")
                                        }}
                                    </p>
                                </div>

                                <!-- Kecamatan Domisili -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="kecamatan_domisili"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kecamatan Domisili</span>
                                        <Loader2
                                            v-if="isDomDistLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="kecamatan_domisili"
                                        v-model="selectedDomDistId"
                                        @update:modelValue="onDomDistChange"
                                        :disabled="
                                            !selectedDomRegId ||
                                            isDomDistLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isDomDistLoading
                                                ? 'Memuat kecamatan...'
                                                : 'Pilih Kecamatan'
                                        "
                                        :className="
                                            getFieldError('kecamatan_domisili')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="dist in domDistricts"
                                            :key="dist.id"
                                            :value="dist.id"
                                        >
                                            {{ formatWilayahName(dist.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('kecamatan_domisili')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("kecamatan_domisili")
                                        }}
                                    </p>
                                </div>

                                <!-- Desa Domisili -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="desa_kelurahan_domisili"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Desa / Kelurahan Domisili</span>
                                        <Loader2
                                            v-if="isDomVillLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="desa_kelurahan_domisili"
                                        v-model="form.desa_kelurahan_domisili"
                                        @update:modelValue="onDomVillChange"
                                        :disabled="
                                            !selectedDomDistId ||
                                            isDomVillLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isDomVillLoading
                                                ? 'Memuat desa/kelurahan...'
                                                : 'Pilih Desa/Kelurahan'
                                        "
                                        :className="
                                            getFieldError(
                                                'desa_kelurahan_domisili',
                                            )
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="vill in domVillages"
                                            :key="vill.id"
                                            :value="
                                                formatWilayahName(vill.name)
                                            "
                                        >
                                            {{ formatWilayahName(vill.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError(
                                                'desa_kelurahan_domisili',
                                            )
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError(
                                                "desa_kelurahan_domisili",
                                            )
                                        }}
                                    </p>
                                </div>

                                <!-- Kode Pos Domisili (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label
                                        for="kode_pos_domisili"
                                        :required="true"
                                        >Kode Pos Domisili</Label
                                    >
                                    <Input
                                        id="kode_pos_domisili"
                                        v-model="form.kode_pos_domisili"
                                        placeholder="5 digit kode pos (contoh: 81161)"
                                        required
                                        :className="
                                            getFieldError('kode_pos_domisili')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.kode_pos_domisili =
                                                form.kode_pos_domisili
                                                    .replace(/\D/g, '')
                                                    .slice(0, 5);
                                            clearFieldError(
                                                'kode_pos_domisili',
                                            );
                                        "
                                    />
                                    <p
                                        v-if="
                                            getFieldError('kode_pos_domisili')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kode_pos_domisili") }}
                                    </p>
                                </div>

                                <!-- Alamat Lengkap Domisili (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label
                                        for="alamat_lengkap_domisili"
                                        :required="true"
                                        >Alamat Lengkap Domisili</Label
                                    >
                                    <textarea
                                        id="alamat_lengkap_domisili"
                                        v-model="form.alamat_lengkap_domisili"
                                        rows="2"
                                        placeholder="Nama jalan, nomor rumah, RT/RW, patokan tempat tinggal saat ini..."
                                        required
                                        :class="
                                            'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                            (getFieldError(
                                                'alamat_lengkap_domisili',
                                            )
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : 'border-input')
                                        "
                                        @input="
                                            clearFieldError(
                                                'alamat_lengkap_domisili',
                                            )
                                        "
                                    ></textarea>
                                    <p
                                        v-if="
                                            getFieldError(
                                                'alamat_lengkap_domisili',
                                            )
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError(
                                                "alamat_lengkap_domisili",
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Map Picker Domisili -->
                            <div class="pt-2">
                                <MapPicker
                                    ref="domisiliMapRef"
                                    v-model:latitude="form.latitude_domisili"
                                    v-model:longitude="form.longitude_domisili"
                                    label="Titik Koordinat Lokasi Domisili"
                                    @update:latitude="
                                        clearFieldError('latitude_domisili')
                                    "
                                    @update:longitude="
                                        clearFieldError('latitude_domisili')
                                    "
                                />
                                <p
                                    v-if="getFieldError('latitude_domisili')"
                                    class="text-xs text-destructive font-medium flex items-center gap-1 mt-1.5"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ getFieldError("latitude_domisili") }}
                                </p>
                            </div>
                        </div>

                        <!-- Sub: Akun & Kontak -->
                        <div class="space-y-4 pt-2">
                            <h3
                                class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px] bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                D. Akun & Kontak
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Telepon (+62, langsung 85...) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="telepon" :required="true"
                                        >Nomor Telepon / WhatsApp</Label
                                    >
                                    <div class="flex rounded-md shadow-sm">
                                        <span
                                            class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-slate-100 text-slate-700 font-semibold text-sm select-none"
                                        >
                                            +62
                                        </span>
                                        <Input
                                            id="telepon"
                                            v-model="rawPhone"
                                            placeholder="Contoh: 85239182736 (tanpa 0 di depan)"
                                            required
                                            :className="
                                                'rounded-l-none ' +
                                                (getFieldError('telepon')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : '')
                                            "
                                        />
                                    </div>
                                    <p
                                        v-if="getFieldError('telepon')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("telepon") }}
                                    </p>
                                </div>

                                <!-- Email (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="email" :required="true"
                                        >Alamat Email</Label
                                    >
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
                                        >
                                            <Mail class="h-4 w-4" />
                                        </div>
                                        <Input
                                            id="email"
                                            type="email"
                                            v-model="form.email"
                                            placeholder="nama@domain.com"
                                            required
                                            autocomplete="username"
                                            :className="
                                                'pl-9 ' +
                                                (getFieldError('email')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : '')
                                            "
                                            @input="clearFieldError('email')"
                                        />
                                    </div>
                                    <p
                                        v-if="getFieldError('email')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("email") }}
                                    </p>
                                </div>

                                <!-- Password (wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="password" :required="true"
                                        >Password</Label
                                    >
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
                                        >
                                            <Lock class="h-4 w-4" />
                                        </div>
                                        <Input
                                            id="password"
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="form.password"
                                            placeholder="Minimal 8 karakter"
                                            required
                                            :className="
                                                'pl-9 pr-10 ' +
                                                (getFieldError('password')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : '')
                                            "
                                            @input="clearFieldError('password')"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showPassword = !showPassword
                                            "
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                            tabindex="-1"
                                        >
                                            <EyeOff
                                                v-if="showPassword"
                                                class="h-4 w-4"
                                            />
                                            <Eye v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p
                                        v-if="getFieldError('password')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("password") }}
                                    </p>
                                </div>

                                <!-- Password Confirmation (wajib) -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="password_confirmation"
                                        :required="true"
                                        >Konfirmasi Password</Label
                                    >
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400"
                                        >
                                            <Lock class="h-4 w-4" />
                                        </div>
                                        <Input
                                            id="password_confirmation"
                                            :type="
                                                showPasswordConfirmation
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="form.password_confirmation"
                                            placeholder="Ulangi password"
                                            required
                                            :className="
                                                'pl-9 pr-10 ' +
                                                (getFieldError(
                                                    'password_confirmation',
                                                )
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : '')
                                            "
                                            @input="
                                                clearFieldError(
                                                    'password_confirmation',
                                                )
                                            "
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                showPasswordConfirmation =
                                                    !showPasswordConfirmation
                                            "
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                            tabindex="-1"
                                        >
                                            <EyeOff
                                                v-if="showPasswordConfirmation"
                                                class="h-4 w-4"
                                            />
                                            <Eye v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p
                                        v-if="
                                            getFieldError(
                                                'password_confirmation',
                                            )
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError(
                                                "password_confirmation",
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Step 1 -->
                        <div
                            class="flex items-center justify-between pt-4 border-t border-slate-100"
                        >
                            <Link :href="route('login')">
                                <Button
                                    variant="ghost"
                                    className="text-slate-600 text-xs"
                                >
                                    Sudah punya akun? Masuk
                                </Button>
                            </Link>

                            <Button
                                type="button"
                                @click="nextStep"
                                className="flex items-center gap-2 px-6"
                            >
                                <span>Lanjut ke Data SPPG</span>
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- ================= STEP 2: DATA UNIT SPPG ================= -->
                    <div
                        v-show="currentStep === 2"
                        class="p-6 sm:p-8 space-y-8"
                    >
                        <!-- Section Header -->
                        <div class="border-b border-slate-100 pb-4">
                            <Badge variant="secondary" className="mb-1 text-xs"
                                >Langkah 2 dari 3</Badge
                            >
                            <h2
                                class="text-xl font-bold text-slate-900 flex items-center gap-2"
                            >
                                <Building2 class="h-5 w-5 text-primary" />
                                <span>1.2.2 Data Unit SPPG</span>
                            </h2>
                            <p class="text-xs text-slate-500">
                                Informasi profil unit dan lokasi titik
                                operasional SPPG
                            </p>
                        </div>

                        <!-- Sub: Informasi Unit -->
                        <div class="space-y-4">
                            <h3
                                class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px] bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                A. Informasi Utama Unit
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- ID SPPG (8 digit alfanumerik, diinput sendiri) -->
                                <div class="space-y-1.5">
                                    <Label for="id_sppg" :required="true"
                                        >ID SPPG</Label
                                    >
                                    <Input
                                        id="id_sppg"
                                        v-model="form.id_sppg"
                                        maxlength="8"
                                        placeholder="Contoh: SPPG8899"
                                        required
                                        :className="
                                            getFieldError('id_sppg')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.id_sppg = form.id_sppg
                                                .toUpperCase()
                                                .replace(/[^A-Z0-9]/g, '')
                                                .slice(0, 8);
                                            clearFieldError('id_sppg');
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        8 digit angka/huruf alfanumerik ({{
                                            form.id_sppg.length
                                        }}/8)
                                    </p>
                                    <p
                                        v-if="getFieldError('id_sppg')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("id_sppg") }}
                                    </p>
                                </div>

                                <!-- Kode SPPG (wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="kode_sppg" :required="true"
                                        >Kode Unit SPPG</Label
                                    >
                                    <Input
                                        id="kode_sppg"
                                        v-model="form.kode_sppg"
                                        placeholder="Contoh: UNIT-BALI-01"
                                        required
                                        :className="
                                            getFieldError('kode_sppg')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="clearFieldError('kode_sppg')"
                                    />
                                    <p
                                        v-if="getFieldError('kode_sppg')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kode_sppg") }}
                                    </p>
                                </div>

                                <!-- Nama Unit SPPG (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="unit_nama" :required="true"
                                        >Nama Unit SPPG</Label
                                    >
                                    <Input
                                        id="unit_nama"
                                        v-model="form.unit_nama"
                                        placeholder="Contoh: SPPG Sukasada Utama"
                                        required
                                        :className="
                                            getFieldError('unit_nama')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="clearFieldError('unit_nama')"
                                    />
                                    <p
                                        v-if="getFieldError('unit_nama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("unit_nama") }}
                                    </p>
                                </div>

                                <!-- Status Unit SPPG (wajib) -->
                                <div class="space-y-1.5">
                                    <Label for="status" :required="true"
                                        >Status Operasional</Label
                                    >
                                    <Select
                                        id="status"
                                        v-model="form.status"
                                        :required="true"
                                        :className="
                                            getFieldError('status')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @update:modelValue="
                                            clearFieldError('status')
                                        "
                                    >
                                        <option value="Belum Operasional">
                                            Belum Operasional
                                        </option>
                                        <option value="Operasional">
                                            Operasional
                                        </option>
                                        <option value="Suspend Ringan">
                                            Suspend Ringan
                                        </option>
                                        <option value="Suspend Sedang">
                                            Suspend Sedang
                                        </option>
                                        <option value="Suspend Berat">
                                            Suspend Berat
                                        </option>
                                        <option value="Suspend Permanen">
                                            Suspend Permanen
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('status')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("status") }}
                                    </p>
                                </div>

                                <!-- Tanggal Operasional (opsional) -->
                                <div class="space-y-1.5">
                                    <Label for="tanggal_operasional"
                                        >Tanggal Mulai Operasional</Label
                                    >
                                    <Input
                                        id="tanggal_operasional"
                                        type="date"
                                        v-model="form.tanggal_operasional"
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Opsional
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Sub: Wilayah Unit SPPG -->
                        <div class="space-y-4 pt-2">
                            <h3
                                class="text-sm font-bold text-slate-800 uppercase tracking-wider text-[11px] bg-slate-50 px-3 py-1.5 rounded-md border border-slate-100"
                            >
                                B. Wilayah & Peta Unit SPPG
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Provinsi Unit -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="unit_provinsi"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Provinsi</span>
                                        <Loader2
                                            v-if="isProvincesLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="unit_provinsi"
                                        v-model="selectedUnitProvId"
                                        @update:modelValue="onUnitProvChange"
                                        :disabled="isProvincesLoading"
                                        :required="true"
                                        :placeholder="
                                            isProvincesLoading
                                                ? 'Memuat data provinsi...'
                                                : 'Pilih Provinsi'
                                        "
                                        :className="
                                            getFieldError('unit_provinsi')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="prov in provincesList"
                                            :key="prov.id"
                                            :value="prov.id"
                                        >
                                            {{ formatWilayahName(prov.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('unit_provinsi')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("unit_provinsi") }}
                                    </p>
                                </div>

                                <!-- Kabupaten Unit (Hanya nama langsung tanpa kata 'Kabupaten' / 'Kota') -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="unit_kabupaten"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kabupaten / Kota</span>
                                        <Loader2
                                            v-if="isUnitRegLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="unit_kabupaten"
                                        v-model="selectedUnitRegId"
                                        @update:modelValue="onUnitRegChange"
                                        :disabled="
                                            !selectedUnitProvId ||
                                            isUnitRegLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isUnitRegLoading
                                                ? 'Memuat kabupaten/kota...'
                                                : 'Pilih Kabupaten/Kota'
                                        "
                                        :className="
                                            getFieldError('unit_kabupaten')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="reg in unitRegencies"
                                            :key="reg.id"
                                            :value="reg.id"
                                        >
                                            {{ formatKabupatenName(reg.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('unit_kabupaten')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("unit_kabupaten") }}
                                    </p>
                                </div>

                                <!-- Kecamatan Unit -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="unit_kecamatan"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Kecamatan</span>
                                        <Loader2
                                            v-if="isUnitDistLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="unit_kecamatan"
                                        v-model="selectedUnitDistId"
                                        @update:modelValue="onUnitDistChange"
                                        :disabled="
                                            !selectedUnitRegId ||
                                            isUnitDistLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isUnitDistLoading
                                                ? 'Memuat kecamatan...'
                                                : 'Pilih Kecamatan'
                                        "
                                        :className="
                                            getFieldError('unit_kecamatan')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="dist in unitDistricts"
                                            :key="dist.id"
                                            :value="dist.id"
                                        >
                                            {{ formatWilayahName(dist.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="getFieldError('unit_kecamatan')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("unit_kecamatan") }}
                                    </p>
                                </div>

                                <!-- Desa Unit -->
                                <div class="space-y-1.5">
                                    <Label
                                        for="unit_desa_kelurahan"
                                        :required="true"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Desa / Kelurahan</span>
                                        <Loader2
                                            v-if="isUnitVillLoading"
                                            class="h-3 w-3 animate-spin text-primary"
                                        />
                                    </Label>
                                    <Select
                                        id="unit_desa_kelurahan"
                                        v-model="form.unit_desa_kelurahan"
                                        @update:modelValue="onUnitVillChange"
                                        :disabled="
                                            !selectedUnitDistId ||
                                            isUnitVillLoading
                                        "
                                        :required="true"
                                        :placeholder="
                                            isUnitVillLoading
                                                ? 'Memuat desa/kelurahan...'
                                                : 'Pilih Desa/Kelurahan'
                                        "
                                        :className="
                                            getFieldError('unit_desa_kelurahan')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                    >
                                        <option
                                            v-for="vill in unitVillages"
                                            :key="vill.id"
                                            :value="
                                                formatWilayahName(vill.name)
                                            "
                                        >
                                            {{ formatWilayahName(vill.name) }}
                                        </option>
                                    </Select>
                                    <p
                                        v-if="
                                            getFieldError('unit_desa_kelurahan')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getFieldError("unit_desa_kelurahan")
                                        }}
                                    </p>
                                </div>

                                <!-- Kode Pos (hanya angka, wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="kode_pos" :required="true"
                                        >Kode Pos</Label
                                    >
                                    <Input
                                        id="kode_pos"
                                        v-model="form.kode_pos"
                                        placeholder="5 digit kode pos (contoh: 81161)"
                                        required
                                        :className="
                                            getFieldError('kode_pos')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        "
                                        @input="
                                            form.kode_pos = form.kode_pos
                                                .replace(/\D/g, '')
                                                .slice(0, 5);
                                            clearFieldError('kode_pos');
                                        "
                                    />
                                    <p
                                        v-if="getFieldError('kode_pos')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("kode_pos") }}
                                    </p>
                                </div>

                                <!-- Alamat Lengkap (wajib) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="alamat_lengkap" :required="true"
                                        >Alamat Lengkap Unit SPPG</Label
                                    >
                                    <textarea
                                        id="alamat_lengkap"
                                        v-model="form.alamat_lengkap"
                                        rows="3"
                                        placeholder="Nama jalan, nomor bangunan, RT/RW, patokan lokasi..."
                                        required
                                        :class="
                                            'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                            (getFieldError('alamat_lengkap')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : 'border-input')
                                        "
                                        @input="
                                            clearFieldError('alamat_lengkap')
                                        "
                                    ></textarea>
                                    <p
                                        v-if="getFieldError('alamat_lengkap')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getFieldError("alamat_lengkap") }}
                                    </p>
                                </div>
                            </div>

                            <!-- Map Picker Unit SPPG -->
                            <div class="pt-2">
                                <MapPicker
                                    ref="unitMapRef"
                                    v-model:latitude="form.unit_latitude"
                                    v-model:longitude="form.unit_longitude"
                                    label="Titik Koordinat Lokasi Unit SPPG"
                                    @update:latitude="
                                        clearFieldError('unit_latitude')
                                    "
                                    @update:longitude="
                                        clearFieldError('unit_latitude')
                                    "
                                />
                                <p
                                    v-if="getFieldError('unit_latitude')"
                                    class="text-xs text-destructive font-medium flex items-center gap-1 mt-1.5"
                                >
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ getFieldError("unit_latitude") }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer Step 2 -->
                        <div
                            class="flex items-center justify-between pt-4 border-t border-slate-100"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="prevStep"
                                className="flex items-center gap-2"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali</span>
                            </Button>

                            <Button
                                type="button"
                                @click="nextStep"
                                className="flex items-center gap-2 px-6"
                            >
                                <span>Tinjau Ringkasan Data</span>
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- ================= STEP 3: REVIEW & KONFIRMASI ================= -->
                    <div
                        v-show="currentStep === 3"
                        class="p-6 sm:p-8 space-y-6"
                    >
                        <div class="border-b border-slate-100 pb-4">
                            <Badge variant="secondary" className="mb-1 text-xs"
                                >Langkah 3 dari 3</Badge
                            >
                            <h2
                                class="text-xl font-bold text-slate-900 flex items-center gap-2"
                            >
                                <CheckCircle class="h-5 w-5 text-emerald-600" />
                                <span>Konfirmasi & Pendaftaran</span>
                            </h2>
                            <p class="text-xs text-slate-500">
                                Periksa kembali data Anda sebelum mengirimkan
                                formulir pendaftaran
                            </p>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Ringkasan Kepala SPPG -->
                            <Card className="border-slate-200 bg-slate-50/50">
                                <CardHeader
                                    className="p-4 pb-2 border-b border-slate-100"
                                >
                                    <CardTitle
                                        className="text-sm font-bold flex items-center justify-between"
                                    >
                                        <span class="flex items-center gap-2">
                                            <User
                                                class="h-4 w-4 text-primary"
                                            />
                                            Profil Kepala SPPG
                                        </span>
                                        <button
                                            type="button"
                                            @click="goToStep(1)"
                                            class="text-xs text-primary font-normal hover:underline cursor-pointer"
                                        >
                                            Ubah
                                        </button>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent
                                    className="p-4 space-y-2 text-xs text-slate-700"
                                >
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500">NIK:</span>
                                        <span class="font-mono font-semibold">{{
                                            form.nik
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Nama:</span
                                        >
                                        <span class="font-semibold">{{
                                            formatNamaLengkap(
                                                form.nama,
                                                form.gelar_depan,
                                                form.gelar_belakang,
                                            )
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >TTL / JK:</span
                                        >
                                        <span
                                            >{{
                                                form.tempat_lahir
                                                    ? `${form.tempat_lahir}, `
                                                    : ""
                                            }}{{
                                                formatTanggalIndo(
                                                    form.tanggal_lahir,
                                                )
                                            }}
                                            ({{ form.jenis_kelamin }})</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Agama / Status:</span
                                        >
                                        <span
                                            >{{ form.agama }} /
                                            {{ form.status_kawin }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Pendidikan:</span
                                        >
                                        <span
                                            >{{ form.jenjang_pendidikan }} -
                                            {{ form.bidang_pendidikan }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Alamat KTP:</span
                                        >
                                        <span class="text-right max-w-[65%]"
                                            >{{
                                                form.alamat_lengkap_ktp
                                                    ? `${form.alamat_lengkap_ktp}, `
                                                    : ""
                                            }}Desa/Kelurahan
                                            {{ form.desa_kelurahan_ktp }},
                                            Kecamatan {{ form.kecamatan_ktp }},
                                            Kabupaten/Kota
                                            {{ form.kabupaten_ktp }}, Provinsi
                                            {{ form.provinsi_ktp
                                            }}{{
                                                form.kode_pos_ktp
                                                    ? ` (${form.kode_pos_ktp})`
                                                    : ""
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Alamat Domisili:</span
                                        >
                                        <span class="text-right max-w-[65%]"
                                            >{{
                                                form.alamat_lengkap_domisili
                                                    ? `${form.alamat_lengkap_domisili}, `
                                                    : ""
                                            }}Desa/Kelurahan
                                            {{ form.desa_kelurahan_domisili }},
                                            Kecamatan
                                            {{ form.kecamatan_domisili }},
                                            Kabupaten/Kota
                                            {{ form.kabupaten_domisili }},
                                            Provinsi {{ form.provinsi_domisili
                                            }}{{
                                                form.kode_pos_domisili
                                                    ? ` (${form.kode_pos_domisili})`
                                                    : ""
                                            }}</span
                                        >
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500"
                                            >Kontak:</span
                                        >
                                        <span
                                            >+{{ form.telepon }} &bull;
                                            {{ form.email }}</span
                                        >
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Ringkasan Unit SPPG -->
                            <Card className="border-slate-200 bg-slate-50/50">
                                <CardHeader
                                    className="p-4 pb-2 border-b border-slate-100"
                                >
                                    <CardTitle
                                        className="text-sm font-bold flex items-center justify-between"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Building2
                                                class="h-4 w-4 text-primary"
                                            />
                                            Data Unit SPPG
                                        </span>
                                        <button
                                            type="button"
                                            @click="goToStep(2)"
                                            class="text-xs text-primary font-normal hover:underline cursor-pointer"
                                        >
                                            Ubah
                                        </button>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent
                                    className="p-4 space-y-2 text-xs text-slate-700"
                                >
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >ID SPPG:</span
                                        >
                                        <span
                                            class="font-mono font-bold text-primary"
                                            >{{ form.id_sppg }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Kode SPPG:</span
                                        >
                                        <span class="font-mono font-semibold">{{
                                            form.kode_sppg
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Nama Unit:</span
                                        >
                                        <span class="font-semibold">{{
                                            form.unit_nama
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Status:</span
                                        >
                                        <Badge
                                            variant="outline"
                                            className="font-medium text-[11px]"
                                            >{{ form.status }}</Badge
                                        >
                                    </div>
                                    <div
                                        class="flex justify-between py-1 border-b border-slate-100"
                                    >
                                        <span class="text-slate-500"
                                            >Wilayah:</span
                                        >
                                        <span
                                            >{{ form.unit_desa_kelurahan }},
                                            {{ form.unit_kecamatan }},
                                            {{ form.unit_kabupaten }},
                                            {{ form.unit_provinsi }} ({{
                                                form.kode_pos
                                            }})</span
                                        >
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-slate-500"
                                            >Koordinat:</span
                                        >
                                        <span class="font-mono text-[11px]"
                                            >{{ form.unit_latitude }},
                                            {{ form.unit_longitude }}</span
                                        >
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Agreement notice with reactive checkbox requirement -->
                        <div
                            :class="[
                                'p-4 rounded-xl border text-xs transition-all duration-200 flex items-start gap-3 cursor-pointer select-none',
                                isAgreed
                                    ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950 ring-1 ring-emerald-400/30'
                                    : 'bg-blue-50/70 border-blue-200 text-slate-700',
                            ]"
                            @click="isAgreed = !isAgreed"
                        >
                            <input
                                type="checkbox"
                                id="agree"
                                v-model="isAgreed"
                                @click.stop
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            />
                            <label
                                for="agree"
                                class="cursor-pointer font-medium leading-relaxed"
                                @click.stop="isAgreed = !isAgreed"
                            >
                                Saya menyatakan dengan sesungguhnya bahwa
                                seluruh data profil Kepala SPPG dan data Unit
                                SPPG yang saya isikan di atas adalah benar,
                                valid, dan dapat dipertanggungjawabkan sesuai
                                peraturan dan ketentuan sistem SIPEGE.
                            </label>
                        </div>

                        <!-- Footer Step 3 -->
                        <div
                            class="flex items-center justify-between pt-4 border-t border-slate-100"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="prevStep"
                                className="flex items-center gap-2"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali</span>
                            </Button>

                            <Button
                                type="submit"
                                :disabled="form.processing || !isAgreed"
                                :className="[
                                    'flex items-center gap-2 px-8 h-11 transition-all duration-200',
                                    isAgreed && !form.processing
                                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 cursor-pointer opacity-100'
                                        : 'bg-slate-300 text-slate-500 cursor-not-allowed opacity-60',
                                ]"
                            >
                                <CheckCircle class="h-4 w-4" />
                                <span>{{
                                    form.processing
                                        ? "Mendaftarkan..."
                                        : "Selesaikan Pendaftaran"
                                }}</span>
                            </Button>
                        </div>
                    </div>
                </form>
            </Card>
        </div>
    </div>
</template>
