<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    ShieldCheck,
    ShieldAlert,
    Shield,
    Clock,
    Calendar,
    Users,
    MapPin,
    Navigation,
    Send,
    CheckCircle2,
    AlertTriangle,
    AlertCircle,
    LogOut,
    LogIn,
    Sparkles,
    Check,
    Sun,
    Flame,
    Cloud,
    Wind,
    CloudRain,
    Snowflake,
    CloudLightning,
    Building2,
    CheckCheck,
    FileText,
    X,
    Info,
    HelpCircle,
    ArrowLeftRight,
    BookOpen,
} from "lucide-vue-next";
import MiniGoogleMap from "./Partials/MiniGoogleMap.vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
    token: {
        type: String,
        required: true,
    },
    todayDate: {
        type: String,
        default: () => new Date().toISOString().split("T")[0],
    },
    defaultIntervalJam: {
        type: String,
        default: "08.00 - 09.00 WITA",
    },
    serverTimeWita: {
        type: String,
        default: "",
    },
    petugasKeamanan: {
        type: Array,
        default: () => [],
    },
    petugasSppgAktif: {
        type: Array,
        default: () => [],
    },
    mutasiAktif: {
        type: Object,
        default: null,
    },
});

// Mode Form: 'pengecekan' (Laporan Pengecekan) | 'serah_terima' | 'keluar_jaga' (Laporan Keluar Jaga) | 'sedang_diluar' (Laporan Sedang Diluar) | 'kembali_jaga' (Laporan Kembali Jaga) | 'izin_kembali_list'
const activeMode = ref("pengecekan");

// Jam Realtime Berjalan (Digital Clock WITA UTC+8)
const currentLiveDate = ref(new Date());
let liveTimer = null;

onMounted(() => {
    liveTimer = setInterval(() => {
        currentLiveDate.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (liveTimer) clearInterval(liveTimer);
});

// String Jam Realtime Berjalan (WITA, misal: 19:20:46 WITA)
const liveTimeWita = computed(() => {
    const d = currentLiveDate.value;
    try {
        const timePart = new Intl.DateTimeFormat("id-ID", {
            timeZone: "Asia/Makassar",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
        })
            .format(d)
            .replace(/\./g, ":");
        return `${timePart} WITA`;
    } catch {
        const hh = String(d.getHours()).padStart(2, "0");
        const mm = String(d.getMinutes()).padStart(2, "0");
        const ss = String(d.getSeconds()).padStart(2, "0");
        return `${hh}:${mm}:${ss} WITA`;
    }
});
const liveTimeWitaString = liveTimeWita;

// String Hari & Tanggal Realtime (WITA, misal: Senin, 16 November 2026)
const liveDateWita = computed(() => {
    const d = currentLiveDate.value;
    try {
        return new Intl.DateTimeFormat("id-ID", {
            timeZone: "Asia/Makassar",
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        }).format(d);
    } catch {
        return formattedTanggalHariIni.value;
    }
});

// Rentang Jam Otomatis Berjalan (Misal: 08.00 - 09.00 WITA)
const currentActiveInterval = computed(() => {
    const d = currentLiveDate.value;
    let hour = d.getHours();
    try {
        const hourStr = new Intl.DateTimeFormat("en-US", {
            timeZone: "Asia/Makassar",
            hour: "numeric",
            hour12: false,
        }).format(d);
        hour = parseInt(hourStr, 10) % 24;
    } catch {
        hour = d.getHours();
    }
    const h1 = String(hour).padStart(2, "0");
    const h2 = String((hour + 1) % 24).padStart(2, "0");
    return `${h1}.00 - ${h2}.00 WITA`;
});

// Opsi Cuaca
const CUACA_OPTIONS = [
    { label: "Cerah", icon: Sun, color: "text-amber-500" },
    { label: "Panas", icon: Flame, color: "text-orange-500" },
    { label: "Berawan", icon: Cloud, color: "text-sky-500" },
    { label: "Berangin", icon: Wind, color: "text-teal-500" },
    { label: "Mendung", icon: Cloud, color: "text-slate-500" },
    { label: "Dingin", icon: Snowflake, color: "text-cyan-500" },
    { label: "Hujan", icon: CloudRain, color: "text-blue-500" },
    { label: "Badai", icon: CloudLightning, color: "text-purple-600" },
];

// 7 Item Pengecekan Umum
const DEFAULT_PENGECEKAN_UMUM = [
    {
        key: "lalu_lintas",
        label: "Lalu Lintas Jalan SPPG",
        type: "aman_tidak",
        status: null,
        uraian: "",
    },
    {
        key: "konflik_sekitar",
        label: "Konflik Sekitar SPPG",
        type: "aman_tidak",
        status: null,
        uraian: "",
    },
    {
        key: "konflik_dalam",
        label: "Konflik Dalam SPPG",
        type: "aman_tidak",
        status: null,
        uraian: "",
    },
    {
        key: "kunjungan_tamu",
        label: "Kunjungan Tamu",
        type: "ada_tidak",
        status: null,
        uraian: "",
    },
    {
        key: "kedatangan_supplier",
        label: "Kedatangan Supplier / Pemasok",
        type: "ada_tidak",
        status: null,
        uraian: "",
    },
    {
        key: "relawan",
        label: "Kendala Petugas/Relawan",
        type: "ada_tidak",
        status: null,
        uraian: "",
        petugas_id: null,
        petugas_nama: "",
        petugas_jabatan: "",
        catatan: "",
    },
];

// 21 Item Pengecekan Lingkungan Kerja
const LINGKUNGAN_KERJA_NAMES = [
    "Pos Keamanan",
    "Area Parkir",
    "Loker",
    "WC",
    "Listrik",
    "Air",
    "Gas",
    "IPAL",
    "Ruang Kantor",
    "Ruang Rapat",
    "Area Masuk Penerimaan Barang",
    "Area Keluar Distribusi",
    "Gudang Basah",
    "Gudang Kering",
    "Gudang Ompreng",
    "Gudang Alat",
    "Ruang Persiapan",
    "Ruang Pengolahan",
    "Ruang Pemorsian",
    "Ruang Cuci Ompreng",
    "Mess",
];

function createDefaultLingkunganKerja() {
    return LINGKUNGAN_KERJA_NAMES.map((nama) => ({
        key: nama.toLowerCase().replace(/[^a-z0-9]/g, "_"),
        label: nama,
        status: null,
        uraian: "",
    }));
}

// State Form Laporan Pengecekan - SEMUA KOSONG DIAWAL
const formPengecekan = ref({
    petugas_id: null,
    cuaca: null,
    pengecekan_umum: JSON.parse(JSON.stringify(DEFAULT_PENGECEKAN_UMUM)),
    lingkungan_kerja: createDefaultLingkunganKerja(),
    catatan_tambahan: "",
    lokasi_nama: "",
    latitude: null,
    longitude: null,
});

// State Error Validasi (Tanpa alert, langsung scroll & outline merah)
const errorsPengecekan = ref({});

function clearErrorPengecekan(key) {
    if (errorsPengecekan.value[key]) {
        const next = { ...errorsPengecekan.value };
        delete next[key];
        errorsPengecekan.value = next;
    }
}

// Watchers untuk menghapus error seketika saat user mengisi/memilih
watch(
    () => formPengecekan.value.petugas_id,
    () => clearErrorPengecekan("petugas_id"),
);
watch(
    () => formPengecekan.value.cuaca,
    () => clearErrorPengecekan("cuaca"),
);
watch(
    () => formPengecekan.value.catatan_tambahan,
    () => clearErrorPengecekan("catatan_tambahan"),
);
watch(
    () => formPengecekan.value.lokasi_nama,
    () => clearErrorPengecekan("lokasi_nama"),
);
watch(
    () => formPengecekan.value.latitude,
    () => clearErrorPengecekan("lokasi"),
);

// State Form Laporan Keluar Jaga - SEMUA KOSONG DIAWAL
const formKeluarJaga = ref({
    petugas_id: null,
    lokasi_tujuan: "",
    keperluan: "",
    latitude: null,
    longitude: null,
    lat_awal: null,
    lng_awal: null,
    lat_tujuan: null,
    lng_tujuan: null,
    lat_kembali: null,
    lng_kembali: null,
});

const errorsKeluarJaga = ref({});

function clearErrorKeluarJaga(key) {
    if (errorsKeluarJaga.value[key]) {
        const next = { ...errorsKeluarJaga.value };
        delete next[key];
        errorsKeluarJaga.value = next;
    }
}

watch(
    () => formKeluarJaga.value.petugas_id,
    () => clearErrorKeluarJaga("petugas_id"),
);
watch(
    () => formKeluarJaga.value.lokasi_tujuan,
    () => clearErrorKeluarJaga("lokasi_tujuan"),
);
watch(
    () => formKeluarJaga.value.keperluan,
    () => clearErrorKeluarJaga("keperluan"),
);
watch(
    () => formKeluarJaga.value.latitude,
    () => clearErrorKeluarJaga("lokasi"),
);
watch(
    () => formKeluarJaga.value.lat_awal,
    () => clearErrorKeluarJaga("lat_awal"),
);
watch(
    () => formKeluarJaga.value.lat_tujuan,
    () => clearErrorKeluarJaga("lat_tujuan"),
);
watch(
    () => formKeluarJaga.value.lat_kembali,
    () => clearErrorKeluarJaga("lat_kembali"),
);

// State Form Laporan Sedang Diluar - SEMUA KOSONG DIAWAL
const formSedangDiluar = ref({
    petugas_id: null,
    lokasi_nama: "",
    latitude: null,
    longitude: null,
});

const errorsSedangDiluar = ref({
    petugas_id: "",
    lokasi_nama: "",
    lokasi: "",
});

function clearErrorSedangDiluar(field) {
    if (errorsSedangDiluar.value[field]) {
        errorsSedangDiluar.value[field] = "";
    }
}

watch(
    () => formSedangDiluar.value.petugas_id,
    () => clearErrorSedangDiluar("petugas_id"),
);
watch(
    () => formSedangDiluar.value.lokasi_nama,
    () => clearErrorSedangDiluar("lokasi_nama"),
);
watch(
    () => formSedangDiluar.value.latitude,
    () => clearErrorSedangDiluar("lokasi"),
);

// Smooth Scroll ke Element Pertama yang Bermasalah
function scrollToElement(id) {
    nextTick(() => {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "center" });
            const input = el.querySelector(
                'input:not([type="radio"]):not([type="checkbox"]), textarea',
            );
            if (input) {
                input.focus();
            }
        }
    });
}

// Modal State
const showPetunjukModal = ref(false);
const showConfirmSetAmanModal = ref(false);
const showLocationPermissionModal = ref(false);
const pendingLocationTarget = ref("pengecekan");
const showLocationErrorModal = ref(false);
const locationErrorMessage = ref("");
const showConfirmKirimPengecekanModal = ref(false);
const showConfirmKirimKeluarModal = ref(false);
const showConfirmSedangDiluarModal = ref(false);
const showConfirmKembaliJagaModal = ref(false);
const showConfirmKembaliModal = ref(false);
const pendingKembaliIzin = ref(null);

// Fitur Bantuan: Set Semua Aman (Dengan Modal Konfirmasi)
function openConfirmSetSemuaAman() {
    showConfirmSetAmanModal.value = true;
}

function executeSetSemuaAman() {
    formPengecekan.value.pengecekan_umum.forEach((it, idx) => {
        if (!it.status) {
            it.status = it.type === "ada_tidak" ? "Tidak Ada" : "Aman";
            clearErrorPengecekan("umum_" + idx);
        }
    });
    formPengecekan.value.lingkungan_kerja.forEach((it, idx) => {
        if (!it.status) {
            it.status = "Aman";
            clearErrorPengecekan("lingkungan_" + idx);
        }
    });
    showConfirmSetAmanModal.value = false;
}

// State Form Laporan Serah Terima - SEMUA AWALNYA KOSONG
const formSerahTerima = ref({
    petugas_id: null,
    jenis: null,
    catatan: "",
    lokasi_nama: "",
    latitude: null,
    longitude: null,
});

const errorsSerahTerima = ref({});

function clearErrorSerahTerima(key) {
    if (errorsSerahTerima.value[key]) {
        const next = { ...errorsSerahTerima.value };
        delete next[key];
        errorsSerahTerima.value = next;
    }
}

watch(
    () => formSerahTerima.value.petugas_id,
    () => clearErrorSerahTerima("petugas_id"),
);
watch(
    () => formSerahTerima.value.jenis,
    () => clearErrorSerahTerima("jenis"),
);
watch(
    () => formSerahTerima.value.catatan,
    () => clearErrorSerahTerima("catatan"),
);
watch(
    () => formSerahTerima.value.lokasi_nama,
    () => clearErrorSerahTerima("lokasi_nama"),
);
watch(
    () => formSerahTerima.value.latitude,
    () => clearErrorSerahTerima("lokasi"),
);

// State Form Laporan Kembali Jaga - 3 Item (Petugas, Tempat Posisi Terakhir, Titik Lokasi Terakhir)
const formKembaliJaga = ref({
    petugas_id: null,
    lokasi_nama: "",
    latitude: null,
    longitude: null,
});

const errorsKembaliJaga = ref({});

function clearErrorKembaliJaga(key) {
    if (errorsKembaliJaga.value[key]) {
        const next = { ...errorsKembaliJaga.value };
        delete next[key];
        errorsKembaliJaga.value = next;
    }
}

watch(
    () => formKembaliJaga.value.petugas_id,
    () => clearErrorKembaliJaga("petugas_id"),
);
watch(
    () => formKembaliJaga.value.lokasi_nama,
    () => clearErrorKembaliJaga("lokasi_nama"),
);
watch(
    () => formKembaliJaga.value.latitude,
    () => clearErrorKembaliJaga("lokasi"),
);

function resetKembaliJaga() {
    formKembaliJaga.value = {
        petugas_id: null,
        lokasi_nama: "",
        latitude: null,
        longitude: null,
    };
    errorsKembaliJaga.value = {};
}

// GPS Location Handler Langsung (Tanpa Dobel Modal)
const isLocating = ref(false);
const locatingTarget = ref("");

function requestLocation(target = "pengecekan") {
    executeFetchLocation(target);
}

function handleAgreeLocationPermission() {
    showLocationPermissionModal.value = false;
    executeFetchLocation(pendingLocationTarget.value);
}

function executeFetchLocation(target = "pengecekan") {
    if (!navigator.geolocation) {
        locationErrorMessage.value =
            "Browser atau perangkat HP Anda tidak mendukung deteksi GPS lokasi.";
        showLocationErrorModal.value = true;
        return;
    }

    isLocating.value = true;
    locatingTarget.value = target;

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(7));
            const lng = Number(pos.coords.longitude.toFixed(7));

            if (target === "pengecekan") {
                formPengecekan.value.latitude = lat;
                formPengecekan.value.longitude = lng;
                clearErrorPengecekan("lokasi");
            } else if (
                target === "keluar_jaga" ||
                target === "keluar_awal" ||
                target === "keluar_tujuan"
            ) {
                formKeluarJaga.value.latitude = lat;
                formKeluarJaga.value.longitude = lng;
                formKeluarJaga.value.lat_awal = lat;
                formKeluarJaga.value.lng_awal = lng;
                formKeluarJaga.value.lat_tujuan = lat;
                formKeluarJaga.value.lng_tujuan = lng;
                clearErrorKeluarJaga("latitude");
                clearErrorKeluarJaga("lat_awal");
                clearErrorKeluarJaga("lat_tujuan");
            } else if (target === "sedang_diluar") {
                formSedangDiluar.value.latitude = lat;
                formSedangDiluar.value.longitude = lng;
                clearErrorSedangDiluar("lokasi");
            } else if (target === "keluar_kembali") {
                formKeluarJaga.value.lat_kembali = lat;
                formKeluarJaga.value.lng_kembali = lng;
                clearErrorKeluarJaga("lat_kembali");
            } else if (target === "serah_terima") {
                formSerahTerima.value.latitude = lat;
                formSerahTerima.value.longitude = lng;
                clearErrorSerahTerima("lokasi");
            } else if (target === "kembali_jaga") {
                formKembaliJaga.value.latitude = lat;
                formKembaliJaga.value.longitude = lng;
                clearErrorKembaliJaga("lokasi");
            }

            isLocating.value = false;
            locatingTarget.value = "";
        },
        (err) => {
            isLocating.value = false;
            locatingTarget.value = "";
            locationErrorMessage.value =
                err.message ||
                "Izin lokasi belum aktif pada browser HP Anda. Silakan aktifkan izin lokasi di pengaturan situs browser HP Anda lalu coba lagi.";
            showLocationErrorModal.value = true;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
    );
}

// Hitung Jarak Geodesik (Haversine) dari Titik Koordinat Pengguna ke SPPG
function getDistanceFromSppg(lat, lng) {
    if (!lat || !lng) return null;
    const sppgLat = props.unitSppg?.latitude
        ? Number(props.unitSppg.latitude)
        : null;
    const sppgLng = props.unitSppg?.longitude
        ? Number(props.unitSppg.longitude)
        : null;
    if (
        sppgLat === null ||
        sppgLng === null ||
        isNaN(sppgLat) ||
        isNaN(sppgLng)
    )
        return null;

    const latNum = Number(lat);
    const lngNum = Number(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return null;

    const R = 6371000; // Radius bumi dalam meter
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(latNum - sppgLat);
    const dLng = toRad(lngNum - sppgLng);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(sppgLat)) *
            Math.cos(toRad(latNum)) *
            Math.sin(dLng / 2) *
            Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distanceMeter = Math.round(R * c);

    if (distanceMeter >= 1000) {
        const km = (distanceMeter / 1000).toFixed(2).replace(".", ",");
        return `${km} km`;
    }
    return `${distanceMeter} m`;
}

// Status pengiriman
const isSubmitting = ref(false);
const submitSuccess = ref(false);
const successText = ref("");

// Handler Khusus Kendala Petugas/Relawan
function onRelawanPetugasChange(it, idx) {
    const found = props.petugasSppgAktif.find((p) => p.id === it.petugas_id);
    if (found) {
        it.petugas_nama = found.nama;
        it.petugas_jabatan = found.jabatan || "Petugas SPPG";
        syncRelawanUraian(it);
    }
    clearErrorPengecekan("relawan_petugas");
}

function syncRelawanUraian(it) {
    if (!it.petugas_nama) {
        it.uraian = it.catatan?.trim() || "";
        return;
    }
    const identitas = `${it.petugas_nama} - ${it.petugas_jabatan || "Petugas SPPG"}`;
    if (it.catatan?.trim()) {
        it.uraian = `${identitas}: ${it.catatan.trim()}`;
    } else {
        it.uraian = identitas;
    }
}

// Validasi Form Pengecekan Tanpa Alert (Arahkan Scroll & Tandai Merah)
function validatePengecekan() {
    const errs = {};
    let firstErrorElementId = null;

    if (!formPengecekan.value.petugas_id) {
        errs.petugas_id = "Nama Petugas Jaga wajib dipilih!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-pengecekan-petugas";
    }

    if (!formPengecekan.value.cuaca) {
        errs.cuaca = "Kondisi cuaca saat pengecekan wajib dipilih!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-pengecekan-cuaca";
    }

    formPengecekan.value.pengecekan_umum.forEach((item, idx) => {
        if (!item.status) {
            errs["umum_" + idx] = `Status ${item.label} wajib dipilih!`;
            if (!firstErrorElementId)
                firstErrorElementId = "field-pengecekan-umum-" + idx;
        } else if (item.key === "relawan" && item.status === "Ada") {
            if (!item.petugas_id) {
                errs.relawan_petugas =
                    "Nama Petugas SPPG yang terkendala wajib dipilih!";
                if (!firstErrorElementId)
                    firstErrorElementId = "field-relawan-petugas";
            }
            if (!item.catatan?.trim()) {
                errs["umum_uraian_" + idx] =
                    "Kendala / catatan untuk petugas tersebut wajib diisi!";
                if (!firstErrorElementId)
                    firstErrorElementId = "field-pengecekan-umum-uraian-" + idx;
            }
        } else if (
            (item.status === "Tidak Aman" || item.status === "Ada") &&
            !item.uraian?.trim()
        ) {
            errs["umum_uraian_" + idx] =
                `Uraian untuk ${item.label} wajib diisi karena berstatus ${item.status}!`;
            if (!firstErrorElementId)
                firstErrorElementId = "field-pengecekan-umum-uraian-" + idx;
        }
    });

    formPengecekan.value.lingkungan_kerja.forEach((item, idx) => {
        if (!item.status) {
            errs["lingkungan_" + idx] =
                `Status area "${item.label}" belum dipilih!`;
            if (!firstErrorElementId)
                firstErrorElementId = "field-pengecekan-lingkungan-" + idx;
        } else if (item.status === "Tidak Aman" && !item.uraian?.trim()) {
            errs["lingkungan_uraian_" + idx] =
                `Uraian masalah di area "${item.label}" wajib diisi!`;
            if (!firstErrorElementId)
                firstErrorElementId =
                    "field-pengecekan-lingkungan-uraian-" + idx;
        }
    });

    if (!formPengecekan.value.catatan_tambahan?.trim()) {
        errs.catatan_tambahan = "Catatan Tambahan wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-pengecekan-catatan-tambahan";
    }

    if (!formPengecekan.value.lokasi_nama?.trim()) {
        errs.lokasi_nama = "Tempat Posisi Pengecekan Terakhir wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-pengecekan-tempat";
    }

    if (
        formPengecekan.value.latitude === null ||
        formPengecekan.value.longitude === null
    ) {
        errs.lokasi =
            'Titik lokasi koordinat GPS belum terekam! Klik tombol "Ambil Lokasi".';
        if (!firstErrorElementId)
            firstErrorElementId = "field-pengecekan-lokasi";
    }

    errorsPengecekan.value = errs;

    if (firstErrorElementId) {
        scrollToElement(firstErrorElementId);
        return false;
    }
    return true;
}

// Buka Modal Konfirmasi Sebelum Kirim Laporan Pengecekan
function handleKirimPengecekanClick() {
    if (validatePengecekan()) {
        showConfirmKirimPengecekanModal.value = true;
    }
}

// Eksekusi Submit Laporan Pengecekan
function executeSubmitPengecekan() {
    showConfirmKirimPengecekanModal.value = false;
    isSubmitting.value = true;

    const payload = {
        petugas_id: formPengecekan.value.petugas_id,
        lokasi_nama: formPengecekan.value.lokasi_nama
            ? formPengecekan.value.lokasi_nama.trim()
            : null,
        latitude: formPengecekan.value.latitude,
        longitude: formPengecekan.value.longitude,
        data_pengecekan: {
            cuaca: formPengecekan.value.cuaca,
            pengecekan_umum: formPengecekan.value.pengecekan_umum,
            lingkungan_kerja: formPengecekan.value.lingkungan_kerja,
            catatan_tambahan: formPengecekan.value.catatan_tambahan,
            tempat_posisi: formPengecekan.value.lokasi_nama
                ? formPengecekan.value.lokasi_nama.trim()
                : null,
        },
    };

    router.post(
        route("keamanan.public-store-jam", { token: props.token }),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                successText.value = `Laporan Pengecekan untuk rentang ${currentActiveInterval.value} telah berhasil disimpan dan otomatis masuk ke Buku Mutasi Keamanan SPPG!`;
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] || "Terjadi kesalahan saat mengirim.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

// Reset Laporan Pengecekan untuk input jam berikutnya
function resetPengecekan() {
    submitSuccess.value = false;
    errorsPengecekan.value = {};
    formPengecekan.value.petugas_id = null;
    formPengecekan.value.cuaca = null;
    formPengecekan.value.pengecekan_umum = JSON.parse(
        JSON.stringify(DEFAULT_PENGECEKAN_UMUM),
    );
    formPengecekan.value.lingkungan_kerja = createDefaultLingkunganKerja();
    formPengecekan.value.catatan_tambahan = "";
    formPengecekan.value.lokasi_nama = "";
    formPengecekan.value.latitude = null;
    formPengecekan.value.longitude = null;
}

// Validasi Form Keluar Jaga Tanpa Alert (Arahkan Scroll & Tandai Merah)
function validateKeluarJaga() {
    const errs = {};
    let firstErrorElementId = null;

    if (!formKeluarJaga.value.petugas_id) {
        errs.petugas_id = "Nama Petugas Jaga wajib dipilih!";
        if (!firstErrorElementId) firstErrorElementId = "field-keluar-petugas";
    }
    if (
        !formKeluarJaga.value.lokasi_tujuan ||
        !formKeluarJaga.value.lokasi_tujuan.trim()
    ) {
        errs.lokasi_tujuan = "Tempat Tujuan Ijin wajib diisi!";
        if (!firstErrorElementId) firstErrorElementId = "field-keluar-tujuan";
    }
    if (
        !formKeluarJaga.value.keperluan ||
        !formKeluarJaga.value.keperluan.trim()
    ) {
        errs.keperluan = "Keperluan Ijin Keluar wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-keluar-keperluan";
    }
    if (
        formKeluarJaga.value.latitude === null ||
        formKeluarJaga.value.longitude === null
    ) {
        errs.lokasi =
            'Titik Lokasi Pengecekan Terakhir (koordinat GPS) belum diambil! Klik "Ambil Lokasi".';
        if (!firstErrorElementId) firstErrorElementId = "field-keluar-lokasi";
    }

    errorsKeluarJaga.value = errs;

    if (firstErrorElementId) {
        scrollToElement(firstErrorElementId);
        return false;
    }
    return true;
}

// Buka Modal Konfirmasi Sebelum Kirim Laporan Keluar Jaga
function handleKirimKeluarJagaClick() {
    if (validateKeluarJaga()) {
        showConfirmKirimKeluarModal.value = true;
    }
}

// Eksekusi Submit Laporan Keluar Jaga
function executeSubmitKeluarJaga() {
    showConfirmKirimKeluarModal.value = false;
    isSubmitting.value = true;

    // Pastikan fallback lat_awal & lat_tujuan juga terisi dari latitude jika ada controller lawas
    if (formKeluarJaga.value.latitude) {
        formKeluarJaga.value.lat_awal = formKeluarJaga.value.latitude;
        formKeluarJaga.value.lat_tujuan = formKeluarJaga.value.latitude;
    }
    if (formKeluarJaga.value.longitude) {
        formKeluarJaga.value.lng_awal = formKeluarJaga.value.longitude;
        formKeluarJaga.value.lng_tujuan = formKeluarJaga.value.longitude;
    }

    router.post(
        route("keamanan.public-store-izin", { token: props.token }),
        formKeluarJaga.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                successText.value = `Laporan Keluar Jaga atas nama petugas berhasil dikirim ke Buku Mutasi!`;
                errorsKeluarJaga.value = {};
                formKeluarJaga.value.petugas_id = null;
                formKeluarJaga.value.lokasi_tujuan = "";
                formKeluarJaga.value.keperluan = "";
                formKeluarJaga.value.latitude = null;
                formKeluarJaga.value.longitude = null;
                formKeluarJaga.value.lat_awal = null;
                formKeluarJaga.value.lng_awal = null;
                formKeluarJaga.value.lat_tujuan = null;
                formKeluarJaga.value.lng_tujuan = null;
                formKeluarJaga.value.lat_kembali = null;
                formKeluarJaga.value.lng_kembali = null;
                activeMode.value = "pengecekan";
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] ||
                    "Terjadi kesalahan saat menyimpan.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

// Validasi Form Sedang Diluar (1. Nama Petugas, 2. Titik Lokasi Pengecekan Terakhir)
function validateSedangDiluar() {
    const errs = {};
    let firstErrorElementId = null;

    if (!formSedangDiluar.value.petugas_id) {
        errs.petugas_id = "Nama Petugas Keamanan wajib dipilih!";
        if (!firstErrorElementId) firstErrorElementId = "field-sedang-petugas";
    }
    if (
        !formSedangDiluar.value.lokasi_nama ||
        !formSedangDiluar.value.lokasi_nama.trim()
    ) {
        errs.lokasi_nama = "Tempat Posisi Pengecekan Terakhir wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-sedang-lokasi-nama";
    }
    if (
        formSedangDiluar.value.latitude === null ||
        formSedangDiluar.value.longitude === null
    ) {
        errs.lokasi =
            'Titik Lokasi Pengecekan Terakhir (koordinat GPS) belum diambil! Klik "Ambil Lokasi".';
        if (!firstErrorElementId)
            firstErrorElementId = "field-sedang-lokasi-gps";
    }

    errorsSedangDiluar.value = errs;

    if (firstErrorElementId) {
        scrollToElement(firstErrorElementId);
        return false;
    }
    return true;
}

// Buka Modal Konfirmasi Sebelum Kirim Laporan Sedang Diluar
function handleKirimSedangDiluarClick() {
    if (validateSedangDiluar()) {
        showConfirmSedangDiluarModal.value = true;
    }
}

// Eksekusi Submit Laporan Sedang Diluar
function executeSubmitSedangDiluar() {
    showConfirmSedangDiluarModal.value = false;
    isSubmitting.value = true;

    router.post(
        route("keamanan.public-store-sedang-diluar", { token: props.token }),
        formSedangDiluar.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                const pet = props.petugasKeamanan.find(
                    (p) => p.id === formSedangDiluar.value.petugas_id,
                );
                successText.value = `Laporan Sedang Diluar atas nama ${pet ? pet.nama : "petugas"} berhasil dikirim ke Buku Mutasi!`;
                resetSedangDiluar();
                window.scrollTo({ top: 0, behavior: "smooth" });
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] ||
                    "Terjadi kesalahan saat menyimpan laporan sedang diluar.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

function resetSedangDiluar() {
    formSedangDiluar.value = {
        petugas_id: null,
        lokasi_nama: "",
        latitude: null,
        longitude: null,
    };
    errorsSedangDiluar.value = {
        petugas_id: "",
        lokasi_nama: "",
        lokasi: "",
    };
}

// Validasi Form Kembali Jaga (1. Petugas, 2. Tempat Posisi, 3. Titik Lokasi)
function validateKembaliJaga() {
    const errs = {};
    let firstErrorElementId = null;

    if (!formKembaliJaga.value.petugas_id) {
        errs.petugas_id = "Nama Petugas Jaga wajib dipilih!";
        if (!firstErrorElementId) firstErrorElementId = "field-kembali-petugas";
    }
    if (
        !formKembaliJaga.value.lokasi_nama ||
        !formKembaliJaga.value.lokasi_nama.trim()
    ) {
        errs.lokasi_nama = "Tempat Posisi Pengecekan Terakhir wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-kembali-lokasi-nama";
    }
    if (
        formKembaliJaga.value.latitude === null ||
        formKembaliJaga.value.longitude === null
    ) {
        errs.lokasi =
            'Titik Lokasi Pengecekan Terakhir (koordinat GPS) belum diambil! Klik "Ambil Lokasi Kembali".';
        if (!firstErrorElementId)
            firstErrorElementId = "field-kembali-lokasi-gps";
    }

    errorsKembaliJaga.value = errs;

    if (firstErrorElementId) {
        scrollToElement(firstErrorElementId);
        return false;
    }
    return true;
}

// Buka Modal Konfirmasi Sebelum Kirim Laporan Kembali Jaga
function handleKirimKembaliJagaClick() {
    if (validateKembaliJaga()) {
        showConfirmKembaliJagaModal.value = true;
    }
}

// Eksekusi Submit Laporan Kembali Jaga
function executeSubmitKembaliJaga() {
    showConfirmKembaliJagaModal.value = false;
    isSubmitting.value = true;

    router.post(
        route("keamanan.public-store-kembali-jaga", { token: props.token }),
        formKembaliJaga.value,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                const pet = props.petugasKeamanan.find(
                    (p) => p.id === formKembaliJaga.value.petugas_id,
                );
                successText.value = `Laporan Kembali Jaga atas nama ${pet ? pet.nama : "petugas"} berhasil dikirim ke Buku Mutasi!`;
                resetKembaliJaga();
                window.scrollTo({ top: 0, behavior: "smooth" });
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] ||
                    "Terjadi kesalahan saat menyimpan laporan Kembali Jaga.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

// Konfirmasi Petugas Kembali ke Penjagaan
function handleKembaliClick(iz) {
    if (!formKeluarJaga.value.lat_kembali) {
        errorsKeluarJaga.value.lat_kembali =
            "Titik koordinat Lokasi Kembali wajib diambil terlebih dahulu!";
        scrollToElement("field-keluar-lokasi-kembali");
        return;
    }
    pendingKembaliIzin.value = iz;
    showConfirmKembaliModal.value = true;
}

function executeSubmitKembali() {
    if (!pendingKembaliIzin.value) return;
    const iz = pendingKembaliIzin.value;
    showConfirmKembaliModal.value = false;
    isSubmitting.value = true;

    router.put(
        route("keamanan.public-kembali-izin", {
            token: props.token,
            id: iz.id,
        }),
        {
            lokasi_kembali: "Pos Penjagaan SPPG",
            lat_kembali: formKeluarJaga.value.lat_kembali,
            lng_kembali: formKeluarJaga.value.lng_kembali,
            laporan_kegiatan: "Telah tiba kembali di pos penjagaan.",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                successText.value = `Petugas ${iz.petugas_nama} telah dikonfirmasi kembali ke pos penjagaan!`;
                formKeluarJaga.value.lat_kembali = null;
                formKeluarJaga.value.lng_kembali = null;
                activeMode.value = "pengecekan";
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] ||
                    "Terjadi kesalahan saat menyimpan konfirmasi.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

// Modal Konfirmasi Serah Terima
const showConfirmSerahTerimaModal = ref(false);

const selectedPetugasSerahTerimaNama = computed(() => {
    if (!formSerahTerima.value.petugas_id) return "-";
    const pet = props.petugasKeamanan.find(
        (p) => p.id === formSerahTerima.value.petugas_id,
    );
    return pet ? `${pet.nama} - ${pet.jabatan || "Petugas Keamanan"}` : "-";
});

function validateSerahTerima() {
    const errs = {};
    let firstErrorElementId = null;

    if (!formSerahTerima.value.petugas_id) {
        errs.petugas_id = "Nama Petugas Keamanan wajib dipilih!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-serah-terima-petugas";
    }

    if (!formSerahTerima.value.jenis) {
        errs.jenis =
            "Status Serah Terima (Masuk Jaga atau Lepas Jaga) wajib dipilih!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-serah-terima-jenis";
    }

    if (!formSerahTerima.value.catatan?.trim()) {
        errs.catatan = "Catatan Serah Terima wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-serah-terima-catatan";
    }

    if (!formSerahTerima.value.lokasi_nama?.trim()) {
        errs.lokasi_nama = "Tempat Posisi Serah Terima wajib diisi!";
        if (!firstErrorElementId)
            firstErrorElementId = "field-serah-terima-tempat";
    }

    if (
        formSerahTerima.value.latitude === null ||
        formSerahTerima.value.longitude === null
    ) {
        errs.lokasi =
            'Titik Lokasi Serah Terima (Koordinat GPS) belum diambil! Klik tombol "Ambil Lokasi".';
        if (!firstErrorElementId)
            firstErrorElementId = "field-serah-terima-lokasi";
    }

    errorsSerahTerima.value = errs;

    if (firstErrorElementId) {
        scrollToElement(firstErrorElementId);
        return false;
    }
    return true;
}

function handleKirimSerahTerimaClick() {
    if (validateSerahTerima()) {
        showConfirmSerahTerimaModal.value = true;
    }
}

function executeSubmitSerahTerima() {
    showConfirmSerahTerimaModal.value = false;
    isSubmitting.value = true;

    const payload = {
        petugas_id: formSerahTerima.value.petugas_id,
        jenis: formSerahTerima.value.jenis,
        catatan: formSerahTerima.value.catatan
            ? formSerahTerima.value.catatan.trim()
            : null,
        lokasi_nama: formSerahTerima.value.lokasi_nama
            ? formSerahTerima.value.lokasi_nama.trim()
            : null,
        latitude: formSerahTerima.value.latitude,
        longitude: formSerahTerima.value.longitude,
    };

    router.post(
        route("keamanan.public-store-serah-terima", { token: props.token }),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => {
                isSubmitting.value = false;
                submitSuccess.value = true;
                const pet = props.petugasKeamanan.find(
                    (p) => p.id === payload.petugas_id,
                );
                successText.value = `Laporan Serah Terima (${payload.jenis}) atas nama ${pet?.nama || "Petugas Keamanan"} berhasil disimpan dan tersinkron ke monitoring!`;
            },
            onError: (err) => {
                isSubmitting.value = false;
                locationErrorMessage.value =
                    Object.values(err)[0] ||
                    "Terjadi kesalahan saat menyimpan laporan serah terima.";
                showLocationErrorModal.value = true;
            },
        },
    );
}

function resetSerahTerima() {
    submitSuccess.value = false;
    errorsSerahTerima.value = {};
    formSerahTerima.value.petugas_id = null;
    formSerahTerima.value.jenis = null;
    formSerahTerima.value.catatan = "";
    formSerahTerima.value.lokasi_nama = "";
    formSerahTerima.value.latitude = null;
    formSerahTerima.value.longitude = null;
}

// Helper Ringkasan Data untuk Modal Konfirmasi
const selectedPetugasPengecekanNama = computed(() => {
    return (
        props.petugasKeamanan.find(
            (p) => p.id === formPengecekan.value.petugas_id,
        )?.nama || "-"
    );
});

const countTemuanUmum = computed(() => {
    return formPengecekan.value.pengecekan_umum.filter(
        (it) => it.status === "Tidak Aman" || it.status === "Ada",
    ).length;
});

const countTemuanLingkungan = computed(() => {
    return formPengecekan.value.lingkungan_kerja.filter(
        (it) => it.status === "Tidak Aman",
    ).length;
});

const selectedPetugasKeluarNama = computed(() => {
    return (
        props.petugasKeamanan.find(
            (p) => p.id === formKeluarJaga.value.petugas_id,
        )?.nama || "-"
    );
});

const selectedPetugasSedangDiluarNama = computed(() => {
    return (
        props.petugasKeamanan.find(
            (p) => p.id === formSedangDiluar.value.petugas_id,
        )?.nama || "-"
    );
});

// List laporan jam yang sudah terisi hari ini (dapat menampung > 1 laporan per rentang jam)
const filledJamMap = computed(() => {
    const map = {};
    if (props.mutasiAktif?.details) {
        props.mutasiAktif.details.forEach((d) => {
            if (d.jam) {
                const k = d.jam.trim();
                if (!map[k]) map[k] = [];
                map[k].push(d);
            }
        });
    }
    return map;
});

const showPreviousReports = ref(false);

const izinsSedangKeluar = computed(() => {
    return (props.mutasiAktif?.izins || []).filter(
        (i) => i.status === "Sedang Keluar",
    );
});

const formattedTanggalHariIni = computed(() => {
    try {
        const d = new Date(props.todayDate + "T00:00:00");
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch {
        return props.todayDate;
    }
});
</script>

<template>
    <div
        class="min-h-screen bg-slate-100 text-slate-800 font-sans pb-16 antialiased"
    >
        <Head
            :title="
                'Formulir Laporan Mutasi Keamanan SPPG - ' +
                (unitSppg?.nama || 'SPPG')
            "
        />

        <!-- HEADER BANNER RESMI KEAMANAN (TEMA BIRU LANGIT ELEGAN & BERKONTRAS TINGGI) -->
        <header
            class="bg-gradient-to-r from-sky-900 via-sky-800 to-blue-900 text-white shadow-md border-b-4 border-sky-500"
        >
            <div class="max-w-2xl mx-auto px-3.5 sm:px-4 py-4 sm:py-6">
                <div class="flex items-center justify-between gap-2.5 sm:gap-3.5">
                    <div
                        class="flex items-center gap-2.5 sm:gap-3.5 min-w-0 flex-1"
                    >
                        <div
                            class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-white/15 border border-white/25 text-white flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <ShieldCheck
                                class="h-5 w-5 sm:h-6 sm:w-6 text-sky-300 shrink-0"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1
                                class="text-sm sm:text-base md:text-lg font-black tracking-tight leading-snug truncate sm:whitespace-normal text-white"
                            >
                                Formulir Laporan Mutasi Keamanan SPPG
                            </h1>
                            <p
                                class="text-[11px] sm:text-xs text-sky-200 truncate mt-0.5"
                            >
                                {{
                                    unitSppg?.nama ||
                                    "Satuan Pelayanan Program Gizi"
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Petunjuk Pengisian di Ujung Kanan Atas -->
                    <button
                        type="button"
                        @click="showPetunjukModal = true"
                        class="h-10 w-10 sm:h-10 sm:w-auto p-0 sm:px-3 rounded-2xl bg-white/15 hover:bg-white/25 active:bg-white/30 text-white border border-white/25 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0 text-xs font-bold"
                        title="Petunjuk Pengisian Formulir Keamanan"
                    >
                        <BookOpen class="h-5 w-5 text-amber-300 shrink-0" />
                        <span class="hidden sm:inline text-white"
                            >Petunjuk</span
                        >
                    </button>
                </div>

                <!-- BOX WAKTU REALTIME & HARI/TANGGAL RESMI (DI BAGIAN ATAS) -->
                <div
                    class="mt-3.5 bg-black/30 backdrop-blur-md rounded-2xl p-3 sm:p-4 border border-white/20 shadow-inner"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"
                                ></span>
                            </span>
                            <span
                                class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-400"
                            >
                                Waktu Realtime Otomatis
                            </span>
                        </div>
                        <span
                            class="text-[9.5px] sm:text-[10.5px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30"
                        >
                            WITA (UTC+8)
                        </span>
                    </div>

                    <div
                        class="mt-2.5 pt-2.5 border-t border-white/15 grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-4 items-center"
                    >
                        <!-- Waktu Realtime (misal: 19:20:46 WITA) -->
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-white/15 border border-white/25 text-emerald-300 flex items-center justify-center shrink-0"
                            >
                                <Clock class="h-5 w-5" />
                            </div>
                            <div>
                                <span
                                    class="text-[10px] text-sky-200 block font-medium leading-none"
                                    >Waktu Realtime:</span
                                >
                                <span
                                    class="text-xl sm:text-2xl font-black font-mono tracking-wider text-white block mt-1"
                                >
                                    {{ liveTimeWita }}
                                </span>
                            </div>
                        </div>

                        <!-- Hari & Tanggal (misal: Senin, 16 November 2026) -->
                        <div
                            class="flex items-center gap-3 sm:border-l sm:border-white/15 sm:pl-4"
                        >
                            <div
                                class="h-10 w-10 rounded-xl bg-white/15 border border-white/25 text-sky-300 flex items-center justify-center shrink-0"
                            >
                                <Calendar class="h-5 w-5" />
                            </div>
                            <div>
                                <span
                                    class="text-[10px] text-sky-200 block font-medium leading-none"
                                    >Hari / Tanggal:</span
                                >
                                <span
                                    class="text-xs sm:text-sm font-bold text-white block mt-1"
                                >
                                    {{ liveDateWita }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sesi Jam Masuk Saat Ini & Badge Jumlah Laporan Terisi -->
                    <div
                        class="mt-2.5 pt-2.5 border-t border-white/15 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-[10.5px] text-sky-200 font-medium"
                                >Sesi Jam Masuk:</span
                            >
                            <span
                                class="px-2.5 py-0.5 rounded-lg bg-white/20 text-white font-mono font-black text-xs border border-white/25 shadow-2xs"
                            >
                                {{ currentActiveInterval }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                v-if="
                                    (filledJamMap[currentActiveInterval]
                                        ?.length || 0) > 0
                                "
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[10.5px] font-black bg-emerald-400 text-emerald-950 shadow-xs"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-950 animate-ping"
                                ></span>
                                <span
                                    >{{
                                        filledJamMap[currentActiveInterval]
                                            .length
                                    }}
                                    Laporan Masuk di Sesi Ini</span
                                >
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[10.5px] font-semibold bg-white/15 text-sky-200 border border-white/20"
                            >
                                <span>Belum Ada Laporan di Sesi Ini</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Singkat -->
                <div
                    class="mt-3 p-2.5 sm:p-3 bg-white/10 rounded-xl border border-white/15 text-[11px] sm:text-[11.5px] text-sky-100 leading-relaxed flex items-start gap-2"
                >
                    <Sparkles class="h-4 w-4 text-amber-300 shrink-0 mt-0.5" />
                    <span>
                        Waktu pelaporan tercatat <strong>otomatis</strong> dan
                        langsung disinkronkan ke Dashboard SPPG.
                    </span>
                </div>

                <!-- SWITCH MODE FORM (RESPONSIVE GRID) -->
                <!-- Tab Mode Pilihan Form: Pengecekan, Serah Terima, Keluar Jaga, Sedang Diluar, Kembali Jaga -->
                <div
                    class="mt-3.5 pt-3 border-t border-white/20 grid grid-cols-2 sm:grid-cols-5 gap-1.5 sm:gap-2"
                >
                    <!-- 1. TAB PENGECEKAN -->
                    <button
                        type="button"
                        @click="
                            () => {
                                activeMode = 'pengecekan';
                                submitSuccess = false;
                            }
                        "
                        :class="[
                            activeMode === 'pengecekan'
                                ? 'bg-white text-sky-950 font-black shadow-md ring-2 ring-white/70'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/15',
                            'py-2 px-1.5 sm:px-2 rounded-xl text-[11px] sm:text-xs transition-all cursor-pointer flex items-center justify-center gap-1 sm:gap-1.5 text-center',
                        ]"
                    >
                        <Clock class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Pengecekan</span>
                    </button>

                    <!-- 2. TAB SERAH TERIMA -->
                    <button
                        type="button"
                        @click="
                            () => {
                                activeMode = 'serah_terima';
                                submitSuccess = false;
                            }
                        "
                        :class="[
                            activeMode === 'serah_terima'
                                ? 'bg-white text-indigo-950 font-black shadow-md ring-2 ring-white/70'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/15',
                            'py-2 px-1.5 sm:px-2 rounded-xl text-[11px] sm:text-xs transition-all cursor-pointer flex items-center justify-center gap-1 sm:gap-1.5 text-center',
                        ]"
                    >
                        <ArrowLeftRight class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Serah Terima</span>
                    </button>

                    <!-- 3. TAB KELUAR JAGA -->
                    <button
                        type="button"
                        @click="
                            () => {
                                activeMode = 'keluar_jaga';
                                submitSuccess = false;
                            }
                        "
                        :class="[
                            activeMode === 'keluar_jaga'
                                ? 'bg-amber-400 text-amber-950 font-black shadow-md ring-2 ring-amber-300'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/15',
                            'py-2 px-1.5 sm:px-2 rounded-xl text-[11px] sm:text-xs transition-all cursor-pointer flex items-center justify-center gap-1 sm:gap-1.5 text-center',
                        ]"
                    >
                        <LogOut class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Keluar Jaga</span>
                    </button>

                    <!-- 4. TAB SEDANG DILUAR -->
                    <button
                        type="button"
                        @click="
                            () => {
                                activeMode = 'sedang_diluar';
                                submitSuccess = false;
                            }
                        "
                        :class="[
                            activeMode === 'sedang_diluar'
                                ? 'bg-sky-300 text-sky-950 font-black shadow-md ring-2 ring-sky-200'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/15',
                            'py-2 px-1.5 sm:px-2 rounded-xl text-[11px] sm:text-xs transition-all cursor-pointer flex items-center justify-center gap-1 sm:gap-1.5 text-center',
                        ]"
                    >
                        <MapPin class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Sedang Diluar</span>
                    </button>

                    <!-- 5. TAB KEMBALI JAGA -->
                    <button
                        type="button"
                        @click="
                            () => {
                                activeMode = 'kembali_jaga';
                                submitSuccess = false;
                            }
                        "
                        :class="[
                            activeMode === 'kembali_jaga'
                                ? 'bg-white text-emerald-950 font-black shadow-md ring-2 ring-white/70'
                                : 'bg-white/10 text-white hover:bg-white/20 border border-white/15',
                            'col-span-2 sm:col-span-1 py-2 px-1.5 sm:px-2 rounded-xl text-[11px] sm:text-xs transition-all cursor-pointer flex items-center justify-center gap-1 sm:gap-1.5 text-center',
                        ]"
                    >
                        <LogIn class="h-3.5 w-3.5 shrink-0" />
                        <span class="truncate">Kembali Jaga</span>
                    </button>

                    <button
                        v-if="izinsSedangKeluar.length > 0"
                        type="button"
                        @click="
                            () => {
                                activeMode = 'kembali_jaga';
                                submitSuccess = false;
                            }
                        "
                        class="col-span-2 sm:col-span-5 py-2 px-3 rounded-xl text-xs bg-rose-600 hover:bg-rose-700 text-white font-bold animate-pulse cursor-pointer flex items-center justify-center gap-1.5 text-center transition-colors shadow-xs"
                    >
                        <LogIn class="h-3.5 w-3.5 shrink-0" />
                        <span
                            >{{ izinsSedangKeluar.length }} Petugas Sedang di
                            Luar Pos (Klik untuk Kembali Jaga)</span
                        >
                    </button>
                </div>
            </div>
        </header>

        <!-- CONTAINER BODY FORM -->
        <main
            class="max-w-2xl mx-auto px-3 sm:px-4 pt-4 sm:pt-5 space-y-3.5 sm:space-y-4"
        >
            <!-- TAMPILAN SUKSES SETELAH KIRIM -->
            <div
                v-if="submitSuccess"
                class="bg-white rounded-2xl border-2 border-emerald-400 p-5 sm:p-6 shadow-sm text-center space-y-3.5 animate-in fade-in"
            >
                <div
                    class="h-12 w-12 sm:h-14 sm:w-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto"
                >
                    <CheckCircle2 class="h-7 w-7 sm:h-8 sm:w-8" />
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-slate-900">
                        Laporan Berhasil Terkirim!
                    </h2>
                    <p
                        class="text-xs text-slate-600 mt-1 max-w-md mx-auto leading-relaxed"
                    >
                        {{ successText }}
                    </p>
                </div>
                <div class="pt-1 flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="
                            () => {
                                if (activeMode === 'serah_terima')
                                    resetSerahTerima();
                                else if (activeMode === 'keluar_jaga')
                                    resetKeluarJaga();
                                else if (activeMode === 'kembali_jaga')
                                    resetKembaliJaga();
                                else resetPengecekan();
                            }
                        "
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs cursor-pointer flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <Clock class="h-4 w-4" />
                        <span>Input Laporan Baru</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODE 1: LAPORAN PENGECEKAN                                   -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'pengecekan'"
                class="space-y-3.5 sm:space-y-4"
            >
                <!-- Informasi jika rentang jam ini telah terisi laporan sebelumnya -->
                <div
                    v-if="filledJamMap[currentActiveInterval]?.length > 0"
                    class="p-3.5 bg-blue-50 rounded-2xl border border-blue-200 text-xs text-blue-900 space-y-2 shadow-2xs"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <CheckCircle2
                                class="h-4 w-4 text-blue-600 shrink-0"
                            />
                            <span class="font-bold truncate">
                                {{ filledJamMap[currentActiveInterval].length }}
                                Laporan telah tersimpan di rentang
                                {{ currentActiveInterval }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="showPreviousReports = !showPreviousReports"
                            class="text-[11px] font-bold text-blue-700 underline hover:text-blue-900 cursor-pointer shrink-0"
                        >
                            {{
                                showPreviousReports
                                    ? "Tutup Rincian"
                                    : "Lihat Laporan"
                            }}
                        </button>
                    </div>

                    <!-- Daftar laporan sebelumnya di rentang jam ini -->
                    <div
                        v-if="showPreviousReports"
                        class="pt-2 border-t border-blue-200/80 space-y-2 animate-in fade-in"
                    >
                        <div
                            v-for="(rep, rIdx) in filledJamMap[
                                currentActiveInterval
                            ]"
                            :key="rep.id || rIdx"
                            class="p-2.5 rounded-xl bg-white border border-blue-100 text-slate-800 space-y-1 text-xs shadow-2xs"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="font-extrabold text-slate-900">
                                    #{{ rIdx + 1 }}.
                                    {{ rep.petugas_nama || "Petugas Keamanan" }}
                                </span>
                                <span
                                    class="text-[10.5px] font-mono font-bold text-slate-500"
                                >
                                    Pukul {{ rep.waktu_cek || "-" }} WITA
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px]">
                                <span
                                    class="px-1.5 py-0.5 rounded font-extrabold text-[10px]"
                                    :class="
                                        rep.kondisi === 'Tidak Aman'
                                            ? 'bg-rose-100 text-rose-800'
                                            : 'bg-emerald-100 text-emerald-800'
                                    "
                                >
                                    {{ rep.kondisi }}
                                </span>
                                <span class="text-slate-500 truncate"
                                    >•
                                    {{
                                        rep.lokasi_nama || "Pos Penjagaan"
                                    }}</span
                                >
                            </div>
                            <p
                                v-if="rep.data_pengecekan?.catatan_tambahan"
                                class="text-[11px] text-slate-600 italic bg-slate-50 p-1.5 rounded"
                            >
                                "{{ rep.data_pengecekan.catatan_tambahan }}"
                            </p>
                        </div>
                    </div>

                    <p class="text-[11px] text-blue-700/90 leading-tight">
                        ℹ️ Anda dapat mengirimkan laporan pengecekan patroli
                        tambahan lagi di rentang jam ini.
                    </p>
                </div>

                <!-- 1. NAMA PETUGAS JAGA -->
                <div
                    id="field-pengecekan-petugas"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsPengecekan.petugas_id
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider"
                        >
                            <Users class="h-4 w-4 text-blue-600 shrink-0" />
                            <span>1. Nama Petugas Jaga</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            v-for="p in petugasKeamanan"
                            :key="p.id"
                            :class="[
                                formPengecekan.petugas_id === p.id
                                    ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-500/20 text-blue-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="petugas_jaga_pengecekan"
                                :value="p.id"
                                v-model="formPengecekan.petugas_id"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs block font-bold truncate"
                                    >{{ p.nama }}</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >{{ p.jabatan || "Keamanan SPPG" }}</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsPengecekan.petugas_id"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsPengecekan.petugas_id }}</span>
                    </p>
                </div>

                <!-- 2. KONDISI LINGKUNGAN SPPG -->
                <div
                    class="bg-white rounded-2xl border border-slate-200 p-3.5 sm:p-5 shadow-xs space-y-4"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border-b border-slate-100 pb-3"
                    >
                        <div>
                            <label
                                class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider"
                            >
                                <Shield class="h-4 w-4 text-primary shrink-0" />
                                <span>2. Kondisi Lingkungan SPPG</span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Periksa pengecekan seluruh area lingkungan kerja.
                            </p>
                        </div>

                        <!-- Tombol Set Semua Aman (Full Width di Mobile agar Nyaman Ditekan) -->
                        <button
                            type="button"
                            @click="openConfirmSetSemuaAman"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition-colors cursor-pointer shadow-2xs shrink-0"
                            title="Terapkan status aman ke semua item yang belum dipilih"
                        >
                            <CheckCheck
                                class="h-3.5 w-3.5 text-emerald-600 shrink-0"
                            />
                            <span>Set Semua Aman</span>
                        </button>
                    </div>

                    <!-- SUB-BAGIAN: PENGECEKAN UMUM -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center gap-1.5"
                            >
                                <span
                                    class="w-1.5 h-3.5 bg-blue-600 rounded-full"
                                ></span>
                                <span>Pengecekan Umum</span>
                            </h3>
                            <span
                                class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                            >
                                Wajib Diisi
                            </span>
                        </div>

                        <!-- 2.A: Cuaca (Grid 4 Kolom Rapi di Mobile) -->
                        <div
                            id="field-pengecekan-cuaca"
                            class="p-2.5 sm:p-3 rounded-xl border space-y-2 transition-all"
                            :class="
                                errorsPengecekan.cuaca
                                    ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                    : 'bg-slate-50 border-slate-200/80'
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800"
                                    >Cuaca Saat Ini
                                    <span class="text-rose-500">*</span>:</span
                                >
                                <span
                                    v-if="formPengecekan.cuaca"
                                    class="text-[10.5px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded"
                                >
                                    {{ formPengecekan.cuaca }}
                                </span>
                            </div>

                            <div
                                class="grid grid-cols-4 sm:grid-cols-8 gap-1.5"
                            >
                                <button
                                    v-for="c in CUACA_OPTIONS"
                                    :key="c.label"
                                    type="button"
                                    @click="formPengecekan.cuaca = c.label"
                                    :class="[
                                        formPengecekan.cuaca === c.label
                                            ? 'bg-slate-900 text-white font-bold ring-2 ring-primary shadow-xs'
                                            : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200',
                                        'p-1.5 sm:p-2 rounded-xl text-[10.5px] flex flex-col items-center justify-center gap-1 transition-all cursor-pointer min-h-[50px]',
                                    ]"
                                >
                                    <component
                                        :is="c.icon"
                                        class="h-4 w-4 shrink-0"
                                        :class="
                                            formPengecekan.cuaca === c.label
                                                ? 'text-amber-400'
                                                : c.color
                                        "
                                    />
                                    <span
                                        class="truncate max-w-full text-center leading-tight"
                                        >{{ c.label }}</span
                                    >
                                </button>
                            </div>

                            <p
                                v-if="errorsPengecekan.cuaca"
                                class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                            >
                                <AlertCircle
                                    class="h-4 w-4 shrink-0 text-rose-600"
                                />
                                <span>{{ errorsPengecekan.cuaca }}</span>
                            </p>
                        </div>

                        <!-- 2.B s/d 2.G: 6 Item Pengecekan Umum Lainnya (Tombol Lebar Penuh di HP) -->
                        <div class="space-y-2">
                            <div
                                v-for="(
                                    it, idx
                                ) in formPengecekan.pengecekan_umum"
                                :key="it.key"
                                :id="'field-pengecekan-umum-' + idx"
                                class="p-2.5 sm:p-3 rounded-xl border transition-all space-y-2"
                                :class="[
                                    errorsPengecekan['umum_' + idx]
                                        ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/60'
                                        : it.status === 'Tidak Aman' ||
                                            it.status === 'Ada'
                                          ? 'bg-rose-50/70 border-rose-300'
                                          : 'bg-slate-50 border-slate-200/80',
                                ]"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-800 leading-snug"
                                    >
                                        {{ idx + 1 }}. {{ it.label }}
                                        <span class="text-rose-500">*</span>
                                    </span>

                                    <!-- Switch Button Opsi: 2 Kolom Lebar Penuh di HP -->
                                    <div
                                        class="grid grid-cols-2 w-full sm:w-auto p-0.5 bg-white rounded-lg border border-slate-200 shrink-0 shadow-2xs gap-0.5"
                                    >
                                        <template
                                            v-if="it.type === 'aman_tidak'"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    () => {
                                                        it.status = 'Aman';
                                                        clearErrorPengecekan(
                                                            'umum_' + idx,
                                                        );
                                                    }
                                                "
                                                :class="[
                                                    it.status === 'Aman'
                                                        ? 'bg-emerald-600 text-white font-bold shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                    'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                                ]"
                                            >
                                                <Check
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>Aman</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="
                                                    () => {
                                                        it.status =
                                                            'Tidak Aman';
                                                        clearErrorPengecekan(
                                                            'umum_' + idx,
                                                        );
                                                    }
                                                "
                                                :class="[
                                                    it.status === 'Tidak Aman'
                                                        ? 'bg-rose-600 text-white font-bold shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                    'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                                ]"
                                            >
                                                <AlertTriangle
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>Tidak Aman</span>
                                            </button>
                                        </template>

                                        <template v-else>
                                            <button
                                                type="button"
                                                @click="
                                                    () => {
                                                        it.status = 'Tidak Ada';
                                                        if (
                                                            it.key === 'relawan'
                                                        ) {
                                                            it.petugas_id =
                                                                null;
                                                            it.petugas_nama =
                                                                '';
                                                            it.petugas_jabatan =
                                                                '';
                                                            it.catatan = '';
                                                            it.uraian = '';
                                                            clearErrorPengecekan(
                                                                'relawan_petugas',
                                                            );
                                                        }
                                                        clearErrorPengecekan(
                                                            'umum_' + idx,
                                                        );
                                                        clearErrorPengecekan(
                                                            'umum_uraian_' +
                                                                idx,
                                                        );
                                                    }
                                                "
                                                :class="[
                                                    it.status === 'Tidak Ada'
                                                        ? 'bg-emerald-600 text-white font-bold shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                    'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                                ]"
                                            >
                                                <Check
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>Tidak Ada</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="
                                                    () => {
                                                        it.status = 'Ada';
                                                        clearErrorPengecekan(
                                                            'umum_' + idx,
                                                        );
                                                    }
                                                "
                                                :class="[
                                                    it.status === 'Ada'
                                                        ? it.key === 'relawan'
                                                            ? 'bg-rose-600 text-white font-bold shadow-xs'
                                                            : 'bg-amber-600 text-white font-bold shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                    'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                                ]"
                                            >
                                                <AlertTriangle
                                                    v-if="it.key === 'relawan'"
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <Users
                                                    v-else
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>Ada</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <p
                                    v-if="errorsPengecekan['umum_' + idx]"
                                    class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                                >
                                    <AlertCircle
                                        class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                    />
                                    <span>{{
                                        errorsPengecekan["umum_" + idx]
                                    }}</span>
                                </p>

                                <!-- KONDISI 1: INPUT KHUSUS KENDALA PETUGAS/RELAWAN (PILIH ORANG + KETERANGAN KENDALA) -->
                                <div
                                    v-if="
                                        it.key === 'relawan' &&
                                        it.status === 'Ada'
                                    "
                                    class="pt-2 animate-in fade-in space-y-2.5 bg-rose-50/60 p-3 rounded-xl border border-rose-200"
                                >
                                    <!-- A. Pilihan Nama Petugas SPPG yang Aktif -->
                                    <div
                                        id="field-relawan-petugas"
                                        class="space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="text-[10.5px] font-bold text-rose-900 flex items-center gap-1.5"
                                            >
                                                <Users
                                                    class="h-3.5 w-3.5 text-rose-600 shrink-0"
                                                />
                                                <span
                                                    >Pilih Petugas / Personil
                                                    SPPG yang
                                                    Bersangkutan:</span
                                                >
                                                <span class="text-rose-500"
                                                    >*</span
                                                >
                                            </label>
                                            <span
                                                class="text-[10px] text-slate-400 font-medium"
                                            >
                                                {{
                                                    props.petugasSppgAktif
                                                        .length
                                                }}
                                                personil aktif
                                            </span>
                                        </div>

                                        <div class="relative">
                                            <select
                                                v-model="it.petugas_id"
                                                @change="
                                                    onRelawanPetugasChange(
                                                        it,
                                                        idx,
                                                    )
                                                "
                                                class="w-full text-xs rounded-xl focus:ring-2 focus:ring-rose-500 bg-white p-2.5 text-slate-900 font-bold transition-all border shadow-2xs"
                                                :class="
                                                    errorsPengecekan.relawan_petugas
                                                        ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                                        : 'border-rose-300'
                                                "
                                            >
                                                <option :value="null" disabled>
                                                    -- Klik untuk Memilih Nama
                                                    Petugas SPPG --
                                                </option>
                                                <option
                                                    v-for="pet in props.petugasSppgAktif"
                                                    :key="pet.id"
                                                    :value="pet.id"
                                                >
                                                    {{ pet.nama }} -
                                                    {{
                                                        pet.jabatan ||
                                                        "Petugas SPPG"
                                                    }}
                                                </option>
                                            </select>
                                        </div>

                                        <p
                                            v-if="
                                                errorsPengecekan.relawan_petugas
                                            "
                                            class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                            />
                                            <span>{{
                                                errorsPengecekan.relawan_petugas
                                            }}</span>
                                        </p>
                                    </div>

                                    <!-- B. Kendala / Catatan untuk Orang Tersebut -->
                                    <div
                                        :id="
                                            'field-pengecekan-umum-uraian-' +
                                            idx
                                        "
                                        class="space-y-1"
                                    >
                                        <label
                                            class="text-[10.5px] font-bold text-rose-900 flex items-center gap-1.5"
                                        >
                                            <FileText
                                                class="h-3.5 w-3.5 text-rose-600 shrink-0"
                                            />
                                            <span
                                                >Kendala / Catatan untuk
                                                {{
                                                    it.petugas_nama
                                                        ? it.petugas_nama +
                                                          " - " +
                                                          (it.petugas_jabatan ||
                                                              "Petugas SPPG")
                                                        : "Petugas Tersebut"
                                                }}:</span
                                            >
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="it.catatan"
                                            @input="
                                                () => {
                                                    syncRelawanUraian(it);
                                                    clearErrorPengecekan(
                                                        'umum_uraian_' + idx,
                                                    );
                                                }
                                            "
                                            rows="2"
                                            :placeholder="
                                                'Tuliskan kendala / catatan untuk ' +
                                                (it.petugas_nama
                                                    ? it.petugas_nama +
                                                      ' - ' +
                                                      (it.petugas_jabatan ||
                                                          'Petugas SPPG')
                                                    : 'petugas yang dipilih') +
                                                ' (misal: sakit, izin mendadak, kendala tugas)...'
                                            "
                                            class="w-full text-xs rounded-xl focus:ring-2 focus:ring-rose-500 bg-white p-2.5 text-slate-800 transition-all border shadow-2xs"
                                            :class="
                                                errorsPengecekan[
                                                    'umum_uraian_' + idx
                                                ]
                                                    ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                                    : 'border-rose-300'
                                            "
                                        ></textarea>
                                        <p
                                            v-if="
                                                errorsPengecekan[
                                                    'umum_uraian_' + idx
                                                ]
                                            "
                                            class="text-xs font-bold text-rose-600 flex items-center gap-1.5 animate-in fade-in"
                                        >
                                            <AlertCircle
                                                class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                            />
                                            <span>{{
                                                errorsPengecekan[
                                                    "umum_uraian_" + idx
                                                ]
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- KONDISI 2: INPUT URAIAN STANDAR UNTUK ITEM LAINNYA (JIKA TIDAK AMAN ATAU ADA) -->
                                <div
                                    v-else-if="
                                        it.status === 'Tidak Aman' ||
                                        it.status === 'Ada'
                                    "
                                    :id="'field-pengecekan-umum-uraian-' + idx"
                                    class="pt-1 animate-in fade-in space-y-1"
                                >
                                    <label
                                        class="text-[10.5px] font-bold text-rose-800 flex items-center gap-1"
                                    >
                                        <span
                                            >Uraian {{ it.label }} (Wajib
                                            Disertakan):</span
                                        >
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="it.uraian"
                                        @input="
                                            clearErrorPengecekan(
                                                'umum_uraian_' + idx,
                                            )
                                        "
                                        rows="2"
                                        :placeholder="
                                            'Jelaskan keterangan ' +
                                            it.label.toLowerCase() +
                                            '...'
                                        "
                                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-rose-500 bg-white p-2.5 text-slate-800 transition-all"
                                        :class="
                                            errorsPengecekan[
                                                'umum_uraian_' + idx
                                            ]
                                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/30'
                                                : 'border-rose-300'
                                        "
                                    ></textarea>
                                    <p
                                        v-if="
                                            errorsPengecekan[
                                                'umum_uraian_' + idx
                                            ]
                                        "
                                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 animate-in fade-in"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                        />
                                        <span>{{
                                            errorsPengecekan[
                                                "umum_uraian_" + idx
                                            ]
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-BAGIAN: PENGECEKAN LINGKUNGAN KERJA (21 AREA) -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center gap-1.5"
                            >
                                <span
                                    class="w-1.5 h-3.5 bg-emerald-600 rounded-full"
                                ></span>
                                <span
                                    >Pengecekan Lingkungan Kerja (21 Area)</span
                                >
                            </h3>
                            <span class="text-[10px] text-slate-400 italic"
                                >Aman / Tidak Aman</span
                            >
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(
                                    it, idx
                                ) in formPengecekan.lingkungan_kerja"
                                :key="it.key"
                                :id="'field-pengecekan-lingkungan-' + idx"
                                class="p-2.5 sm:p-3 rounded-xl border transition-all space-y-2"
                                :class="[
                                    errorsPengecekan['lingkungan_' + idx]
                                        ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/60'
                                        : it.status === 'Tidak Aman'
                                          ? 'bg-rose-50/70 border-rose-300'
                                          : 'bg-slate-50 border-slate-200/80',
                                ]"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-800 leading-snug"
                                    >
                                        {{ idx + 1 }}. {{ it.label }}
                                        <span class="text-rose-500">*</span>
                                    </span>

                                    <!-- Switch Aman / Tidak Aman dengan Desain Seragam Pengecekan Umum -->
                                    <div
                                        class="grid grid-cols-2 w-full sm:w-auto p-0.5 bg-white rounded-lg border border-slate-200 shrink-0 shadow-2xs gap-0.5"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                () => {
                                                    it.status = 'Aman';
                                                    clearErrorPengecekan(
                                                        'lingkungan_' + idx,
                                                    );
                                                }
                                            "
                                            :class="[
                                                it.status === 'Aman'
                                                    ? 'bg-emerald-600 text-white font-bold shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900',
                                                'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                            ]"
                                        >
                                            <Check class="h-3 w-3 shrink-0" />
                                            <span>Aman</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                () => {
                                                    it.status = 'Tidak Aman';
                                                    clearErrorPengecekan(
                                                        'lingkungan_' + idx,
                                                    );
                                                }
                                            "
                                            :class="[
                                                it.status === 'Tidak Aman'
                                                    ? 'bg-rose-600 text-white font-bold shadow-xs'
                                                    : 'text-slate-600 hover:text-slate-900',
                                                'py-1.5 px-2 rounded-md text-[11px] transition-all cursor-pointer flex items-center justify-center gap-1',
                                            ]"
                                        >
                                            <AlertTriangle
                                                class="h-3 w-3 shrink-0"
                                            />
                                            <span>Tidak Aman</span>
                                        </button>
                                    </div>
                                </div>

                                <p
                                    v-if="errorsPengecekan['lingkungan_' + idx]"
                                    class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                                >
                                    <AlertCircle
                                        class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                    />
                                    <span>{{
                                        errorsPengecekan["lingkungan_" + idx]
                                    }}</span>
                                </p>

                                <!-- Textarea Uraian Bagian yang Tidak Aman -->
                                <div
                                    v-if="it.status === 'Tidak Aman'"
                                    :id="
                                        'field-pengecekan-lingkungan-uraian-' +
                                        idx
                                    "
                                    class="pt-1 animate-in fade-in space-y-1"
                                >
                                    <label
                                        class="text-[10.5px] font-bold text-rose-800 flex items-center gap-1"
                                    >
                                        <span
                                            >Uraian Masalah di
                                            {{ it.label }} (Wajib
                                            Disertakan):</span
                                        >
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="it.uraian"
                                        @input="
                                            clearErrorPengecekan(
                                                'lingkungan_uraian_' + idx,
                                            )
                                        "
                                        rows="2"
                                        :placeholder="
                                            'Jelaskan kondisi tidak aman di ' +
                                            it.label.toLowerCase() +
                                            '...'
                                        "
                                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-rose-500 bg-white p-2 text-slate-800 transition-all"
                                        :class="
                                            errorsPengecekan[
                                                'lingkungan_uraian_' + idx
                                            ]
                                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/30'
                                                : 'border-rose-300'
                                        "
                                    ></textarea>
                                    <p
                                        v-if="
                                            errorsPengecekan[
                                                'lingkungan_uraian_' + idx
                                            ]
                                        "
                                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 animate-in fade-in"
                                    >
                                        <AlertCircle
                                            class="h-3.5 w-3.5 shrink-0 text-rose-600"
                                        />
                                        <span>{{
                                            errorsPengecekan[
                                                "lingkungan_uraian_" + idx
                                            ]
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. CATATAN TAMBAHAN -->
                <div
                    id="field-pengecekan-catatan-tambahan"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsPengecekan.catatan_tambahan
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <FileText
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span>3. Catatan Tambahan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <textarea
                        v-model="formPengecekan.catatan_tambahan"
                        @input="clearErrorPengecekan('catatan_tambahan')"
                        rows="3"
                        placeholder="Tuliskan catatan tambahan mengenai situasi pos atau hal khusus yang perlu dilaporkan..."
                        class="w-full text-xs rounded-xl border focus:ring-2 p-2.5 sm:p-3 font-medium text-slate-800 transition-colors"
                        :class="
                            errorsPengecekan.catatan_tambahan
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50 focus:ring-emerald-500'
                        "
                    ></textarea>

                    <p
                        v-if="errorsPengecekan.catatan_tambahan"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsPengecekan.catatan_tambahan }}</span>
                    </p>
                </div>

                <!-- 4. TEMPAT POSISI PENGECEKAN TERAKHIR -->
                <div
                    id="field-pengecekan-tempat"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsPengecekan.lokasi_nama
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <Building2
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span class="truncate"
                                >4. Tempat Posisi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <input
                        type="text"
                        v-model="formPengecekan.lokasi_nama"
                        @input="clearErrorPengecekan('lokasi_nama')"
                        placeholder="Contoh: Pos Penjagaan Depan / Pintu Gerbang Utama / Area Dapur / Gudang Logistik / Keliling SPPG..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium text-slate-800 transition-all"
                        :class="
                            errorsPengecekan.lokasi_nama
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    />

                    <p
                        v-if="errorsPengecekan.lokasi_nama"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsPengecekan.lokasi_nama }}</span>
                    </p>
                </div>

                <!-- 5. TITIK LOKASI PENGECEKAN TERAKHIR -->
                <div
                    id="field-pengecekan-lokasi"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsPengecekan.lokasi
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <span class="truncate"
                                >5. Titik Lokasi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                    >
                        <span class="text-xs text-slate-500 italic">
                            * Rekam titik koordinat GPS posisi pengecekan saat
                            ini.
                        </span>
                        <button
                            type="button"
                            @click="requestLocation('pengecekan')"
                            :disabled="isLocating"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs cursor-pointer transition-colors shrink-0"
                        >
                            <Navigation
                                class="h-3.5 w-3.5 shrink-0"
                                :class="
                                    isLocating &&
                                    locatingTarget === 'pengecekan'
                                        ? 'animate-spin'
                                        : ''
                                "
                            />
                            <span>{{
                                isLocating && locatingTarget === "pengecekan"
                                    ? "Mencari..."
                                    : formPengecekan.latitude
                                      ? "Perbarui Lokasi"
                                      : "Ambil Lokasi"
                            }}</span>
                        </button>
                    </div>

                    <!-- Tampilan Peta Google Style saat lokasi didapatkan -->
                    <div
                        v-if="
                            formPengecekan.latitude && formPengecekan.longitude
                        "
                        class="animate-in fade-in"
                    >
                        <MiniGoogleMap
                            :latitude="formPengecekan.latitude"
                            :longitude="formPengecekan.longitude"
                            :label="
                                formPengecekan.lokasi_nama ||
                                'Titik Lokasi Pengecekan Terakhir'
                            "
                            :sppg-latitude="unitSppg?.latitude"
                            :sppg-longitude="unitSppg?.longitude"
                            :sppg-label="unitSppg?.nama || 'Pos SPPG'"
                            height="210px"
                        />
                        <div
                            v-if="
                                getDistanceFromSppg(
                                    formPengecekan.latitude,
                                    formPengecekan.longitude,
                                )
                            "
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                        >
                            <Navigation
                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                            />
                            <span
                                >Jarak lokasi Anda ke SPPG:
                                <strong class="font-black text-emerald-950">{{
                                    getDistanceFromSppg(
                                        formPengecekan.latitude,
                                        formPengecekan.longitude,
                                    )
                                }}</strong></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-2.5 sm:p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            Titik koordinat GPS belum diambil. Klik tombol biru
                            <strong>"Ambil Lokasi"</strong> di atas.
                        </span>
                    </div>

                    <p
                        v-if="errorsPengecekan.lokasi"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsPengecekan.lokasi }}</span>
                    </p>
                </div>

                <!-- TOMBOL KIRIM LAPORAN PENGECEKAN -->
                <div class="pt-1">
                    <button
                        type="button"
                        @click="handleKirimPengecekanClick"
                        :disabled="isSubmitting"
                        class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-md transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <Send
                            class="h-4 w-4 shrink-0"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting
                                ? "Mengirim Laporan..."
                                : "Kirim Laporan Pengecekan"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODE 2: LAPORAN KELUAR JAGA                                  -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'keluar_jaga'"
                class="space-y-3.5 sm:space-y-4"
            >
                <!-- 1. NAMA PETUGAS JAGA -->
                <div
                    id="field-keluar-petugas"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsKeluarJaga.petugas_id
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider"
                        >
                            <Users class="h-4 w-4 text-blue-600 shrink-0" />
                            <span>1. Nama Petugas Jaga</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            v-for="p in petugasKeamanan"
                            :key="p.id"
                            :class="[
                                formKeluarJaga.petugas_id === p.id
                                    ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-500/20 text-blue-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="petugas_jaga_keluar"
                                :value="p.id"
                                v-model="formKeluarJaga.petugas_id"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs block font-bold truncate"
                                    >{{ p.nama }}</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >{{ p.jabatan || "Keamanan SPPG" }}</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsKeluarJaga.petugas_id"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKeluarJaga.petugas_id }}</span>
                    </p>
                </div>

                <!-- 2. TEMPAT TUJUAN IJIN -->
                <div
                    id="field-keluar-tujuan"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2 transition-all"
                    :class="
                        errorsKeluarJaga.lokasi_tujuan
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <Building2
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span class="truncate">2. Tempat Tujuan Ijin</span>
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <input
                        type="text"
                        v-model="formKeluarJaga.lokasi_tujuan"
                        @input="clearErrorKeluarJaga('lokasi_tujuan')"
                        placeholder="Contoh: Toko Elektronik Sukasada / Kantor Desa / Patroli Perimeter..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-amber-500 p-2.5 font-medium text-slate-800 transition-all"
                        :class="
                            errorsKeluarJaga.lokasi_tujuan
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    />

                    <p
                        v-if="errorsKeluarJaga.lokasi_tujuan"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKeluarJaga.lokasi_tujuan }}</span>
                    </p>
                </div>

                <!-- 3. KEPERLUAN IJIN KELUAR -->
                <div
                    id="field-keluar-keperluan"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2 transition-all"
                    :class="
                        errorsKeluarJaga.keperluan
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <FileText class="h-4 w-4 text-amber-600 shrink-0" />
                            <span class="truncate"
                                >3. Keperluan Ijin Keluar</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <textarea
                        v-model="formKeluarJaga.keperluan"
                        @input="clearErrorKeluarJaga('keperluan')"
                        rows="3"
                        placeholder="Tuliskan alasan / rincian keperluan ijin keluar pos penjagaan..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-amber-500 p-2.5 font-medium text-slate-800 transition-all"
                        :class="
                            errorsKeluarJaga.keperluan
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    ></textarea>

                    <p
                        v-if="errorsKeluarJaga.keperluan"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKeluarJaga.keperluan }}</span>
                    </p>
                </div>

                <!-- 4. TITIK LOKASI PENGECEKAN TERAKHIR -->
                <div
                    id="field-keluar-lokasi"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsKeluarJaga.lokasi
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <span class="truncate"
                                >4. Titik Lokasi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                    >
                        <span class="text-xs text-slate-500 italic">
                            * Rekam koordinat GPS titik lokasi pengecekan
                            terakhir Anda saat ini.
                        </span>
                        <button
                            type="button"
                            @click="requestLocation('keluar_jaga')"
                            :disabled="isLocating"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs cursor-pointer transition-colors shrink-0"
                        >
                            <Navigation
                                class="h-3.5 w-3.5 shrink-0"
                                :class="
                                    isLocating &&
                                    locatingTarget === 'keluar_jaga'
                                        ? 'animate-spin'
                                        : ''
                                "
                            />
                            <span>{{
                                isLocating && locatingTarget === "keluar_jaga"
                                    ? "Mencari..."
                                    : formKeluarJaga.latitude
                                      ? "Perbarui Lokasi"
                                      : "Ambil Lokasi"
                            }}</span>
                        </button>
                    </div>

                    <!-- Tampilan Peta Google Style saat lokasi didapatkan -->
                    <div
                        v-if="
                            formKeluarJaga.latitude && formKeluarJaga.longitude
                        "
                        class="animate-in fade-in"
                    >
                        <MiniGoogleMap
                            :latitude="formKeluarJaga.latitude"
                            :longitude="formKeluarJaga.longitude"
                            label="Titik Lokasi Pengecekan Terakhir"
                            :sppg-latitude="unitSppg?.latitude"
                            :sppg-longitude="unitSppg?.longitude"
                            :sppg-label="unitSppg?.nama || 'Pos SPPG'"
                            height="210px"
                        />
                        <div
                            v-if="
                                getDistanceFromSppg(
                                    formKeluarJaga.latitude,
                                    formKeluarJaga.longitude,
                                )
                            "
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                        >
                            <Navigation
                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                            />
                            <span
                                >Jarak lokasi Anda ke SPPG:
                                <strong class="font-black text-emerald-950">{{
                                    getDistanceFromSppg(
                                        formKeluarJaga.latitude,
                                        formKeluarJaga.longitude,
                                    )
                                }}</strong></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-2.5 sm:p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            Titik koordinat GPS belum diambil. Klik tombol biru
                            <strong>"Ambil Lokasi"</strong> di atas.
                        </span>
                    </div>

                    <p
                        v-if="errorsKeluarJaga.lokasi"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKeluarJaga.lokasi }}</span>
                    </p>
                </div>

                <!-- TOMBOL KIRIM LAPORAN KELUAR JAGA -->
                <div class="pt-1">
                    <button
                        type="button"
                        @click="handleKirimKeluarJagaClick"
                        :disabled="isSubmitting"
                        class="w-full py-3.5 px-4 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white font-black text-sm shadow-md transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <LogOut
                            class="h-4 w-4 shrink-0"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting
                                ? "Menyimpan..."
                                : "Kirim Laporan Keluar Jaga"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODE: LAPORAN SEDANG DILUAR                                  -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'sedang_diluar'"
                class="space-y-3.5 sm:space-y-4"
            >
                <!-- 1. NAMA PETUGAS KEAMANAN -->
                <div
                    id="field-sedang-petugas"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsSedangDiluar.petugas_id
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider"
                        >
                            <Users class="h-4 w-4 text-sky-600 shrink-0" />
                            <span>1. Nama Petugas Keamanan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            v-for="p in petugasKeamanan"
                            :key="p.id"
                            :class="[
                                formSedangDiluar.petugas_id === p.id
                                    ? 'bg-sky-50 border-sky-500 ring-2 ring-sky-500/20 text-sky-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="petugas_sedang_diluar"
                                :value="p.id"
                                v-model="formSedangDiluar.petugas_id"
                                class="h-4 w-4 text-sky-600 focus:ring-sky-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs block font-bold truncate"
                                    >{{ p.nama }}</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >{{ p.jabatan || "Keamanan SPPG" }}</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsSedangDiluar.petugas_id"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSedangDiluar.petugas_id }}</span>
                    </p>
                </div>

                <!-- 2. TITIK LOKASI PENGECEKAN TERAKHIR -->
                <div
                    id="field-sedang-lokasi-nama"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsSedangDiluar.lokasi_nama ||
                        errorsSedangDiluar.lokasi
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <span class="truncate"
                                >2. Titik Lokasi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <!-- Input Nama Posisi Pengecekan Terakhir -->
                    <div class="space-y-1.5">
                        <label
                            class="text-[11px] font-bold text-slate-600 block"
                        >
                            Nama Tempat / Posisi Pengecekan Terakhir:
                        </label>
                        <input
                            type="text"
                            v-model="formSedangDiluar.lokasi_nama"
                            @input="clearErrorSedangDiluar('lokasi_nama')"
                            placeholder="Contoh: Pos Jaga Utama / Gerbang Depan / Patroli Timur..."
                            class="w-full text-xs rounded-xl focus:ring-2 focus:ring-sky-500 p-2.5 font-medium text-slate-800 transition-all"
                            :class="
                                errorsSedangDiluar.lokasi_nama
                                    ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                    : 'border-slate-200 bg-slate-50'
                            "
                        />
                        <p
                            v-if="errorsSedangDiluar.lokasi_nama"
                            class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                        >
                            <AlertCircle
                                class="h-4 w-4 shrink-0 text-rose-600"
                            />
                            <span>{{ errorsSedangDiluar.lokasi_nama }}</span>
                        </p>
                    </div>

                    <!-- Tombol Ambil Lokasi GPS -->
                    <div
                        id="field-sedang-lokasi-gps"
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-2 border-t border-slate-100"
                    >
                        <span class="text-xs text-slate-500 italic">
                            * Rekam koordinat GPS titik lokasi pengecekan
                            terakhir Anda saat ini.
                        </span>
                        <button
                            type="button"
                            @click="requestLocation('sedang_diluar')"
                            :disabled="isLocating"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white shadow-2xs cursor-pointer transition-colors shrink-0"
                        >
                            <Navigation
                                class="h-3.5 w-3.5 shrink-0"
                                :class="
                                    isLocating &&
                                    locatingTarget === 'sedang_diluar'
                                        ? 'animate-spin'
                                        : ''
                                "
                            />
                            <span>{{
                                isLocating && locatingTarget === "sedang_diluar"
                                    ? "Mencari..."
                                    : formSedangDiluar.latitude
                                      ? "Perbarui Lokasi"
                                      : "Ambil Lokasi"
                            }}</span>
                        </button>
                    </div>

                    <!-- Tampilan Peta Google Style saat lokasi didapatkan -->
                    <div
                        v-if="
                            formSedangDiluar.latitude &&
                            formSedangDiluar.longitude
                        "
                        class="animate-in fade-in"
                    >
                        <MiniGoogleMap
                            :latitude="formSedangDiluar.latitude"
                            :longitude="formSedangDiluar.longitude"
                            :label="
                                formSedangDiluar.lokasi_nama ||
                                'Titik Lokasi Pengecekan Terakhir'
                            "
                            :sppg-latitude="unitSppg?.latitude"
                            :sppg-longitude="unitSppg?.longitude"
                            :sppg-label="unitSppg?.nama || 'Pos SPPG'"
                            height="210px"
                        />
                        <div
                            v-if="
                                getDistanceFromSppg(
                                    formSedangDiluar.latitude,
                                    formSedangDiluar.longitude,
                                )
                            "
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                        >
                            <Navigation
                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                            />
                            <span
                                >Jarak lokasi Anda ke SPPG:
                                <strong class="font-black text-emerald-950">{{
                                    getDistanceFromSppg(
                                        formSedangDiluar.latitude,
                                        formSedangDiluar.longitude,
                                    )
                                }}</strong></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-2.5 sm:p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            Titik koordinat GPS belum diambil. Klik tombol biru
                            <strong>"Ambil Lokasi"</strong> di atas.
                        </span>
                    </div>

                    <p
                        v-if="errorsSedangDiluar.lokasi"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSedangDiluar.lokasi }}</span>
                    </p>
                </div>

                <!-- TOMBOL KIRIM LAPORAN SEDANG DILUAR -->
                <div class="pt-1">
                    <button
                        type="button"
                        @click="handleKirimSedangDiluarClick"
                        :disabled="isSubmitting"
                        class="w-full py-3.5 px-4 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-black text-sm shadow-md transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <MapPin
                            class="h-4 w-4 shrink-0"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting
                                ? "Menyimpan..."
                                : "Kirim Laporan Sedang Diluar"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB: LAPORAN KEMBALI JAGA                                     -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'kembali_jaga'"
                class="space-y-3.5 sm:space-y-4"
            >
                <!-- 1. NAMA PETUGAS JAGA -->
                <div
                    id="field-kembali-petugas"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsKembaliJaga.petugas_id
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-2 uppercase tracking-wider"
                        >
                            <Users class="h-4 w-4 text-emerald-600 shrink-0" />
                            <span>1. Nama Petugas Jaga</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            v-for="p in petugasKeamanan"
                            :key="p.id"
                            :class="[
                                formKembaliJaga.petugas_id === p.id
                                    ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 text-emerald-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="petugas_jaga_kembali"
                                :value="p.id"
                                v-model="formKembaliJaga.petugas_id"
                                class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs block font-bold truncate"
                                    >{{ p.nama }}</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >{{ p.jabatan || "Keamanan SPPG" }}</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsKembaliJaga.petugas_id"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKembaliJaga.petugas_id }}</span>
                    </p>
                </div>

                <!-- 2. TEMPAT POSISI PENGECEKAN TERAKHIR -->
                <div
                    id="field-kembali-lokasi-nama"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2 transition-all"
                    :class="
                        errorsKembaliJaga.lokasi_nama
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <Building2
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span class="truncate"
                                >2. Tempat Posisi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <input
                        type="text"
                        v-model="formKembaliJaga.lokasi_nama"
                        @input="clearErrorKembaliJaga('lokasi_nama')"
                        placeholder="Contoh: Pos Penjagaan SPPG / Gerbang Utama / Area Kantor..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium text-slate-800 transition-all"
                        :class="
                            errorsKembaliJaga.lokasi_nama
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    />

                    <p
                        v-if="errorsKembaliJaga.lokasi_nama"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKembaliJaga.lokasi_nama }}</span>
                    </p>
                </div>

                <!-- 3. TITIK LOKASI PENGECEKAN TERAKHIR -->
                <div
                    id="field-kembali-lokasi-gps"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsKembaliJaga.lokasi
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <span class="truncate"
                                >3. Titik Lokasi Pengecekan Terakhir</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                    >
                        <span class="text-xs text-slate-500 italic">
                            * Rekam titik koordinat GPS posisi pengecekan saat
                            ini.
                        </span>
                        <button
                            type="button"
                            @click="requestLocation('kembali_jaga')"
                            :disabled="isLocating"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs cursor-pointer transition-colors shrink-0"
                        >
                            <Navigation
                                class="h-3.5 w-3.5 shrink-0"
                                :class="
                                    isLocating &&
                                    locatingTarget === 'kembali_jaga'
                                        ? 'animate-spin'
                                        : ''
                                "
                            />
                            <span>{{
                                isLocating && locatingTarget === "kembali_jaga"
                                    ? "Mencari..."
                                    : formKembaliJaga.latitude
                                      ? "Perbarui Lokasi"
                                      : "Ambil Lokasi"
                            }}</span>
                        </button>
                    </div>

                    <!-- Tampilan Peta Google Style saat lokasi didapatkan -->
                    <div
                        v-if="
                            formKembaliJaga.latitude &&
                            formKembaliJaga.longitude
                        "
                        class="animate-in fade-in"
                    >
                        <MiniGoogleMap
                            :latitude="formKembaliJaga.latitude"
                            :longitude="formKembaliJaga.longitude"
                            :label="
                                formKembaliJaga.lokasi_nama ||
                                'Titik Lokasi Pengecekan Terakhir'
                            "
                            :sppg-latitude="unitSppg?.latitude"
                            :sppg-longitude="unitSppg?.longitude"
                            :sppg-label="unitSppg?.nama || 'Pos SPPG'"
                            height="210px"
                        />
                        <div
                            v-if="
                                getDistanceFromSppg(
                                    formKembaliJaga.latitude,
                                    formKembaliJaga.longitude,
                                )
                            "
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                        >
                            <Navigation
                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                            />
                            <span
                                >Jarak lokasi Anda ke SPPG:
                                <strong class="font-black text-emerald-950">{{
                                    getDistanceFromSppg(
                                        formKembaliJaga.latitude,
                                        formKembaliJaga.longitude,
                                    )
                                }}</strong></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-2.5 sm:p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            Titik koordinat GPS belum diambil. Klik tombol biru
                            <strong>"Ambil Lokasi"</strong> di atas.
                        </span>
                    </div>

                    <p
                        v-if="errorsKembaliJaga.lokasi"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsKembaliJaga.lokasi }}</span>
                    </p>
                </div>

                <!-- TOMBOL KIRIM LAPORAN KEMBALI JAGA -->
                <div class="pt-1">
                    <button
                        type="button"
                        @click="handleKirimKembaliJagaClick"
                        :disabled="isSubmitting"
                        class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-md transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <LogIn
                            class="h-4 w-4 shrink-0"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting
                                ? "Menyimpan..."
                                : "Kirim Laporan Kembali Jaga"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODE 3: LAPORAN SERAH TERIMA JAGA                            -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'serah_terima'"
                class="space-y-3.5 sm:space-y-4"
            >
                <!-- 1. PILIH PETUGAS KEAMANAN -->
                <div
                    id="field-serah-terima-petugas"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsSerahTerima.petugas_id
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <Users class="h-4 w-4 text-indigo-600 shrink-0" />
                            <span>1. Nama Petugas Keamanan</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            v-for="pet in props.petugasKeamanan"
                            :key="pet.id"
                            :class="[
                                formSerahTerima.petugas_id === pet.id
                                    ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-500/20 text-blue-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="petugas_serah_terima"
                                :value="pet.id"
                                v-model="formSerahTerima.petugas_id"
                                @change="clearErrorSerahTerima('petugas_id')"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs block font-bold truncate"
                                    >{{ pet.nama }}</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >{{ pet.jabatan || "Keamanan SPPG" }}</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsSerahTerima.petugas_id"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSerahTerima.petugas_id }}</span>
                    </p>
                </div>

                <!-- 2. OPSI SERAH TERIMA: MASUK JAGA / LEPAS JAGA -->
                <div
                    id="field-serah-terima-jenis"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsSerahTerima.jenis
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <ArrowLeftRight
                                class="h-4 w-4 text-indigo-600 shrink-0"
                            />
                            <span>2. Status Serah Terima</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Dipilih
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5"
                    >
                        <label
                            :class="[
                                formSerahTerima.jenis === 'Masuk Jaga'
                                    ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-500/20 text-blue-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="jenis_serah_terima"
                                value="Masuk Jaga"
                                v-model="formSerahTerima.jenis"
                                @change="clearErrorSerahTerima('jenis')"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span class="text-xs block font-bold truncate"
                                    >Masuk Jaga</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >Mulai Dinas / Tugas</span
                                >
                            </div>
                        </label>

                        <label
                            :class="[
                                formSerahTerima.jenis === 'Lepas Jaga'
                                    ? 'bg-blue-50 border-blue-500 ring-2 ring-blue-500/20 text-blue-950 font-bold'
                                    : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700',
                                'p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all',
                            ]"
                        >
                            <input
                                type="radio"
                                name="jenis_serah_terima"
                                value="Lepas Jaga"
                                v-model="formSerahTerima.jenis"
                                @change="clearErrorSerahTerima('jenis')"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <span class="text-xs block font-bold truncate"
                                    >Lepas Jaga</span
                                >
                                <span
                                    class="text-[10.5px] text-slate-400 block truncate"
                                    >Selesai Dinas / Tugas</span
                                >
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="errorsSerahTerima.jenis"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSerahTerima.jenis }}</span>
                    </p>
                </div>

                <!-- 3. CATATAN SERAH TERIMA -->
                <div
                    id="field-serah-terima-catatan"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsSerahTerima.catatan
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider"
                        >
                            <FileText
                                class="h-4 w-4 text-indigo-600 shrink-0"
                            />
                            <span>3. Catatan Serah Terima</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Catat barang inventaris yang diserahterimakan (kunci
                        pos/gerbang, handy talky, kelengkapan pos, kondisi
                        lingkungan terkini).
                    </p>
                    <textarea
                        v-model="formSerahTerima.catatan"
                        @input="clearErrorSerahTerima('catatan')"
                        rows="3"
                        placeholder="Contoh: Kunci pos dan gerbang lengkap, HT 2 unit baterai penuh, situasi pos dan lingkungan aman terkendali..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-indigo-500 p-2.5 sm:p-3 font-medium text-slate-800 transition-all border"
                        :class="
                            errorsSerahTerima.catatan
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    ></textarea>

                    <p
                        v-if="errorsSerahTerima.catatan"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSerahTerima.catatan }}</span>
                    </p>
                </div>

                <!-- 4. TEMPAT POSISI SERAH TERIMA -->
                <div
                    id="field-serah-terima-tempat"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-2.5 transition-all"
                    :class="
                        errorsSerahTerima.lokasi_nama
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <Building2
                                class="h-4 w-4 text-emerald-600 shrink-0"
                            />
                            <span class="truncate"
                                >4. Tempat Posisi Serah Terima</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <input
                        type="text"
                        v-model="formSerahTerima.lokasi_nama"
                        @input="clearErrorSerahTerima('lokasi_nama')"
                        placeholder="Contoh: Pos Penjagaan SPPG / Pintu Gerbang Utama / Area Dapur..."
                        class="w-full text-xs rounded-xl focus:ring-2 focus:ring-emerald-500 p-2.5 font-medium text-slate-800 transition-all border"
                        :class="
                            errorsSerahTerima.lokasi_nama
                                ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/50'
                                : 'border-slate-200 bg-slate-50'
                        "
                    />

                    <p
                        v-if="errorsSerahTerima.lokasi_nama"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-0.5 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSerahTerima.lokasi_nama }}</span>
                    </p>
                </div>

                <!-- 5. TITIK LOKASI SERAH TERIMA -->
                <div
                    id="field-serah-terima-lokasi"
                    class="bg-white rounded-2xl border p-3.5 sm:p-5 shadow-xs space-y-3 transition-all"
                    :class="
                        errorsSerahTerima.lokasi
                            ? 'border-rose-500 ring-2 ring-rose-400 bg-rose-50/20'
                            : 'border-slate-200'
                    "
                >
                    <div class="flex items-center justify-between gap-2">
                        <label
                            class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider min-w-0"
                        >
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <span class="truncate"
                                >5. Titik Lokasi Serah Terima</span
                            >
                            <span class="text-rose-500 shrink-0">*</span>
                        </label>
                        <span
                            class="text-[10px] font-bold text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-200 shrink-0"
                        >
                            Wajib Diisi
                        </span>
                    </div>

                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                    >
                        <span class="text-xs text-slate-500 italic">
                            * Rekam titik koordinat GPS posisi serah terima saat
                            ini.
                        </span>
                        <button
                            type="button"
                            @click="requestLocation('serah_terima')"
                            :disabled="isLocating"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-2xs cursor-pointer transition-colors shrink-0"
                        >
                            <Navigation
                                class="h-3.5 w-3.5 shrink-0"
                                :class="
                                    isLocating &&
                                    locatingTarget === 'serah_terima'
                                        ? 'animate-spin'
                                        : ''
                                "
                            />
                            <span>{{
                                isLocating && locatingTarget === "serah_terima"
                                    ? "Mencari..."
                                    : formSerahTerima.latitude
                                      ? "Perbarui Lokasi"
                                      : "Ambil Lokasi"
                            }}</span>
                        </button>
                    </div>

                    <!-- Tampilan Peta Google Style saat lokasi didapatkan -->
                    <div
                        v-if="
                            formSerahTerima.latitude &&
                            formSerahTerima.longitude
                        "
                        class="animate-in fade-in"
                    >
                        <MiniGoogleMap
                            :latitude="formSerahTerima.latitude"
                            :longitude="formSerahTerima.longitude"
                            :label="
                                formSerahTerima.lokasi_nama ||
                                'Titik Lokasi Serah Terima'
                            "
                            :sppg-latitude="unitSppg?.latitude"
                            :sppg-longitude="unitSppg?.longitude"
                            :sppg-label="unitSppg?.nama || 'Pos SPPG'"
                            height="210px"
                        />
                        <div
                            v-if="
                                getDistanceFromSppg(
                                    formSerahTerima.latitude,
                                    formSerahTerima.longitude,
                                )
                            "
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                        >
                            <Navigation
                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                            />
                            <span
                                >Jarak lokasi Anda ke SPPG:
                                <strong class="font-black text-emerald-950">{{
                                    getDistanceFromSppg(
                                        formSerahTerima.latitude,
                                        formSerahTerima.longitude,
                                    )
                                }}</strong></span
                            >
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-2.5 sm:p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
                    >
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>
                            Titik koordinat GPS belum diambil. Klik tombol biru
                            <strong>"Ambil Lokasi"</strong> di atas.
                        </span>
                    </div>

                    <p
                        v-if="errorsSerahTerima.lokasi"
                        class="text-xs font-bold text-rose-600 flex items-center gap-1.5 pt-1 animate-in fade-in"
                    >
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>{{ errorsSerahTerima.lokasi }}</span>
                    </p>
                </div>

                <!-- TOMBOL KIRIM LAPORAN SERAH TERIMA -->
                <div class="pt-2">
                    <button
                        type="button"
                        @click="handleKirimSerahTerimaClick"
                        :disabled="isSubmitting"
                        class="w-full py-3.5 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-black text-sm shadow-md hover:shadow-lg transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <Send
                            class="h-4 w-4"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting
                                ? "Mengirim Laporan..."
                                : "Kirim Laporan Serah Terima"
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- MODE 4: DAFTAR PETUGAS SEDANG DI LUAR (KONFIRMASI KEPULANGAN) -->
            <!-- ============================================================ -->
            <div
                v-else-if="activeMode === 'izin_kembali_list'"
                class="space-y-3.5 sm:space-y-4"
            >
                <div
                    class="bg-rose-600 text-white p-3.5 sm:p-4 rounded-2xl shadow-xs"
                >
                    <h2 class="text-sm font-black flex items-center gap-2">
                        <LogIn class="h-4 w-4 shrink-0" />
                        <span>Daftar Petugas yang Sedang Keluar</span>
                    </h2>
                    <p class="text-[11px] text-white/80 mt-0.5">
                        Pilih petugas yang sudah tiba kembali di pos untuk
                        merekam koordinat kepulangan.
                    </p>
                </div>

                <div class="space-y-2.5">
                    <div
                        v-for="iz in izinsSedangKeluar"
                        :key="iz.id"
                        class="bg-white rounded-2xl border border-slate-200 p-3.5 sm:p-4 shadow-xs space-y-2.5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="font-bold text-slate-900 text-sm truncate"
                                >{{ iz.petugas_nama }}</span
                            >
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 shrink-0"
                            >
                                Sedang di Luar
                            </span>
                        </div>
                        <div class="text-xs text-slate-600">
                            Tujuan:
                            <strong>{{ iz.lokasi_tujuan }}</strong> (Keluar jam
                            {{ iz.waktu_keluar }})
                        </div>
                        <p class="text-[11px] text-slate-500 italic">
                            {{ iz.keperluan }}
                        </p>

                        <!-- Form Cepat Konfirmasi Kembali -->
                        <div
                            class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                        >
                            <button
                                type="button"
                                @click="requestLocation('keluar_kembali')"
                                class="w-full sm:w-auto px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 cursor-pointer text-center"
                            >
                                <Navigation class="h-3 w-3 inline mr-1" />
                                <span>{{
                                    formKeluarJaga.lat_kembali
                                        ? "Perbarui Lokasi"
                                        : "Ambil Lokasi Kembali"
                                }}</span>
                            </button>

                            <button
                                type="button"
                                @click="handleKembaliClick(iz)"
                                class="w-full sm:w-auto px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs cursor-pointer flex items-center justify-center gap-1.5 text-center"
                            >
                                <LogIn class="h-3.5 w-3.5 shrink-0" />
                                <span>Simpan Kembali</span>
                            </button>
                        </div>
                        <div
                            v-if="formKeluarJaga.lat_kembali"
                            class="text-[10.5px] font-mono text-emerald-800 flex flex-wrap items-center gap-2"
                        >
                            <span
                                >📍 Titik Terkunci:
                                {{ formKeluarJaga.lat_kembali }},
                                {{ formKeluarJaga.lng_kembali }}</span
                            >
                            <span
                                v-if="
                                    getDistanceFromSppg(
                                        formKeluarJaga.lat_kembali,
                                        formKeluarJaga.lng_kembali,
                                    )
                                "
                                class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"
                            >
                                Jarak ke SPPG:
                                {{
                                    getDistanceFromSppg(
                                        formKeluarJaga.lat_kembali,
                                        formKeluarJaga.lng_kembali,
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ============================================================ -->
        <!-- MODALS KONFIRMASI & PERIZINAN                                -->
        <!-- ============================================================ -->

        <!-- MODAL 1: KONFIRMASI SET SEMUA AMAN -->
        <div
            v-if="showConfirmSetAmanModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-sm w-full p-5 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200"
                    >
                        <CheckCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Set Semua Aman
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Pemeriksaan kondisi lingkungan SPPG
                        </p>
                    </div>
                </div>

                <div
                    class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 leading-relaxed space-y-1.5"
                >
                    <p
                        class="font-bold flex items-center gap-1.5 text-amber-800"
                    >
                        <AlertTriangle
                            class="h-4 w-4 shrink-0 text-amber-600"
                        />
                        <span>Peringatan SOP Keamanan:</span>
                    </p>
                    <p>
                        Apakah Anda yakin ingin mengatur seluruh checklist umum
                        dan 21 area menjadi <strong>"Aman"</strong>?
                    </p>
                    <p class="text-[11px] text-amber-700 italic">
                        Petugas jaga wajib melakukan pemeriksaan fisik secara
                        langsung ke seluruh area sebelum menyatakan situasi
                        kondusif.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmSetAmanModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeSetSemuaAman"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>Ya, Set Aman</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 2: IZIN AKSES LOKASI (GPS) PETUGAS -->
        <div
            v-if="showLocationPermissionModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-sm w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div class="text-center space-y-2">
                    <div
                        class="h-14 w-14 rounded-2xl bg-blue-100 text-blue-700 mx-auto flex items-center justify-center border border-blue-200 shadow-xs"
                    >
                        <Navigation
                            class="h-7 w-7 text-blue-600 animate-pulse"
                        />
                    </div>
                    <h3
                        class="text-base font-black text-slate-900 tracking-tight"
                    >
                        Izin Akses Lokasi (GPS)
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Pencatatan koordinat GPS kehadiran &amp; patroli
                        keamanan SPPG
                    </p>
                </div>

                <div
                    class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-700 leading-relaxed space-y-2"
                >
                    <p>
                        Aplikasi memerlukan akses lokasi GPS perangkat HP Anda
                        untuk memvalidasi posisi petugas di pos jaga atau titik
                        dinas luar.
                    </p>
                    <div
                        class="p-2.5 bg-blue-50/80 rounded-xl border border-blue-200/80 text-[11px] text-blue-900 flex items-start gap-2"
                    >
                        <Info class="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                        <span>
                            Tekan tombol
                            <strong>"Setuju &amp; Izinkan Lokasi"</strong> di
                            bawah, kemudian pilih
                            <strong>"Izinkan / Allow"</strong> pada notifikasi
                            browser HP Anda agar lokasi terus diizinkan secara
                            otomatis.
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showLocationPermissionModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="handleAgreeLocationPermission"
                        class="px-3.5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>Setuju &amp; Izinkan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 3: PERINGATAN / ERROR LOKASI GPS -->
        <div
            v-if="showLocationErrorModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-sm w-full p-5 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200"
                    >
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Pemberitahuan Sistem
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Izin lokasi perangkat
                        </p>
                    </div>
                </div>

                <div
                    class="p-3 bg-rose-50 rounded-2xl border border-rose-200 text-xs text-rose-900 leading-relaxed"
                >
                    {{ locationErrorMessage }}
                </div>

                <button
                    type="button"
                    @click="showLocationErrorModal = false"
                    class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-xs font-bold text-white shadow-xs cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>

        <!-- MODAL 4: KONFIRMASI KIRIM LAPORAN PENGECEKAN -->
        <div
            v-if="showConfirmKirimPengecekanModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95 max-h-[90vh] overflow-y-auto"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3"
                >
                    <div
                        class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200"
                    >
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Laporan Pengecekan
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Pastikan seluruh data sudah benar sebelum dikirim
                        </p>
                    </div>
                </div>

                <div
                    class="bg-slate-50 rounded-2xl border border-slate-200 p-3.5 space-y-2.5 text-xs text-slate-700"
                >
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Petugas Jaga:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            selectedPetugasPengecekanNama
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Interval Jam Patroli:</span
                        >
                        <span
                            class="font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded"
                            >{{ currentActiveInterval }}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Kondisi Cuaca:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            formPengecekan.cuaca || "-"
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Pengecekan Umum:</span
                        >
                        <span
                            class="font-bold"
                            :class="
                                countTemuanUmum > 0
                                    ? 'text-amber-700'
                                    : 'text-emerald-700'
                            "
                        >
                            {{
                                countTemuanUmum > 0
                                    ? `${countTemuanUmum} Catatan Khusus`
                                    : "Semua Kondusif (Aman)"
                            }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Lingkungan Kerja (21 Area):</span
                        >
                        <span
                            class="font-bold"
                            :class="
                                countTemuanLingkungan > 0
                                    ? 'text-rose-700'
                                    : 'text-emerald-700'
                            "
                        >
                            {{
                                countTemuanLingkungan > 0
                                    ? `${countTemuanLingkungan} Area Perlu Perhatian`
                                    : "Seluruh 21 Area Aman"
                            }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Tempat Posisi Pengecekan:</span
                        >
                        <span
                            class="font-bold text-slate-900 truncate max-w-[200px] text-right"
                            >{{ formPengecekan.lokasi_nama || "-" }}</span
                        >
                    </div>
                    <div class="pb-1.5 border-b border-slate-200 space-y-1">
                        <span class="text-slate-500 font-medium block"
                            >Catatan Tambahan:</span
                        >
                        <p
                            class="font-semibold text-slate-800 bg-white p-2 rounded-xl border border-slate-200 text-[11.5px] leading-relaxed"
                        >
                            {{ formPengecekan.catatan_tambahan }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium"
                            >Titik GPS Koordinat:</span
                        >
                        <span
                            class="font-mono font-bold text-blue-700 text-[11px]"
                        >
                            {{ formPengecekan.latitude }},
                            {{ formPengecekan.longitude }}
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 text-center">
                    Data akan langsung dicatat secara permanen ke Buku Mutasi
                    Keamanan SPPG.
                </p>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmKirimPengecekanModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitPengecekan"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <Send
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting ? "Mengirim..." : "Ya, Kirim Laporan"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 5: KONFIRMASI KIRIM LAPORAN KELUAR JAGA -->
        <div
            v-if="showConfirmKirimKeluarModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3"
                >
                    <div
                        class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200"
                    >
                        <LogOut class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Keluar Penjagaan
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Izin dinas luar / keluar pos satpam
                        </p>
                    </div>
                </div>

                <div
                    class="bg-slate-50 rounded-2xl border border-slate-200 p-3.5 space-y-2.5 text-xs text-slate-700"
                >
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Petugas Jaga:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            selectedPetugasKeluarNama
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Tempat Tujuan Ijin:</span
                        >
                        <span class="font-bold text-amber-900">{{
                            formKeluarJaga.lokasi_tujuan
                        }}</span>
                    </div>
                    <div class="pb-1.5 border-b border-slate-200 space-y-1">
                        <span class="text-slate-500 font-medium block"
                            >Keperluan Ijin Keluar:</span
                        >
                        <p
                            class="font-semibold text-slate-800 bg-white p-2 rounded-xl border border-slate-200 text-[11.5px] leading-relaxed"
                        >
                            {{ formKeluarJaga.keperluan }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-medium"
                            >Titik Lokasi Pengecekan Terakhir (GPS):</span
                        >
                        <span
                            class="font-mono font-bold text-amber-700 text-[11px]"
                        >
                            {{ formKeluarJaga.latitude }},
                            {{ formKeluarJaga.longitude }}
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 text-center">
                    Waktu keluar akan tercatat otomatis realtime WITA pada Buku
                    Mutasi Keamanan.
                </p>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmKirimKeluarModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitKeluarJaga"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <LogOut
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting ? "Menyimpan..." : "Ya, Kirim Laporan"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: KONFIRMASI KIRIM LAPORAN SEDANG DILUAR -->
        <div
            v-if="showConfirmSedangDiluarModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3"
                >
                    <div
                        class="h-11 w-11 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0 border border-sky-200"
                    >
                        <MapPin class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Laporan Sedang Diluar
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Pastikan data titik lokasi pengecekan terakhir sudah
                            sesuai.
                        </p>
                    </div>
                </div>

                <div
                    class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5 text-xs text-slate-700"
                >
                    <div
                        class="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Nama Petugas Keamanan:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            selectedPetugasSedangDiluarNama
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Tempat Posisi Pengecekan:</span
                        >
                        <span
                            class="font-bold text-slate-900 truncate max-w-[200px] text-right"
                            >{{ formSedangDiluar.lokasi_nama || "-" }}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Waktu Realtime WITA:</span
                        >
                        <span class="font-bold font-mono text-sky-800"
                            >{{ currentTimeWita }} WITA</span
                        >
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium"
                            >Titik Lokasi Pengecekan Terakhir:</span
                        >
                        <span
                            class="font-mono font-bold text-sky-700 text-[11px]"
                        >
                            {{ formSedangDiluar.latitude }},
                            {{ formSedangDiluar.longitude }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmSedangDiluarModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitSedangDiluar"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <MapPin
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting ? "Menyimpan..." : "Ya, Kirim Laporan"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 6: KONFIRMASI KIRIM LAPORAN KEMBALI JAGA -->
        <div
            v-if="showConfirmKembaliJagaModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3"
                >
                    <div
                        class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200"
                    >
                        <LogIn class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Laporan Kembali Jaga
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Pastikan data kepulangan dan koordinat lokasi sudah
                            sesuai.
                        </p>
                    </div>
                </div>

                <div
                    class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5 text-xs"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium"
                            >Nama Petugas Jaga:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            petugasKeamanan.find(
                                (p) => p.id === formKembaliJaga.petugas_id,
                            )?.nama || "-"
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium"
                            >Tempat Posisi Pengecekan:</span
                        >
                        <span
                            class="font-bold text-slate-900 truncate max-w-[200px] text-right"
                            >{{ formKembaliJaga.lokasi_nama || "-" }}</span
                        >
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium"
                            >Waktu Realtime WITA:</span
                        >
                        <span class="font-bold font-mono text-emerald-800"
                            >{{ currentTimeWita }} WITA</span
                        >
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium"
                            >Titik Lokasi Pengecekan Terakhir:</span
                        >
                        <span
                            class="font-mono font-bold text-emerald-700 text-[11px]"
                        >
                            {{ formKembaliJaga.latitude }},
                            {{ formKembaliJaga.longitude }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmKembaliJagaModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitKembaliJaga"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <LogIn
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting ? "Menyimpan..." : "Ya, Kirim Kembali"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 6: KONFIRMASI PETUGAS KEMBALI KE POS -->
        <div
            v-if="showConfirmKembaliModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-sm w-full p-5 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200"
                    >
                        <LogIn class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Kembali
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Tiba kembali di pos penjagaan
                        </p>
                    </div>
                </div>

                <div
                    class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-700 space-y-2"
                >
                    <p>
                        Konfirmasikan kepulangan petugas
                        <strong>{{ pendingKembaliIzin?.petugas_nama }}</strong>
                        ke pos penjagaan SPPG?
                    </p>
                    <div
                        class="text-[11px] font-mono text-emerald-800 bg-emerald-50 p-2 rounded-xl border border-emerald-200"
                    >
                        📍 Titik GPS Kembali: {{ formKeluarJaga.lat_kembali }},
                        {{ formKeluarJaga.lng_kembali }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmKembaliModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitKembali"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <Check
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>Ya, Simpan Kembali</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 7: KONFIRMASI KIRIM LAPORAN SERAH TERIMA -->
        <div
            v-if="showConfirmSerahTerimaModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95"
            >
                <div
                    class="flex items-center gap-3 border-b border-slate-100 pb-3"
                >
                    <div
                        class="h-11 w-11 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-200"
                    >
                        <ArrowLeftRight class="h-6 w-6" />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-black text-slate-900 leading-tight"
                        >
                            Konfirmasi Laporan Serah Terima
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Mutasi giliran dinas jaga pos keamanan
                        </p>
                    </div>
                </div>

                <div
                    class="bg-slate-50 rounded-2xl border border-slate-200 p-3.5 space-y-2.5 text-xs text-slate-700"
                >
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Petugas Keamanan:</span
                        >
                        <span class="font-bold text-slate-900">{{
                            selectedPetugasSerahTerimaNama
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Status Serah Terima:</span
                        >
                        <span
                            class="px-2 py-0.5 rounded-md font-black text-xs uppercase tracking-wide"
                            :class="
                                formSerahTerima.jenis === 'Masuk Jaga'
                                    ? 'bg-emerald-100 text-emerald-800'
                                    : 'bg-slate-700 text-white'
                            "
                        >
                            {{ formSerahTerima.jenis }}
                        </span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Waktu Realtime:</span
                        >
                        <span class="font-mono font-bold text-indigo-800">{{
                            liveTimeWita
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between pb-1.5 border-b border-slate-200"
                    >
                        <span class="text-slate-500 font-medium"
                            >Posisi / Tempat:</span
                        >
                        <span
                            class="font-bold text-slate-900 truncate max-w-[200px] text-right"
                            >{{ formSerahTerima.lokasi_nama || "-" }}</span
                        >
                    </div>
                    <div
                        v-if="formSerahTerima.catatan"
                        class="pb-1.5 border-b border-slate-200 space-y-1"
                    >
                        <span class="text-slate-500 font-medium block"
                            >Catatan / Inventaris:</span
                        >
                        <p
                            class="font-semibold text-slate-800 bg-white p-2 rounded-xl border border-slate-200 text-[11.5px] leading-relaxed"
                        >
                            {{ formSerahTerima.catatan }}
                        </p>
                    </div>
                    <div
                        v-if="
                            formSerahTerima.latitude &&
                            formSerahTerima.longitude
                        "
                        class="flex items-center justify-between"
                    >
                        <span class="text-slate-500 font-medium"
                            >Titik GPS Koordinat:</span
                        >
                        <span
                            class="font-mono font-bold text-indigo-700 text-[11px]"
                        >
                            {{ formSerahTerima.latitude }},
                            {{ formSerahTerima.longitude }}
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 text-center">
                    Laporan akan tersimpan dan langsung terlihat di layar
                    monitoring dashboard.
                </p>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button
                        type="button"
                        @click="showConfirmSerahTerimaModal = false"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="executeSubmitSerahTerima"
                        :disabled="isSubmitting"
                        class="px-3.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                    >
                        <Send
                            class="h-3.5 w-3.5"
                            :class="isSubmitting ? 'animate-spin' : ''"
                        />
                        <span>{{
                            isSubmitting ? "Menyimpan..." : "Ya, Kirim Laporan"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 8: PETUNJUK PENGISIAN FORMULIR MUTASI KEAMANAN (5 FITUR) -->
        <div
            v-if="showPetunjukModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-3.5 sm:p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in"
        >
            <div
                class="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 animate-in zoom-in-95 max-h-[90vh] flex flex-col"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-sky-100 text-sky-800 flex items-center justify-center shrink-0 border border-sky-200"
                        >
                            <BookOpen class="h-5 w-5 sm:h-6 sm:w-6" />
                        </div>
                        <div>
                            <h3
                                class="text-sm sm:text-base font-black text-slate-900 leading-tight"
                            >
                                Petunjuk Pengisian Formulir
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500">
                                Panduan 5 fitur pelaporan mutasi keamanan SPPG
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showPetunjukModal = false"
                        class="h-8 w-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer shrink-0"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Modal Body (List 5 Fitur Pelaporan) -->
                <div class="space-y-3 overflow-y-auto pr-1 flex-1 text-xs">
                    <!-- 1. Pengecekan -->
                    <div
                        class="p-3.5 bg-slate-50 hover:bg-sky-50/50 rounded-2xl border border-slate-200 transition-colors space-y-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-lg bg-sky-600 text-white font-black text-[11px] flex items-center justify-center shrink-0"
                            >
                                1
                            </span>
                            <div
                                class="flex items-center gap-1.5 min-w-0 flex-1"
                            >
                                <Clock class="h-4 w-4 text-sky-700 shrink-0" />
                                <h4
                                    class="font-black text-slate-900 text-xs truncate"
                                >
                                    Pengecekan (Rutin Setiap Jam)
                                </h4>
                            </div>
                        </div>
                        <p
                            class="text-slate-600 leading-relaxed pl-8 text-[11.5px]"
                        >
                            Dilakukan
                            <strong>secara rutin setiap jamnya</strong> selama
                            masa dinas jaga. Petugas melaporkan kondisi cuaca,
                            pemeriksaan situasi umum, kondisi 21 area lingkungan
                            kerja SPPG, dan merekam titik koordinat GPS terkini.
                        </p>
                    </div>

                    <!-- 2. Serah Terima -->
                    <div
                        class="p-3.5 bg-slate-50 hover:bg-indigo-50/50 rounded-2xl border border-slate-200 transition-colors space-y-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-lg bg-indigo-600 text-white font-black text-[11px] flex items-center justify-center shrink-0"
                            >
                                2
                            </span>
                            <div
                                class="flex items-center gap-1.5 min-w-0 flex-1"
                            >
                                <ArrowLeftRight
                                    class="h-4 w-4 text-indigo-700 shrink-0"
                                />
                                <h4
                                    class="font-black text-slate-900 text-xs truncate"
                                >
                                    Serah Terima (Mulai & Selesai Jaga)
                                </h4>
                            </div>
                        </div>
                        <p
                            class="text-slate-600 leading-relaxed pl-8 text-[11.5px]"
                        >
                            Diisi saat petugas
                            <strong>akan selesai bertugas (Lepas Jaga)</strong>
                            dan
                            <strong>akan mulai dinas jaga (Masuk Jaga)</strong>
                            sesuai jadwal kerja, guna mendokumentasikan serah
                            terima pos dan situasi operasional antar shift.
                        </p>
                    </div>

                    <!-- 3. Keluar Jaga -->
                    <div
                        class="p-3.5 bg-slate-50 hover:bg-amber-50/50 rounded-2xl border border-slate-200 transition-colors space-y-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-lg bg-amber-600 text-white font-black text-[11px] flex items-center justify-center shrink-0"
                            >
                                3
                            </span>
                            <div
                                class="flex items-center gap-1.5 min-w-0 flex-1"
                            >
                                <LogOut
                                    class="h-4 w-4 text-amber-700 shrink-0"
                                />
                                <h4
                                    class="font-black text-slate-900 text-xs truncate"
                                >
                                    Keluar Jaga (Izin Keluar Pos)
                                </h4>
                            </div>
                        </div>
                        <p
                            class="text-slate-600 leading-relaxed pl-8 text-[11.5px]"
                        >
                            Diisi untuk
                            <strong
                                >izin keluar sementara dari tugas menjaga
                                SPPG</strong
                            >
                            karena hal-hal tertentu (dinas luar, patroli
                            perimeter, atau keperluan mendesak). Melaporkan
                            tempat tujuan, keperluan, dan titik lokasi koordinat
                            awal.
                        </p>
                    </div>

                    <!-- 4. Sedang Diluar -->
                    <div
                        class="p-3.5 bg-slate-50 hover:bg-teal-50/50 rounded-2xl border border-slate-200 transition-colors space-y-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-lg bg-teal-600 text-white font-black text-[11px] flex items-center justify-center shrink-0"
                            >
                                4
                            </span>
                            <div
                                class="flex items-center gap-1.5 min-w-0 flex-1"
                            >
                                <MapPin
                                    class="h-4 w-4 text-teal-700 shrink-0"
                                />
                                <h4
                                    class="font-black text-slate-900 text-xs truncate"
                                >
                                    Sedang Diluar (Lapor Tiba di Lokasi Izin)
                                </h4>
                            </div>
                        </div>
                        <p
                            class="text-slate-600 leading-relaxed pl-8 text-[11.5px]"
                        >
                            Diisi saat petugas berada di luar untuk
                            <strong
                                >melaporkan bahwa sudah tiba di lokasi / tempat
                                izin</strong
                            >, mencatat tempat posisi terkini, serta memperbarui
                            titik lokasi koordinat GPS pengecekan terakhir.
                        </p>
                    </div>

                    <!-- 5. Kembali Jaga -->
                    <div
                        class="p-3.5 bg-slate-50 hover:bg-emerald-50/50 rounded-2xl border border-slate-200 transition-colors space-y-1.5"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-lg bg-emerald-600 text-white font-black text-[11px] flex items-center justify-center shrink-0"
                            >
                                5
                            </span>
                            <div
                                class="flex items-center gap-1.5 min-w-0 flex-1"
                            >
                                <LogIn
                                    class="h-4 w-4 text-emerald-700 shrink-0"
                                />
                                <h4
                                    class="font-black text-slate-900 text-xs truncate"
                                >
                                    Kembali Jaga (Masuk Kembali ke SPPG)
                                </h4>
                            </div>
                        </div>
                        <p
                            class="text-slate-600 leading-relaxed pl-8 text-[11.5px]"
                        >
                            Diisi saat petugas
                            <strong
                                >telah kembali dan masuk bertugas kembali di pos
                                SPPG</strong
                            >
                            seusai izin dari luar, untuk mengonfirmasi bahwa
                            sudah kembali siaga di pos penjagaan.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-2 shrink-0">
                    <button
                        type="button"
                        @click="showPetunjukModal = false"
                        class="w-full py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span>Saya Mengerti</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
