<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldCheck,
    ShieldAlert,
    Shield,
    Clock,
    Calendar,
    Users,
    MapPin,
    Navigation,
    Save,
    Printer,
    CheckCircle2,
    AlertTriangle,
    RefreshCw,
    Map,
    Check,
    X,
    LogOut,
    LogIn,
    FileText,
    Trash2,
    UserCheck,
    ArrowRightLeft,
    QrCode,
    Copy,
    ExternalLink,
    Smartphone,
    Link as LinkIcon,
    Sun,
    Flame,
    Cloud,
    Wind,
    Snowflake,
    CloudRain,
    CloudLightning,
    Edit3,
    Plus,
    Eye,
    Radio,
    Download,
    Truck,
} from 'lucide-vue-next';
import { exportKeamananExcel } from '@/Services/exportKeamananExcelHelper';
import KeamananMapModal from './Partials/KeamananMapModal.vue';
import KeamananPrintModal from './Partials/KeamananPrintModal.vue';
import KeamananIzinModal from './Partials/KeamananIzinModal.vue';
import KeamananQrModal from './Partials/KeamananQrModal.vue';
import KeamananChecklistModal from './Partials/KeamananChecklistModal.vue';
import KeamananSerahTerimaModal from './Partials/KeamananSerahTerimaModal.vue';

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    selectedTanggal: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
    hariPertama: {
        type: String,
        default: '',
    },
    hariKedua: {
        type: String,
        default: '',
    },
    labelRentangOperasional: {
        type: String,
        default: '',
    },
    selectedShift: {
        type: String,
        default: '24 Jam',
    },
    mutasiAktif: {
        type: Object,
        default: null,
    },
    petugasKeamanan: {
        type: Array,
        default: () => [],
    },
    serahTerimasHariIni: {
        type: Array,
        default: () => [],
    },
    formPublicUrl: {
        type: String,
        default: null,
    },
    monitoringPublicUrl: {
        type: String,
        default: null,
    },
    tokenKeamanan: {
        type: String,
        default: null,
    },
    tokenMonitoring: {
        type: String,
        default: null,
    },
    riwayatList: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    pejabatSppg: {
        type: Object,
        default: () => ({}),
    },
});

// Tab Aktif: 'jurnal' (24 Jam Jurnal Patroli) | 'izin' (Izin Keluar Penjagaan) | 'riwayat' (Arsip Mutasi)
const activeTab = ref('jurnal');

// Filter & Pilihan Tanggal
const filterTanggal = ref(props.selectedTanggal);
const filterShift = ref(props.selectedShift);

// 24 Interval Jam Patroli Siklus 24 Jam (06.00 WITA Hari Pertama s/d 06.00 WITA Hari Berikutnya)
const FIXED_24_INTERVALS = [
    { jam: '06.00 - 07.00 WITA', hour: 6, is_next_day: false },
    { jam: '07.00 - 08.00 WITA', hour: 7, is_next_day: false },
    { jam: '08.00 - 09.00 WITA', hour: 8, is_next_day: false },
    { jam: '09.00 - 10.00 WITA', hour: 9, is_next_day: false },
    { jam: '10.00 - 11.00 WITA', hour: 10, is_next_day: false },
    { jam: '11.00 - 12.00 WITA', hour: 11, is_next_day: false },
    { jam: '12.00 - 13.00 WITA', hour: 12, is_next_day: false },
    { jam: '13.00 - 14.00 WITA', hour: 13, is_next_day: false },
    { jam: '14.00 - 15.00 WITA', hour: 14, is_next_day: false },
    { jam: '15.00 - 16.00 WITA', hour: 15, is_next_day: false },
    { jam: '16.00 - 17.00 WITA', hour: 16, is_next_day: false },
    { jam: '17.00 - 18.00 WITA', hour: 17, is_next_day: false },
    { jam: '18.00 - 19.00 WITA', hour: 18, is_next_day: false },
    { jam: '19.00 - 20.00 WITA', hour: 19, is_next_day: false },
    { jam: '20.00 - 21.00 WITA', hour: 20, is_next_day: false },
    { jam: '21.00 - 22.00 WITA', hour: 21, is_next_day: false },
    { jam: '22.00 - 23.00 WITA', hour: 22, is_next_day: false },
    { jam: '23.00 - 00.00 WITA', hour: 23, is_next_day: false },
    { jam: '00.00 - 01.00 WITA', hour: 0, is_next_day: true },
    { jam: '01.00 - 02.00 WITA', hour: 1, is_next_day: true },
    { jam: '02.00 - 03.00 WITA', hour: 2, is_next_day: true },
    { jam: '03.00 - 04.00 WITA', hour: 3, is_next_day: true },
    { jam: '04.00 - 05.00 WITA', hour: 4, is_next_day: true },
    { jam: '05.00 - 06.00 WITA', hour: 5, is_next_day: true },
];

function formatIndoDate(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
}

const formattedHariPertama = computed(() => {
    return formatIndoDate(props.hariPertama || props.selectedTanggal || form.value.tanggal);
});

const formattedHariKedua = computed(() => {
    if (props.hariKedua) return formatIndoDate(props.hariKedua);
    try {
        const base = props.hariPertama || props.selectedTanggal || form.value.tanggal;
        const d = new Date(base + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return '';
    }
});

// State Form Mutasi Harian
const form = ref({
    tanggal: props.selectedTanggal,
    shift: props.selectedShift,
    petugas_id: props.mutasiAktif?.petugas_id || (props.petugasKeamanan[0]?.id || null),
    petugas_nama: props.mutasiAktif?.petugas_nama || (props.petugasKeamanan[0]?.nama || ''),
    status_keseluruhan: props.mutasiAktif?.status_keseluruhan || 'Aman',
    catatan_serah_terima: props.mutasiAktif?.catatan_serah_terima || '',
    details: [],
});

const isSaving = ref(false);
const saveSuccessMsg = ref('');

// Inisialisasi daftar 24 Jam Patroli Siklus 06.00 s/d 06.00 WITA
function init24JamDetails() {
    const existingDetails = props.mutasiAktif?.details || [];
    const detailsGrouped = {};

    existingDetails.forEach((d) => {
        if (d.jam) {
            const key = d.jam.trim();
            const groupKey = `${d.is_next_day ? 'next_' : 'cur_'}${key}`;
            if (!detailsGrouped[groupKey]) detailsGrouped[groupKey] = [];
            detailsGrouped[groupKey].push(d);

            if (!detailsGrouped[key]) detailsGrouped[key] = [];
            detailsGrouped[key].push(d);
        }
    });

    form.value.details = FIXED_24_INTERVALS.map((intObj) => {
        const intervalStr = intObj.jam;
        const hourPrefix = String(intObj.hour).padStart(2, '0');
        const legacyFormat = `${hourPrefix}:00`;

        const groupKey = `${intObj.is_next_day ? 'next_' : 'cur_'}${intervalStr}`;
        const legacyGroupKey = `${intObj.is_next_day ? 'next_' : 'cur_'}${legacyFormat}`;

        const list = detailsGrouped[groupKey] || detailsGrouped[legacyGroupKey] || detailsGrouped[intervalStr] || detailsGrouped[legacyFormat] || [];

        // Filter jika list bercampur antara Hari Pertama dan Hari Berikutnya
        const filteredList = list.filter((item) => {
            if (typeof item.is_next_day !== 'undefined') {
                return !!item.is_next_day === intObj.is_next_day;
            }
            return true;
        });

        const activeList = filteredList.length > 0 ? filteredList : list;

        // Urutkan laporan berdasarkan waktu_cek atau id secara kronologis
        activeList.sort((a, b) => (a.waktu_cek || '').localeCompare(b.waktu_cek || '') || (a.id - b.id));

        const reports = activeList.map((item) => {
            const isRep = !!(item.waktu_cek || item.data_pengecekan || item.latitude || (item.petugas_id && item.uraian_kejadian));
            return {
                id: item.id || null,
                jam: intervalStr,
                is_next_day: intObj.is_next_day,
                hour: intObj.hour,
                is_reported: isRep,
                waktu_cek: item.waktu_cek || '',
                petugas_id: item.petugas_id || null,
                petugas_nama: item.petugas_nama || item.petugas?.nama || '',
                kondisi: item.kondisi || (isRep ? 'Aman' : ''),
                uraian_kejadian: item.uraian_kejadian || '',
                data_pengecekan: item.data_pengecekan || null,
                latitude: item.latitude ? Number(item.latitude) : null,
                longitude: item.longitude ? Number(item.longitude) : null,
                lokasi_nama: item.lokasi_nama || 'Pos Penjagaan SPPG',
            };
        });

        const isReported = reports.length > 0;
        const primary = reports[reports.length - 1] || null;

        return {
            jam: intervalStr,
            is_next_day: intObj.is_next_day,
            hour: intObj.hour,
            is_reported: isReported,
            report_count: reports.length,
            reports: reports,
            // Fallback fields jika dipanggil langsung:
            id: primary?.id || null,
            waktu_cek: primary?.waktu_cek || '',
            petugas_id: primary?.petugas_id || null,
            petugas_nama: primary?.petugas_nama || '',
            kondisi: reports.some((r) => r.kondisi === 'Tidak Aman') ? 'Tidak Aman' : (primary?.kondisi || (isReported ? 'Aman' : '')),
            uraian_kejadian: primary?.uraian_kejadian || '',
            data_pengecekan: primary?.data_pengecekan || null,
            latitude: primary?.latitude || null,
            longitude: primary?.longitude || null,
            lokasi_nama: primary?.lokasi_nama || 'Pos Penjagaan SPPG',
        };
    });
}

// Bantuan Badge Cuaca dengan Ikon
function getCuacaBadge(cuaca) {
    switch (cuaca) {
        case 'Cerah': return { label: 'Cerah', icon: Sun, color: 'text-amber-700 bg-amber-50 border-amber-200' };
        case 'Panas': return { label: 'Panas', icon: Flame, color: 'text-orange-700 bg-orange-50 border-orange-200' };
        case 'Berawan': return { label: 'Berawan', icon: Cloud, color: 'text-slate-700 bg-slate-100 border-slate-200' };
        case 'Berangin': return { label: 'Berangin', icon: Wind, color: 'text-teal-800 bg-teal-50 border-teal-200' };
        case 'Mendung': return { label: 'Mendung', icon: Cloud, color: 'text-slate-800 bg-slate-200 border-slate-300' };
        case 'Dingin': return { label: 'Dingin', icon: Snowflake, color: 'text-sky-700 bg-sky-50 border-sky-200' };
        case 'Hujan': return { label: 'Hujan', icon: CloudRain, color: 'text-blue-700 bg-blue-50 border-blue-200' };
        case 'Badai': return { label: 'Badai', icon: CloudLightning, color: 'text-purple-800 bg-purple-50 border-purple-200' };
        default: return { label: cuaca || 'Cerah', icon: Sun, color: 'text-amber-700 bg-amber-50 border-amber-200' };
    }
}

// Ekstrak insight dan ringkasan dari JSON data_pengecekan
function getDetailInsights(item) {
    if (!item.is_reported) return null;
    const dp = item.data_pengecekan;
    if (!dp) {
        return {
            cuaca: null,
            cuacaBadge: null,
            allAreasSafe: item.kondisi === 'Aman',
            unsafeAreas: [],
            highlights: [],
            catatanTambahan: '',
        };
    }

    const cuaca = dp.cuaca || 'Cerah';
    const cuacaBadge = getCuacaBadge(cuaca);
    const unsafeAreas = (dp.lingkungan_kerja || [])
        .filter((a) => a.status === 'Tidak Aman')
        .map((a) => a.label);

    const highlights = [];
    const routineActivities = [];
    (dp.pengecekan_umum || []).forEach((u) => {
        const isRoutine =
            u.key === 'kunjungan_tamu' ||
            u.key === 'kedatangan_supplier' ||
            (u.label &&
                (u.label.toLowerCase().includes('tamu') ||
                    u.label.toLowerCase().includes('supplier')));

        if (u.status === 'Ada') {
            if (isRoutine) {
                routineActivities.push({
                    key: u.key,
                    label: u.label,
                    status: u.status,
                    uraian: u.uraian,
                });
            } else {
                highlights.push({
                    key: u.key,
                    label: u.label,
                    status: u.status,
                    uraian: u.uraian,
                });
            }
        } else if (u.status === 'Tidak Aman') {
            highlights.push({
                key: u.key,
                label: u.label,
                status: u.status,
                uraian: u.uraian,
            });
        }
    });

    return {
        cuaca,
        cuacaBadge,
        allAreasSafe: unsafeAreas.length === 0 && highlights.length === 0,
        unsafeAreas,
        highlights,
        routineActivities,
        catatanTambahan: dp.catatan_tambahan || '',
    };
}

function formatWaktuWita(val) {
    if (!val) return '-';
    const clean = String(val).replace(/\s*WITA/gi, '').trim();
    return clean ? `${clean} WITA` : '-';
}

// Modal Edit / Input Manual Cepat untuk Supervisor
const showManualEditModal = ref(false);
const isSavingManual = ref(false);
const isLocatingModal = ref(false);
const manualForm = ref({
    index: null,
    jam: '',
    petugas_id: null,
    petugas_nama: '',
    kondisi: 'Aman',
    cuaca: 'Cerah',
    uraian_kejadian: '',
    latitude: null,
    longitude: null,
    lokasi_nama: 'Pos Penjagaan SPPG',
});

function openManualEdit(it, idx) {
    const defaultPet = props.petugasKeamanan[0] || null;
    manualForm.value = {
        index: idx,
        jam: it.jam,
        is_next_day: it.is_next_day || false,
        petugas_id: it.petugas_id || defaultPet?.id || null,
        petugas_nama: it.petugas_nama || defaultPet?.nama || '',
        kondisi: it.kondisi || 'Aman',
        cuaca: it.data_pengecekan?.cuaca || 'Cerah',
        uraian_kejadian: it.uraian_kejadian || 'Situasi terpantau aman terkendali, area steril dan kondusif.',
        latitude: it.latitude || null,
        longitude: it.longitude || null,
        lokasi_nama: it.lokasi_nama || 'Pos Penjagaan SPPG',
    };
    showManualEditModal.value = true;
}

function fetchGpsForManual() {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung deteksi GPS.');
        return;
    }
    isLocatingModal.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            manualForm.value.latitude = Number(pos.coords.latitude.toFixed(7));
            manualForm.value.longitude = Number(pos.coords.longitude.toFixed(7));
            isLocatingModal.value = false;
        },
        (err) => {
            isLocatingModal.value = false;
            alert(`Gagal mengambil GPS: ${err.message || 'Izin akses lokasi ditolak.'}`);
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

function submitManualEdit() {
    if (!manualForm.value.petugas_id) {
        alert('Pilih nama petugas yang bertugas jaga.');
        return;
    }
    isSavingManual.value = true;
    router.post(
        route('keamanan.quick-check'),
        {
            tanggal: form.value.tanggal,
            shift: form.value.shift,
            jam: manualForm.value.jam,
            is_next_day: manualForm.value.is_next_day || false,
            petugas_id: manualForm.value.petugas_id,
            kondisi: manualForm.value.kondisi,
            uraian_kejadian: manualForm.value.uraian_kejadian,
            latitude: manualForm.value.latitude,
            longitude: manualForm.value.longitude,
            lokasi_nama: manualForm.value.lokasi_nama,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSavingManual.value = false;
                showManualEditModal.value = false;
            },
            onError: () => {
                isSavingManual.value = false;
            },
        }
    );
}

// Modal View Map Leaflet
const showMapModal = ref(false);
const activeMapItem = ref(null);
const mapModalTitle = ref('Titik Koordinat Lokasi Patroli Keamanan');
const mapTargetContext = ref('row'); // 'row' | 'izin_modal'

// Modal Checklist Rincian Pengecekan
const showChecklistModal = ref(false);
const activeChecklistDetail = ref(null);

function openChecklistModal(detail) {
    activeChecklistDetail.value = detail;
    showChecklistModal.value = true;
}

function openMapModal(item, title = 'Titik Koordinat Lokasi Patroli Keamanan', targetContext = 'row') {
    activeMapItem.value = item;
    mapModalTitle.value = title;
    mapTargetContext.value = targetContext;
    showMapModal.value = true;
}

function openIzinRouteMap(iz) {
    openMapModal(
        {
            ...iz,
            isRoute: true,
            jam: iz.waktu_keluar,
        },
        `Rute & Titik Fase Izin: ${iz.petugas_nama} (${iz.lokasi_tujuan})`
    );
}

function calculateIzinDurations(iz) {
    function parseToMinutes(tStr) {
        if (!tStr) return null;
        const clean = tStr.replace(/[^0-9:\.]/g, '').replace('.', ':');
        const parts = clean.split(':');
        if (parts.length >= 2) {
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }
        return null;
    }

    const mIzin = parseToMinutes(iz.waktu_izin || iz.waktu_keluar);
    const mKeluar = parseToMinutes(iz.waktu_keluar);
    const mKembali = parseToMinutes(iz.waktu_kembali);

    let durasiKeluarMenit = iz.durasi_keluar_menit;
    let durasiTotalMenit = iz.durasi_total_menit;

    if (!durasiKeluarMenit && mKeluar !== null && mKembali !== null) {
        let diff = mKembali - mKeluar;
        if (diff < 0) diff += 1440;
        durasiKeluarMenit = diff;
    }

    if (!durasiTotalMenit && mIzin !== null && mKembali !== null) {
        let diff = mKembali - mIzin;
        if (diff < 0) diff += 1440;
        durasiTotalMenit = diff;
    }

    function formatText(menit) {
        if (!menit || menit <= 0) return '-';
        const jam = Math.floor(menit / 60);
        const sisa = menit % 60;
        if (jam > 0 && sisa > 0) return `${jam} Jam ${sisa} Menit`;
        if (jam > 0) return `${jam} Jam`;
        return `${sisa} Menit`;
    }

    return {
        durasiKeluarText: formatText(durasiKeluarMenit),
        durasiTotalText: formatText(durasiTotalMenit),
        durasiKeluarMenit,
        durasiTotalMenit,
    };
}

const izinModalRef = ref(null);

function handleUpdateCoords({ item, latitude, longitude, lokasi_nama }) {
    if (mapTargetContext.value === 'izin_modal' && izinModalRef.value) {
        izinModalRef.value.updateCoordsFromMap({
            target: item?._targetField,
            latitude,
            longitude,
            lokasi_nama,
        });
    } else if (item) {
        item.latitude = latitude;
        item.longitude = longitude;
        if (lokasi_nama) item.lokasi_nama = lokasi_nama;
    }
}

// Handler Download Excel
const isDownloadingExcel = ref(false);

async function handleDownloadExcel() {
    isDownloadingExcel.value = true;
    try {
        await exportKeamananExcel({
            unitSppg: props.unitSppg,
            selectedTanggal: filterTanggal.value || props.selectedTanggal,
            details24Jam: form.value.details,
            serahTerimas: props.serahTerimasHariIni,
            izins: izinsHariIni.value,
            stats: props.stats,
            petugasKeamanan: props.petugasKeamanan,
            pejabatSppg: props.pejabatSppg,
        });
    } catch (err) {
        console.error('Gagal mengunduh Excel:', err);
        alert('Terjadi kesalahan saat mengunduh Excel: ' + (err.message || ''));
    } finally {
        isDownloadingExcel.value = false;
    }
}

// Modal Cetak Dokumen
const showPrintModal = ref(false);

// Modal Izin Keluar
const showIzinModal = ref(false);
const izinModalMode = ref('create'); // 'create' | 'kembali'
const selectedIzinData = ref(null);

function openCreateIzinModal() {
    izinModalMode.value = 'create';
    selectedIzinData.value = null;
    showIzinModal.value = true;
}

function openKembaliIzinModal(iz) {
    izinModalMode.value = 'kembali';
    selectedIzinData.value = iz;
    showIzinModal.value = true;
}

function openEditIzinModal(iz) {
    izinModalMode.value = 'edit';
    selectedIzinData.value = iz;
    showIzinModal.value = true;
}

// Modal Serah Terima
const showSerahTerimaModal = ref(false);
const selectedSerahTerima = ref(null);

function openEditSerahTerima(st) {
    selectedSerahTerima.value = st;
    showSerahTerimaModal.value = true;
}

function deleteSerahTerima(id) {
    if (confirm('Apakah Anda yakin ingin menghapus catatan laporan serah terima ini?')) {
        router.delete(route('keamanan.serah-terima.destroy', id), {
            preserveScroll: true,
        });
    }
}

function handleOpenMapFromIzin({ item, title, target }) {
    openMapModal(item, title, 'izin_modal');
}

function deleteIzin(id) {
    if (confirm('Apakah Anda yakin ingin menghapus catatan izin keluar ini?')) {
        router.delete(route('keamanan.izin.destroy', id), {
            preserveScroll: true,
        });
    }
}

// Perubahan Filter Tanggal / Shift
function onFilterChange() {
    router.get(
        route('keamanan.index'),
        {
            tanggal: filterTanggal.value,
            shift: filterShift.value,
        },
        { preserveState: false, preserveScroll: true }
    );
}

function onPetugasHeaderChange() {
    const pet = props.petugasKeamanan.find((p) => p.id === form.value.petugas_id);
    if (pet) {
        form.value.petugas_nama = pet.nama;
    }
}

// Simpan Formulir Mutasi ke Database
function submitForm() {
    isSaving.value = true;
    saveSuccessMsg.value = '';

    const flatDetails = [];
    form.value.details.forEach((item) => {
        if (item.reports && item.reports.length > 0) {
            item.reports.forEach((r) => {
                flatDetails.push({
                    ...r,
                    is_next_day: typeof r.is_next_day !== 'undefined' ? r.is_next_day : item.is_next_day,
                });
            });
        } else {
            flatDetails.push({
                ...item,
                is_next_day: item.is_next_day,
            });
        }
    });

    router.post(
        route('keamanan.store'),
        {
            tanggal: form.value.tanggal,
            shift: form.value.shift,
            petugas_id: form.value.petugas_id,
            petugas_nama: form.value.petugas_nama,
            status_keseluruhan: form.value.status_keseluruhan,
            catatan_serah_terima: form.value.catatan_serah_terima,
            details: flatDetails,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                saveSuccessMsg.value = 'Laporan Mutasi Keamanan 24 Jam berhasil disimpan ke database!';
                setTimeout(() => {
                    saveSuccessMsg.value = '';
                }, 4000);
            },
            onError: () => {
                isSaving.value = false;
            },
        }
    );
}

// Hapus Dokumen Mutasi
function deleteMutasi(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data laporan mutasi keamanan tanggal ini?')) {
        router.delete(route('keamanan.destroy', id), {
            preserveScroll: true,
        });
    }
}

// Computed Realtime Sinkron Form Mandiri
const totalJamCount = computed(() => form.value.details.length);
const totalReportedCount = computed(() => form.value.details.filter((d) => d.is_reported).length);
const totalAllReports = computed(() => {
    return form.value.details.reduce((acc, d) => acc + (d.reports?.length || (d.is_reported ? 1 : 0)), 0);
});
const totalAmanCount = computed(() => {
    return form.value.details.reduce((acc, d) => {
        if (!d.reports || d.reports.length === 0) {
            return acc + (d.is_reported && d.kondisi === 'Aman' ? 1 : 0);
        }
        return acc + d.reports.filter((r) => r.kondisi === 'Aman').length;
    }, 0);
});
const totalTidakAmanCount = computed(() => {
    return form.value.details.reduce((acc, d) => {
        if (!d.reports || d.reports.length === 0) {
            return acc + (d.is_reported && d.kondisi === 'Tidak Aman' ? 1 : 0);
        }
        return acc + d.reports.filter((r) => r.kondisi === 'Tidak Aman').length;
    }, 0);
});
const reportedPercent = computed(() => Math.round((totalReportedCount.value / 24) * 100));

const izinsHariIni = computed(() => props.mutasiAktif?.izins || []);
const countIzinSedangKeluar = computed(() => izinsHariIni.value.filter((i) => i.status === 'Sedang Keluar').length);

const keluarJagasHariIni = computed(() => {
    return izinsHariIni.value.filter((i) => {
        return i.status === 'Sedang Keluar' || (i.waktu_keluar && !i.keperluan?.startsWith('Kembali ke Pos Penjagaan SPPG'));
    });
});

const kembaliJagasHariIni = computed(() => {
    return izinsHariIni.value.filter((i) => {
        return i.waktu_kembali || i.status === 'Sudah Kembali' || (i.lat_kembali && i.lng_kembali);
    });
});

function getDistanceFromSppg(lat, lng) {
    if (!lat || !lng) return null;
    const sppgLat = props.unitSppg?.latitude ? Number(props.unitSppg.latitude) : null;
    const sppgLng = props.unitSppg?.longitude ? Number(props.unitSppg.longitude) : null;
    if (sppgLat === null || sppgLng === null || isNaN(sppgLat) || isNaN(sppgLng)) return null;

    const latNum = Number(lat);
    const lngNum = Number(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return null;

    const R = 6371000;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(latNum - sppgLat);
    const dLng = toRad(lngNum - sppgLng);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(sppgLat)) * Math.cos(toRad(latNum)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distanceMeter = Math.round(R * c);

    if (distanceMeter >= 1000) {
        const km = (distanceMeter / 1000).toFixed(2).replace('.', ',');
        return `${km} km`;
    }
    return `${distanceMeter} m`;
}

const formattedTanggalAktif = computed(() => {
    if (!form.value.tanggal) return '-';
    try {
        const d = new Date(form.value.tanggal + 'T00:00:00');
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch {
        return form.value.tanggal;
    }
});

// State Modal QR & Link
const showQrModal = ref(false);
const isLinkCopied = ref(false);
const isRefreshing = ref(false);

function copyPublicLink() {
    if (!props.formPublicUrl) return;
    navigator.clipboard.writeText(props.formPublicUrl).then(() => {
        isLinkCopied.value = true;
        setTimeout(() => {
            isLinkCopied.value = false;
        }, 3000);
    });
}

function openPublicForm() {
    if (props.formPublicUrl) {
        window.open(props.formPublicUrl, '_blank');
    }
}

// Handler Link Monitoring Keamanan Realtime
const isMonitoringLinkCopied = ref(false);
const showMonitoringQrModal = ref(false);

function copyMonitoringLink() {
    if (!props.monitoringPublicUrl) return;
    navigator.clipboard.writeText(props.monitoringPublicUrl).then(() => {
        isMonitoringLinkCopied.value = true;
        setTimeout(() => {
            isMonitoringLinkCopied.value = false;
        }, 3000);
    });
}

function openMonitoringPage() {
    if (props.monitoringPublicUrl) {
        window.open(props.monitoringPublicUrl, '_blank');
    }
}

function refreshData() {
    isRefreshing.value = true;
    router.reload({
        only: ['mutasiAktif', 'stats', 'riwayatList'],
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
            init24JamDetails();
        },
    });
}

function regenerateToken() {
    if (confirm('Apakah Anda yakin ingin membuat ulang link form keamanan? Link sebelumnya tidak akan dapat digunakan lagi.')) {
        router.post(route('keamanan.regenerate-token'), {}, {
            preserveScroll: true,
        });
    }
}

function regenerateMonitoringToken() {
    if (confirm('Apakah Anda yakin ingin membuat ulang link monitoring keamanan? Perangkat layar TV/pos atau pihak yang membuka link sebelumnya tidak akan menampilkan data lagi sampai link diperbarui.')) {
        router.post(route('keamanan.regenerate-monitoring-token'), {}, {
            preserveScroll: true,
        });
    }
}

// Watch data mutasi aktif dari server (termasuk update dari link mandiri HP satpam)
watch(
    () => props.mutasiAktif,
    () => {
        init24JamDetails();
    },
    { deep: true }
);

// Auto-polling ringan setiap 20 detik agar inputan satpam dari HP langsung muncul otomatis
let pollInterval = null;

onMounted(() => {
    init24JamDetails();
    pollInterval = setInterval(() => {
        router.reload({
            only: ['mutasiAktif', 'stats'],
            preserveScroll: true,
        });
    }, 20000);
});

onBeforeUnmount(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
});
</script>

<template>
    <AppLayout title="Keamanan - SIPEGE">
        <Head title="Keamanan - SIPEGE" />

        <div class="space-y-5 pb-12">
            <!-- HEADER UTAMA -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-slate-900 text-emerald-400 flex items-center justify-center shrink-0 shadow-xs border border-slate-800">
                            <ShieldCheck class="h-6 w-6 stroke-[2.2]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                                    Laporan Mutasi Keamanan SPPG
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ formattedTanggalAktif }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Laporan dan Monitoring Petugas Keamanan
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi Header: Download Excel Sesuai Tanggal -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <button
                            type="button"
                            @click="handleDownloadExcel"
                            :disabled="isDownloadingExcel"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition-colors cursor-pointer disabled:opacity-50"
                            :title="'Download Laporan Mutasi Keamanan Tanggal ' + formattedTanggalAktif + ' (Excel .xlsx)'"
                        >
                            <Download class="h-4 w-4" :class="isDownloadingExcel ? 'animate-bounce' : ''" />
                            <span>{{ isDownloadingExcel ? 'Mengunduh...' : 'Download' }}</span>
                        </button>
                    </div>
                </div>

                <!-- NOTIFIKASI SUKSES -->
                <div v-if="saveSuccessMsg" class="mt-4 p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-900 font-bold flex items-center gap-2 animate-in fade-in">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                    <span>{{ saveSuccessMsg }}</span>
                </div>

                <!-- 2 KOTAK LINK PUBLIK RESMI SPPG (FORMULIR INPUT & LIVE MONITORING) - Clean White Cards -->
                <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-3.5">
                    <!-- LINK 1: FORMULIR INPUT PETUGAS KEAMANAN (HP SATPAM) -->
                    <div v-if="props.formPublicUrl" class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2.5 hover:border-emerald-300 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                                    <Smartphone class="h-4.5 w-4.5" />
                                </div>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-black text-slate-900 leading-tight">
                                        Formulir Laporan Petugas Keamanan
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Dibuka petugas keamanan tiap 1 jam di HP
                                    </p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[9.5px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                Form Input
                            </span>
                        </div>

                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5">
                            <LinkIcon class="h-3.5 w-3.5 text-emerald-600 mr-2 shrink-0" />
                            <input
                                type="text"
                                readonly
                                :value="props.formPublicUrl"
                                class="w-full bg-transparent border-none p-0 text-[11px] font-mono font-bold text-slate-800 focus:ring-0 select-all truncate"
                            />
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <button
                                type="button"
                                @click="copyPublicLink"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-xs transition-colors"
                            >
                                <Check v-if="isLinkCopied" class="h-3.5 w-3.5" />
                                <Copy v-else class="h-3.5 w-3.5" />
                                <span>{{ isLinkCopied ? 'Tersalin!' : 'Salin Link' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="openPublicForm"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 cursor-pointer transition-colors"
                            >
                                <ExternalLink class="h-3.5 w-3.5 text-slate-500" />
                                <span>Buka Form</span>
                            </button>

                            <button
                                type="button"
                                @click="showQrModal = true"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 cursor-pointer transition-colors shadow-2xs"
                            >
                                <QrCode class="h-3.5 w-3.5 text-emerald-600" />
                                <span>QR Code</span>
                            </button>

                            <button
                                type="button"
                                @click="regenerateToken"
                                class="p-1 text-slate-400 hover:text-rose-600 text-[10px] underline cursor-pointer ml-auto transition-colors"
                                title="Buat ulang link jika ingin mengganti URL"
                            >
                                Buat Ulang
                            </button>
                        </div>
                    </div>

                    <!-- LINK 2: MONITORING KEAMANAN REALTIME (COMMAND CENTER / TV / PIMPINAN) -->
                    <div v-if="props.monitoringPublicUrl" class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2.5 hover:border-indigo-300 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center justify-center shrink-0">
                                    <Radio class="h-4.5 w-4.5 animate-pulse" />
                                </div>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-black text-slate-900 leading-tight">
                                        Monitoring Petugas Keamanan
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Laporan terkini tiap petugas &amp; peta posisi
                                    </p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[9.5px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                Live Display
                            </span>
                        </div>

                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5">
                            <LinkIcon class="h-3.5 w-3.5 text-indigo-600 mr-2 shrink-0" />
                            <input
                                type="text"
                                readonly
                                :value="props.monitoringPublicUrl"
                                class="w-full bg-transparent border-none p-0 text-[11px] font-mono font-bold text-slate-800 focus:ring-0 select-all truncate"
                            />
                        </div>

                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <button
                                type="button"
                                @click="copyMonitoringLink"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shadow-xs transition-colors"
                            >
                                <Check v-if="isMonitoringLinkCopied" class="h-3.5 w-3.5" />
                                <Copy v-else class="h-3.5 w-3.5" />
                                <span>{{ isMonitoringLinkCopied ? 'Tersalin!' : 'Salin Link Monitoring' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="openMonitoringPage"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 cursor-pointer transition-colors"
                                title="Buka layar live monitoring (cocok untuk TV pos / pimpinan)"
                            >
                                <ExternalLink class="h-3.5 w-3.5 text-indigo-600" />
                                <span>Buka Layar Monitoring</span>
                            </button>

                            <button
                                type="button"
                                @click="showMonitoringQrModal = true"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 cursor-pointer transition-colors shadow-2xs"
                            >
                                <QrCode class="h-3.5 w-3.5 text-indigo-600" />
                                <span>QR Code</span>
                            </button>

                            <button
                                type="button"
                                @click="regenerateMonitoringToken"
                                class="p-1 text-slate-400 hover:text-rose-600 text-[10px] underline cursor-pointer ml-auto transition-colors"
                                title="Buat ulang link jika ingin mengganti URL monitoring"
                            >
                                Buat Ulang
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABS & FILTER BAR -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <!-- Tab Switcher -->
                    <div class="inline-flex p-1 bg-slate-100/90 rounded-xl border border-slate-200 text-xs shadow-2xs flex-wrap gap-1">
                        <!-- 1. TAB PENGECEKAN -->
                        <button
                            type="button"
                            @click="activeTab = 'jurnal'"
                            :class="[
                                activeTab === 'jurnal'
                                    ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-200'
                                    : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-white/60',
                                'px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                        >
                            <Clock class="h-3.5 w-3.5 text-emerald-600" />
                            <span>Laporan Pengecekan</span>
                        </button>

                        <!-- 2. TAB SERAH TERIMA -->
                        <button
                            type="button"
                            @click="activeTab = 'serah_terima'"
                            :class="[
                                activeTab === 'serah_terima'
                                    ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-200'
                                    : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-white/60',
                                'px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                        >
                            <ArrowRightLeft class="h-3.5 w-3.5 text-indigo-600" />
                            <span>Laporan Serah Terima ({{ serahTerimasHariIni.length }})</span>
                        </button>

                        <!-- 3. TAB KELUAR JAGA -->
                        <button
                            type="button"
                            @click="activeTab = 'izin'"
                            :class="[
                                activeTab === 'izin'
                                    ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-200'
                                    : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-white/60',
                                'px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer relative'
                            ]"
                        >
                            <LogOut class="h-3.5 w-3.5 text-amber-600" />
                            <span>Laporan Keluar Jaga ({{ keluarJagasHariIni.length }})</span>
                            <span
                                v-if="countIzinSedangKeluar > 0"
                                class="h-2 w-2 rounded-full bg-amber-500 animate-ping absolute -top-0.5 -right-0.5"
                            ></span>
                        </button>

                        <!-- 4. TAB KEMBALI JAGA -->
                        <button
                            type="button"
                            @click="activeTab = 'kembali_jaga'"
                            :class="[
                                activeTab === 'kembali_jaga'
                                    ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-200'
                                    : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-white/60',
                                'px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                        >
                            <LogIn class="h-3.5 w-3.5 text-emerald-600" />
                            <span>Laporan Kembali Jaga ({{ kembaliJagasHariIni.length }})</span>
                        </button>

                        <!-- 5. TAB RIWAYAT -->
                        <button
                            type="button"
                            @click="activeTab = 'riwayat'"
                            :class="[
                                activeTab === 'riwayat'
                                    ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-200'
                                    : 'text-slate-600 hover:text-slate-900 font-medium hover:bg-white/60',
                                'px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 transition-all cursor-pointer'
                            ]"
                        >
                            <Calendar class="h-3.5 w-3.5 text-blue-600" />
                            <span>Riwayat ({{ riwayatList.total || riwayatList.data?.length || 0 }})</span>
                        </button>
                    </div>

                    <!-- Filter Tanggal -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1">
                            <Calendar class="h-3.5 w-3.5 text-slate-400" />
                            <span class="text-xs text-slate-500 font-medium">Tanggal Shift:</span>
                            <input
                                type="date"
                                v-model="filterTanggal"
                                @change="onFilterChange"
                                class="border-none bg-transparent text-xs font-bold text-slate-800 p-0 focus:ring-0 cursor-pointer"
                            />
                        </div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <Clock class="h-3 w-3 text-sky-600" />
                            <span>Siklus 24 Jam: {{ formattedHariPertama }} (06.00) s/d {{ formattedHariKedua }} (06.00)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 1: FORMULIR JURNAL 24 JAM FIXED -->
            <div v-show="activeTab === 'jurnal'" class="space-y-5">
                <!-- 4 KPI SUMMARY CARDS -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- 1. Progres Laporan 24 Jam -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Progres Patroli (06.00 - 06.00 WITA)</span>
                            <Clock class="h-4 w-4 text-emerald-500" />
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-900">
                            {{ totalReportedCount }} / 24 Jam
                            <span class="text-xs font-bold text-emerald-600 ml-1">({{ reportedPercent }}%)</span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: reportedPercent + '%' }"
                            ></div>
                        </div>
                        <span class="text-[10.5px] text-slate-400 mt-1 block">
                            {{ 24 - totalReportedCount }} jam menunggu giliran laporan
                        </span>
                    </div>

                    <!-- 2. Status Lingkungan Terlapor -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Status Lingkungan</span>
                            <ShieldCheck v-if="totalTidakAmanCount === 0" class="h-4 w-4 text-emerald-500" />
                            <ShieldAlert v-else class="h-4 w-4 text-rose-500" />
                        </div>
                        <div
                            class="text-base sm:text-lg font-black"
                            :class="totalReportedCount === 0 ? 'text-slate-400' : (totalTidakAmanCount === 0 ? 'text-emerald-700' : 'text-rose-700')"
                        >
                            {{ totalReportedCount === 0 ? 'Belum Ada Laporan' : (totalTidakAmanCount === 0 ? '✓ AMAN TERKENDALI' : `⚠️ ${totalTidakAmanCount} Jam Insiden`) }}
                        </div>
                        <span class="text-[10.5px] text-slate-400 mt-1 block">
                            {{ totalAmanCount }} jam aman dari {{ totalReportedCount }} jam terlapor
                        </span>
                    </div>

                    <!-- 3. Petugas Divisi Keamanan -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Petugas Keamanan</span>
                            <Users class="h-4 w-4 text-blue-500" />
                        </div>
                        <div class="text-sm sm:text-base font-bold text-slate-900 truncate">
                            {{ petugasKeamanan.map(p => p.nama).join(' & ') || '2 Petugas Jaga' }}
                        </div>
                        <span class="text-[10.5px] text-blue-700 font-semibold mt-1 block">
                            {{ petugasKeamanan.length }} Petugas Jaga Resmi Terdaftar
                        </span>
                    </div>

                    <!-- 4. Izin Keluar Hari Ini -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Izin Keluar Hari Ini</span>
                            <LogOut class="h-4 w-4 text-amber-600" />
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-900">
                            {{ izinsHariIni.length }} Izin Tercatat
                        </div>
                        <span class="text-[10.5px] mt-1 block font-semibold" :class="countIzinSedangKeluar > 0 ? 'text-amber-700' : 'text-emerald-700'">
                            {{ countIzinSedangKeluar > 0 ? `⚠️ ${countIzinSedangKeluar} Masih Di Luar Pos` : '✓ Semua Sudah di Pos' }}
                        </span>
                    </div>
                </div>

                <!-- TABEL 24 BARIS MUTLAK JURNAL PENGECEKAN TIAP JAM (HASIL FORM PETUGAS) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <!-- Sub-bar Aksi Tabel -->
                    <div class="p-3.5 sm:p-4 bg-slate-50/80 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-emerald-600" />
                                    <span>Jurnal Patroli Siklus 24 Jam (06.00 - 06.00 WITA)</span>
                                </h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ totalReportedCount }} / 24 Terisi
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Siklus patroli: <strong class="text-slate-800">{{ labelRentangOperasional || (formattedHariPertama + ' s/d ' + formattedHariKedua) }}</strong>. Menampilkan data patroli yang dikirimkan langsung oleh petugas keamanan setiap jam.
                            </p>
                        </div>

                        <!-- Status Auto-Sync & Refresh -->
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10.5px] font-semibold text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Auto-Sync Realtime (20s)</span>
                            </span>

                            <button
                                type="button"
                                @click="refreshData"
                                :disabled="isRefreshing"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 shadow-2xs cursor-pointer transition-colors"
                                title="Segarkan data inputan terbaru dari petugas"
                            >
                                <RefreshCw class="h-3.5 w-3.5 text-slate-500" :class="isRefreshing ? 'animate-spin' : ''" />
                                <span>{{ isRefreshing ? 'Menyegarkan...' : 'Segarkan Data' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Isi 24 Jam Monitoring -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse min-w-[1050px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px]">
                                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                                    <th class="py-2.5 px-3 w-48">Rentang Jam &amp; Waktu</th>
                                    <th class="py-2.5 px-3 w-48">Petugas Melapor</th>
                                    <th class="py-2.5 px-3 w-36 text-center">Cuaca &amp; Kondisi</th>
                                    <th class="py-2.5 px-3 min-w-[320px]">Rincian Pengecekan &amp; Temuan</th>
                                    <th class="py-2.5 px-3 w-48 text-center">Titik Lokasi (GPS)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr
                                    v-for="(it, idx) in form.details"
                                    :key="idx"
                                    :class="[
                                        'transition-colors',
                                        it.is_reported
                                            ? (it.kondisi === 'Tidak Aman' ? 'bg-rose-50/50 hover:bg-rose-50/80' : 'bg-white hover:bg-slate-50/80')
                                            : 'bg-slate-50/40 text-slate-400 hover:bg-slate-50/70'
                                    ]"
                                >
                                    <!-- No -->
                                    <td class="py-3 px-3 text-center font-bold text-slate-500 align-top">
                                        {{ idx + 1 }}
                                    </td>

                                    <!-- Rentang Jam & Waktu Pengecekan Realtime -->
                                    <td class="py-3 px-3 align-top">
                                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 font-mono font-bold text-slate-900 text-[11px] border border-slate-200">
                                            {{ it.jam }}
                                        </span>
                                        <!-- Badge Indikator Hari Pertama / Hari Berikutnya -->
                                        <div class="mt-1 flex items-center gap-1 flex-wrap">
                                            <span
                                                v-if="it.is_next_day"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9.5px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200"
                                            >
                                                <span>[Hari Berikutnya]</span>
                                                <span class="font-semibold text-indigo-900">{{ formattedHariKedua }}</span>
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                                            >
                                                <span>{{ formattedHariPertama }}</span>
                                            </span>
                                        </div>
                                        <div v-if="it.is_reported" class="mt-1 space-y-0.5">
                                            <div v-if="it.reports && it.reports.length > 1" class="flex items-center gap-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                                    <CheckCircle2 class="h-3 w-3 text-blue-600 shrink-0" />
                                                    <span>{{ it.reports.length }} Laporan Masuk</span>
                                                </span>
                                            </div>
                                            <div v-else class="flex items-center gap-1 text-[10.5px] font-bold text-emerald-700">
                                                <CheckCircle2 class="h-3 w-3 text-emerald-600 shrink-0" />
                                                <span>Terlapor: {{ formatWaktuWita(it.waktu_cek) }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="mt-1 flex items-center gap-1 text-[10.5px] text-slate-400 font-semibold">
                                            <Clock class="h-3 w-3 shrink-0" />
                                            <span>Menunggu Laporan</span>
                                        </div>
                                    </td>

                                    <!-- Petugas yang Mengisi Form -->
                                    <td class="py-3 px-3 align-top">
                                        <template v-if="it.is_reported">
                                            <!-- Jika 1 laporan -->
                                            <div v-if="!it.reports || it.reports.length <= 1" class="flex items-center gap-2">
                                                <div class="h-8 w-8 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center font-black text-xs shrink-0">
                                                    <Users class="h-4 w-4" />
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="font-bold text-slate-900 block text-xs truncate">{{ it.petugas_nama || 'Petugas Keamanan' }}</span>
                                                    <span class="text-[10px] text-slate-400 block truncate">Divisi Keamanan</span>
                                                </div>
                                            </div>

                                            <!-- Jika lebih dari 1 laporan dalam rentang waktu yang sama -->
                                            <div v-else class="space-y-1.5">
                                                <div
                                                    v-for="(rep, rIdx) in it.reports"
                                                    :key="rep.id || rIdx"
                                                    class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-50 border border-slate-200/80"
                                                >
                                                    <div class="h-6 w-6 rounded-md bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-[10px] shrink-0">
                                                        #{{ rIdx + 1 }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <span class="font-bold text-slate-900 block text-xs truncate">{{ rep.petugas_nama || 'Petugas' }}</span>
                                                        <span class="text-[9.5px] text-slate-500 font-mono block">Pukul {{ formatWaktuWita(rep.waktu_cek) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="text-slate-400 italic text-[11px]">- (Menunggu Jadwal)</span>
                                        </template>
                                    </td>

                                    <!-- Cuaca & Kondisi -->
                                    <td class="py-3 px-3 text-center align-top">
                                        <template v-if="it.is_reported">
                                            <!-- Jika 1 laporan -->
                                            <div v-if="!it.reports || it.reports.length <= 1" class="flex flex-col items-center gap-1">
                                                <!-- Badge Cuaca -->
                                                <div
                                                    v-if="it.data_pengecekan?.cuaca"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10.5px] font-bold border"
                                                    :class="getCuacaBadge(it.data_pengecekan.cuaca).color"
                                                >
                                                    <component :is="getCuacaBadge(it.data_pengecekan.cuaca).icon" class="h-3 w-3 shrink-0" />
                                                    <span>{{ it.data_pengecekan.cuaca }}</span>
                                                </div>

                                                <!-- Status Kondisi -->
                                                <span
                                                    v-if="it.kondisi === 'Tidak Aman'"
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-800 border border-rose-300 text-[10.5px] font-extrabold"
                                                >
                                                    <AlertTriangle class="h-3 w-3 shrink-0" />
                                                    <span>Tidak Aman</span>
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10.5px] font-extrabold"
                                                >
                                                    <Check class="h-3 w-3 shrink-0" />
                                                    <span>Aman</span>
                                                </span>
                                            </div>

                                            <!-- Jika lebih dari 1 laporan -->
                                            <div v-else class="space-y-1.5">
                                                <div
                                                    v-for="(rep, rIdx) in it.reports"
                                                    :key="rep.id || rIdx"
                                                    class="flex flex-col items-center gap-1 p-1.5 rounded-lg bg-slate-50 border border-slate-200/80"
                                                >
                                                    <div
                                                        v-if="rep.data_pengecekan?.cuaca"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold border"
                                                        :class="getCuacaBadge(rep.data_pengecekan.cuaca).color"
                                                    >
                                                        <component :is="getCuacaBadge(rep.data_pengecekan.cuaca).icon" class="h-2.5 w-2.5 shrink-0" />
                                                        <span>{{ rep.data_pengecekan.cuaca }}</span>
                                                    </div>
                                                    <span
                                                        v-if="rep.kondisi === 'Tidak Aman'"
                                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9.5px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300"
                                                    >
                                                        <AlertTriangle class="h-2.5 w-2.5 shrink-0" />
                                                        <span>Tidak Aman</span>
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9.5px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                                    >
                                                        <Check class="h-2.5 w-2.5 shrink-0" />
                                                        <span>Aman</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="text-slate-300 font-mono text-xs">-</span>
                                        </template>
                                    </td>

                                    <!-- Rincian Pengecekan & Uraian Kejadian -->
                                    <td class="py-3 px-3 align-top">
                                        <template v-if="it.is_reported">
                                            <!-- Jika 1 laporan -->
                                            <div v-if="!it.reports || it.reports.length <= 1" class="space-y-1.5">
                                                <div v-if="it.data_pengecekan" class="space-y-1.5">
                                                    <!-- Status 21 Area Lingkungan Kerja -->
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span
                                                            v-if="getDetailInsights(it)?.allAreasSafe"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10.5px] font-bold"
                                                        >
                                                            <Check class="h-3 w-3 text-emerald-600 shrink-0" />
                                                            <span>21/21 Area Kerja Aman</span>
                                                        </span>
                                                        <span
                                                            v-else
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-800 border border-rose-300 text-[10.5px] font-bold"
                                                        >
                                                            <AlertTriangle class="h-3 w-3 text-rose-600 shrink-0" />
                                                            <span>{{ getDetailInsights(it)?.unsafeAreas.length }} Area Bermasalah: {{ getDetailInsights(it)?.unsafeAreas.join(', ') }}</span>
                                                        </span>
                                                    </div>

                                                    <!-- Aktivitas Rutin (Kedatangan Supplier / Kunjungan Tamu - BUKAN Kendala) -->
                                                    <div v-if="getDetailInsights(it)?.routineActivities?.length > 0" class="flex flex-wrap gap-1">
                                                        <span
                                                            v-for="(r, rIdx) in getDetailInsights(it)?.routineActivities"
                                                            :key="'rout-' + rIdx"
                                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200"
                                                        >
                                                            <Truck v-if="r.key === 'kedatangan_supplier' || r.label?.toLowerCase().includes('supplier')" class="h-3 w-3 text-sky-600" />
                                                            <Users v-else class="h-3 w-3 text-sky-600" />
                                                            <span>{{ r.label }}: {{ r.uraian || 'Tercatat' }}</span>
                                                        </span>
                                                    </div>

                                                    <!-- Highlights Pengecekan Umum Kendala / Perhatian Khusus -->
                                                    <div v-if="getDetailInsights(it)?.highlights.length > 0" class="flex flex-wrap gap-1">
                                                        <span
                                                            v-for="(h, hIdx) in getDetailInsights(it)?.highlights"
                                                            :key="hIdx"
                                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-300"
                                                        >
                                                            <AlertTriangle class="h-3 w-3" />
                                                            <span>{{ h.label }}: {{ h.status }} {{ h.uraian ? '(' + h.uraian + ')' : '' }}</span>
                                                        </span>
                                                    </div>

                                                    <!-- Catatan Tambahan Petugas -->
                                                    <p
                                                        v-if="getDetailInsights(it)?.catatanTambahan"
                                                        class="text-[11px] text-slate-700 bg-amber-50/70 p-1.5 rounded-lg border border-amber-200/80 leading-snug"
                                                    >
                                                        📝 <strong>Catatan:</strong> {{ getDetailInsights(it)?.catatanTambahan }}
                                                    </p>

                                                    <!-- Tombol Buka Modal Checklist Lengkap -->
                                                    <div>
                                                        <button
                                                            type="button"
                                                            @click="openChecklistModal(it)"
                                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[10.5px] font-bold cursor-pointer transition-colors shadow-2xs"
                                                        >
                                                            <FileText class="h-3 w-3" />
                                                            <span>Rincian Checklist Form</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Fallback jika hanya teks uraian -->
                                                <div v-else>
                                                    <p class="text-xs text-slate-800 font-medium">
                                                        {{ it.uraian_kejadian || 'Situasi aman terkendali, area steril dan terpantau normal.' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Jika lebih dari 1 laporan dalam rentang waktu yang sama -->
                                            <div v-else class="space-y-2">
                                                <div
                                                    v-for="(rep, rIdx) in it.reports"
                                                    :key="rep.id || rIdx"
                                                    class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5"
                                                >
                                                    <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-1">
                                                        <span class="text-[10.5px] font-extrabold text-blue-900 flex items-center gap-1.5">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                                            <span>Laporan #{{ rIdx + 1 }} • {{ rep.petugas_nama || 'Petugas' }} (Pukul {{ formatWaktuWita(rep.waktu_cek) }})</span>
                                                        </span>
                                                        <button
                                                            type="button"
                                                            @click="openChecklistModal(rep)"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[10px] font-bold cursor-pointer transition-colors shadow-2xs shrink-0"
                                                        >
                                                            <FileText class="h-3 w-3" />
                                                            <span>Checklist</span>
                                                        </button>
                                                    </div>

                                                    <div v-if="rep.data_pengecekan" class="space-y-1">
                                                        <!-- Area Status -->
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span
                                                                v-if="getDetailInsights(rep)?.allAreasSafe"
                                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-bold"
                                                            >
                                                                <Check class="h-2.5 w-2.5 text-emerald-600 shrink-0" />
                                                                <span>21/21 Area Aman</span>
                                                            </span>
                                                            <span
                                                                v-else
                                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rose-50 text-rose-800 border border-rose-300 text-[10px] font-bold"
                                                            >
                                                                <AlertTriangle class="h-2.5 w-2.5 text-rose-600 shrink-0" />
                                                                <span>{{ getDetailInsights(rep)?.unsafeAreas.length }} Masalah: {{ getDetailInsights(rep)?.unsafeAreas.join(', ') }}</span>
                                                            </span>
                                                        </div>

                                                        <!-- Highlights -->
                                                        <div v-if="getDetailInsights(rep)?.highlights.length > 0" class="flex flex-wrap gap-1">
                                                            <span
                                                                v-for="(h, hIdx) in getDetailInsights(rep)?.highlights"
                                                                :key="hIdx"
                                                                class="px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                                            >
                                                                ⚠️ {{ h.label }}: {{ h.status }} {{ h.uraian ? '(' + h.uraian + ')' : '' }}
                                                            </span>
                                                        </div>

                                                        <!-- Catatan -->
                                                        <p
                                                            v-if="getDetailInsights(rep)?.catatanTambahan"
                                                            class="text-[10.5px] text-slate-700 bg-amber-50/70 p-1.5 rounded-lg border border-amber-200/80 leading-snug"
                                                        >
                                                            📝 <strong>Catatan:</strong> {{ getDetailInsights(rep)?.catatanTambahan }}
                                                        </p>
                                                    </div>
                                                    <div v-else class="text-xs text-slate-700">
                                                        {{ rep.uraian_kejadian || 'Situasi aman terkendali, area steril dan terpantau normal.' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Jika Jam Ini Belum Dilaporkan Petugas -->
                                        <template v-else>
                                            <span class="text-slate-400 italic text-xs flex items-center gap-1.5">
                                                <Clock class="h-3.5 w-3.5 shrink-0 opacity-50" />
                                                <span>Belum ada laporan dari formulir mandiri keamanan untuk rentang waktu ini.</span>
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Titik Koordinat Lokasi -->
                                    <td class="py-3 px-3 text-center align-top">
                                        <template v-if="it.is_reported">
                                            <!-- Jika 1 laporan -->
                                            <div v-if="!it.reports || it.reports.length <= 1">
                                                <template v-if="it.latitude && it.longitude">
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span class="text-[11px] font-bold text-slate-900 truncate max-w-[170px]" :title="it.lokasi_nama">
                                                            {{ it.lokasi_nama || 'Pos Penjagaan SPPG' }}
                                                        </span>
                                                        <div class="font-mono text-[10px] text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 font-bold truncate max-w-[150px]">
                                                            📍 {{ Number(it.latitude).toFixed(5) }}, {{ Number(it.longitude).toFixed(5) }}
                                                        </div>
                                                        <button
                                                            type="button"
                                                            @click="openMapModal(it, `Koordinat Patroli Jam ${it.jam}`, 'row')"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10.5px] font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-2xs transition-colors cursor-pointer mt-0.5"
                                                            title="Buka titik koordinat pada Peta Google"
                                                        >
                                                            <Map class="h-3 w-3 text-emerald-400" />
                                                            <span>Lihat Peta</span>
                                                        </button>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <span class="text-[10px] text-slate-400 italic">Belum ada lokasi</span>
                                                </template>
                                            </div>

                                            <!-- Jika lebih dari 1 laporan -->
                                            <div v-else class="space-y-1.5">
                                                <div
                                                    v-for="(rep, rIdx) in it.reports"
                                                    :key="rep.id || rIdx"
                                                    class="flex flex-col items-center gap-1 p-1.5 rounded-lg bg-slate-50 border border-slate-200/80"
                                                >
                                                    <span class="text-[10px] font-extrabold text-slate-800 truncate max-w-[150px]" :title="rep.lokasi_nama">
                                                        #{{ rIdx + 1 }}. {{ rep.lokasi_nama || 'Pos Penjagaan' }}
                                                    </span>
                                                    <button
                                                        v-if="rep.latitude && rep.longitude"
                                                        type="button"
                                                        @click="openMapModal(rep, `Koordinat Laporan #${rIdx + 1} (${rep.jam})`, 'row')"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[9.5px] font-bold cursor-pointer transition-colors shadow-2xs"
                                                    >
                                                        <MapPin class="h-2.5 w-2.5 text-rose-500 shrink-0" />
                                                        <span>Peta ({{ Number(rep.latitude).toFixed(4) }}, {{ Number(rep.longitude).toFixed(4) }})</span>
                                                    </button>
                                                    <span v-else class="text-[9.5px] text-slate-400 italic">Tanpa GPS</span>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span class="text-slate-300 italic text-[11px]">-</span>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Tabel Log -->
                    <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 text-xs text-slate-600 flex-wrap">
                            <span>Siklus: <strong>24 Baris Mutlak</strong></span>
                            <span>•</span>
                            <span class="text-emerald-700 font-bold">✓ {{ totalReportedCount }} Jam Telah Dilaporkan ({{ reportedPercent }}%)</span>
                            <span>•</span>
                            <span class="text-emerald-700 font-bold">{{ totalAmanCount }} Jam Aman</span>
                            <span v-if="totalTidakAmanCount > 0" class="text-rose-700 font-bold">• ⚠️ {{ totalTidakAmanCount }} Insiden</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="handleDownloadExcel"
                                :disabled="isDownloadingExcel"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer shadow-xs transition-colors flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <Download class="h-3.5 w-3.5" :class="isDownloadingExcel ? 'animate-bounce' : ''" />
                                <span>{{ isDownloadingExcel ? 'Mengunduh...' : 'Download Excel' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 2: LOG LAPORAN SERAH TERIMA JAGA KEAMANAN               -->
            <!-- ============================================================ -->
            <div v-show="activeTab === 'serah_terima'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-4 bg-indigo-50/60 border-b border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <ArrowRightLeft class="h-4 w-4 text-indigo-600" />
                                <span>Laporan &amp; Log Serah Terima Jaga Keamanan</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Catatan serah terima tugas penjagaan (Masuk Jaga &amp; Lepas Jaga), kelengkapan inventaris, dan titik posisi petugas.
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-white text-indigo-700 border border-indigo-200 shadow-2xs self-start sm:self-auto">
                            Total: {{ serahTerimasHariIni.length }} Laporan Hari Ini
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                    <th class="py-2.5 px-3 w-12 text-center">No</th>
                                    <th class="py-2.5 px-3 w-36">Waktu (WITA)</th>
                                    <th class="py-2.5 px-3 w-36">Jenis Serah Terima</th>
                                    <th class="py-2.5 px-3 min-w-[160px]">Nama Petugas</th>
                                    <th class="py-2.5 px-3 min-w-[200px]">Catatan / Keterangan</th>
                                    <th class="py-2.5 px-3 min-w-[160px]">Lokasi &amp; Koordinat</th>
                                    <th class="py-2.5 px-3 w-28 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr
                                    v-for="(st, idx) in serahTerimasHariIni"
                                    :key="st.id || idx"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td class="py-3 px-3 text-center font-bold text-slate-500">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                        {{ st.waktu }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span
                                            class="inline-block px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold"
                                            :class="st.jenis === 'Masuk Jaga' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-800 border border-slate-300'"
                                        >
                                            {{ st.jenis }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-bold text-slate-900 block">
                                            {{ st.petugas_nama || st.petugas?.nama || 'Petugas Keamanan' }}
                                        </span>
                                        <span class="text-[10px] text-blue-700 font-semibold">
                                            {{ st.petugas?.jabatan || 'Petugas Keamanan' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-700 leading-relaxed">
                                        {{ st.catatan || '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-slate-800">{{ st.lokasi_nama || 'Pos Penjagaan SPPG' }}</div>
                                        <div v-if="st.latitude && st.longitude" class="text-[10px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                            <MapPin class="h-3 w-3 text-emerald-600" />
                                            <a
                                                :href="`https://www.google.com/maps?q=${st.latitude},${st.longitude}`"
                                                target="_blank"
                                                class="text-indigo-600 hover:underline"
                                            >
                                                {{ st.latitude }}, {{ st.longitude }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openEditSerahTerima(st)"
                                                class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 cursor-pointer transition-colors"
                                                title="Edit Laporan Serah Terima Ini"
                                            >
                                                <Edit3 class="h-3 w-3" />
                                                <span>Edit</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteSerahTerima(st.id)"
                                                class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer transition-colors"
                                                title="Hapus Laporan Serah Terima Ini"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="serahTerimasHariIni.length === 0">
                                    <td colspan="7" class="py-10 text-center text-slate-400 italic">
                                        Belum ada catatan laporan serah terima jaga untuk tanggal ini. Petugas dapat mengirimkan serah terima dari formulir mandiri di HP.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 3: LOG IZIN KELUAR PENJAGAAN & DINAS LUAR                -->
            <!-- ============================================================ -->
            <div v-show="activeTab === 'izin'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-4 bg-amber-50/60 border-b border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <LogOut class="h-4 w-4 text-amber-600" />
                                <span>Jurnal Mutasi Izin Keluar Penjagaan &amp; Pelaporan Petugas</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Mencatat alur fase pengajuan izin, waktu keluar, lokasi tujuan, waktu kembali, dan perhitungan waktu durasi keluar.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="openCreateIzinModal"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-xs transition-colors cursor-pointer self-start sm:self-auto"
                        >
                            <LogOut class="h-3.5 w-3.5" />
                            <span>+ Catat Izin Keluar Penjagaan</span>
                        </button>
                    </div>

                    <!-- Tabel Daftar Izin Keluar -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse min-w-[1050px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                                    <th class="py-2.5 px-3 w-36">Petugas Jaga</th>
                                    <th class="py-2.5 px-3 w-52">Fase 1: Waktu &amp; Pos Izin</th>
                                    <th class="py-2.5 px-3 w-52">Fase 2: Keluar Penjagaan</th>
                                    <th class="py-2.5 px-3 min-w-[200px]">Fase 3: Tempat Izin &amp; Keperluan</th>
                                    <th class="py-2.5 px-3 w-52">Fase 4: Waktu Kembali</th>
                                    <th class="py-2.5 px-3 w-40 text-center">Hitungan Waktu</th>
                                    <th class="py-2.5 px-3 min-w-[200px]">Pelaporan Kegiatan Petugas</th>
                                    <th class="py-2.5 px-3 w-36 text-center">Peta &amp; Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr
                                    v-for="(iz, izIdx) in keluarJagasHariIni"
                                    :key="iz.id"
                                    :class="iz.status === 'Sedang Keluar' ? 'bg-amber-50/40 hover:bg-amber-50/70' : 'hover:bg-slate-50/80'"
                                    class="transition-colors"
                                >
                                    <!-- No -->
                                    <td class="py-3 px-3 text-center font-bold text-slate-500">
                                        {{ izIdx + 1 }}
                                    </td>

                                    <!-- Petugas Jaga -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900">{{ iz.petugas_nama }}</div>
                                        <span class="text-[10px] text-blue-700 font-semibold">Divisi Keamanan</span>
                                    </td>

                                    <!-- Fase 1: Waktu & Pos Pengajuan Izin -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-blue-900 font-mono">{{ iz.waktu_izin || iz.waktu_keluar }}</div>
                                        <div class="text-[11px] text-slate-600 mt-0.5">{{ iz.lokasi_izin || iz.lokasi_awal || 'Pos Penjagaan' }}</div>
                                        <div v-if="iz.lat_izin && iz.lng_izin" class="flex items-center gap-1 font-mono text-[9px] text-blue-700 mt-1">
                                            <MapPin class="h-3 w-3 shrink-0" />
                                            <span>{{ Number(iz.lat_izin).toFixed(5) }}, {{ Number(iz.lng_izin).toFixed(5) }}</span>
                                        </div>
                                    </td>

                                    <!-- Fase 2: Waktu & Pos Keluar -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-amber-900 font-mono">{{ iz.waktu_keluar }}</div>
                                        <div class="text-[11px] text-slate-600 mt-0.5">{{ iz.lokasi_awal || 'Pos Penjagaan' }}</div>
                                        <div v-if="iz.lat_awal && iz.lng_awal" class="flex items-center gap-1 font-mono text-[9px] text-amber-700 mt-1">
                                            <MapPin class="h-3 w-3 shrink-0" />
                                            <span>{{ Number(iz.lat_awal).toFixed(5) }}, {{ Number(iz.lng_awal).toFixed(5) }}</span>
                                        </div>
                                    </td>

                                    <!-- Fase 3: Tempat Izin & Keperluan -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900">{{ iz.lokasi_tujuan }}</div>
                                        <div class="text-slate-600 text-[11px] mt-0.5 italic">{{ iz.keperluan }}</div>
                                        <div v-if="iz.lat_tujuan && iz.lng_tujuan" class="flex items-center gap-1 font-mono text-[9px] text-rose-600 mt-1">
                                            <MapPin class="h-3 w-3 shrink-0" />
                                            <span>Tujuan: {{ Number(iz.lat_tujuan).toFixed(5) }}, {{ Number(iz.lng_tujuan).toFixed(5) }}</span>
                                        </div>
                                    </td>

                                    <!-- Fase 4: Waktu Kembali -->
                                    <td class="py-3 px-3">
                                        <template v-if="iz.status === 'Sudah Kembali'">
                                            <div class="font-bold text-emerald-800 font-mono">{{ iz.waktu_kembali }}</div>
                                            <div class="text-[11px] text-slate-600 mt-0.5">{{ iz.lokasi_kembali || 'Pos Penjagaan' }}</div>
                                            <div v-if="iz.lat_kembali && iz.lng_kembali" class="flex items-center gap-1 font-mono text-[9px] text-emerald-700 mt-1">
                                                <MapPin class="h-3 w-3 shrink-0" />
                                                <span>{{ Number(iz.lat_kembali).toFixed(5) }}, {{ Number(iz.lng_kembali).toFixed(5) }}</span>
                                            </div>
                                        </template>
                                        <div v-else class="text-amber-700 font-bold text-[11px] flex items-center gap-1">
                                            <Clock class="h-3.5 w-3.5 animate-spin" />
                                            <span>Sedang di Luar</span>
                                        </div>
                                    </td>

                                    <!-- Perhitungan Waktu -->
                                    <td class="py-3 px-3 text-center">
                                        <template v-if="iz.status === 'Sudah Kembali'">
                                            <div class="inline-block p-1.5 bg-emerald-50 rounded-xl border border-emerald-200">
                                                <span class="block text-[10px] text-emerald-700 font-semibold">Durasi Keluar:</span>
                                                <span class="font-extrabold text-xs text-emerald-950">
                                                    {{ calculateIzinDurations(iz).durasiKeluarText }}
                                                </span>
                                            </div>
                                        </template>
                                        <div v-else class="inline-block px-2 py-1 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 text-[10.5px] font-bold">
                                            Sedang Bertugas
                                        </div>
                                    </td>

                                    <!-- Pelaporan Kegiatan Petugas -->
                                    <td class="py-3 px-3">
                                        <div v-if="iz.laporan_kegiatan" class="text-slate-800 whitespace-pre-line text-xs font-medium bg-slate-50 p-2 rounded-lg border border-slate-200">
                                            {{ iz.laporan_kegiatan }}
                                        </div>
                                        <div v-else class="text-slate-400 italic text-[11px]">
                                            Menunggu kepulangan petugas untuk pelaporan.
                                        </div>
                                    </td>

                                    <!-- Peta & Aksi -->
                                    <td class="py-3 px-3 text-center">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <!-- Tombol Lihat Titik & Rute (Style Google) -->
                                            <button
                                                type="button"
                                                @click="openIzinRouteMap(iz)"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 shadow-2xs transition-colors cursor-pointer flex items-center justify-center gap-1"
                                                title="Lihat seluruh titik point lokasi fase izin hingga kembali di peta style Google"
                                            >
                                                <Map class="h-3.5 w-3.5" />
                                                <span>Lihat Titik &amp; Rute</span>
                                            </button>

                                            <!-- Tombol Konfirmasi Kembali jika status masih Sedang Keluar -->
                                            <button
                                                v-if="iz.status === 'Sedang Keluar'"
                                                type="button"
                                                @click="openKembaliIzinModal(iz)"
                                                class="w-full px-2.5 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-2xs transition-colors cursor-pointer flex items-center justify-center gap-1"
                                                title="Petugas telah kembali, klik untuk mencatat kepulangan dan pelaporan"
                                            >
                                                <LogIn class="h-3 w-3" />
                                                <span>Konfirmasi Kembali</span>
                                            </button>

                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"
                                            >
                                                <Check class="h-3 w-3" />
                                                <span>Sudah Kembali</span>
                                            </span>

                                            <!-- Tombol Aksi Edit & Hapus -->
                                            <div class="flex items-center justify-center gap-1.5 w-full mt-1">
                                                <button
                                                    type="button"
                                                    @click="openEditIzinModal(iz)"
                                                    class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 cursor-pointer transition-colors"
                                                    title="Edit data izin ini"
                                                >
                                                    <Edit3 class="h-3 w-3" />
                                                    <span>Edit</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="deleteIzin(iz.id)"
                                                    class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer transition-colors"
                                                    title="Hapus data izin ini"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="keluarJagasHariIni.length === 0">
                                    <td colspan="9" class="py-10 text-center text-slate-400 italic">
                                        Belum ada catatan izin keluar penjagaan untuk tanggal ini. Klik <strong>"+ Catat Izin Keluar Penjagaan"</strong> untuk menambahkan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 4: LOG LAPORAN KEMBALI JAGA & PENGECEKAN TERAKHIR        -->
            <!-- ============================================================ -->
            <div v-show="activeTab === 'kembali_jaga'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <div class="p-4 bg-emerald-50/60 border-b border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <LogIn class="h-4 w-4 text-emerald-600" />
                                <span>Jurnal Laporan Kembali Jaga &amp; Pengecekan Terakhir Petugas</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Mencatat laporan petugas saat kembali ke pos penjagaan atau checkpoint patroli terakhir beserta titik koordinat GPS dan jarak ke SPPG.
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-white text-emerald-700 border border-emerald-200 shadow-2xs self-start sm:self-auto">
                            Total: {{ kembaliJagasHariIni.length }} Laporan Kembali
                        </span>
                    </div>

                    <!-- Tabel Daftar Kembali Jaga -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse min-w-[950px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                    <th class="py-2.5 px-3 w-10 text-center">No</th>
                                    <th class="py-2.5 px-3 w-44">Nama Petugas Jaga</th>
                                    <th class="py-2.5 px-3 w-36">Waktu Kembali</th>
                                    <th class="py-2.5 px-3 min-w-[200px]">Tempat Posisi Pengecekan Terakhir</th>
                                    <th class="py-2.5 px-3 w-64">Titik Koordinat GPS &amp; Jarak SPPG</th>
                                    <th class="py-2.5 px-3 min-w-[180px]">Keterangan / Laporan</th>
                                    <th class="py-2.5 px-3 w-36 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-800">
                                <tr
                                    v-for="(kj, kjIdx) in kembaliJagasHariIni"
                                    :key="kj.id || kjIdx"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <!-- No -->
                                    <td class="py-3 px-3 text-center font-bold text-slate-500">
                                        {{ kjIdx + 1 }}
                                    </td>

                                    <!-- Petugas Jaga -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900">{{ kj.petugas_nama }}</div>
                                        <span class="text-[10px] text-blue-700 font-semibold">Divisi Keamanan</span>
                                    </td>

                                    <!-- Waktu Kembali -->
                                    <td class="py-3 px-3">
                                        <div class="font-mono font-bold text-emerald-900">{{ kj.waktu_kembali || '-' }}</div>
                                        <div class="text-[10.5px] text-slate-500">{{ kj.tanggal_kembali || filterTanggal }}</div>
                                    </td>

                                    <!-- Tempat Posisi Pengecekan Terakhir -->
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-900">{{ kj.lokasi_kembali || kj.lokasi_awal || 'Pos Penjagaan' }}</div>
                                        <div v-if="kj.durasi_keluar_menit" class="text-[10.5px] text-emerald-700 font-semibold mt-0.5">
                                            Durasi: {{ kj.durasi_keluar_menit }} menit
                                        </div>
                                    </td>

                                    <!-- Titik Koordinat GPS & Jarak SPPG -->
                                    <td class="py-3 px-3">
                                        <div v-if="kj.lat_kembali && kj.lng_kembali" class="space-y-1">
                                            <div class="flex items-center gap-1 font-mono text-[10.5px] text-emerald-800 font-bold">
                                                <MapPin class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                                <span>{{ Number(kj.lat_kembali).toFixed(5) }}, {{ Number(kj.lng_kembali).toFixed(5) }}</span>
                                            </div>
                                            <div
                                                v-if="getDistanceFromSppg(kj.lat_kembali, kj.lng_kembali)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-semibold"
                                            >
                                                <Navigation class="h-2.5 w-2.5 text-emerald-600" />
                                                <span>Jarak ke SPPG: {{ getDistanceFromSppg(kj.lat_kembali, kj.lng_kembali) }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-400 italic text-[11px]">- Tidak ada GPS -</span>
                                    </td>

                                    <!-- Keterangan / Laporan -->
                                    <td class="py-3 px-3">
                                        <div v-if="kj.laporan_kegiatan" class="text-slate-800 text-xs font-medium bg-slate-50 p-2 rounded-lg border border-slate-200">
                                            {{ kj.laporan_kegiatan }}
                                        </div>
                                        <div v-else class="text-slate-400 italic text-[11px]">
                                            Tidak ada catatan tambahan.
                                        </div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-3 px-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <button
                                                v-if="kj.lat_kembali && kj.lng_kembali"
                                                type="button"
                                                @click="openMapModal({
                                                    latitude: kj.lat_kembali,
                                                    longitude: kj.lng_kembali,
                                                    lokasi_nama: kj.lokasi_kembali,
                                                    petugas_nama: kj.petugas_nama,
                                                    jam: kj.waktu_kembali,
                                                    isRoute: false
                                                }, `Titik Lokasi Kembali: ${kj.petugas_nama} (${kj.lokasi_kembali})`)"
                                                class="px-2 py-1 rounded-lg text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 cursor-pointer flex items-center gap-1"
                                                title="Lihat Titik Pengecekan Terakhir di Peta"
                                            >
                                                <MapPin class="h-3 w-3 text-emerald-600" />
                                                <span>Peta</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="openEditIzinModal(kj)"
                                                class="px-2 py-1 rounded-lg text-[11px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 cursor-pointer flex items-center gap-1"
                                                title="Edit Data Kembali Jaga Ini"
                                            >
                                                <Edit3 class="h-3 w-3" />
                                                <span>Edit</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="deleteIzin(kj.id)"
                                                class="px-2 py-1 rounded-lg text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer flex items-center gap-1"
                                                title="Hapus Data Ini"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="kembaliJagasHariIni.length === 0">
                                    <td colspan="7" class="py-10 text-center text-slate-400 italic">
                                        Belum ada catatan laporan kembali jaga untuk tanggal ini. Petugas dapat mengirimkan laporan dari formulir mandiri di HP pada tab "Kembali Jaga".
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: RIWAYAT / ARSIP MUTASI KEAMANAN -->
            <div v-show="activeTab === 'riwayat'" class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Arsip &amp; Riwayat Buku Mutasi Keamanan SPPG
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Daftar buku mutasi keamanan harian yang telah tersimpan di database.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                                <th class="py-2.5 px-3 w-12 text-center">No</th>
                                <th class="py-2.5 px-3 w-36">Tanggal</th>
                                <th class="py-2.5 px-3 w-32">Siklus Patroli</th>
                                <th class="py-2.5 px-3 min-w-[180px]">Koordinator Jaga</th>
                                <th class="py-2.5 px-3 w-28 text-center">Jumlah Cek</th>
                                <th class="py-2.5 px-3 w-28 text-center">Izin Keluar</th>
                                <th class="py-2.5 px-3 w-32 text-center">Status Lingkungan</th>
                                <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800">
                            <tr
                                v-for="(m, idx) in riwayatList.data"
                                :key="m.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-3 px-3 text-center font-bold text-slate-500">
                                    {{ (riwayatList.from || 1) + idx }}
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900">
                                    {{ m.tanggal }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-700">
                                    {{ m.shift }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="font-bold text-slate-900 block">
                                        {{ m.petugas_nama || m.petugas?.nama || 'Petugas Keamanan' }}
                                    </span>
                                    <span class="text-[10px] text-blue-700 font-semibold">
                                        Divisi Keamanan
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-semibold text-slate-700">
                                    {{ m.details?.length || 0 }} Baris
                                </td>
                                <td class="py-3 px-3 text-center font-semibold text-slate-700">
                                    {{ m.izins?.length || 0 }} Izin
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="inline-block px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold"
                                        :class="m.status_keseluruhan === 'Tidak Aman' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'"
                                    >
                                        {{ m.status_keseluruhan === 'Tidak Aman' ? '⚠️ Insiden' : '✓ Aman' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="() => { filterTanggal = m.tanggal; filterShift = m.shift; onFilterChange(); activeTab = 'jurnal'; }"
                                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 cursor-pointer transition-colors"
                                            title="Buka & Edit Laporan Mutasi Ini"
                                        >
                                            <Edit3 class="h-3 w-3" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteMutasi(m.id)"
                                            class="inline-flex items-center justify-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 cursor-pointer transition-colors"
                                            title="Hapus Laporan Mutasi Ini"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!riwayatList.data || riwayatList.data.length === 0">
                                <td colspan="8" class="py-8 text-center text-slate-400 italic">
                                    Belum ada data riwayat mutasi keamanan yang tersimpan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL VIEW MAP LEAFLET -->
        <KeamananMapModal
            :show="showMapModal"
            :title="mapModalTitle"
            :item="activeMapItem"
            @close="showMapModal = false"
            @update-coords="handleUpdateCoords"
        />

        <!-- MODAL CATAT / KONFIRMASI IZIN KELUAR PENJAGAAN -->
        <KeamananIzinModal
            ref="izinModalRef"
            :show="showIzinModal"
            :mode="izinModalMode"
            :izin="selectedIzinData"
            :tanggal="form.tanggal"
            :shift="form.shift"
            :petugas-keamanan="props.petugasKeamanan"
            @close="showIzinModal = false"
            @open-map="handleOpenMapFromIzin"
        />

        <!-- MODAL EDIT LAPORAN SERAH TERIMA JAGA -->
        <KeamananSerahTerimaModal
            :show="showSerahTerimaModal"
            :serah-terima="selectedSerahTerima"
            :petugas-keamanan="props.petugasKeamanan"
            @close="showSerahTerimaModal = false"
        />

        <!-- MODAL CETAK LAPORAN BERITA ACARA KEAMANAN (24 JAM) -->
        <KeamananPrintModal
            :show="showPrintModal"
            :mutasi="props.mutasiAktif ? { ...props.mutasiAktif, details: form.details } : form"
            :unit-sppg="props.unitSppg"
            :pejabat-sppg="props.pejabatSppg"
            @close="showPrintModal = false"
        />

        <!-- MODAL QR CODE UNTUK LINK FORM PETUGAS -->
        <KeamananQrModal
            :show="showQrModal"
            :url="props.formPublicUrl || ''"
            title="QR Code Formulir Petugas Keamanan"
            subtitle="Scan pakai HP satpam untuk langsung mengisi patroli"
            :unit-sppg="props.unitSppg"
            @close="showQrModal = false"
        />

        <!-- MODAL QR CODE UNTUK LINK MONITORING KEAMANAN -->
        <KeamananQrModal
            :show="showMonitoringQrModal"
            :url="props.monitoringPublicUrl || ''"
            title="QR Code Layar Monitoring Keamanan Realtime"
            subtitle="Scan untuk membuka dashboard live monitoring laporan tiap petugas"
            :unit-sppg="props.unitSppg"
            @close="showMonitoringQrModal = false"
        />

        <!-- MODAL CHECKLIST DETAIL 21 AREA & UMUM -->
        <KeamananChecklistModal
            :show="showChecklistModal"
            :detail="activeChecklistDetail"
            @close="showChecklistModal = false"
        />

        <!-- MODAL KOREKSI / INPUT MANUAL JAM TERTENTU -->
        <Teleport to="body">
            <div
                v-if="showManualEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in"
                style="z-index: 99999;"
            >
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center border border-emerald-400/30">
                                <Clock class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black">
                                    Input / Koreksi Laporan Patroli
                                </h3>
                                <p class="text-[11px] text-slate-300 font-mono">
                                    Rentang: {{ manualForm.jam }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showManualEditModal = false"
                            class="h-8 w-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center cursor-pointer transition-colors"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-5 overflow-y-auto space-y-3.5 text-xs">
                        <!-- Petugas Jaga -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Petugas Jaga <span class="text-rose-500">*</span>:
                            </label>
                            <select
                                v-model="manualForm.petugas_id"
                                class="w-full rounded-xl border-slate-200 text-xs font-bold p-2.5 focus:ring-1 focus:ring-primary"
                            >
                                <option
                                    v-for="p in petugasKeamanan"
                                    :key="p.id"
                                    :value="p.id"
                                >
                                    {{ p.nama }} ({{ p.jabatan || 'Keamanan' }})
                                </option>
                            </select>
                        </div>

                        <!-- Kondisi -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Status Kondisi:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="manualForm.kondisi = 'Aman'"
                                    :class="[
                                        manualForm.kondisi === 'Aman'
                                            ? 'bg-emerald-600 text-white font-bold shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                                        'py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all'
                                    ]"
                                >
                                    <Check class="h-3.5 w-3.5" />
                                    <span>✓ Aman</span>
                                </button>
                                <button
                                    type="button"
                                    @click="manualForm.kondisi = 'Tidak Aman'"
                                    :class="[
                                        manualForm.kondisi === 'Tidak Aman'
                                            ? 'bg-rose-600 text-white font-bold shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                                        'py-2 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 cursor-pointer transition-all'
                                    ]"
                                >
                                    <AlertTriangle class="h-3.5 w-3.5" />
                                    <span>⚠️ Tidak Aman</span>
                                </button>
                            </div>
                        </div>

                        <!-- Uraian Kejadian -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Uraian Kejadian / Aktivitas Patroli:
                            </label>
                            <textarea
                                v-model="manualForm.uraian_kejadian"
                                rows="3"
                                placeholder="Jelaskan kondisi dan aktivitas patroli pada jam ini..."
                                class="w-full rounded-xl border-slate-200 text-xs p-2.5 focus:ring-1 focus:ring-primary font-medium"
                            ></textarea>
                        </div>

                        <!-- Koordinat GPS -->
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-700">Titik Koordinat Lokasi:</span>
                                <button
                                    type="button"
                                    @click="fetchGpsForManual"
                                    :disabled="isLocatingModal"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10.5px] cursor-pointer shadow-2xs"
                                >
                                    <Navigation class="h-3 w-3" :class="isLocatingModal ? 'animate-spin' : ''" />
                                    <span>{{ isLocatingModal ? 'Mencari...' : 'Ambil GPS' }}</span>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Latitude</span>
                                    <input
                                        type="number"
                                        step="any"
                                        v-model="manualForm.latitude"
                                        placeholder="-8.12345"
                                        class="w-full text-xs font-mono font-bold p-1.5 rounded-lg border-slate-200"
                                    />
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Longitude</span>
                                    <input
                                        type="number"
                                        step="any"
                                        v-model="manualForm.longitude"
                                        placeholder="115.12345"
                                        class="w-full text-xs font-mono font-bold p-1.5 rounded-lg border-slate-200"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showManualEditModal = false"
                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-100 cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitManualEdit"
                            :disabled="isSavingManual"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs cursor-pointer shadow-xs flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Save class="h-3.5 w-3.5" :class="isSavingManual ? 'animate-spin' : ''" />
                            <span>{{ isSavingManual ? 'Menyimpan...' : 'Simpan Laporan Jam Ini' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
