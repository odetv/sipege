<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ArrowLeft, Save } from "lucide-vue-next";
import {
    ALERGI_OPTIONS,
    getSubKategoriByKategori,
    getJenisPorsiBySubKategori,
    sortRincianByKategori,
} from "@/Services/penerimaManfaatConfig";
import {
    getProvinces,
    getRegencies,
    getDistricts,
    getVillages,
} from "@/Services/wilayah";

// Partials
import FormIdentitasSection from "@/Pages/PenerimaManfaat/Partials/FormIdentitasSection.vue";
import FormKontakSection from "@/Pages/PenerimaManfaat/Partials/FormKontakSection.vue";
import FormAlamatSection from "@/Pages/PenerimaManfaat/Partials/FormAlamatSection.vue";
import FormRincianSection from "@/Pages/PenerimaManfaat/Partials/FormRincianSection.vue";
import FormAlergiSection from "@/Pages/PenerimaManfaat/Partials/FormAlergiSection.vue";

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
        return [
            Number(props.kelompok.latitude),
            Number(props.kelompok.longitude),
        ];
    }
    return [-8.409518, 115.188916];
});

function normalizeAlergiData(data) {
    if (!Array.isArray(data)) return [];
    return data.map((item) => {
        if (typeof item === "string") {
            return {
                jenis_alergi: item,
                porsi_kecil: 0,
                porsi_besar: 0,
            };
        }
        return {
            jenis_alergi: item.jenis_alergi || "",
            porsi_kecil: Number(item.porsi_kecil) || 0,
            porsi_besar: Number(item.porsi_besar) || 0,
        };
    });
}

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

// Form state
const form = useForm({
    nama_kelompok: props.kelompok.nama_kelompok || "",
    kategori: props.kelompok.kategori || "SD",
    jenis_kepemilikan: props.kelompok.jenis_kepemilikan || "Negeri",
    tipe_identitas: props.kelompok.tipe_identitas || "NPSN",
    kode_identitas: props.kelompok.kode_identitas || "",
    nama_kepala: props.kelompok.nama_kepala || "",
    email_kepala: props.kelompok.email_kepala || "",
    telepon_kepala: props.kelompok.telepon_kepala || "",
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
    longitude: props.kelompok.longitude
        ? Number(props.kelompok.longitude)
        : null,
    jumlah_kader:
        Number(props.kelompok.jumlah_kader) ||
        (props.kelompok.kategori === "Posyandu" ? 5 : 0),
    alergi_porsi_kecil: Number(props.kelompok.alergi_porsi_kecil) || 0,
    alergi_porsi_besar: Number(props.kelompok.alergi_porsi_besar) || 0,
    keterangan_alergi: normalizeAlergiData(props.kelompok.keterangan_alergi),
    rincian: sortRincianByKategori(
        Array.isArray(props.kelompok.rincian)
            ? props.kelompok.rincian.map((r) => ({
                  id: r.id,
                  sub_kategori: r.sub_kategori,
                  jenis_porsi:
                      r.jenis_porsi ||
                      getJenisPorsiBySubKategori(
                          r.sub_kategori,
                          props.kelompok.kategori,
                      ),
                  jumlah_laki_laki: Number(r.jumlah_laki_laki) || 0,
                  jumlah_perempuan: Number(r.jumlah_perempuan) || 0,
              }))
            : [],
        props.kelompok.kategori,
    ),
});

// Helper state & functions untuk data alergi per jenis
const customAlergiInput = ref("");
const showCustomAlergiInput = ref(false);

const totalAlergiPK = computed(() => {
    if (!Array.isArray(form.keterangan_alergi)) return 0;
    return form.keterangan_alergi.reduce(
        (sum, item) => sum + (Number(item.porsi_kecil) || 0),
        0,
    );
});

const totalAlergiPB = computed(() => {
    if (!Array.isArray(form.keterangan_alergi)) return 0;
    return form.keterangan_alergi.reduce(
        (sum, item) => sum + (Number(item.porsi_besar) || 0),
        0,
    );
});

const grandTotalAlergi = computed(() => {
    return totalAlergiPK.value + totalAlergiPB.value;
});

// Sync total porsi alergi PK & PB ke form payload
watch([totalAlergiPK, totalAlergiPB], ([pk, pb]) => {
    form.alergi_porsi_kecil = pk;
    form.alergi_porsi_besar = pb;
});

function isAlergiSelected(jenis) {
    if (jenis === "Lainnya") return showCustomAlergiInput.value;
    if (!Array.isArray(form.keterangan_alergi)) return false;
    return form.keterangan_alergi.some(
        (item) => item.jenis_alergi === jenis,
    );
}

function toggleAlergi(jenis) {
    if (jenis === "Lainnya") {
        showCustomAlergiInput.value = !showCustomAlergiInput.value;
        return;
    }
    if (!Array.isArray(form.keterangan_alergi)) form.keterangan_alergi = [];
    const idx = form.keterangan_alergi.findIndex(
        (item) => item.jenis_alergi === jenis,
    );
    if (idx > -1) {
        form.keterangan_alergi.splice(idx, 1);
    } else {
        form.keterangan_alergi.push({
            jenis_alergi: jenis,
            porsi_kecil: 0,
            porsi_besar: 0,
        });
    }
}

function getSelectableAlergiOptions(currentVal) {
    const options = ALERGI_OPTIONS.filter((o) => o.value !== "Lainnya").map(
        (o) => ({
            value: o.value,
            label: o.label,
        }),
    );
    if (currentVal && !options.some((o) => o.value === currentVal)) {
        options.push({ value: currentVal, label: `${currentVal} (Kustom)` });
    }
    return options;
}

function addCustomAlergi() {
    const val = customAlergiInput.value.trim();
    if (!val) return;
    if (!Array.isArray(form.keterangan_alergi)) form.keterangan_alergi = [];
    const exists = form.keterangan_alergi.some(
        (item) => item.jenis_alergi.toLowerCase() === val.toLowerCase(),
    );
    if (!exists) {
        form.keterangan_alergi.push({
            jenis_alergi: val,
            porsi_kecil: 0,
            porsi_besar: 0,
        });
    }
    customAlergiInput.value = "";
}

function removeAlergi(idx) {
    if (!Array.isArray(form.keterangan_alergi)) return;
    form.keterangan_alergi.splice(idx, 1);
}

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
const rawTeleponKepala = ref(cleanPhone(form.telepon_kepala));
const rawTeleponPIC = ref(cleanPhone(form.telepon_pic));

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
        (opt) => opt === currentVal || !selectedOthers.includes(opt),
    );
}

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
    const subList = getSubKategoriByKategori(form.kategori);
    const updated = subList.map((sub) => {
        const existing = form.rincian.find((r) => r.sub_kategori === sub);
        return {
            sub_kategori: sub,
            jenis_porsi:
                existing?.jenis_porsi ||
                getJenisPorsiBySubKategori(sub, form.kategori),
            jumlah_laki_laki: existing ? existing.jumlah_laki_laki : 0,
            jumlah_perempuan: existing ? existing.jumlah_perempuan : 0,
        };
    });
    form.rincian = sortRincianByKategori(updated, form.kategori);
    clearFieldError("rincian");
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

        if (form.provinsi) {
            const prov = provinceList.value.find(
                (p) =>
                    p.name.toLowerCase() === form.provinsi.toLowerCase() ||
                    p.name.toLowerCase().includes(form.provinsi.toLowerCase()),
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
                                .includes(r.name.toLowerCase()),
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
                                            form.kecamatan.toLowerCase(),
                                        ) ||
                                    form.kecamatan
                                        .toLowerCase()
                                        .includes(d.name.toLowerCase()),
                            );
                            if (dist) {
                                selectedDistrictCode.value = dist.code;
                                isVillagesLoading.value = true;
                                villageList.value = await getVillages(
                                    dist.code,
                                );
                                isVillagesLoading.value = false;

                                if (form.desa_kelurahan) {
                                    const vil = villageList.value.find(
                                        (v) =>
                                            v.name
                                                .toLowerCase()
                                                .includes(
                                                    form.desa_kelurahan.toLowerCase(),
                                                ) ||
                                            form.desa_kelurahan
                                                .toLowerCase()
                                                .includes(v.name.toLowerCase()),
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
        errs.kategori = "Pilih kategori jenjang / lembaga.";
    }

    if (!form.jenis_kepemilikan) {
        errs.jenis_kepemilikan = "Pilih jenis kepemilikan.";
    }

    if (!form.tipe_identitas) {
        errs.tipe_identitas = "Pilih tipe nomor identitas legalitas.";
    }

    if (!form.kode_identitas || !form.kode_identitas.trim()) {
        errs.kode_identitas = "Nomor / kode identitas legalitas wajib diisi.";
    }

    if (form.kategori === "Posyandu") {
        if (
            form.jumlah_kader === null ||
            form.jumlah_kader === "" ||
            isNaN(Number(form.jumlah_kader)) ||
            Number(form.jumlah_kader) < 1
        ) {
            errs.jumlah_kader =
                "Jumlah kader posyandu wajib diisi minimal 1 orang.";
        }
    }

    // 2. Kontak Kepala Satuan
    if (!form.nama_kepala || !form.nama_kepala.trim()) {
        errs.nama_kepala = "Nama Kepala Satuan / Pimpinan wajib diisi.";
    }

    if (!form.email_kepala || !form.email_kepala.trim()) {
        errs.email_kepala = "Email Kepala Satuan / Pimpinan wajib diisi.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email_kepala.trim())) {
        errs.email_kepala = "Format email Kepala Satuan tidak valid.";
    }

    if (!form.telepon_kepala) {
        errs.telepon_kepala =
            "Nomor WhatsApp Kepala Satuan / Pimpinan wajib diisi.";
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
        errs.telepon_pic = "Nomor WhatsApp PIC wajib diisi.";
    } else if (!/^62[0-9]{8,15}$/.test(form.telepon_pic)) {
        errs.telepon_pic =
            "Format nomor telepon tidak valid (contoh: +62 81234567890).";
    }

    // 4. Wilayah & Alamat
    if (!form.provinsi) {
        errs.provinsi = "Pilih provinsi lokasi kelompok.";
    }
    if (!form.kabupaten) {
        errs.kabupaten = "Pilih kabupaten / kota lokasi kelompok.";
    }
    if (!form.kecamatan) {
        errs.kecamatan = "Pilih kecamatan lokasi kelompok.";
    }
    if (!form.desa_kelurahan) {
        errs.desa_kelurahan = "Pilih desa / kelurahan lokasi kelompok.";
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

// Focus field pertama yang error
function focusFirstError() {
    const errorKeys = Object.keys(clientErrors.value);
    if (errorKeys.length === 0) return;

    const firstKey = errorKeys[0];
    const el = document.getElementById(firstKey);
    if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
        if (typeof el.focus === "function") {
            el.focus();
        }
    }
}

// Submit handler
function submitForm() {
    if (!validateForm()) {
        nextTick(() => {
            focusFirstError();
        });
        return;
    }

    form.put(route("penerima-manfaat.update", props.kelompok.uid), {
        preserveScroll: true,
        onError: () => {
            nextTick(() => {
                focusFirstError();
            });
        },
    });
}
</script>

<template>
    <AppLayout
        title="Edit Kelompok Penerima Manfaat"
        :subtitle="`Formulir Pembaharuan Kelompok Penerima Manfaat`"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Edit Kelompok Penerima Manfaat" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Top Actions Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200"
            >
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('penerima-manfaat.index')"
                        class="h-9 w-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-colors shadow-2xs"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900 leading-tight"
                        >
                            Edit Data Kelompok
                        </h2>
                        <p class="text-xs text-slate-600 font-semibold mt-0.5">
                            {{ kelompok.nama_kelompok }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 h-10 px-5 text-xs font-bold rounded-lg bg-primary hover:bg-primary/90 text-white shadow-sm transition-all cursor-pointer disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" />
                        <span>{{
                            form.processing
                                ? "Menyimpan Perubahan..."
                                : "Simpan Perubahan"
                        }}</span>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- 1. IDENTITAS & LEGALITAS KELOMPOK -->
                <FormIdentitasSection
                    :form="form"
                    :get-field-error="getFieldError"
                    :clear-field-error="clearFieldError"
                />

                <!-- 2. KONTAK PENANGGUNG JAWAB (KS & PIC) -->
                <FormKontakSection
                    :form="form"
                    v-model:raw-telepon-kepala="rawTeleponKepala"
                    v-model:raw-telepon-p-i-c="rawTeleponPIC"
                    :get-field-error="getFieldError"
                    :clear-field-error="clearFieldError"
                />

                <!-- 3. WILAYAH & TITIK LOKASI PETA -->
                <FormAlamatSection
                    :form="form"
                    :default-center="defaultCenter"
                    :province-list="provinceList"
                    :regency-list="regencyList"
                    :district-list="districtList"
                    :village-list="villageList"
                    :is-provinces-loading="isProvincesLoading"
                    :is-regencies-loading="isRegenciesLoading"
                    :is-districts-loading="isDistrictsLoading"
                    :is-villages-loading="isVillagesLoading"
                    v-model:selected-province-code="selectedProvinceCode"
                    v-model:selected-regency-code="selectedRegencyCode"
                    v-model:selected-district-code="selectedDistrictCode"
                    v-model:selected-village-code="selectedVillageCode"
                    :get-field-error="getFieldError"
                    :clear-field-error="clearFieldError"
                    :clean-kabupaten-name="cleanKabupatenName"
                    @province-change="onProvinceChange"
                    @regency-change="onRegencyChange"
                    @district-change="onDistrictChange"
                    @village-change="onVillageChange"
                />

                <!-- 4. RINCIAN JUMLAH PENERIMA MANFAAT -->
                <FormRincianSection
                    :form="form"
                    :total-laki-laki="totalLakiLaki"
                    :total-perempuan="totalPerempuan"
                    :total-porsi-kecil="totalPorsiKecil"
                    :total-porsi-besar="totalPorsiBesar"
                    :grand-total="grandTotal"
                    :is-all-subkategori-added="isAllSubkategoriAdded"
                    :get-available-sub-kategori-for-row="getAvailableSubKategoriForRow"
                    :on-sub-kategori-change="onSubKategoriChange"
                    :add-custom-subkategori="addCustomSubkategori"
                    :remove-subkategori="removeSubkategori"
                    :reset-rincian-to-default="resetRincianToDefault"
                    :get-jenis-porsi-by-sub-kategori="getJenisPorsiBySubKategori"
                    :get-field-error="getFieldError"
                />

                <!-- 5. DATA ALERGI MAKANAN & KEBUTUHAN KHUSUS -->
                <FormAlergiSection
                    :form="form"
                    v-model:custom-alergi-input="customAlergiInput"
                    :show-custom-alergi-input="showCustomAlergiInput"
                    :total-alergi-p-k="totalAlergiPK"
                    :total-alergi-p-b="totalAlergiPB"
                    :grand-total-alergi="grandTotalAlergi"
                    :is-alergi-selected="isAlergiSelected"
                    :toggle-alergi="toggleAlergi"
                    :get-selectable-alergi-options="getSelectableAlergiOptions"
                    :add-custom-alergi="addCustomAlergi"
                    :remove-alergi="removeAlergi"
                />
            </form>
        </div>
    </AppLayout>
</template>
