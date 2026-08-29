<script setup>
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import Input from "@/Components/ui/Input.vue";
import Label from "@/Components/ui/Label.vue";
import Select from "@/Components/ui/Select.vue";
import MapPicker from "@/Components/MapPicker.vue";
import {
    Building2,
    MapPin,
    Edit3,
    Save,
    X,
    Loader2,
    AlertCircle,
} from "lucide-vue-next";

import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
    formatWilayahName,
    formatKabupatenName,
} from "@/Services/wilayah";
import { formatTanggalIndo, formatTanggalWaktuIndo } from "@/lib/utils";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
});

// ================= EDIT STATE =================
const isEditingUnit = ref(false);
const unitClientErrors = ref({});

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

// Form
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
</script>

<template>
    <!-- ================= TAB 1: DATA UNIT SPPG ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
</template>
