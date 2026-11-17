<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    X,
    MapPin,
    Navigation,
    ExternalLink,
    Check,
    AlertTriangle,
    Layers,
    Clock,
    Route,
    ArrowRight,
} from 'lucide-vue-next';

// Fix leaflet default icon
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Titik Koordinat Lokasi Patroli Keamanan',
    },
    item: {
        type: Object,
        default: null,
    },
    defaultCenter: {
        type: Array,
        default: () => [-8.1568224, 115.0972345], // Default Bali / SPPG
    },
});

const emit = defineEmits(['close', 'update-coords']);

const mapContainer = ref(null);
let map = null;
let singleMarker = null;
let polylineLayer = null;
let markersGroup = null;

const currentMapType = ref('roadmap'); // 'roadmap' | 'satellite'
let tileLayer = null;

const isLocating = ref(false);
const locationMsg = ref('');

const curLat = ref(null);
const curLng = ref(null);
const curLokasiNama = ref('');

// Cek apakah item adalah rute multi-fase izin keluar
const isFaseRouteMode = ref(false);
const fasePointsList = ref([]);

watch(
    () => props.show,
    async (newVal) => {
        if (newVal && props.item) {
            checkAndInitFaseRoute();
            curLat.value = props.item.latitude ? Number(props.item.latitude) : null;
            curLng.value = props.item.longitude ? Number(props.item.longitude) : null;
            curLokasiNama.value = props.item.lokasi_nama || 'Pos Penjagaan';
            locationMsg.value = '';

            await nextTick();
            initMap();
        } else {
            destroyMap();
        }
    }
);

function checkAndInitFaseRoute() {
    fasePointsList.value = [];
    isFaseRouteMode.value = false;

    if (!props.item) return;

    // Jika item adalah objek Izin Keluar (memiliki lat_awal atau lat_tujuan atau lat_izin)
    if (props.item.isRoute || props.item.keperluan || props.item.lokasi_tujuan) {
        const points = [];

        // Fase 1: Izin Diajukan
        const latIzin = props.item.lat_izin ?? props.item.lat_awal;
        const lngIzin = props.item.lng_izin ?? props.item.lng_awal;
        if (latIzin && lngIzin) {
            points.push({
                fase: 1,
                label: 'Fase 1: Pengajuan Izin',
                nama: props.item.lokasi_izin || props.item.lokasi_awal || 'Pos Penjagaan SPPG',
                waktu: props.item.waktu_izin || props.item.waktu_keluar || '-',
                tanggal: props.item.tanggal_izin || props.item.tanggal_keluar || '-',
                lat: Number(latIzin),
                lng: Number(lngIzin),
                color: '#3b82f6', // Biru
                icon: '📋',
            });
        }

        // Fase 2: Keluar Penjagaan (Pos Awal)
        if (props.item.lat_awal && props.item.lng_awal) {
            points.push({
                fase: 2,
                label: 'Fase 2: Keluar Penjagaan',
                nama: props.item.lokasi_awal || 'Pos Penjagaan SPPG',
                waktu: props.item.waktu_keluar || '-',
                tanggal: props.item.tanggal_keluar || '-',
                lat: Number(props.item.lat_awal),
                lng: Number(props.item.lng_awal),
                color: '#f59e0b', // Oranye/Amber
                icon: '🚪',
            });
        }

        // Fase 3: Tempat Izin / Tujuan
        if (props.item.lat_tujuan && props.item.lng_tujuan) {
            points.push({
                fase: 3,
                label: 'Fase 3: Tempat Izin / Tujuan',
                nama: props.item.lokasi_tujuan || 'Tempat Tujuan',
                waktu: props.item.waktu_sampai_tujuan || props.item.waktu_keluar || '-',
                tanggal: props.item.tanggal_keluar || '-',
                lat: Number(props.item.lat_tujuan),
                lng: Number(props.item.lng_tujuan),
                color: '#ef4444', // Merah
                icon: '🎯',
            });
        }

        // Fase 4: Kembali ke Penjagaan
        if (props.item.lat_kembali && props.item.lng_kembali) {
            points.push({
                fase: 4,
                label: 'Fase 4: Kembali ke Penjagaan',
                nama: props.item.lokasi_kembali || 'Pos Penjagaan SPPG',
                waktu: props.item.waktu_kembali || '-',
                tanggal: props.item.tanggal_kembali || '-',
                lat: Number(props.item.lat_kembali),
                lng: Number(props.item.lng_kembali),
                color: '#10b981', // Hijau
                icon: '🏁',
            });
        }

        if (points.length > 0) {
            fasePointsList.value = points;
            isFaseRouteMode.value = true;
        }
    }
}

function initMap() {
    if (!mapContainer.value) return;
    destroyMap();

    let initialCenter = props.defaultCenter;
    let initialZoom = 15;

    if (isFaseRouteMode.value && fasePointsList.value.length > 0) {
        initialCenter = [fasePointsList.value[0].lat, fasePointsList.value[0].lng];
    } else if (curLat.value !== null && curLng.value !== null) {
        initialCenter = [curLat.value, curLng.value];
        initialZoom = 17;
    }

    map = L.map(mapContainer.value, {
        center: initialCenter,
        zoom: initialZoom,
        zoomControl: true,
        attributionControl: false,
    });

    applyGoogleTileLayer();

    markersGroup = L.layerGroup().addTo(map);

    if (isFaseRouteMode.value && fasePointsList.value.length > 0) {
        renderFaseRouteMarkers();
    } else if (curLat.value !== null && curLng.value !== null) {
        setSingleMarker(curLat.value, curLng.value);
    }

    // Klik di peta untuk memindahkan marker jika mode single point
    map.on('click', (e) => {
        if (isFaseRouteMode.value) return;
        const { lat, lng } = e.latlng;
        curLat.value = Number(lat.toFixed(7));
        curLng.value = Number(lng.toFixed(7));
        setSingleMarker(curLat.value, curLng.value);
        locationMsg.value = `Titik diperbarui: ${curLat.value}, ${curLng.value}`;
    });

    setTimeout(() => {
        if (map) map.invalidateSize();
    }, 250);
}

// Google Maps Style Tile Layer
function applyGoogleTileLayer() {
    if (!map) return;
    if (tileLayer) {
        map.removeLayer(tileLayer);
    }

    const lyrs = currentMapType.value === 'satellite' ? 'y' : 'm'; // 'm' = Roadmap, 'y' = Hybrid Satelit + Label
    tileLayer = L.tileLayer(`https://{s}.google.com/vt/lyrs=${lyrs}&x={x}&y={y}&z={z}`, {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '&copy; Google Maps',
    }).addTo(map);
}

function setMapType(type) {
    currentMapType.value = type;
    applyGoogleTileLayer();
}

// Render marker satu titik biasa
function setSingleMarker(lat, lng) {
    if (!map || !markersGroup) return;
    markersGroup.clearLayers();

    const isAman = props.item?.kondisi !== 'Tidak Aman';

    const customIcon = L.divIcon({
        className: 'custom-google-pin',
        html: `
            <div style="background-color: ${isAman ? '#059669' : '#dc2626'}; width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.4); border: 2.5px solid #ffffff;">
                <span style="transform: rotate(45deg); color: #ffffff; font-size: 13px; font-weight: 900;">${isAman ? '🛡️' : '⚠️'}</span>
            </div>
        `,
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -34],
    });

    singleMarker = L.marker([lat, lng], { icon: customIcon }).addTo(markersGroup);

    const popupHtml = `
        <div style="font-family: inherit; font-size: 12px; line-height: 1.4; padding: 2px;">
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                ${props.item?.jam ? 'Jam ' + props.item.jam + ' • ' : ''} ${isAman ? '<span style="color:#059669;">AMAN</span>' : '<span style="color:#dc2626;">TIDAK AMAN</span>'}
            </div>
            <div style="color: #475569; font-size: 11px; margin-bottom: 3px;">
                Lokasi: <strong>${curLokasiNama.value || 'Pos Penjagaan'}</strong>
            </div>
            <div style="color: #334155; font-size: 11px; max-width: 220px; margin-bottom: 4px;">
                ${props.item?.uraian_kejadian || 'Situasi terpantau steril.'}
            </div>
            <div style="font-size: 10px; color: #64748b; font-family: monospace;">
                📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>
        </div>
    `;
    singleMarker.bindPopup(popupHtml).openPopup();
}

// Render marker titik-titik point di tiap fase izin keluar & rute polyline
function renderFaseRouteMarkers() {
    if (!map || !markersGroup) return;
    markersGroup.clearLayers();

    const latLngs = [];

    fasePointsList.value.forEach((p) => {
        latLngs.push([p.lat, p.lng]);

        const faseIcon = L.divIcon({
            className: 'custom-fase-pin',
            html: `
                <div style="background-color: ${p.color}; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(0,0,0,0.45); border: 3px solid #ffffff;">
                    <span style="transform: rotate(45deg); color: #ffffff; font-size: 13px; font-weight: 900;">${p.icon}</span>
                </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
        });

        const m = L.marker([p.lat, p.lng], { icon: faseIcon }).addTo(markersGroup);

        const popupHtml = `
            <div style="font-family: inherit; font-size: 12px; line-height: 1.4; padding: 2px;">
                <div style="font-weight: 800; color: ${p.color}; font-size: 12.5px; margin-bottom: 3px;">
                    ${p.label}
                </div>
                <div style="color: #0f172a; font-weight: 700; margin-bottom: 2px;">
                    ${p.nama}
                </div>
                <div style="color: #475569; font-size: 11px; margin-bottom: 4px;">
                    Waktu: <strong>${p.waktu}</strong> (${p.tanggal})
                </div>
                <div style="font-size: 10px; color: #64748b; font-family: monospace;">
                    Koordinat: ${p.lat.toFixed(6)}, ${p.lng.toFixed(6)}
                </div>
            </div>
        `;
        m.bindPopup(popupHtml);
    });

    // Buat garis lintasan rute antar fase jika lebih dari 1 titik
    if (latLngs.length > 1) {
        polylineLayer = L.polyline(latLngs, {
            color: '#2563eb',
            weight: 4,
            opacity: 0.85,
            dashArray: '8, 8',
            lineJoin: 'round',
        }).addTo(markersGroup);

        map.fitBounds(polylineLayer.getBounds(), { padding: [40, 40] });
    } else if (latLngs.length === 1) {
        map.setView(latLngs[0], 16);
    }
}

function destroyMap() {
    if (map) {
        map.remove();
        map = null;
        singleMarker = null;
        polylineLayer = null;
        markersGroup = null;
        tileLayer = null;
    }
}

onBeforeUnmount(() => {
    destroyMap();
});

// Ambil Lokasi (GPS)
function getCurrentGps() {
    if (!navigator.geolocation) {
        locationMsg.value = 'Browser Anda tidak mendukung deteksi lokasi GPS.';
        return;
    }

    isLocating.value = true;
    locationMsg.value = 'Sedang mencari koordinat lokasi terkini...';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(7));
            const lng = Number(pos.coords.longitude.toFixed(7));
            curLat.value = lat;
            curLng.value = lng;
            isLocating.value = false;
            locationMsg.value = `✓ Lokasi Ditemukan (Akurasi ±${Math.round(pos.coords.accuracy)}m): ${lat}, ${lng}`;

            if (map) {
                map.setView([lat, lng], 17);
                setSingleMarker(lat, lng);
            }
        },
        (err) => {
            isLocating.value = false;
            locationMsg.value = `Gagal mendeteksi lokasi: ${err.message || 'Izin akses lokasi ditolak.'}`;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }
    );
}

function handleSave() {
    emit('update-coords', {
        item: props.item,
        latitude: curLat.value,
        longitude: curLng.value,
        lokasi_nama: curLokasiNama.value,
    });
    emit('close');
}

function openInGoogleMaps() {
    if (curLat.value !== null && curLng.value !== null) {
        window.open(`https://www.google.com/maps?q=${curLat.value},${curLng.value}`, '_blank');
    }
}

// Format durasi teks helper
function formatMenitText(menit) {
    if (!menit || menit <= 0) return '-';
    const jam = Math.floor(menit / 60);
    const sisa = menit % 60;
    if (jam > 0 && sisa > 0) return `${jam} Jam ${sisa} Menit`;
    if (jam > 0) return `${jam} Jam`;
    return `${sisa} Menit`;
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/75 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
            style="z-index: 99999;"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl overflow-hidden my-auto flex flex-col max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="px-5 py-4 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-500/20 border border-blue-400/40 flex items-center justify-center text-blue-400 shrink-0 font-bold">
                            <Route v-if="isFaseRouteMode" class="h-5 w-5" />
                            <MapPin v-else class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm sm:text-base font-bold text-white">
                                    {{ title }}
                                </h3>
                                <span
                                    v-if="isFaseRouteMode"
                                    class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-blue-500/30 text-blue-200 border border-blue-400/30 uppercase tracking-wider"
                                >
                                    Rute Fase Izin ({{ fasePointsList.length }} Titik)
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 rounded-full text-[10.5px] font-extrabold uppercase tracking-wider"
                                    :class="item?.kondisi === 'Tidak Aman' ? 'bg-rose-500/30 text-rose-200 border border-rose-400/30' : 'bg-emerald-500/30 text-emerald-200 border border-emerald-400/30'"
                                >
                                    {{ item?.kondisi || 'Aman' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Petugas: <strong>{{ item?.petugas_nama || 'Petugas Keamanan' }}</strong>
                                <span v-if="item?.keperluan"> • Keperluan: {{ item.keperluan }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- INFO DURASI WAKTU DI TIAP FASE (JIKA MODE RUTE IZIN KELUAR) -->
                <div v-if="isFaseRouteMode" class="px-5 py-3 bg-blue-50/90 border-b border-blue-200 shrink-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                        <!-- Timeline Per Fase -->
                        <div class="flex items-center gap-2 flex-wrap text-[11px] font-semibold text-slate-700">
                            <span class="text-blue-900 font-bold flex items-center gap-1">
                                <Clock class="h-3.5 w-3.5 text-blue-700" />
                                <span>Alur Waktu:</span>
                            </span>
                            <div
                                v-for="(fp, fIdx) in fasePointsList"
                                :key="fIdx"
                                class="inline-flex items-center gap-1 bg-white px-2 py-1 rounded-lg border border-slate-200 shadow-2xs"
                            >
                                <span>{{ fp.icon }}</span>
                                <span class="font-bold text-slate-900">{{ fp.waktu }}</span>
                                <span class="text-slate-500 text-[10px]">({{ fp.nama.split(' ')[0] }})</span>
                                <ArrowRight v-if="fIdx < fasePointsList.length - 1" class="h-3 w-3 text-slate-400 ml-1" />
                            </div>
                        </div>

                        <!-- Perhitungan Total Durasi Waktu -->
                        <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                            <div v-if="item?.durasi_keluar_menit" class="bg-amber-100 text-amber-900 font-bold px-2.5 py-1 rounded-lg border border-amber-300 text-[11px]">
                                Waktu Keluar: {{ formatMenitText(item.durasi_keluar_menit) }}
                            </div>
                            <div v-if="item?.durasi_total_menit" class="bg-emerald-100 text-emerald-900 font-extrabold px-2.5 py-1 rounded-lg border border-emerald-300 text-[11px]">
                                Total Durasi Izin: {{ formatMenitText(item.durasi_total_menit) }}
                            </div>
                            <div v-else-if="item?.status === 'Sedang Keluar'" class="bg-amber-100 text-amber-900 font-bold px-2.5 py-1 rounded-lg border border-amber-300 text-[11px] flex items-center gap-1">
                                <Clock class="h-3 w-3 animate-spin" />
                                <span>Sedang Berjalan Keluar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar & Style Google Toggle -->
                <div class="p-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shrink-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Switch Google Maps Style: Roadmap vs Satellite -->
                        <div class="inline-flex p-0.5 bg-white rounded-xl border border-slate-300 shadow-2xs text-xs font-semibold">
                            <button
                                type="button"
                                @click="setMapType('roadmap')"
                                :class="[
                                    currentMapType === 'roadmap'
                                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900',
                                    'px-2.5 py-1 rounded-lg transition-all cursor-pointer flex items-center gap-1'
                                ]"
                            >
                                <Layers class="h-3.5 w-3.5" />
                                <span>Peta Jalan (Google)</span>
                            </button>
                            <button
                                type="button"
                                @click="setMapType('satellite')"
                                :class="[
                                    currentMapType === 'satellite'
                                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900',
                                    'px-2.5 py-1 rounded-lg transition-all cursor-pointer flex items-center gap-1'
                                ]"
                            >
                                <Layers class="h-3.5 w-3.5" />
                                <span>Satelit (Google)</span>
                            </button>
                        </div>

                        <!-- Tombol Ambil Lokasi (Mode Single Point) -->
                        <button
                            v-if="!isFaseRouteMode"
                            type="button"
                            @click="getCurrentGps"
                            :disabled="isLocating"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-300 shadow-2xs transition-all cursor-pointer"
                        >
                            <Navigation class="h-3.5 w-3.5" :class="isLocating ? 'animate-spin' : ''" />
                            <span>{{ isLocating ? 'Mencari Lokasi...' : 'Ambil Lokasi' }}</span>
                        </button>

                        <button
                            v-if="curLat !== null && curLng !== null"
                            type="button"
                            @click="openInGoogleMaps"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 shadow-2xs transition-colors cursor-pointer"
                        >
                            <ExternalLink class="h-3.5 w-3.5 text-slate-500" />
                            <span>Buka di Google Maps</span>
                        </button>
                    </div>

                    <!-- Label Koordinat Saat Ini -->
                    <div class="text-xs text-slate-600 flex items-center gap-1.5 font-mono">
                        <span v-if="isFaseRouteMode" class="bg-white px-2.5 py-1 rounded-lg border border-slate-200 font-bold text-blue-900 text-[11px]">
                            🗺️ {{ fasePointsList.length }} Point Titik Fase
                        </span>
                        <span v-else-if="curLat !== null && curLng !== null" class="bg-white px-2.5 py-1 rounded-lg border border-slate-200 font-bold text-slate-800 text-[11px]">
                            📍 {{ curLat }}, {{ curLng }}
                        </span>
                        <span v-else class="text-slate-400 italic text-[11px]">
                            Belum ada titik koordinat
                        </span>
                    </div>
                </div>

                <!-- Notifikasi Status Lokasi -->
                <div v-if="locationMsg" class="px-4 py-2 bg-blue-50/80 border-b border-blue-100 text-xs text-blue-900 flex items-center gap-2 font-medium">
                    <MapPin class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                    <span>{{ locationMsg }}</span>
                </div>

                <!-- Input Nama Titik Lokasi (Hanya saat edit single coordinate) -->
                <div v-if="!isFaseRouteMode" class="px-4 py-2 bg-white border-b border-slate-100 flex items-center gap-3">
                    <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Nama Pos / Area Titik:</label>
                    <input
                        type="text"
                        v-model="curLokasiNama"
                        placeholder="Contoh: Pos Gerbang Utama, Area Dapur, Loading Dock..."
                        class="flex-1 text-xs border border-slate-200 rounded-lg py-1 px-2.5 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Map Container (Style Google) -->
                <div class="flex-1 min-h-[400px] relative bg-slate-100">
                    <div ref="mapContainer" class="w-full h-full min-h-[400px] z-10"></div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                    <span class="text-xs text-slate-500">
                        {{ isFaseRouteMode ? '*Menampilkan seluruh titik point fase izin keluar hingga kembali ke pos penjagaan.' : '*Klik di peta Google untuk menyesuaikan posisi pin lokasi.' }}
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold cursor-pointer transition-colors"
                        >
                            Tutup
                        </button>
                        <button
                            v-if="!isFaseRouteMode"
                            type="button"
                            @click="handleSave"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold cursor-pointer shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <Check class="h-4 w-4" />
                            <span>Terapkan Titik</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 12px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
}
:deep(.leaflet-popup-content) {
    margin: 12px 14px;
}
</style>
