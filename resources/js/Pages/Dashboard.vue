<script setup>
import { ref, computed, nextTick, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Select from "@/Components/ui/Select.vue";
import Separator from "@/Components/ui/Separator.vue";
import MapPicker from "@/Components/MapPicker.vue";
import GeospatialMap from "@/Components/GeospatialMap.vue";
import {
    Building2,
    User,
    MapPin,
    Phone,
    Mail,
    Calendar,
    CheckCircle2,
    AlertTriangle,
    Activity,
    Shield,
    FileBadge,
    Sparkles,
    ExternalLink,
    Layers,
    Map,
    Edit3,
    Save,
    X,
    Loader2,
    Copy,
    AlertCircle,
} from "lucide-vue-next";

import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
    formatWilayahName,
    formatKabupatenName,
    formatNamaLengkap,
} from "@/Services/wilayah";
import { formatTanggalIndo, formatTanggalWaktuIndo } from "@/lib/utils";

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

const activeTab = ref("overview");
const geoMapRef = ref(null);

// ================= EDIT STATE =================
const isEditingUnit = ref(false);
const isEditingProfile = ref(false);

// Client-side Errors State
const unitClientErrors = ref({});
const profileClientErrors = ref({});

// Phone raw input without +62
const rawPhone = ref("");

// Wilayah Data State
const provincesList = ref([]);
const isProvincesLoading = ref(false);

// Unit Wilayah State
const isUnitRegLoading = ref(false);
const isUnitDistLoading = ref(false);
const isUnitVillLoading = ref(false);
const selectedUnitProvId = ref("");
const selectedUnitRegId = ref("");
const selectedUnitDistId = ref("");
const unitRegencies = ref([]);
const unitDistricts = ref([]);
const unitVillages = ref([]);

// Profile KTP Wilayah State
const isKtpRegLoading = ref(false);
const isKtpDistLoading = ref(false);
const isKtpVillLoading = ref(false);
const selectedKtpProvId = ref("");
const selectedKtpRegId = ref("");
const selectedKtpDistId = ref("");
const ktpRegencies = ref([]);
const ktpDistricts = ref([]);
const ktpVillages = ref([]);

// Profile Domisili Wilayah State
const isDomRegLoading = ref(false);
const isDomDistLoading = ref(false);
const isDomVillLoading = ref(false);
const selectedDomProvId = ref("");
const selectedDomRegId = ref("");
const selectedDomDistId = ref("");
const domRegencies = ref([]);
const domDistricts = ref([]);
const domVillages = ref([]);

// Forms
const formUnit = useForm({
    id_sppg: props.unitSppg?.id_sppg || "",
    kode_sppg: props.unitSppg?.kode_sppg || "",
    nama: props.unitSppg?.nama || "",
    status: props.unitSppg?.status || "Operasional",
    tanggal_operasional: props.unitSppg?.tanggal_operasional
        ? String(props.unitSppg.tanggal_operasional).substring(0, 10)
        : "",
    provinsi: props.unitSppg?.provinsi || "",
    kabupaten: props.unitSppg?.kabupaten || "",
    kecamatan: props.unitSppg?.kecamatan || "",
    desa_kelurahan: props.unitSppg?.desa_kelurahan || "",
    kode_pos: props.unitSppg?.kode_pos || "",
    alamat_lengkap: props.unitSppg?.alamat_lengkap || "",
    latitude: props.unitSppg?.latitude ? Number(props.unitSppg.latitude) : null,
    longitude: props.unitSppg?.longitude
        ? Number(props.unitSppg.longitude)
        : null,
});

const formProfile = useForm({
    nik: props.user.nik || "",
    nip: props.user.nip || "",
    nama: props.user.nama || "",
    gelar_depan: props.user.gelar_depan || "",
    gelar_belakang: props.user.gelar_belakang || "",
    agama: props.user.agama || "Islam",
    jenis_kelamin: props.user.jenis_kelamin || "L",
    tempat_lahir: props.user.tempat_lahir || "",
    tanggal_lahir: props.user.tanggal_lahir
        ? String(props.user.tanggal_lahir).substring(0, 10)
        : "",
    jenjang_pendidikan: props.user.jenjang_pendidikan || "S-I",
    bidang_pendidikan: props.user.bidang_pendidikan || "",
    status_kawin: props.user.status_kawin || "Belum Menikah",
    provinsi_ktp: props.user.provinsi_ktp || "",
    kabupaten_ktp: props.user.kabupaten_ktp || "",
    kecamatan_ktp: props.user.kecamatan_ktp || "",
    desa_kelurahan_ktp: props.user.desa_kelurahan_ktp || "",
    kode_pos_ktp: props.user.kode_pos_ktp || "",
    alamat_lengkap_ktp: props.user.alamat_lengkap_ktp || "",
    provinsi_domisili: props.user.provinsi_domisili || "",
    kabupaten_domisili: props.user.kabupaten_domisili || "",
    kecamatan_domisili: props.user.kecamatan_domisili || "",
    desa_kelurahan_domisili: props.user.desa_kelurahan_domisili || "",
    kode_pos_domisili: props.user.kode_pos_domisili || "",
    alamat_lengkap_domisili: props.user.alamat_lengkap_domisili || "",
    latitude_domisili: props.user.latitude_domisili
        ? Number(props.user.latitude_domisili)
        : null,
    longitude_domisili: props.user.longitude_domisili
        ? Number(props.user.longitude_domisili)
        : null,
    telepon: props.user.telepon || "",
});

// Helper Errors
function getUnitError(fieldName) {
    return (
        formUnit.errors[fieldName] || unitClientErrors.value[fieldName] || ""
    );
}

function clearUnitError(fieldName) {
    if (unitClientErrors.value[fieldName]) {
        delete unitClientErrors.value[fieldName];
    }
    if (formUnit.errors[fieldName]) {
        formUnit.clearErrors(fieldName);
    }
}

function getProfileError(fieldName) {
    return (
        formProfile.errors[fieldName] ||
        profileClientErrors.value[fieldName] ||
        ""
    );
}

function clearProfileError(fieldName) {
    if (profileClientErrors.value[fieldName]) {
        delete profileClientErrors.value[fieldName];
    }
    if (formProfile.errors[fieldName]) {
        formProfile.clearErrors(fieldName);
    }
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
    formProfile.telepon = cleaned ? `62${cleaned}` : "";
    clearProfileError("telepon");
});

// Computed Display
const fullName = computed(() => {
    if (props.user.nama_lengkap) return props.user.nama_lengkap;
    return formatNamaLengkap(
        props.user.nama,
        props.user.gelar_depan,
        props.user.gelar_belakang,
    );
});

const userInitials = computed(() => {
    const name = props.user.nama || props.user.name;
    if (!name) return "U";
    const words = name.trim().split(" ");
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

const statusVariant = computed(() => {
    const status = props.unitSppg?.status;
    if (status === "Operasional") return "success";
    if (status === "Belum Operasional") return "secondary";
    if (status?.startsWith("Suspend Ringan")) return "warning";
    return "destructive";
});

const domisiliFullAddress = computed(() => {
    const pos = props.user.kode_pos_domisili;
    const wilayah = [
        props.user.desa_kelurahan_domisili
            ? `Desa/Kelurahan ${props.user.desa_kelurahan_domisili}`
            : "",
        props.user.kecamatan_domisili
            ? `Kecamatan ${props.user.kecamatan_domisili}`
            : "",
        props.user.kabupaten_domisili
            ? `Kabupaten/Kota ${props.user.kabupaten_domisili}`
            : "",
        props.user.provinsi_domisili
            ? `Provinsi ${props.user.provinsi_domisili}${pos ? ` (${pos})` : ""}`
            : pos
              ? `(${pos})`
              : "",
    ]
        .filter(Boolean)
        .join(", ");

    if (props.user.alamat_lengkap_domisili) {
        return `${props.user.alamat_lengkap_domisili}, ${wilayah}`;
    }
    return wilayah;
});

const unitFullAddress = computed(() => {
    if (!props.unitSppg) return "";
    const wilayah = [
        props.unitSppg.desa_kelurahan
            ? `Desa/Kelurahan ${props.unitSppg.desa_kelurahan}`
            : "",
        props.unitSppg.kecamatan ? `Kecamatan ${props.unitSppg.kecamatan}` : "",
        props.unitSppg.kabupaten
            ? `Kabupaten/Kota ${props.unitSppg.kabupaten}`
            : "",
        props.unitSppg.provinsi
            ? `Provinsi ${props.unitSppg.provinsi}${props.unitSppg.kode_pos ? ` (${props.unitSppg.kode_pos})` : ""}`
            : props.unitSppg.kode_pos
              ? `(${props.unitSppg.kode_pos})`
              : "",
    ]
        .filter(Boolean)
        .join(", ");

    if (props.unitSppg.alamat_lengkap) {
        return `${props.unitSppg.alamat_lengkap}, ${wilayah}`;
    }
    return wilayah;
});

// Live Preview of Unit Address during edit
const liveUnitAddress = computed(() => {
    const pos = formUnit.kode_pos;
    const wilayah = [
        formUnit.desa_kelurahan
            ? `Desa/Kelurahan ${formUnit.desa_kelurahan}`
            : "",
        formUnit.kecamatan ? `Kecamatan ${formUnit.kecamatan}` : "",
        formUnit.kabupaten ? `Kabupaten/Kota ${formUnit.kabupaten}` : "",
        formUnit.provinsi
            ? `Provinsi ${formUnit.provinsi}${pos ? ` (${pos})` : ""}`
            : pos
              ? `(${pos})`
              : "",
    ]
        .filter(Boolean)
        .join(", ");

    return wilayah || "Alamat belum ditentukan";
});

// Live Preview of KTP Address during edit
const liveKtpAddress = computed(() => {
    const pos = formProfile.kode_pos_ktp;
    return (
        [
            formProfile.desa_kelurahan_ktp
                ? `Desa/Kelurahan ${formProfile.desa_kelurahan_ktp}`
                : "",
            formProfile.kecamatan_ktp
                ? `Kecamatan ${formProfile.kecamatan_ktp}`
                : "",
            formProfile.kabupaten_ktp
                ? `Kabupaten/Kota ${formProfile.kabupaten_ktp}`
                : "",
            formProfile.provinsi_ktp
                ? `Provinsi ${formProfile.provinsi_ktp}${pos ? ` (${pos})` : ""}`
                : pos
                  ? `(${pos})`
                  : "",
        ]
            .filter(Boolean)
            .join(", ") || "-"
    );
});

// Live Preview of Domisili Address during edit
const liveDomisiliAddress = computed(() => {
    const pos = formProfile.kode_pos_domisili;
    return (
        [
            formProfile.desa_kelurahan_domisili
                ? `Desa/Kelurahan ${formProfile.desa_kelurahan_domisili}`
                : "",
            formProfile.kecamatan_domisili
                ? `Kecamatan ${formProfile.kecamatan_domisili}`
                : "",
            formProfile.kabupaten_domisili
                ? `Kabupaten/Kota ${formProfile.kabupaten_domisili}`
                : "",
            formProfile.provinsi_domisili
                ? `Provinsi ${formProfile.provinsi_domisili}${pos ? ` (${pos})` : ""}`
                : pos
                  ? `(${pos})`
                  : "",
        ]
            .filter(Boolean)
            .join(", ") || "-"
    );
});

// ================= WILAYAH LOAD & EDIT LOGIC =================
async function loadProvinces() {
    if (provincesList.value.length === 0) {
        isProvincesLoading.value = true;
        provincesList.value = await getProvinces();
        isProvincesLoading.value = false;
    }
}

// ----------------- UNIT SPPG EDIT & VALIDATION -----------------
async function startEditUnit() {
    isEditingUnit.value = true;
    unitClientErrors.value = {};
    formUnit.clearErrors();
    formUnit.reset();
    formUnit.id_sppg = props.unitSppg?.id_sppg || "";
    formUnit.kode_sppg = props.unitSppg?.kode_sppg || "";
    formUnit.nama = props.unitSppg?.nama || "";
    formUnit.status = props.unitSppg?.status || "Operasional";
    formUnit.tanggal_operasional = props.unitSppg?.tanggal_operasional
        ? String(props.unitSppg.tanggal_operasional).substring(0, 10)
        : "";
    formUnit.provinsi = props.unitSppg?.provinsi || "";
    formUnit.kabupaten = props.unitSppg?.kabupaten || "";
    formUnit.kecamatan = props.unitSppg?.kecamatan || "";
    formUnit.desa_kelurahan = props.unitSppg?.desa_kelurahan || "";
    formUnit.kode_pos = props.unitSppg?.kode_pos || "";
    formUnit.alamat_lengkap = props.unitSppg?.alamat_lengkap || "";
    formUnit.latitude = props.unitSppg?.latitude
        ? Number(props.unitSppg.latitude)
        : null;
    formUnit.longitude = props.unitSppg?.longitude
        ? Number(props.unitSppg.longitude)
        : null;

    await loadProvinces();

    // Pre-match Province
    if (formUnit.provinsi) {
        const foundProv = provincesList.value.find(
            (p) =>
                formatWilayahName(p.name).toLowerCase() ===
                    formUnit.provinsi.toLowerCase() ||
                p.name.toLowerCase() === formUnit.provinsi.toLowerCase(),
        );
        if (foundProv) {
            selectedUnitProvId.value = foundProv.id;
            isUnitRegLoading.value = true;
            unitRegencies.value = await getRegencies(foundProv.id);
            isUnitRegLoading.value = false;

            // Pre-match Regency
            if (formUnit.kabupaten) {
                const foundReg = unitRegencies.value.find(
                    (r) =>
                        formatKabupatenName(r.name).toLowerCase() ===
                            formUnit.kabupaten.toLowerCase() ||
                        formatWilayahName(r.name).toLowerCase() ===
                            formUnit.kabupaten.toLowerCase() ||
                        r.name.toLowerCase() ===
                            formUnit.kabupaten.toLowerCase(),
                );
                if (foundReg) {
                    selectedUnitRegId.value = foundReg.id;
                    isUnitDistLoading.value = true;
                    unitDistricts.value = await getDistricts(foundReg.id);
                    isUnitDistLoading.value = false;

                    // Pre-match District
                    if (formUnit.kecamatan) {
                        const foundDist = unitDistricts.value.find(
                            (d) =>
                                formatWilayahName(d.name).toLowerCase() ===
                                    formUnit.kecamatan.toLowerCase() ||
                                d.name.toLowerCase() ===
                                    formUnit.kecamatan.toLowerCase(),
                        );
                        if (foundDist) {
                            selectedUnitDistId.value = foundDist.id;
                            isUnitVillLoading.value = true;
                            unitVillages.value = await getVillages(
                                foundDist.id,
                            );
                            isUnitVillLoading.value = false;
                        }
                    }
                }
            }
        }
    }
}

function cancelEditUnit() {
    isEditingUnit.value = false;
    unitClientErrors.value = {};
    formUnit.clearErrors();
}

function validateUnitForm() {
    const errs = {};

    if (!formUnit.id_sppg || !formUnit.id_sppg.trim()) {
        errs.id_sppg = "ID SPPG wajib diisi.";
    } else if (
        formUnit.id_sppg.length !== 8 ||
        !/^[A-Z0-9]{8}$/i.test(formUnit.id_sppg)
    ) {
        errs.id_sppg = "ID SPPG harus berupa tepat 8 karakter huruf dan angka.";
    }

    if (!formUnit.kode_sppg || !formUnit.kode_sppg.trim()) {
        errs.kode_sppg = "Kode SPPG wajib diisi.";
    }

    if (!formUnit.nama || !formUnit.nama.trim()) {
        errs.nama = "Nama SPPG wajib diisi.";
    }

    if (!formUnit.status) {
        errs.status = "Pilih status operasional unit SPPG.";
    }

    if (!formUnit.provinsi) {
        errs.provinsi = "Pilih provinsi unit SPPG.";
    }
    if (!formUnit.kabupaten) {
        errs.kabupaten = "Pilih kabupaten/kota unit SPPG.";
    }
    if (!formUnit.kecamatan) {
        errs.kecamatan = "Pilih kecamatan unit SPPG.";
    }
    if (!formUnit.desa_kelurahan) {
        errs.desa_kelurahan = "Pilih desa/kelurahan unit SPPG.";
    }

    if (!formUnit.kode_pos) {
        errs.kode_pos = "Kode pos unit SPPG wajib diisi.";
    } else if (!/^\d{5}$/.test(formUnit.kode_pos)) {
        errs.kode_pos = "Kode pos harus berupa tepat 5 digit angka.";
    }

    if (!formUnit.alamat_lengkap || !formUnit.alamat_lengkap.trim()) {
        errs.alamat_lengkap = "Alamat lengkap unit SPPG wajib diisi.";
    }

    if (formUnit.latitude === null || formUnit.longitude === null) {
        errs.latitude = "Titik koordinat unit SPPG wajib ditentukan pada peta.";
    }

    unitClientErrors.value = errs;
    return Object.keys(errs).length === 0;
}

function submitEditUnit() {
    if (!validateUnitForm()) {
        return;
    }

    formUnit.put("/dashboard/unit-sppg", {
        preserveScroll: true,
        onSuccess: () => {
            isEditingUnit.value = false;
            unitClientErrors.value = {};
        },
    });
}

async function onUnitProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedUnitProvId.value,
    );
    formUnit.provinsi = prov ? formatWilayahName(prov.name) : "";
    selectedUnitRegId.value = "";
    selectedUnitDistId.value = "";
    unitRegencies.value = [];
    unitDistricts.value = [];
    unitVillages.value = [];
    formUnit.kabupaten = "";
    formUnit.kecamatan = "";
    formUnit.desa_kelurahan = "";
    clearUnitError("provinsi");

    if (selectedUnitProvId.value) {
        isUnitRegLoading.value = true;
        unitRegencies.value = await getRegencies(selectedUnitProvId.value);
        isUnitRegLoading.value = false;
    }
}

async function onUnitRegChange() {
    const reg = unitRegencies.value.find(
        (r) => r.id === selectedUnitRegId.value,
    );
    formUnit.kabupaten = reg ? formatKabupatenName(reg.name) : "";
    selectedUnitDistId.value = "";
    unitDistricts.value = [];
    unitVillages.value = [];
    formUnit.kecamatan = "";
    formUnit.desa_kelurahan = "";
    clearUnitError("kabupaten");

    if (selectedUnitRegId.value) {
        isUnitDistLoading.value = true;
        unitDistricts.value = await getDistricts(selectedUnitRegId.value);
        isUnitDistLoading.value = false;
    }
}

async function onUnitDistChange() {
    const dist = unitDistricts.value.find(
        (d) => d.id === selectedUnitDistId.value,
    );
    formUnit.kecamatan = dist ? formatWilayahName(dist.name) : "";
    unitVillages.value = [];
    formUnit.desa_kelurahan = "";
    clearUnitError("kecamatan");

    if (selectedUnitDistId.value) {
        isUnitVillLoading.value = true;
        unitVillages.value = await getVillages(selectedUnitDistId.value);
        isUnitVillLoading.value = false;
    }
}

// ----------------- KEPALA SPPG EDIT & VALIDATION -----------------
async function startEditProfile() {
    isEditingProfile.value = true;
    profileClientErrors.value = {};
    formProfile.clearErrors();
    formProfile.reset();
    formProfile.nik = props.user.nik || "";
    formProfile.nip = props.user.nip || "";
    formProfile.nama = props.user.nama || "";
    formProfile.gelar_depan = props.user.gelar_depan || "";
    formProfile.gelar_belakang = props.user.gelar_belakang || "";
    formProfile.agama = props.user.agama || "Islam";
    formProfile.jenis_kelamin = props.user.jenis_kelamin || "L";
    formProfile.tempat_lahir = props.user.tempat_lahir || "";
    formProfile.tanggal_lahir = props.user.tanggal_lahir
        ? String(props.user.tanggal_lahir).substring(0, 10)
        : "";
    formProfile.jenjang_pendidikan = props.user.jenjang_pendidikan || "S-I";
    formProfile.bidang_pendidikan = props.user.bidang_pendidikan || "";
    formProfile.status_kawin = props.user.status_kawin || "Belum Menikah";
    formProfile.provinsi_ktp = props.user.provinsi_ktp || "";
    formProfile.kabupaten_ktp = props.user.kabupaten_ktp || "";
    formProfile.kecamatan_ktp = props.user.kecamatan_ktp || "";
    formProfile.desa_kelurahan_ktp = props.user.desa_kelurahan_ktp || "";
    formProfile.kode_pos_ktp = props.user.kode_pos_ktp || "";
    formProfile.alamat_lengkap_ktp = props.user.alamat_lengkap_ktp || "";
    formProfile.provinsi_domisili = props.user.provinsi_domisili || "";
    formProfile.kabupaten_domisili = props.user.kabupaten_domisili || "";
    formProfile.kecamatan_domisili = props.user.kecamatan_domisili || "";
    formProfile.desa_kelurahan_domisili =
        props.user.desa_kelurahan_domisili || "";
    formProfile.kode_pos_domisili = props.user.kode_pos_domisili || "";
    formProfile.alamat_lengkap_domisili =
        props.user.alamat_lengkap_domisili || "";
    formProfile.latitude_domisili = props.user.latitude_domisili
        ? Number(props.user.latitude_domisili)
        : null;
    formProfile.longitude_domisili = props.user.longitude_domisili
        ? Number(props.user.longitude_domisili)
        : null;
    formProfile.telepon = props.user.telepon || "";

    // Sync rawPhone without 62
    let p = props.user.telepon || "";
    if (p.startsWith("62")) p = p.substring(2);
    else if (p.startsWith("0")) p = p.substring(1);
    rawPhone.value = p;

    await loadProvinces();

    // Pre-match KTP Wilayah
    if (formProfile.provinsi_ktp) {
        const foundProv = provincesList.value.find(
            (p) =>
                formatWilayahName(p.name).toLowerCase() ===
                    formProfile.provinsi_ktp.toLowerCase() ||
                p.name.toLowerCase() === formProfile.provinsi_ktp.toLowerCase(),
        );
        if (foundProv) {
            selectedKtpProvId.value = foundProv.id;
            isKtpRegLoading.value = true;
            ktpRegencies.value = await getRegencies(foundProv.id);
            isKtpRegLoading.value = false;

            if (formProfile.kabupaten_ktp) {
                const foundReg = ktpRegencies.value.find(
                    (r) =>
                        formatKabupatenName(r.name).toLowerCase() ===
                            formProfile.kabupaten_ktp.toLowerCase() ||
                        formatWilayahName(r.name).toLowerCase() ===
                            formProfile.kabupaten_ktp.toLowerCase() ||
                        r.name.toLowerCase() ===
                            formProfile.kabupaten_ktp.toLowerCase(),
                );
                if (foundReg) {
                    selectedKtpRegId.value = foundReg.id;
                    isKtpDistLoading.value = true;
                    ktpDistricts.value = await getDistricts(foundReg.id);
                    isKtpDistLoading.value = false;

                    if (formProfile.kecamatan_ktp) {
                        const foundDist = ktpDistricts.value.find(
                            (d) =>
                                formatWilayahName(d.name).toLowerCase() ===
                                    formProfile.kecamatan_ktp.toLowerCase() ||
                                d.name.toLowerCase() ===
                                    formProfile.kecamatan_ktp.toLowerCase(),
                        );
                        if (foundDist) {
                            selectedKtpDistId.value = foundDist.id;
                            isKtpVillLoading.value = true;
                            ktpVillages.value = await getVillages(foundDist.id);
                            isKtpVillLoading.value = false;
                        }
                    }
                }
            }
        }
    }

    // Pre-match Domisili Wilayah
    if (formProfile.provinsi_domisili) {
        const foundProv = provincesList.value.find(
            (p) =>
                formatWilayahName(p.name).toLowerCase() ===
                    formProfile.provinsi_domisili.toLowerCase() ||
                p.name.toLowerCase() ===
                    formProfile.provinsi_domisili.toLowerCase(),
        );
        if (foundProv) {
            selectedDomProvId.value = foundProv.id;
            isDomRegLoading.value = true;
            domRegencies.value = await getRegencies(foundProv.id);
            isDomRegLoading.value = false;

            if (formProfile.kabupaten_domisili) {
                const foundReg = domRegencies.value.find(
                    (r) =>
                        formatKabupatenName(r.name).toLowerCase() ===
                            formProfile.kabupaten_domisili.toLowerCase() ||
                        formatWilayahName(r.name).toLowerCase() ===
                            formProfile.kabupaten_domisili.toLowerCase() ||
                        r.name.toLowerCase() ===
                            formProfile.kabupaten_domisili.toLowerCase(),
                );
                if (foundReg) {
                    selectedDomRegId.value = foundReg.id;
                    isDomDistLoading.value = true;
                    domDistricts.value = await getDistricts(foundReg.id);
                    isDomDistLoading.value = false;

                    if (formProfile.kecamatan_domisili) {
                        const foundDist = domDistricts.value.find(
                            (d) =>
                                formatWilayahName(d.name).toLowerCase() ===
                                    formProfile.kecamatan_domisili.toLowerCase() ||
                                d.name.toLowerCase() ===
                                    formProfile.kecamatan_domisili.toLowerCase(),
                        );
                        if (foundDist) {
                            selectedDomDistId.value = foundDist.id;
                            isDomVillLoading.value = true;
                            domVillages.value = await getVillages(foundDist.id);
                            isDomVillLoading.value = false;
                        }
                    }
                }
            }
        }
    }
}

function cancelEditProfile() {
    isEditingProfile.value = false;
    profileClientErrors.value = {};
    formProfile.clearErrors();
}

function validateProfileForm() {
    const errs = {};

    if (!formProfile.nik) {
        errs.nik = "NIK wajib diisi.";
    } else if (!/^\d{16}$/.test(formProfile.nik)) {
        errs.nik = "NIK harus berupa 16 digit angka.";
    }

    if (!formProfile.nama || !formProfile.nama.trim()) {
        errs.nama = "Nama lengkap wajib diisi.";
    }

    if (!formProfile.agama) {
        errs.agama = "Pilih salah satu agama.";
    }

    if (!formProfile.jenis_kelamin) {
        errs.jenis_kelamin = "Pilih jenis kelamin (L/P).";
    }

    if (!formProfile.tempat_lahir || !formProfile.tempat_lahir.trim()) {
        errs.tempat_lahir = "Tempat lahir wajib diisi.";
    }

    if (!formProfile.tanggal_lahir) {
        errs.tanggal_lahir = "Tanggal lahir wajib diisi.";
    }

    if (!formProfile.jenjang_pendidikan) {
        errs.jenjang_pendidikan = "Pilih jenjang pendidikan.";
    }

    if (
        !formProfile.bidang_pendidikan ||
        !formProfile.bidang_pendidikan.trim()
    ) {
        errs.bidang_pendidikan = "Bidang pendidikan / jurusan wajib diisi.";
    }

    if (!formProfile.status_kawin) {
        errs.status_kawin = "Pilih status perkawinan.";
    }

    if (!formProfile.provinsi_ktp) {
        errs.provinsi_ktp = "Pilih provinsi sesuai KTP.";
    }
    if (!formProfile.kabupaten_ktp) {
        errs.kabupaten_ktp = "Pilih kabupaten/kota sesuai KTP.";
    }
    if (!formProfile.kecamatan_ktp) {
        errs.kecamatan_ktp = "Pilih kecamatan sesuai KTP.";
    }
    if (!formProfile.desa_kelurahan_ktp) {
        errs.desa_kelurahan_ktp = "Pilih desa/kelurahan sesuai KTP.";
    }
    if (!formProfile.kode_pos_ktp) {
        errs.kode_pos_ktp = "Kode pos KTP wajib diisi.";
    } else if (!/^\d{5}$/.test(formProfile.kode_pos_ktp)) {
        errs.kode_pos_ktp = "Kode pos KTP harus berupa 5 digit angka.";
    }
    if (
        !formProfile.alamat_lengkap_ktp ||
        !formProfile.alamat_lengkap_ktp.trim()
    ) {
        errs.alamat_lengkap_ktp = "Alamat lengkap sesuai KTP wajib diisi.";
    }

    if (!formProfile.provinsi_domisili) {
        errs.provinsi_domisili = "Pilih provinsi domisili.";
    }
    if (!formProfile.kabupaten_domisili) {
        errs.kabupaten_domisili = "Pilih kabupaten/kota domisili.";
    }
    if (!formProfile.kecamatan_domisili) {
        errs.kecamatan_domisili = "Pilih kecamatan domisili.";
    }
    if (!formProfile.desa_kelurahan_domisili) {
        errs.desa_kelurahan_domisili = "Pilih desa/kelurahan domisili.";
    }
    if (!formProfile.kode_pos_domisili) {
        errs.kode_pos_domisili = "Kode pos domisili wajib diisi.";
    } else if (!/^\d{5}$/.test(formProfile.kode_pos_domisili)) {
        errs.kode_pos_domisili =
            "Kode pos domisili harus berupa 5 digit angka.";
    }
    if (
        !formProfile.alamat_lengkap_domisili ||
        !formProfile.alamat_lengkap_domisili.trim()
    ) {
        errs.alamat_lengkap_domisili = "Alamat lengkap domisili wajib diisi.";
    }

    if (
        formProfile.latitude_domisili === null ||
        formProfile.longitude_domisili === null
    ) {
        errs.latitude_domisili =
            "Titik koordinat domisili wajib ditentukan pada peta.";
    }

    if (!formProfile.telepon) {
        errs.telepon = "Nomor telepon wajib diisi.";
    } else if (!/^62\d{8,15}$/.test(formProfile.telepon)) {
        errs.telepon = "Nomor telepon minimal 8-15 digit angka setelah +62.";
    }

    profileClientErrors.value = errs;
    return Object.keys(errs).length === 0;
}

function submitEditProfile() {
    if (!validateProfileForm()) {
        return;
    }

    formProfile.put("/dashboard/user-profile", {
        preserveScroll: true,
        onSuccess: () => {
            isEditingProfile.value = false;
            profileClientErrors.value = {};
        },
    });
}

// KTP Handlers
async function onKtpProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedKtpProvId.value,
    );
    formProfile.provinsi_ktp = prov ? formatWilayahName(prov.name) : "";
    selectedKtpRegId.value = "";
    selectedKtpDistId.value = "";
    ktpRegencies.value = [];
    ktpDistricts.value = [];
    ktpVillages.value = [];
    formProfile.kabupaten_ktp = "";
    formProfile.kecamatan_ktp = "";
    formProfile.desa_kelurahan_ktp = "";
    clearProfileError("provinsi_ktp");

    if (selectedKtpProvId.value) {
        isKtpRegLoading.value = true;
        ktpRegencies.value = await getRegencies(selectedKtpProvId.value);
        isKtpRegLoading.value = false;
    }
}

async function onKtpRegChange() {
    const reg = ktpRegencies.value.find((r) => r.id === selectedKtpRegId.value);
    formProfile.kabupaten_ktp = reg ? formatKabupatenName(reg.name) : "";
    selectedKtpDistId.value = "";
    ktpDistricts.value = [];
    ktpVillages.value = [];
    formProfile.kecamatan_ktp = "";
    formProfile.desa_kelurahan_ktp = "";
    clearProfileError("kabupaten_ktp");

    if (selectedKtpRegId.value) {
        isKtpDistLoading.value = true;
        ktpDistricts.value = await getDistricts(selectedKtpRegId.value);
        isKtpDistLoading.value = false;
    }
}

async function onKtpDistChange() {
    const dist = ktpDistricts.value.find(
        (d) => d.id === selectedKtpDistId.value,
    );
    formProfile.kecamatan_ktp = dist ? formatWilayahName(dist.name) : "";
    ktpVillages.value = [];
    formProfile.desa_kelurahan_ktp = "";
    clearProfileError("kecamatan_ktp");

    if (selectedKtpDistId.value) {
        isKtpVillLoading.value = true;
        ktpVillages.value = await getVillages(selectedKtpDistId.value);
        isKtpVillLoading.value = false;
    }
}

// Domisili Handlers
async function onDomProvChange() {
    const prov = provincesList.value.find(
        (p) => p.id === selectedDomProvId.value,
    );
    formProfile.provinsi_domisili = prov ? formatWilayahName(prov.name) : "";
    selectedDomRegId.value = "";
    selectedDomDistId.value = "";
    domRegencies.value = [];
    domDistricts.value = [];
    domVillages.value = [];
    formProfile.kabupaten_domisili = "";
    formProfile.kecamatan_domisili = "";
    formProfile.desa_kelurahan_domisili = "";
    clearProfileError("provinsi_domisili");

    if (selectedDomProvId.value) {
        isDomRegLoading.value = true;
        domRegencies.value = await getRegencies(selectedDomProvId.value);
        isDomRegLoading.value = false;
    }
}

async function onDomRegChange() {
    const reg = domRegencies.value.find((r) => r.id === selectedDomRegId.value);
    formProfile.kabupaten_domisili = reg ? formatKabupatenName(reg.name) : "";
    selectedDomDistId.value = "";
    domDistricts.value = [];
    domVillages.value = [];
    formProfile.kecamatan_domisili = "";
    formProfile.desa_kelurahan_domisili = "";
    clearProfileError("kabupaten_domisili");

    if (selectedDomRegId.value) {
        isDomDistLoading.value = true;
        domDistricts.value = await getDistricts(selectedDomRegId.value);
        isDomDistLoading.value = false;
    }
}

async function onDomDistChange() {
    const dist = domDistricts.value.find(
        (d) => d.id === selectedDomDistId.value,
    );
    formProfile.kecamatan_domisili = dist ? formatWilayahName(dist.name) : "";
    domVillages.value = [];
    formProfile.desa_kelurahan_domisili = "";
    clearProfileError("kecamatan_domisili");

    if (selectedDomDistId.value) {
        isDomVillLoading.value = true;
        domVillages.value = await getVillages(selectedDomDistId.value);
        isDomVillLoading.value = false;
    }
}

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

    if (formProfile.desa_kelurahan_ktp) {
        formProfile.desa_kelurahan_domisili = formProfile.desa_kelurahan_ktp;
        clearProfileError("desa_kelurahan_domisili");
    }

    if (formProfile.kode_pos_ktp) {
        formProfile.kode_pos_domisili = formProfile.kode_pos_ktp;
        clearProfileError("kode_pos_domisili");
    }

    if (formProfile.alamat_lengkap_ktp) {
        formProfile.alamat_lengkap_domisili = formProfile.alamat_lengkap_ktp;
        clearProfileError("alamat_lengkap_domisili");
    }
}

function onToggleCollapse() {
    nextTick(() => {
        setTimeout(() => {
            if (geoMapRef.value?.refresh) geoMapRef.value.refresh();
        }, 310);
    });
}

function switchTab(tab) {
    activeTab.value = tab;
    nextTick(() => {
        setTimeout(() => {
            window.dispatchEvent(new Event("resize"));
            if (tab === "maps" && geoMapRef.value?.refresh) {
                geoMapRef.value.refresh();
            }
        }, 150);
    });
}
</script>

<template>
    <Head title="Dashboard SPPG - SIPEGE" />

    <AppLayout
        :user="user"
        :unit-sppg="unitSppg"
        @toggle-collapse="onToggleCollapse"
    >
        <!-- Welcome Hero Banner -->
        <div
            class="rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 p-6 sm:p-8 text-white shadow-lg shadow-blue-900/10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center mb-6"
        >
            <!-- Sisi Kiri: Info Pengguna & Hak Akses (col-span-7) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="secondary"
                            className="bg-white/20 hover:bg-white/30 text-white border-none text-xs"
                        >
                            <Sparkles class="h-3 w-3 mr-1 text-amber-300" />
                            Terverifikasi
                        </Badge>
                        <Badge
                            :variant="statusVariant"
                            className="text-xs font-semibold"
                        >
                            {{ unitSppg?.status || "Belum Operasional" }}
                        </Badge>
                    </div>
                    <h1
                        class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white"
                    >
                        Hai, {{ fullName }}
                    </h1>
                    <p class="text-blue-100 text-sm max-w-2xl leading-relaxed">
                        Kepala {{ unitSppg?.nama || "Unit SPPG" }}
                    </p>
                </div>

                <!-- Info Hak Akses & Registrasi -->
                <div
                    class="grid grid-cols-2 gap-4 pt-3 border-t border-white/15 max-w-md"
                >
                    <div>
                        <p
                            class="text-[11px] text-blue-200 uppercase tracking-wider font-medium"
                        >
                            Hak Akses
                        </p>
                        <p
                            class="text-sm font-bold uppercase tracking-wider text-white mt-0.5"
                        >
                            {{ user.role || "-" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[11px] text-blue-200 uppercase tracking-wider font-medium"
                        >
                            Terdaftar Pada
                        </p>
                        <p class="text-xs font-medium text-white mt-0.5">
                            {{ formatTanggalWaktuIndo(user.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sisi Kanan: Foto Unit SPPG (col-span-5) -->
            <div class="lg:col-span-5 flex justify-center lg:justify-end">
                <div
                    v-if="unitSppg?.photo"
                    class="relative w-full max-w-md h-44 sm:h-48 rounded-xl overflow-hidden border border-white/20 shadow-md bg-white/10 group"
                >
                    <img
                        :src="unitSppg.photo"
                        :alt="unitSppg.nama || 'Foto Unit SPPG'"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent flex items-end p-3"
                    >
                        <span class="text-xs font-semibold text-white truncate">
                            {{ unitSppg?.nama || "Unit SPPG" }}
                        </span>
                    </div>
                </div>
                <div
                    v-else
                    class="w-full max-w-md h-44 sm:h-48 rounded-xl bg-white/10 backdrop-blur-xs border border-white/20 p-5 flex flex-col items-center justify-center text-center shadow-xs"
                >
                    <div
                        class="h-12 w-12 rounded-2xl bg-white/20 text-white flex items-center justify-center mb-2 shadow-2xs border border-white/30"
                    >
                        <Building2 class="h-6 w-6 text-white" />
                    </div>
                    <span
                        class="text-xl font-black tracking-widest text-white font-mono"
                    >
                        SPPG
                    </span>
                    <span class="text-xs text-blue-100/80 mt-0.5 font-medium">
                        Foto Unit SPPG
                    </span>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Metric 1: ID SPPG -->
            <Card
                className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
            >
                <CardContent
                    className="p-5 h-full flex items-center justify-between gap-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500">
                            ID SPPG
                        </p>
                        <h3
                            class="text-xl font-bold font-mono text-primary mt-1 truncate"
                        >
                            {{ unitSppg?.id_sppg || "-" }}
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                            Kode: {{ unitSppg?.kode_sppg || "-" }}
                        </p>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                    >
                        <FileBadge class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Metric 2: Lokasi SPPG -->
            <Card
                className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
            >
                <CardContent
                    className="p-5 h-full flex items-center justify-between gap-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500">
                            Lokasi SPPG
                        </p>
                        <h3
                            class="text-sm font-bold text-slate-900 mt-1 line-clamp-2 leading-tight"
                        >
                            {{
                                [
                                    unitSppg?.desa_kelurahan,
                                    unitSppg?.kecamatan,
                                    unitSppg?.kabupaten,
                                    unitSppg?.provinsi,
                                    unitSppg?.kode_pos,
                                ]
                                    .filter(Boolean)
                                    .join(", ") || "-"
                            }}
                        </h3>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"
                    >
                        <MapPin class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Metric 3: Status Operasional -->
            <Card
                className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
            >
                <CardContent
                    className="p-5 h-full flex items-center justify-between gap-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500">
                            Status Operasional
                        </p>
                        <div class="mt-1">
                            <Badge
                                :variant="statusVariant"
                                className="text-xs font-bold"
                            >
                                {{ unitSppg?.status || "Belum Operasional" }}
                            </Badge>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 truncate">
                            {{
                                unitSppg?.tanggal_operasional
                                    ? `Mulai: ${formatTanggalIndo(
                                          unitSppg.tanggal_operasional,
                                      )}`
                                    : "-"
                            }}
                        </p>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"
                    >
                        <Activity class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>

            <!-- Metric 4: Kepala SPPG -->
            <Card
                className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
            >
                <CardContent
                    className="p-5 h-full flex items-center justify-between gap-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500">
                            Kepala SPPG
                        </p>
                        <h3
                            class="text-sm font-bold text-slate-900 mt-1 truncate"
                        >
                            {{ user.nama_lengkap }}
                        </h3>
                        <p
                            class="text-[11px] text-slate-400 mt-0.5 leading-tight"
                        >
                            NIK: {{ user.nik || "-" }}
                            <span v-if="user.nip" class="block"
                                >NIP: {{ user.nip }}</span
                            >
                        </p>
                    </div>
                    <div
                        class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"
                    >
                        <Shield class="h-5 w-5" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Navigation Tabs -->
        <div
            class="flex items-center justify-start gap-2 border-b border-slate-200 pb-2 overflow-x-auto select-none mb-6"
        >
            <button
                @click="switchTab('overview')"
                :class="[
                    'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                    activeTab === 'overview'
                        ? 'bg-white text-primary shadow-xs border border-slate-200'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
                ]"
            >
                <Building2 class="h-4 w-4 shrink-0" />
                <span>Data Unit SPPG</span>
            </button>

            <button
                @click="switchTab('profile')"
                :class="[
                    'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                    activeTab === 'profile'
                        ? 'bg-white text-primary shadow-xs border border-slate-200'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
                ]"
            >
                <User class="h-4 w-4 shrink-0" />
                <span>Data Kepala SPPG</span>
            </button>

            <button
                @click="switchTab('maps')"
                :class="[
                    'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                    activeTab === 'maps'
                        ? 'bg-white text-primary shadow-xs border border-slate-200'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
                ]"
            >
                <Map class="h-4 w-4 shrink-0" />
                <span>Peta & Geospasial</span>
            </button>
        </div>

        <!-- ================= TAB 1: DATA UNIT SPPG ================= -->
        <div
            v-show="activeTab === 'overview'"
            class="grid grid-cols-1 lg:grid-cols-3 gap-6"
        >
            <!-- Detail Info Unit SPPG (col-span-2) -->
            <Card
                className="lg:col-span-2 bg-white border-slate-200 flex flex-col justify-between"
            >
                <div>
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle
                                    className="text-lg font-bold flex items-center gap-2"
                                >
                                    <Building2 class="h-5 w-5 text-primary" />
                                    <span>{{
                                        isEditingUnit
                                            ? "Edit Data Unit SPPG"
                                            : "Informasi Detail Unit SPPG"
                                    }}</span>
                                </CardTitle>
                                <CardDescription>{{
                                    isEditingUnit
                                        ? "Perbarui data operasional, wilayah, dan titik koordinat unit SPPG"
                                        : "Rincian data operasional dan legalitas unit SPPG"
                                }}</CardDescription>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="!isEditingUnit"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="startEditUnit"
                                    className="flex items-center gap-1.5 font-medium border-slate-300 text-slate-700 hover:bg-slate-50 cursor-pointer"
                                >
                                    <Edit3 class="h-3.5 w-3.5 text-primary" />
                                    <span>Edit Data SPPG</span>
                                </Button>
                                <template v-else>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="cancelEditUnit"
                                        :disabled="formUnit.processing"
                                        className="flex items-center gap-1.5 text-slate-600 cursor-pointer"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        <span>Batal</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="submitEditUnit"
                                        :disabled="formUnit.processing"
                                        className="flex items-center gap-1.5 bg-primary text-white cursor-pointer"
                                    >
                                        <Loader2
                                            v-if="formUnit.processing"
                                            class="h-3.5 w-3.5 animate-spin"
                                        />
                                        <Save v-else class="h-3.5 w-3.5" />
                                        <span>Simpan Perubahan</span>
                                    </Button>
                                </template>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-6 space-y-5">
                        <!-- Read-only View -->
                        <div
                            v-if="!isEditingUnit"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                        >
                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >ID SPPG</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{ unitSppg?.id_sppg || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Kode SPPG</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{ unitSppg?.kode_sppg || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100 sm:col-span-2"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Nama SPPG</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{ unitSppg?.nama || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Status Operasional</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{ unitSppg?.status || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Tanggal Mulai Operasional</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{
                                        formatTanggalIndo(
                                            unitSppg?.tanggal_operasional,
                                            "Belum ada tanggal",
                                        )
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Terdaftar Pada</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{
                                        formatTanggalWaktuIndo(
                                            unitSppg?.created_at,
                                            "-",
                                        )
                                    }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span
                                    class="text-xs text-slate-500 font-medium block"
                                    >Terakhir Diperbarui</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-800"
                                    >{{
                                        formatTanggalWaktuIndo(
                                            unitSppg?.updated_at ||
                                                unitSppg?.created_at,
                                            "-",
                                        )
                                    }}</span
                                >
                            </div>
                        </div>

                        <!-- Edit View -->
                        <div v-else class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- ID SPPG (Bisa diedit, tepat 8 karakter alfanumerik) -->
                                <div class="space-y-1.5">
                                    <Label for="edit_id_sppg" :required="true"
                                        >ID SPPG</Label
                                    >
                                    <Input
                                        id="edit_id_sppg"
                                        v-model="formUnit.id_sppg"
                                        maxlength="8"
                                        placeholder="Contoh: SPPGZGYX"
                                        required
                                        @input="
                                            formUnit.id_sppg = formUnit.id_sppg
                                                .toUpperCase()
                                                .replace(/[^A-Z0-9]/g, '')
                                                .slice(0, 8);
                                            clearUnitError('id_sppg');
                                        "
                                        :className="
                                            getUnitError('id_sppg')
                                                ? 'border-destructive font-mono uppercase'
                                                : 'font-mono uppercase'
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Tepat 8 digit huruf/angka ({{
                                            formUnit.id_sppg.length
                                        }}/8)
                                    </p>
                                    <p
                                        v-if="getUnitError('id_sppg')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getUnitError("id_sppg") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_kode_sppg" :required="true"
                                        >Kode SPPG</Label
                                    >
                                    <Input
                                        id="edit_kode_sppg"
                                        v-model="formUnit.kode_sppg"
                                        placeholder="Contoh: 51.08.05.2013.03"
                                        required
                                        @input="clearUnitError('kode_sppg')"
                                        :className="
                                            getUnitError('kode_sppg')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p
                                        v-if="getUnitError('kode_sppg')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getUnitError("kode_sppg") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="edit_unit_nama" :required="true"
                                        >Nama SPPG</Label
                                    >
                                    <Input
                                        id="edit_unit_nama"
                                        v-model="formUnit.nama"
                                        placeholder="Contoh: SPPG Buleleng Sukasada Tegallinggah"
                                        required
                                        @input="clearUnitError('nama')"
                                        :className="
                                            getUnitError('nama')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p
                                        v-if="getUnitError('nama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getUnitError("nama") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="edit_unit_status"
                                        :required="true"
                                        >Status Operasional</Label
                                    >
                                    <Select
                                        id="edit_unit_status"
                                        v-model="formUnit.status"
                                        required
                                        @update:modelValue="
                                            clearUnitError('status')
                                        "
                                    >
                                        <option value="Operasional">
                                            Operasional
                                        </option>
                                        <option value="Belum Operasional">
                                            Belum Operasional
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
                                        v-if="getUnitError('status')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getUnitError("status") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_unit_tgl_op"
                                        >Tanggal Mulai Operasional</Label
                                    >
                                    <Input
                                        id="edit_unit_tgl_op"
                                        type="date"
                                        v-model="formUnit.tanggal_operasional"
                                    />
                                </div>
                            </div>

                            <!-- Wilayah Unit SPPG -->
                            <div
                                class="pt-4 border-t border-slate-200/80 space-y-4"
                            >
                                <div class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-primary" />
                                    <h4
                                        class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                    >
                                        Wilayah & Alamat Unit SPPG
                                    </h4>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                >
                                    <!-- Provinsi -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_unit_prov"
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
                                            id="edit_unit_prov"
                                            v-model="selectedUnitProvId"
                                            @update:modelValue="
                                                onUnitProvChange
                                            "
                                            :disabled="isProvincesLoading"
                                            required
                                            :placeholder="
                                                isProvincesLoading
                                                    ? 'Memuat provinsi...'
                                                    : 'Pilih Provinsi'
                                            "
                                        >
                                            <option
                                                v-for="prov in provincesList"
                                                :key="prov.id"
                                                :value="prov.id"
                                            >
                                                {{
                                                    formatWilayahName(prov.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="getUnitError('provinsi')"
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("provinsi") }}
                                        </p>
                                    </div>

                                    <!-- Kabupaten/Kota -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_unit_reg"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Kabupaten / Kota</span>
                                            <Loader2
                                                v-if="isUnitRegLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_unit_reg"
                                            v-model="selectedUnitRegId"
                                            @update:modelValue="onUnitRegChange"
                                            :disabled="
                                                !selectedUnitProvId ||
                                                isUnitRegLoading
                                            "
                                            required
                                            :placeholder="
                                                isUnitRegLoading
                                                    ? 'Memuat kabupaten/kota...'
                                                    : 'Pilih Kabupaten/Kota'
                                            "
                                        >
                                            <option
                                                v-for="reg in unitRegencies"
                                                :key="reg.id"
                                                :value="reg.id"
                                            >
                                                {{
                                                    formatKabupatenName(
                                                        reg.name,
                                                    )
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="getUnitError('kabupaten')"
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("kabupaten") }}
                                        </p>
                                    </div>

                                    <!-- Kecamatan -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_unit_dist"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Kecamatan</span>
                                            <Loader2
                                                v-if="isUnitDistLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_unit_dist"
                                            v-model="selectedUnitDistId"
                                            @update:modelValue="
                                                onUnitDistChange
                                            "
                                            :disabled="
                                                !selectedUnitRegId ||
                                                isUnitDistLoading
                                            "
                                            required
                                            :placeholder="
                                                isUnitDistLoading
                                                    ? 'Memuat kecamatan...'
                                                    : 'Pilih Kecamatan'
                                            "
                                        >
                                            <option
                                                v-for="dist in unitDistricts"
                                                :key="dist.id"
                                                :value="dist.id"
                                            >
                                                {{
                                                    formatWilayahName(dist.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="getUnitError('kecamatan')"
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("kecamatan") }}
                                        </p>
                                    </div>

                                    <!-- Desa/Kelurahan -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_unit_vill"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Desa / Kelurahan</span>
                                            <Loader2
                                                v-if="isUnitVillLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_unit_vill"
                                            v-model="formUnit.desa_kelurahan"
                                            @update:modelValue="
                                                clearUnitError('desa_kelurahan')
                                            "
                                            :disabled="
                                                !selectedUnitDistId ||
                                                isUnitVillLoading
                                            "
                                            required
                                            :placeholder="
                                                isUnitVillLoading
                                                    ? 'Memuat desa/kelurahan...'
                                                    : 'Pilih Desa/Kelurahan'
                                            "
                                        >
                                            <option
                                                v-for="vill in unitVillages"
                                                :key="vill.id"
                                                :value="
                                                    formatWilayahName(vill.name)
                                                "
                                            >
                                                {{
                                                    formatWilayahName(vill.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getUnitError('desa_kelurahan')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("desa_kelurahan") }}
                                        </p>
                                    </div>

                                    <!-- Kode Pos -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_unit_kode_pos"
                                            :required="true"
                                            >Kode Pos (5 Digit)</Label
                                        >
                                        <Input
                                            id="edit_unit_kode_pos"
                                            v-model="formUnit.kode_pos"
                                            placeholder="Contoh: 81161"
                                            maxlength="5"
                                            required
                                            @input="
                                                formUnit.kode_pos =
                                                    formUnit.kode_pos
                                                        .replace(/\D/g, '')
                                                        .slice(0, 5);
                                                clearUnitError('kode_pos');
                                            "
                                            :className="
                                                getUnitError('kode_pos')
                                                    ? 'border-destructive'
                                                    : ''
                                            "
                                        />
                                        <p
                                            v-if="getUnitError('kode_pos')"
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("kode_pos") }}
                                        </p>
                                    </div>

                                    <!-- Alamat Lengkap -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_unit_alamat_lengkap"
                                            :required="true"
                                            >Alamat Lengkap Unit SPPG</Label
                                        >
                                        <textarea
                                            id="edit_unit_alamat_lengkap"
                                            v-model="formUnit.alamat_lengkap"
                                            rows="2"
                                            placeholder="Nama jalan, patokan bangunan operasional..."
                                            required
                                            @input="
                                                clearUnitError('alamat_lengkap')
                                            "
                                            :class="
                                                'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                                (getUnitError('alamat_lengkap')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : 'border-input')
                                            "
                                        ></textarea>
                                        <p
                                            v-if="
                                                getUnitError('alamat_lengkap')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{ getUnitError("alamat_lengkap") }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </div>
            </Card>

            <!-- Peta Lokasi Unit SPPG (col-span-1) -->
            <Card
                className="bg-white border-slate-200 flex flex-col justify-between"
            >
                <div>
                    <CardHeader className="border-b border-slate-100 pb-3">
                        <CardTitle
                            className="text-base font-bold flex items-center gap-2"
                        >
                            <MapPin class="h-4 w-4 text-primary" />
                            <span>Titik Koordinat & Alamat</span>
                        </CardTitle>
                        <CardDescription class="text-xs">{{
                            isEditingUnit
                                ? "Klik peta atau geser pin untuk menentukan titik koordinat unit SPPG"
                                : "Koordinat peta dan alamat unit operasional"
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-4 space-y-3">
                        <MapPicker
                            :key="
                                isEditingUnit
                                    ? 'unit-map-edit'
                                    : 'unit-map-view'
                            "
                            v-if="
                                !isEditingUnit
                                    ? unitSppg?.latitude && unitSppg?.longitude
                                    : true
                            "
                            :latitude="
                                !isEditingUnit
                                    ? unitSppg?.latitude
                                    : formUnit.latitude
                            "
                            :longitude="
                                !isEditingUnit
                                    ? unitSppg?.longitude
                                    : formUnit.longitude
                            "
                            :readonly="!isEditingUnit"
                            height="240px"
                            label="Titik Koordinat Unit SPPG"
                            @update:latitude="
                                (val) => {
                                    formUnit.latitude = val;
                                    clearUnitError('latitude');
                                }
                            "
                            @update:longitude="
                                (val) => {
                                    formUnit.longitude = val;
                                    clearUnitError('latitude');
                                }
                            "
                        />
                        <div
                            v-else
                            class="h-44 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs"
                        >
                            Peta koordinat belum diatur
                        </div>

                        <p
                            v-if="getUnitError('latitude')"
                            class="text-xs text-destructive font-medium flex items-center gap-1"
                        >
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ getUnitError("latitude") }}
                        </p>

                        <!-- Alamat Lokasi & Alamat Lengkap Preview -->
                        <div class="space-y-2.5 pt-1">
                            <div
                                class="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs text-slate-500 font-semibold uppercase tracking-wider text-[11px]"
                                        >Alamat Unit SPPG</span
                                    >
                                </div>
                                <div
                                    class="text-xs sm:text-sm font-semibold text-slate-800 leading-relaxed"
                                >
                                    <span>
                                        {{
                                            !isEditingUnit
                                                ? `Desa/Kelurahan ${unitSppg?.desa_kelurahan || "-"}, Kecamatan ${unitSppg?.kecamatan || "-"}, Kabupaten/Kota ${unitSppg?.kabupaten || "-"}, Provinsi ${unitSppg?.provinsi || "-"}${unitSppg?.kode_pos ? ` (${unitSppg.kode_pos})` : ""}`
                                                : liveUnitAddress
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        (!isEditingUnit &&
                                            unitSppg?.alamat_lengkap) ||
                                        (isEditingUnit &&
                                            formUnit.alamat_lengkap)
                                    "
                                    class="pt-1 border-t border-slate-200/60 text-xs text-slate-600"
                                >
                                    <span
                                        class="font-medium text-slate-500 block text-[11px]"
                                        >Alamat Lengkap:</span
                                    >
                                    <span
                                        class="font-normal text-slate-800 mt-0.5 block"
                                        >{{
                                            !isEditingUnit
                                                ? unitSppg.alamat_lengkap
                                                : formUnit.alamat_lengkap
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </div>
            </Card>
        </div>

        <!-- ================= TAB 2: PROFIL KEPALA SPPG ================= -->
        <div
            v-show="activeTab === 'profile'"
            class="grid grid-cols-1 lg:grid-cols-3 gap-6"
        >
            <!-- Detail Lengkap Biodata Kepala SPPG (col-span-2) -->
            <Card
                className="lg:col-span-2 bg-white border-slate-200 flex flex-col justify-between"
            >
                <div>
                    <CardHeader className="border-b border-slate-100 pb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-16 w-16 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl font-extrabold shadow-md shrink-0"
                                >
                                    {{ userInitials }}
                                </div>
                                <div>
                                    <CardTitle
                                        className="text-lg font-bold text-slate-900"
                                        >{{ fullName }}</CardTitle
                                    >
                                    <CardDescription class="text-xs mt-0.5">
                                        Kepala SPPG &bull;
                                        {{
                                            isEditingProfile
                                                ? "Mode Edit Profil"
                                                : "Informasi Lengkap"
                                        }}
                                    </CardDescription>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="!isEditingProfile"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="startEditProfile"
                                    className="flex items-center gap-1.5 font-medium border-slate-300 text-slate-700 hover:bg-slate-50 cursor-pointer"
                                >
                                    <Edit3 class="h-3.5 w-3.5 text-primary" />
                                    <span>Edit Data Pengguna</span>
                                </Button>
                                <template v-else>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="cancelEditProfile"
                                        :disabled="formProfile.processing"
                                        className="flex items-center gap-1.5 text-slate-600 cursor-pointer"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        <span>Batal</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        @click="submitEditProfile"
                                        :disabled="formProfile.processing"
                                        className="flex items-center gap-1.5 bg-primary text-white cursor-pointer"
                                    >
                                        <Loader2
                                            v-if="formProfile.processing"
                                            class="h-3.5 w-3.5 animate-spin"
                                        />
                                        <Save v-else class="h-3.5 w-3.5" />
                                        <span>Simpan Perubahan</span>
                                    </Button>
                                </template>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-6">
                        <!-- Read-only View -->
                        <div
                            v-if="!isEditingProfile"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs"
                        >
                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >NIK</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                    >{{ user.nik || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >NIP</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                    >{{ user.nip || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Email</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900 truncate block"
                                    >{{ user.email || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Nomor Telepon</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                    >+{{ user.telepon || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Tempat, Tanggal Lahir</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        user.tempat_lahir
                                            ? `${user.tempat_lahir}, `
                                            : ""
                                    }}{{
                                        formatTanggalIndo(user.tanggal_lahir)
                                    }}
                                </span>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Agama</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                    >{{ user.agama || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Jenis Kelamin</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        user.jenis_kelamin === "L"
                                            ? "Laki-laki"
                                            : user.jenis_kelamin === "P"
                                              ? "Perempuan"
                                              : "-"
                                    }}
                                </span>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Jenjang & Bidang Pendidikan</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        [
                                            user.jenjang_pendidikan,
                                            user.bidang_pendidikan,
                                        ]
                                            .filter(Boolean)
                                            .join(" ") || "-"
                                    }}
                                </span>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Status Perkawinan</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                    >{{ user.status_kawin || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Hak Akses</span
                                >
                                <span
                                    class="text-sm capitalize font-semibold text-slate-900"
                                    >{{ user.role || "-" }}</span
                                >
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Terdaftar Pada</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        formatTanggalWaktuIndo(user.created_at)
                                    }}
                                </span>
                            </div>

                            <div
                                class="p-3.5 rounded-lg bg-slate-50 border border-slate-100"
                            >
                                <span class="text-slate-500 font-medium block"
                                    >Terakhir Diperbarui</span
                                >
                                <span
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        formatTanggalWaktuIndo(
                                            user.updated_at || user.created_at,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Edit View Profile -->
                        <div v-else class="space-y-5">
                            <!-- NIK & NIP -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="edit_nik" :required="true"
                                        >NIK (Nomor Induk Kependudukan)</Label
                                    >
                                    <Input
                                        id="edit_nik"
                                        v-model="formProfile.nik"
                                        maxlength="16"
                                        placeholder="16 digit angka NIK KTP"
                                        required
                                        @input="
                                            formProfile.nik = formProfile.nik
                                                .replace(/\D/g, '')
                                                .slice(0, 16);
                                            clearProfileError('nik');
                                        "
                                        :className="
                                            getProfileError('nik')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Tepat 16 digit angka ({{
                                            formProfile.nik.length
                                        }}/16)
                                    </p>
                                    <p
                                        v-if="getProfileError('nik')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("nik") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_nip"
                                        >NIP (Nomor Induk Pegawai)</Label
                                    >
                                    <Input
                                        id="edit_nip"
                                        v-model="formProfile.nip"
                                        placeholder="Contoh: 199005152015031001"
                                        @input="
                                            formProfile.nip =
                                                formProfile.nip.replace(
                                                    /\D/g,
                                                    '',
                                                );
                                            clearProfileError('nip');
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Opsional, hanya angka jika ada
                                    </p>
                                </div>
                            </div>

                            <!-- Nama & Gelar -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="edit_nama" :required="true"
                                        >Nama Lengkap (tanpa gelar)</Label
                                    >
                                    <Input
                                        id="edit_nama"
                                        v-model="formProfile.nama"
                                        placeholder="Nama lengkap sesuai KTP"
                                        required
                                        @input="clearProfileError('nama')"
                                        :className="
                                            getProfileError('nama')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p
                                        v-if="getProfileError('nama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("nama") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_gelar_depan"
                                        >Gelar Depan</Label
                                    >
                                    <Input
                                        id="edit_gelar_depan"
                                        v-model="formProfile.gelar_depan"
                                        placeholder="Contoh: Ir., Dr., Drs."
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_gelar_belakang"
                                        >Gelar Belakang</Label
                                    >
                                    <Input
                                        id="edit_gelar_belakang"
                                        v-model="formProfile.gelar_belakang"
                                        placeholder="Contoh: S.T., M.T., M.Kom."
                                    />
                                </div>
                            </div>

                            <!-- Agama & Jenis Kelamin -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label for="edit_agama" :required="true"
                                        >Agama</Label
                                    >
                                    <Select
                                        id="edit_agama"
                                        v-model="formProfile.agama"
                                        required
                                        @update:modelValue="
                                            clearProfileError('agama')
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
                                        v-if="getProfileError('agama')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("agama") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="edit_jenis_kelamin"
                                        :required="true"
                                        >Jenis Kelamin</Label
                                    >
                                    <Select
                                        id="edit_jenis_kelamin"
                                        v-model="formProfile.jenis_kelamin"
                                        required
                                        @update:modelValue="
                                            clearProfileError('jenis_kelamin')
                                        "
                                    >
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </Select>
                                    <p
                                        v-if="getProfileError('jenis_kelamin')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("jenis_kelamin") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="edit_tempat_lahir"
                                        :required="true"
                                        >Tempat Lahir</Label
                                    >
                                    <Input
                                        id="edit_tempat_lahir"
                                        v-model="formProfile.tempat_lahir"
                                        placeholder="Contoh: Buleleng"
                                        required
                                        @input="
                                            formProfile.tempat_lahir =
                                                formProfile.tempat_lahir.replace(
                                                    /[^a-zA-Z\s\.\,\-]/g,
                                                    '',
                                                );
                                            clearProfileError('tempat_lahir');
                                        "
                                        :className="
                                            getProfileError('tempat_lahir')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p class="text-[11px] text-slate-500">
                                        Hanya huruf
                                    </p>
                                    <p
                                        v-if="getProfileError('tempat_lahir')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("tempat_lahir") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="edit_tanggal_lahir"
                                        :required="true"
                                        >Tanggal Lahir</Label
                                    >
                                    <Input
                                        id="edit_tanggal_lahir"
                                        type="date"
                                        v-model="formProfile.tanggal_lahir"
                                        required
                                        @input="
                                            clearProfileError('tanggal_lahir')
                                        "
                                    />
                                    <p
                                        v-if="getProfileError('tanggal_lahir')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("tanggal_lahir") }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="edit_jenjang" :required="true"
                                        >Jenjang Pendidikan</Label
                                    >
                                    <Select
                                        id="edit_jenjang"
                                        v-model="formProfile.jenjang_pendidikan"
                                        required
                                        @update:modelValue="
                                            clearProfileError(
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
                                            getProfileError(
                                                'jenjang_pendidikan',
                                            )
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getProfileError(
                                                "jenjang_pendidikan",
                                            )
                                        }}
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label
                                        for="edit_bidang_pendidikan"
                                        :required="true"
                                        >Bidang Pendidikan / Jurusan</Label
                                    >
                                    <Input
                                        id="edit_bidang_pendidikan"
                                        v-model="formProfile.bidang_pendidikan"
                                        placeholder="Contoh: Teknik Informatika / Manajemen"
                                        required
                                        @input="
                                            clearProfileError(
                                                'bidang_pendidikan',
                                            )
                                        "
                                        :className="
                                            getProfileError('bidang_pendidikan')
                                                ? 'border-destructive'
                                                : ''
                                        "
                                    />
                                    <p
                                        v-if="
                                            getProfileError('bidang_pendidikan')
                                        "
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{
                                            getProfileError("bidang_pendidikan")
                                        }}
                                    </p>
                                </div>

                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label
                                        for="edit_status_kawin"
                                        :required="true"
                                        >Status Perkawinan</Label
                                    >
                                    <Select
                                        id="edit_status_kawin"
                                        v-model="formProfile.status_kawin"
                                        required
                                        @update:modelValue="
                                            clearProfileError('status_kawin')
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
                                        v-if="getProfileError('status_kawin')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("status_kawin") }}
                                    </p>
                                </div>
                            </div>

                            <!-- Kontak & Akun (Dengan format Telepon Kunci +62 dan Email Readonly) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="edit_telepon" :required="true"
                                        >Nomor Telepon / WhatsApp</Label
                                    >
                                    <div class="flex rounded-md shadow-sm">
                                        <span
                                            class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-slate-100 text-slate-700 font-semibold text-sm select-none"
                                        >
                                            +62
                                        </span>
                                        <Input
                                            id="edit_telepon"
                                            v-model="rawPhone"
                                            placeholder="Contoh: 85239182736 (tanpa 0 di depan)"
                                            required
                                            :className="
                                                'rounded-l-none ' +
                                                (getProfileError('telepon')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : '')
                                            "
                                        />
                                    </div>
                                    <p
                                        v-if="getProfileError('telepon')"
                                        class="text-xs text-destructive font-medium flex items-center gap-1"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0"
                                        />
                                        {{ getProfileError("telepon") }}
                                    </p>
                                </div>

                                <!-- Email Akun (Terkunci / Disabled) -->
                                <div class="space-y-1.5 sm:col-span-2">
                                    <Label for="edit_email"
                                        >Email Akun (Terkunci)</Label
                                    >
                                    <Input
                                        id="edit_email"
                                        type="email"
                                        :model-value="props.user.email"
                                        disabled
                                        className="bg-slate-100 font-medium text-slate-500 cursor-not-allowed select-none"
                                    />
                                    <p class="text-[11px] text-slate-400">
                                        Alamat email akun tidak dapat diubah
                                        oleh pengguna.
                                    </p>
                                </div>
                            </div>

                            <!-- Section A: Alamat Sesuai KTP -->
                            <div
                                class="pt-4 border-t border-slate-200/80 space-y-4"
                            >
                                <div class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-primary" />
                                    <h4
                                        class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                    >
                                        Alamat Sesuai KTP (Wilayah API)
                                    </h4>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                >
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_ktp_prov"
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
                                            id="edit_ktp_prov"
                                            v-model="selectedKtpProvId"
                                            @update:modelValue="onKtpProvChange"
                                            :disabled="isProvincesLoading"
                                            required
                                            :placeholder="
                                                isProvincesLoading
                                                    ? 'Memuat provinsi...'
                                                    : 'Pilih Provinsi'
                                            "
                                        >
                                            <option
                                                v-for="prov in provincesList"
                                                :key="prov.id"
                                                :value="prov.id"
                                            >
                                                {{
                                                    formatWilayahName(prov.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError('provinsi_ktp')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError("provinsi_ktp")
                                            }}
                                        </p>
                                    </div>

                                    <!-- Kabupaten KTP -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_ktp_reg"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Kabupaten / Kota KTP</span>
                                            <Loader2
                                                v-if="isKtpRegLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_ktp_reg"
                                            v-model="selectedKtpRegId"
                                            @update:modelValue="onKtpRegChange"
                                            :disabled="
                                                !selectedKtpProvId ||
                                                isKtpRegLoading
                                            "
                                            required
                                            :placeholder="
                                                isKtpRegLoading
                                                    ? 'Memuat kabupaten/kota...'
                                                    : 'Pilih Kabupaten/Kota'
                                            "
                                        >
                                            <option
                                                v-for="reg in ktpRegencies"
                                                :key="reg.id"
                                                :value="reg.id"
                                            >
                                                {{
                                                    formatKabupatenName(
                                                        reg.name,
                                                    )
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError('kabupaten_ktp')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError("kabupaten_ktp")
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_ktp_dist"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Kecamatan KTP</span>
                                            <Loader2
                                                v-if="isKtpDistLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_ktp_dist"
                                            v-model="selectedKtpDistId"
                                            @update:modelValue="onKtpDistChange"
                                            :disabled="
                                                !selectedKtpRegId ||
                                                isKtpDistLoading
                                            "
                                            required
                                            :placeholder="
                                                isKtpDistLoading
                                                    ? 'Memuat kecamatan...'
                                                    : 'Pilih Kecamatan'
                                            "
                                        >
                                            <option
                                                v-for="dist in ktpDistricts"
                                                :key="dist.id"
                                                :value="dist.id"
                                            >
                                                {{
                                                    formatWilayahName(dist.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError('kecamatan_ktp')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError("kecamatan_ktp")
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_ktp_vill"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Desa / Kelurahan KTP</span>
                                            <Loader2
                                                v-if="isKtpVillLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_ktp_vill"
                                            v-model="
                                                formProfile.desa_kelurahan_ktp
                                            "
                                            @update:modelValue="
                                                clearProfileError(
                                                    'desa_kelurahan_ktp',
                                                )
                                            "
                                            :disabled="
                                                !selectedKtpDistId ||
                                                isKtpVillLoading
                                            "
                                            required
                                            :placeholder="
                                                isKtpVillLoading
                                                    ? 'Memuat desa/kelurahan...'
                                                    : 'Pilih Desa/Kelurahan'
                                            "
                                        >
                                            <option
                                                v-for="vill in ktpVillages"
                                                :key="vill.id"
                                                :value="
                                                    formatWilayahName(vill.name)
                                                "
                                            >
                                                {{
                                                    formatWilayahName(vill.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'desa_kelurahan_ktp',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "desa_kelurahan_ktp",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_ktp_kode_pos"
                                            :required="true"
                                            >Kode Pos KTP (5 Digit)</Label
                                        >
                                        <Input
                                            id="edit_ktp_kode_pos"
                                            v-model="formProfile.kode_pos_ktp"
                                            placeholder="Contoh: 81161"
                                            maxlength="5"
                                            required
                                            @input="
                                                formProfile.kode_pos_ktp =
                                                    formProfile.kode_pos_ktp
                                                        .replace(/\D/g, '')
                                                        .slice(0, 5);
                                                clearProfileError(
                                                    'kode_pos_ktp',
                                                );
                                            "
                                            :className="
                                                getProfileError('kode_pos_ktp')
                                                    ? 'border-destructive'
                                                    : ''
                                            "
                                        />
                                        <p
                                            v-if="
                                                getProfileError('kode_pos_ktp')
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError("kode_pos_ktp")
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_ktp_alamat_lengkap"
                                            :required="true"
                                            >Alamat Lengkap Sesuai KTP</Label
                                        >
                                        <textarea
                                            id="edit_ktp_alamat_lengkap"
                                            v-model="
                                                formProfile.alamat_lengkap_ktp
                                            "
                                            rows="2"
                                            placeholder="Nama jalan, nomor rumah, RT/RW, gang..."
                                            required
                                            @input="
                                                clearProfileError(
                                                    'alamat_lengkap_ktp',
                                                )
                                            "
                                            :class="
                                                'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                                (getProfileError(
                                                    'alamat_lengkap_ktp',
                                                )
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : 'border-input')
                                            "
                                        ></textarea>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'alamat_lengkap_ktp',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "alamat_lengkap_ktp",
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section B: Alamat Domisili -->
                            <div
                                class="pt-4 border-t border-slate-200/80 space-y-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="h-4 w-4 text-primary" />
                                        <h4
                                            class="text-xs font-bold text-slate-800 uppercase tracking-wider"
                                        >
                                            Alamat Domisili (Wilayah API)
                                        </h4>
                                    </div>
                                    <button
                                        type="button"
                                        @click="copyKtpToDomisili"
                                        :disabled="!selectedKtpProvId"
                                        class="text-xs font-semibold text-primary hover:underline flex items-center gap-1 cursor-pointer disabled:opacity-40"
                                    >
                                        <Copy class="h-3 w-3" />
                                        <span>Sama dengan KTP</span>
                                    </button>
                                </div>

                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                >
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_dom_prov"
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
                                            id="edit_dom_prov"
                                            v-model="selectedDomProvId"
                                            @update:modelValue="onDomProvChange"
                                            :disabled="isProvincesLoading"
                                            required
                                            :placeholder="
                                                isProvincesLoading
                                                    ? 'Memuat provinsi...'
                                                    : 'Pilih Provinsi'
                                            "
                                        >
                                            <option
                                                v-for="prov in provincesList"
                                                :key="prov.id"
                                                :value="prov.id"
                                            >
                                                {{
                                                    formatWilayahName(prov.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'provinsi_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "provinsi_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- Kabupaten Domisili -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_dom_reg"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                >Kabupaten / Kota Domisili</span
                                            >
                                            <Loader2
                                                v-if="isDomRegLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_dom_reg"
                                            v-model="selectedDomRegId"
                                            @update:modelValue="onDomRegChange"
                                            :disabled="
                                                !selectedDomProvId ||
                                                isDomRegLoading
                                            "
                                            required
                                            :placeholder="
                                                isDomRegLoading
                                                    ? 'Memuat kabupaten/kota...'
                                                    : 'Pilih Kabupaten/Kota'
                                            "
                                        >
                                            <option
                                                v-for="reg in domRegencies"
                                                :key="reg.id"
                                                :value="reg.id"
                                            >
                                                {{
                                                    formatKabupatenName(
                                                        reg.name,
                                                    )
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'kabupaten_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "kabupaten_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_dom_dist"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span>Kecamatan Domisili</span>
                                            <Loader2
                                                v-if="isDomDistLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_dom_dist"
                                            v-model="selectedDomDistId"
                                            @update:modelValue="onDomDistChange"
                                            :disabled="
                                                !selectedDomRegId ||
                                                isDomDistLoading
                                            "
                                            required
                                            :placeholder="
                                                isDomDistLoading
                                                    ? 'Memuat kecamatan...'
                                                    : 'Pilih Kecamatan'
                                            "
                                        >
                                            <option
                                                v-for="dist in domDistricts"
                                                :key="dist.id"
                                                :value="dist.id"
                                            >
                                                {{
                                                    formatWilayahName(dist.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'kecamatan_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "kecamatan_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="edit_dom_vill"
                                            :required="true"
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                >Desa / Kelurahan Domisili</span
                                            >
                                            <Loader2
                                                v-if="isDomVillLoading"
                                                class="h-3 w-3 animate-spin text-primary"
                                            />
                                        </Label>
                                        <Select
                                            id="edit_dom_vill"
                                            v-model="
                                                formProfile.desa_kelurahan_domisili
                                            "
                                            @update:modelValue="
                                                clearProfileError(
                                                    'desa_kelurahan_domisili',
                                                )
                                            "
                                            :disabled="
                                                !selectedDomDistId ||
                                                isDomVillLoading
                                            "
                                            required
                                            :placeholder="
                                                isDomVillLoading
                                                    ? 'Memuat desa/kelurahan...'
                                                    : 'Pilih Desa/Kelurahan'
                                            "
                                        >
                                            <option
                                                v-for="vill in domVillages"
                                                :key="vill.id"
                                                :value="
                                                    formatWilayahName(vill.name)
                                                "
                                            >
                                                {{
                                                    formatWilayahName(vill.name)
                                                }}
                                            </option>
                                        </Select>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'desa_kelurahan_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "desa_kelurahan_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_dom_kode_pos"
                                            :required="true"
                                            >Kode Pos Domisili (5 Digit)</Label
                                        >
                                        <Input
                                            id="edit_dom_kode_pos"
                                            v-model="
                                                formProfile.kode_pos_domisili
                                            "
                                            placeholder="Contoh: 81161"
                                            maxlength="5"
                                            required
                                            @input="
                                                formProfile.kode_pos_domisili =
                                                    formProfile.kode_pos_domisili
                                                        .replace(/\D/g, '')
                                                        .slice(0, 5);
                                                clearProfileError(
                                                    'kode_pos_domisili',
                                                );
                                            "
                                            :className="
                                                getProfileError(
                                                    'kode_pos_domisili',
                                                )
                                                    ? 'border-destructive'
                                                    : ''
                                            "
                                        />
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'kode_pos_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "kode_pos_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1.5 sm:col-span-2">
                                        <Label
                                            for="edit_dom_alamat_lengkap"
                                            :required="true"
                                            >Alamat Lengkap Domisili</Label
                                        >
                                        <textarea
                                            id="edit_dom_alamat_lengkap"
                                            v-model="
                                                formProfile.alamat_lengkap_domisili
                                            "
                                            rows="2"
                                            placeholder="Nama jalan, nomor rumah, RT/RW, patokan tempat tinggal saat ini..."
                                            required
                                            @input="
                                                clearProfileError(
                                                    'alamat_lengkap_domisili',
                                                )
                                            "
                                            :class="
                                                'flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' +
                                                (getProfileError(
                                                    'alamat_lengkap_domisili',
                                                )
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : 'border-input')
                                            "
                                        ></textarea>
                                        <p
                                            v-if="
                                                getProfileError(
                                                    'alamat_lengkap_domisili',
                                                )
                                            "
                                            class="text-xs text-destructive font-medium flex items-center gap-1"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0"
                                            />
                                            {{
                                                getProfileError(
                                                    "alamat_lengkap_domisili",
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </div>
            </Card>

            <!-- Peta Lokasi Domisili Kepala SPPG (col-span-1) -->
            <Card
                className="bg-white border-slate-200 flex flex-col justify-between"
            >
                <div>
                    <CardHeader className="border-b border-slate-100 pb-3">
                        <CardTitle
                            className="text-base font-bold flex items-center gap-2"
                        >
                            <MapPin class="h-4 w-4 text-primary" />
                            <span>Titik Koordinat & Alamat</span>
                        </CardTitle>
                        <CardDescription class="text-xs">{{
                            isEditingProfile
                                ? "Klik peta atau geser pin untuk menentukan titik koordinat domisili"
                                : "Koordinat peta dan alamat Kepala SPPG"
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-4 space-y-3">
                        <MapPicker
                            :key="
                                isEditingProfile
                                    ? 'profile-map-edit'
                                    : 'profile-map-view'
                            "
                            v-if="
                                !isEditingProfile
                                    ? user?.latitude_domisili &&
                                      user?.longitude_domisili
                                    : true
                            "
                            :latitude="
                                !isEditingProfile
                                    ? user?.latitude_domisili
                                    : formProfile.latitude_domisili
                            "
                            :longitude="
                                !isEditingProfile
                                    ? user?.longitude_domisili
                                    : formProfile.longitude_domisili
                            "
                            :readonly="!isEditingProfile"
                            height="240px"
                            label="Titik Koordinat Domisili"
                            @update:latitude="
                                (val) => {
                                    formProfile.latitude_domisili = val;
                                    clearProfileError('latitude_domisili');
                                }
                            "
                            @update:longitude="
                                (val) => {
                                    formProfile.longitude_domisili = val;
                                    clearProfileError('latitude_domisili');
                                }
                            "
                        />
                        <div
                            v-else
                            class="h-44 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs"
                        >
                            Peta koordinat domisili belum diatur
                        </div>

                        <p
                            v-if="getProfileError('latitude_domisili')"
                            class="text-xs text-destructive font-medium flex items-center gap-1"
                        >
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ getProfileError("latitude_domisili") }}
                        </p>

                        <!-- Alamat KTP & Domisili Preview -->
                        <div class="space-y-2.5 pt-1">
                            <!-- Alamat Sesuai KTP -->
                            <div
                                class="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs text-slate-500 font-semibold uppercase tracking-wider text-[11px]"
                                        >Alamat Sesuai KTP</span
                                    >
                                </div>
                                <div
                                    class="text-xs sm:text-sm font-semibold text-slate-800 leading-relaxed"
                                >
                                    <span>
                                        {{
                                            !isEditingProfile
                                                ? [
                                                      user.desa_kelurahan_ktp
                                                          ? `Desa/Kelurahan ${user.desa_kelurahan_ktp}`
                                                          : "",
                                                      user.kecamatan_ktp
                                                          ? `Kecamatan ${user.kecamatan_ktp}`
                                                          : "",
                                                      user.kabupaten_ktp
                                                          ? `Kabupaten/Kota ${user.kabupaten_ktp}`
                                                          : "",
                                                      user.provinsi_ktp
                                                          ? `Provinsi ${user.provinsi_ktp}${user.kode_pos_ktp ? ` (${user.kode_pos_ktp})` : ""}`
                                                          : user.kode_pos_ktp
                                                            ? `(${user.kode_pos_ktp})`
                                                            : "",
                                                  ]
                                                      .filter(Boolean)
                                                      .join(", ") || "-"
                                                : liveKtpAddress
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        (!isEditingProfile &&
                                            user.alamat_lengkap_ktp) ||
                                        (isEditingProfile &&
                                            formProfile.alamat_lengkap_ktp)
                                    "
                                    class="pt-1 border-t border-slate-200/60 text-xs text-slate-600"
                                >
                                    <span
                                        class="font-medium text-slate-500 block text-[11px]"
                                        >Alamat Lengkap:</span
                                    >
                                    <span
                                        class="font-normal text-slate-800 mt-0.5 block"
                                        >{{
                                            !isEditingProfile
                                                ? user.alamat_lengkap_ktp
                                                : formProfile.alamat_lengkap_ktp
                                        }}</span
                                    >
                                </div>
                            </div>

                            <!-- Alamat Domisili -->
                            <div
                                class="p-3 rounded-lg bg-slate-50 border border-slate-100 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs text-slate-500 font-semibold uppercase tracking-wider text-[11px]"
                                        >Alamat Domisili</span
                                    >
                                </div>
                                <div
                                    class="text-xs sm:text-sm font-semibold text-slate-800 leading-relaxed"
                                >
                                    <span>
                                        {{
                                            !isEditingProfile
                                                ? [
                                                      user.desa_kelurahan_domisili
                                                          ? `Desa/Kelurahan ${user.desa_kelurahan_domisili}`
                                                          : "",
                                                      user.kecamatan_domisili
                                                          ? `Kecamatan ${user.kecamatan_domisili}`
                                                          : "",
                                                      user.kabupaten_domisili
                                                          ? `Kabupaten/Kota ${user.kabupaten_domisili}`
                                                          : "",
                                                      user.provinsi_domisili
                                                          ? `Provinsi ${user.provinsi_domisili}${user.kode_pos_domisili ? ` (${user.kode_pos_domisili})` : ""}`
                                                          : user.kode_pos_domisili
                                                            ? `(${user.kode_pos_domisili})`
                                                            : "",
                                                  ]
                                                      .filter(Boolean)
                                                      .join(", ") || "-"
                                                : liveDomisiliAddress
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        (!isEditingProfile &&
                                            user.alamat_lengkap_domisili) ||
                                        (isEditingProfile &&
                                            formProfile.alamat_lengkap_domisili)
                                    "
                                    class="pt-1 border-t border-slate-200/60 text-xs text-slate-600"
                                >
                                    <span
                                        class="font-medium text-slate-500 block text-[11px]"
                                        >Alamat Lengkap:</span
                                    >
                                    <span
                                        class="font-normal text-slate-800 mt-0.5 block"
                                        >{{
                                            !isEditingProfile
                                                ? user.alamat_lengkap_domisili
                                                : formProfile.alamat_lengkap_domisili
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </div>
            </Card>
        </div>

        <!-- ================= TAB 3: PETA & GEOSPASIAL ================= -->
        <div v-show="activeTab === 'maps'" class="space-y-4">
            <Card
                className="bg-white border-slate-200 shadow-sm overflow-hidden"
            >
                <CardHeader className="border-b border-slate-100 p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle
                                className="text-base sm:text-lg font-bold flex items-center gap-2 text-slate-900"
                            >
                                <Map class="h-5 w-5 text-primary" />
                                <span>Peta Analisis Geospasial SPPG</span>
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm mt-0.5"
                                >Visualisasi spasial 2-titik presisi: Titik Awal
                                (Domisili Kepala SPPG) dan Titik Tujuan (Unit
                                SPPG) beserta radius dan rute jalan
                                riil</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="p-4 sm:p-6">
                    <GeospatialMap
                        v-if="
                            user?.latitude_domisili &&
                            user?.longitude_domisili &&
                            unitSppg?.latitude &&
                            unitSppg?.longitude
                        "
                        ref="geoMapRef"
                        :domisili-lat="user.latitude_domisili"
                        :domisili-lng="user.longitude_domisili"
                        :domisili-label="fullName"
                        :domisili-address="domisiliFullAddress"
                        :unit-lat="unitSppg.latitude"
                        :unit-lng="unitSppg.longitude"
                        :unit-label="unitSppg.nama"
                        :unit-address="unitFullAddress"
                        height="520px"
                    />
                    <div
                        v-else
                        class="h-80 rounded-xl bg-slate-50 border border-dashed border-slate-200 flex flex-col items-center justify-center text-center p-6"
                    >
                        <AlertTriangle class="h-10 w-10 text-amber-500 mb-2" />
                        <h4 class="text-sm font-bold text-slate-800">
                            Data Koordinat Belum Lengkap
                        </h4>
                        <p class="text-xs text-slate-500 max-w-md mt-1">
                            Pastikan koordinat lokasi domisili Kepala SPPG dan
                            lokasi operasional Unit SPPG telah diisi untuk
                            menampilkan visualisasi peta geospasial.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
