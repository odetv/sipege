<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ExternalLink, Layers, MapPin } from 'lucide-vue-next';

const props = defineProps({
    latitude: {
        type: [Number, String],
        required: true,
    },
    longitude: {
        type: [Number, String],
        required: true,
    },
    label: {
        type: String,
        default: 'Lokasi Anda',
    },
    legendLabel: {
        type: String,
        default: 'Lokasi Anda',
    },
    sppgLatitude: {
        type: [Number, String],
        default: null,
    },
    sppgLongitude: {
        type: [Number, String],
        default: null,
    },
    sppgLabel: {
        type: String,
        default: 'Pos SPPG',
    },
    height: {
        type: String,
        default: '210px',
    },
});

const mapContainer = ref(null);
let map = null;
let markerUser = null;
let markerSppg = null;
let connectionLine = null;
let tileLayer = null;
let resizeObserver = null;

const mapType = ref('roadmap'); // 'roadmap' | 'satellite'

const hasSppgCoordinates = computed(() => {
    if (
        props.sppgLatitude === null ||
        props.sppgLongitude === null ||
        props.sppgLatitude === undefined ||
        props.sppgLongitude === undefined
    ) {
        return false;
    }
    const sLat = Number(props.sppgLatitude);
    const sLng = Number(props.sppgLongitude);
    return !isNaN(sLat) && !isNaN(sLng);
});

function createPinIcon(color = '#EA4335', badgeText = '') {
    return L.divIcon({
        className: 'google-maps-pin',
        html: `
            <div style="display: flex; flex-direction: column; align-items: center; transform: translate(-50%, -100%); pointer-events: none;">
                ${
                    badgeText
                        ? `<div style="background: rgba(15, 23, 42, 0.9); color: #FFFFFF; font-size: 9.5px; font-weight: 700; padding: 2px 6px; border-radius: 6px; white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.25);">
                            ${badgeText}
                        </div>`
                        : ''
                }
                <div style="width: 28px; height: 28px; background: ${color}; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2.5px solid #FFFFFF; box-shadow: 0 3px 8px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center;">
                    <div style="width: 9px; height: 9px; background: #FFFFFF; border-radius: 50%; transform: rotate(45deg);"></div>
                </div>
                <div style="width: 12px; height: 3px; background: rgba(0,0,0,0.25); border-radius: 50%; filter: blur(1px); margin-top: 1px;"></div>
            </div>
        `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
    });
}

function applyGoogleTile() {
    if (!map) return;
    if (tileLayer) {
        map.removeLayer(tileLayer);
    }
    const lyrs = mapType.value === 'satellite' ? 'y' : 'm';
    tileLayer = L.tileLayer(`https://{s}.google.com/vt/lyrs=${lyrs}&x={x}&y={y}&z={z}`, {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: '&copy; Google Maps',
    }).addTo(map);
}

function toggleMapType() {
    mapType.value = mapType.value === 'roadmap' ? 'satellite' : 'roadmap';
    applyGoogleTile();
}

function renderMapLayers() {
    const lat = Number(props.latitude);
    const lng = Number(props.longitude);
    if (!map || isNaN(lat) || isNaN(lng)) return;

    // Bersihkan marker lama
    if (markerUser) {
        map.removeLayer(markerUser);
        markerUser = null;
    }
    if (markerSppg) {
        map.removeLayer(markerSppg);
        markerSppg = null;
    }
    if (connectionLine) {
        map.removeLayer(connectionLine);
        connectionLine = null;
    }

    // 1. Marker Lokasi Pengguna (Merah)
    const userLabel = props.label || 'Lokasi Anda';
    markerUser = L.marker([lat, lng], {
        icon: createPinIcon('#EA4335', userLabel),
        zIndexOffset: 100,
    }).addTo(map);

    // 2. Marker SPPG & Garis Koneksi Putus-Putus
    if (hasSppgCoordinates.value) {
        const sLat = Number(props.sppgLatitude);
        const sLng = Number(props.sppgLongitude);
        const sppgLabel = props.sppgLabel || 'Pos SPPG';

        markerSppg = L.marker([sLat, sLng], {
            icon: createPinIcon('#059669', sppgLabel),
            zIndexOffset: 50,
        }).addTo(map);

        // Koneksi Garis Putus-Putus
        connectionLine = L.polyline(
            [
                [lat, lng],
                [sLat, sLng],
            ],
            {
                color: '#2563EB',
                weight: 3,
                opacity: 0.85,
                dashArray: '6, 8',
                lineCap: 'round',
                lineJoin: 'round',
            }
        ).addTo(map);

        const dist = map.distance([lat, lng], [sLat, sLng]);
        const formattedDist =
            dist >= 1000
                ? (dist / 1000).toFixed(2).replace('.', ',') + ' km'
                : Math.round(dist) + ' m';

        if (dist > 15) {
            connectionLine.bindTooltip(
                `<span style="font-weight:700; font-size:10px; color:#1e3a8a;">📏 ${formattedDist}</span>`,
                {
                    permanent: true,
                    direction: 'center',
                    className: 'map-dashed-distance-badge',
                }
            );

            // Fit bounds agar kedua titik terlihat nyaman
            const bounds = L.latLngBounds([
                [lat, lng],
                [sLat, sLng],
            ]);
            map.fitBounds(bounds, {
                padding: [42, 42],
                maxZoom: 17,
            });
        } else {
            map.setView([lat, lng], 17);
        }
    } else {
        map.setView([lat, lng], 17);
    }
}

function initMap() {
    const lat = Number(props.latitude);
    const lng = Number(props.longitude);
    if (!mapContainer.value || isNaN(lat) || isNaN(lng)) return;

    if (map) {
        map.remove();
        map = null;
    }

    map = L.map(mapContainer.value, {
        center: [lat, lng],
        zoom: 17,
        zoomControl: false,
        attributionControl: false,
    });

    applyGoogleTile();
    renderMapLayers();

    setTimeout(() => {
        if (map) {
            map.invalidateSize();
            if (hasSppgCoordinates.value) {
                const sLat = Number(props.sppgLatitude);
                const sLng = Number(props.sppgLongitude);
                const dist = map.distance([lat, lng], [sLat, sLng]);
                if (dist > 15) {
                    map.fitBounds(
                        [
                            [lat, lng],
                            [sLat, sLng],
                        ],
                        {
                            padding: [42, 42],
                            maxZoom: 17,
                        }
                    );
                }
            }
        }
    }, 250);
}

watch(
    () => [props.latitude, props.longitude, props.sppgLatitude, props.sppgLongitude],
    () => {
        if (map) {
            renderMapLayers();
        } else {
            nextTick(initMap);
        }
    }
);

onMounted(() => {
    nextTick(() => {
        initMap();
        if (mapContainer.value && typeof window !== 'undefined' && 'ResizeObserver' in window) {
            resizeObserver = new ResizeObserver(() => {
                if (map) {
                    map.invalidateSize();
                    if (hasSppgCoordinates.value) {
                        const sLat = Number(props.sppgLatitude);
                        const sLng = Number(props.sppgLongitude);
                        const lat = Number(props.latitude);
                        const lng = Number(props.longitude);
                        const dist = map.distance([lat, lng], [sLat, sLng]);
                        if (dist > 15) {
                            map.fitBounds(
                                [
                                    [lat, lng],
                                    [sLat, sLng],
                                ],
                                {
                                    padding: [42, 42],
                                    maxZoom: 17,
                                }
                            );
                        }
                    }
                }
            });
            resizeObserver.observe(mapContainer.value);
        }
    });
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div class="relative w-full rounded-2xl overflow-hidden border border-slate-300 shadow-xs animate-in fade-in zoom-in-95 duration-200">
        <!-- Container Peta Leaflet -->
        <div
            ref="mapContainer"
            :style="{ height: height, width: '100%' }"
            class="z-0 bg-slate-100"
        ></div>

        <!-- Legend Titik Anda & SPPG di Kiri Atas -->
        <div
            v-if="hasSppgCoordinates"
            class="absolute top-2 left-2 z-10 pointer-events-none"
        >
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/95 backdrop-blur-xs shadow-xs border border-slate-200 text-[10px] font-bold text-slate-700">
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 border border-white shadow-2xs shrink-0"></span>
                    <span>{{ legendLabel }}</span>
                </span>
                <span class="text-blue-500 font-bold tracking-widest text-[9px]">┄┄</span>
                <span class="flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 border border-white shadow-2xs shrink-0"></span>
                    <span>SPPG</span>
                </span>
            </div>
        </div>

        <!-- Tombol Ganti Tipe Peta (Satelit / Jalan) di Kanan Atas -->
        <div class="absolute top-2 right-2 z-10 pointer-events-auto">
            <button
                type="button"
                @click="toggleMapType"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/95 hover:bg-white text-slate-800 text-[10px] sm:text-[10.5px] font-bold shadow-xs border border-slate-200 transition-colors cursor-pointer"
            >
                <Layers class="h-3 w-3 text-slate-600" />
                <span>{{ mapType === 'roadmap' ? 'Satelit' : 'Jalan' }}</span>
            </button>
        </div>

        <!-- Floating Bar Koordinat & Google Maps Link di Bawah Peta -->
        <div class="absolute bottom-2 left-2 right-2 z-10 flex items-center justify-between gap-1.5 pointer-events-auto">
            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-900/85 backdrop-blur-xs text-[10px] sm:text-[10.5px] font-bold text-white shadow-xs border border-white/20 truncate">
                <span class="h-2 w-2 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span>
                <span class="font-mono truncate">{{ Number(latitude).toFixed(6) }}, {{ Number(longitude).toFixed(6) }}</span>
            </span>

            <a
                :href="`https://www.google.com/maps?q=${latitude},${longitude}`"
                target="_blank"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/95 hover:bg-white text-blue-700 text-[10px] sm:text-[10.5px] font-bold shadow-xs border border-slate-200 transition-colors shrink-0"
                title="Buka titik ini di Google Maps"
            >
                <ExternalLink class="h-3 w-3" />
                <span>Buka Peta</span>
            </a>
        </div>
    </div>
</template>

<style>
/* Reset css default leaflet controls jika ada */
.google-maps-pin {
    background: transparent !important;
    border: none !important;
}

.map-dashed-distance-badge {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1.5px solid #3b82f6 !important;
    border-radius: 9999px !important;
    padding: 2px 7px !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2) !important;
}

.map-dashed-distance-badge::before {
    display: none !important;
}
</style>
