<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import {
  Maximize2,
  User,
  Building2,
  Navigation,
  Car,
  Clock,
  Loader2,
} from 'lucide-vue-next';

// Fix leaflet default icon marker path issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

const props = defineProps({
  domisiliLat: {
    type: [Number, String],
    required: true,
  },
  domisiliLng: {
    type: [Number, String],
    required: true,
  },
  domisiliLabel: {
    type: String,
    default: 'Domisili Kepala SPPG',
  },
  domisiliAddress: {
    type: String,
    default: '',
  },
  unitLat: {
    type: [Number, String],
    required: true,
  },
  unitLng: {
    type: [Number, String],
    required: true,
  },
  unitLabel: {
    type: String,
    default: 'Unit SPPG',
  },
  unitAddress: {
    type: String,
    default: '',
  },
  height: {
    type: String,
    default: '450px',
  },
});

const mapContainer = ref(null);
let map = null;
let markerDomisili = null;
let markerUnit = null;
let straightPolyline = null;
let roadPolylineCasing = null;
let roadPolyline = null;
let roadDistanceMarker = null;
let circleDomisili = null;
let circleUnit = null;
let resizeObserver = null;

// Routing State
const isRoutingLoading = ref(false);
const roadDistance = ref(null); // in km
const roadDuration = ref(null); // in seconds
const roadCoordinates = ref([]);

// Haversine formula to calculate accurate straight distance in kilometers
const distanceKm = computed(() => {
  const lat1 = Number(props.domisiliLat);
  const lon1 = Number(props.domisiliLng);
  const lat2 = Number(props.unitLat);
  const lon2 = Number(props.unitLng);

  if (isNaN(lat1) || isNaN(lon1) || isNaN(lat2) || isNaN(lon2)) return 0;

  const R = 6371; // Earth radius in km
  const dLat = ((lat2 - lat1) * Math.PI) / 180;
  const dLon = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return Number((R * c).toFixed(2));
});

const formattedStraightDistance = computed(() => {
  if (distanceKm.value < 1) {
    return `${Math.round(distanceKm.value * 1000)} meter`;
  }
  return `${distanceKm.value} km`;
});

const formattedRoadDistance = computed(() => {
  if (roadDistance.value === null) return formattedStraightDistance.value;
  if (roadDistance.value < 1) {
    return `${Math.round(roadDistance.value * 1000)} meter`;
  }
  return `${roadDistance.value.toFixed(1)} km`;
});

const formattedDuration = computed(() => {
  if (!roadDuration.value) return null;
  const mins = Math.ceil(roadDuration.value / 60);
  if (mins < 60) {
    return `${mins} mnt`;
  }
  const hours = Math.floor(mins / 60);
  const remainingMins = mins % 60;
  return `${hours} jam ${remainingMins} mnt`;
});

// Custom Pins
const iconDomisili = L.divIcon({
  className: 'custom-domisili-marker',
  html: `
    <div style="position: relative; display: flex; flex-direction: column; align-items: center;">
      <div style="background-color: #2563eb; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4); border: 2.5px solid #ffffff;">
        <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
          <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
      </div>
      <div style="background: #1e3a8a; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 9999px; margin-top: 4px; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
        Kepala SPPG
      </div>
    </div>
  `,
  iconSize: [80, 56],
  iconAnchor: [40, 36],
  popupAnchor: [0, -36],
});

const iconUnit = L.divIcon({
  className: 'custom-unit-marker',
  html: `
    <div style="position: relative; display: flex; flex-direction: column; align-items: center;">
      <div style="background-color: #059669; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.4); border: 2.5px solid #ffffff;">
        <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
          <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
        </div>
      </div>
      <div style="background: #064e3b; color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 9999px; margin-top: 4px; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
        Unit SPPG
      </div>
    </div>
  `,
  iconSize: [80, 56],
  iconAnchor: [40, 36],
  popupAnchor: [0, -36],
});

function initMap() {
  if (!mapContainer.value) return;

  const lat1 = Number(props.domisiliLat);
  const lng1 = Number(props.domisiliLng);
  const lat2 = Number(props.unitLat);
  const lng2 = Number(props.unitLng);

  const midLat = (lat1 + lat2) / 2;
  const midLng = (lng1 + lng2) / 2;

  map = L.map(mapContainer.value, {
    zoomControl: true,
    attributionControl: false,
  }).setView([midLat, midLng], 14);

  // OpenStreetMap Layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);

  // Marker 1: Domisili
  markerDomisili = L.marker([lat1, lng1], { icon: iconDomisili })
    .addTo(map)
    .bindPopup(`
      <div style="padding: 4px; font-family: inherit;">
        <strong style="color: #2563eb; font-size: 13px; display: block; margin-bottom: 2px;">📍 ${props.domisiliLabel}</strong>
        <p style="margin: 0; font-size: 11px; color: #475569;">${props.domisiliAddress || 'Lokasi domisili penanggung jawab'}</p>
        <span style="font-family: monospace; font-size: 10px; color: #64748b; margin-top: 4px; display: block;">${lat1.toFixed(6)}, ${lng1.toFixed(6)}</span>
      </div>
    `);

  // Marker 2: Unit SPPG
  markerUnit = L.marker([lat2, lng2], { icon: iconUnit })
    .addTo(map)
    .bindPopup(`
      <div style="padding: 4px; font-family: inherit;">
        <strong style="color: #059669; font-size: 13px; display: block; margin-bottom: 2px;">🏢 ${props.unitLabel}</strong>
        <p style="margin: 0; font-size: 11px; color: #475569;">${props.unitAddress || 'Lokasi operasional unit SPPG'}</p>
        <span style="font-family: monospace; font-size: 10px; color: #64748b; margin-top: 4px; display: block;">${lat2.toFixed(6)}, ${lng2.toFixed(6)}</span>
      </div>
    `);

  // Garis Halus Geodesik (Radius Lurus)
  straightPolyline = L.polyline(
    [
      [lat1, lng1],
      [lat2, lng2],
    ],
    {
      color: '#818cf8',
      weight: 2,
      opacity: 0.6,
      dashArray: '6, 6',
      lineCap: 'round',
      lineJoin: 'round',
    }
  ).addTo(map);

  straightPolyline.bindPopup(`
    <div style="text-align: center; padding: 4px;">
      <span style="font-size: 11px; color: #64748b; display: block;">Jarak Garis Lurus (Geodesik)</span>
      <strong style="font-size: 13px; color: #4f46e5;">📏 ${formattedStraightDistance.value}</strong>
    </div>
  `);

  // Soft Radius Circles
  circleDomisili = L.circle([lat1, lng1], {
    radius: 300,
    color: '#2563eb',
    weight: 1,
    fillColor: '#3b82f6',
    fillOpacity: 0.08,
  }).addTo(map);

  circleUnit = L.circle([lat2, lng2], {
    radius: 300,
    color: '#059669',
    weight: 1,
    fillColor: '#10b981',
    fillOpacity: 0.08,
  }).addTo(map);

  // Fetch and draw real road route
  fetchDrivingRoute();

  // Fit bounds agar kedua titik dan rute terlihat sempurna
  fitBounds();

  // ResizeObserver untuk memastikan peta ter-render utuh saat tab aktif
  if (window.ResizeObserver && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      if (map) {
        map.invalidateSize();
      }
    });
    resizeObserver.observe(mapContainer.value);
  }

  setTimeout(invalidateSize, 100);
  setTimeout(invalidateSize, 300);
}

// Fetch Driving Route from OSRM (Open Source Routing Machine)
async function fetchDrivingRoute() {
  const lat1 = Number(props.domisiliLat);
  const lng1 = Number(props.domisiliLng);
  const lat2 = Number(props.unitLat);
  const lng2 = Number(props.unitLng);

  if (isNaN(lat1) || isNaN(lng1) || isNaN(lat2) || isNaN(lng2)) return;

  isRoutingLoading.value = true;
  try {
    const url = `https://router.project-osrm.org/route/v1/driving/${lng1},${lat1};${lng2},${lat2}?overview=full&geometries=geojson`;
    const response = await fetch(url);
    if (!response.ok) throw new Error('OSRM API request failed');
    const data = await response.json();

    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
      const primaryRoute = data.routes[0];
      const coords = primaryRoute.geometry.coordinates.map(([lng, lat]) => [lat, lng]);
      roadCoordinates.value = coords;
      roadDistance.value = primaryRoute.distance / 1000; // in km
      roadDuration.value = primaryRoute.duration; // in seconds

      renderRoadRoute(coords);
    }
  } catch (err) {
    console.warn('Gagal memuat rute jalan riil OSRM:', err);
  } finally {
    isRoutingLoading.value = false;
  }
}

// Render the real curvy road path with Google Maps style multi-layer polyline
function renderRoadRoute(coords) {
  if (!map || coords.length === 0) return;

  if (roadPolylineCasing) map.removeLayer(roadPolylineCasing);
  if (roadPolyline) map.removeLayer(roadPolyline);
  if (roadDistanceMarker) map.removeLayer(roadDistanceMarker);

  // Outer Casing (Border) for route line
  roadPolylineCasing = L.polyline(coords, {
    color: '#1d4ed8',
    weight: 7,
    opacity: 0.85,
    lineCap: 'round',
    lineJoin: 'round',
  }).addTo(map);

  // Inner Road Polyline (Vibrant Blue Google Maps style)
  roadPolyline = L.polyline(coords, {
    color: '#3b82f6',
    weight: 5,
    opacity: 0.95,
    lineCap: 'round',
    lineJoin: 'round',
  }).addTo(map);

  const durationText = formattedDuration.value ? ` • ${formattedDuration.value}` : '';

  roadPolyline.bindPopup(`
    <div style="text-align: center; padding: 5px; font-family: inherit;">
      <span style="font-size: 11px; color: #64748b; display: block;">Rute Jalan Riil (Navigasi)</span>
      <strong style="font-size: 14px; color: #1d4ed8;">🚗 ${formattedRoadDistance.value}${durationText}</strong>
    </div>
  `);

  // Floating midpoint label along the road route
  const midIndex = Math.floor(coords.length / 2);
  const midCoord = coords[midIndex] || [(coords[0][0] + coords[coords.length - 1][0]) / 2, (coords[0][1] + coords[coords.length - 1][1]) / 2];

  const iconMidpoint = L.divIcon({
    className: 'custom-distance-badge',
    html: `
      <div style="background: #ffffff; color: #1e3a8a; border: 2px solid #2563eb; border-radius: 9999px; padding: 4px 10px; font-size: 11px; font-weight: 800; white-space: nowrap; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); display: flex; align-items: center; gap: 4px;">
        <span style="color: #2563eb;">🚗</span>
        <span>${formattedRoadDistance.value}</span>
        ${formattedDuration.value ? `<span style="color: #64748b; font-weight: 600; font-size: 10px;">(${formattedDuration.value})</span>` : ''}
      </div>
    `,
    iconSize: [110, 28],
    iconAnchor: [55, 14],
  });

  roadDistanceMarker = L.marker(midCoord, {
    icon: iconMidpoint,
    interactive: false,
  }).addTo(map);

  fitBounds();
}

function fitBounds() {
  if (!map) return;
  if (roadCoordinates.value.length > 0) {
    map.fitBounds(roadCoordinates.value, {
      padding: [60, 60],
      maxZoom: 16,
    });
  } else {
    const lat1 = Number(props.domisiliLat);
    const lng1 = Number(props.domisiliLng);
    const lat2 = Number(props.unitLat);
    const lng2 = Number(props.unitLng);

    map.fitBounds(
      [
        [lat1, lng1],
        [lat2, lng2],
      ],
      {
        padding: [60, 60],
        maxZoom: 16,
      }
    );
  }
}

function invalidateSize() {
  if (map) {
    map.invalidateSize();
  }
}

defineExpose({
  refresh: () => {
    invalidateSize();
    fitBounds();
  },
});

onMounted(() => {
  initMap();
});

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
  }
  if (map) {
    map.remove();
  }
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
    <!-- Left Column: Map Container (lg:col-span-7 xl:col-span-8) -->
    <div class="lg:col-span-7 xl:col-span-8 flex flex-col">
      <div class="relative w-full h-full min-h-[400px] lg:min-h-[500px] rounded-xl border border-slate-200 shadow-xs overflow-hidden bg-slate-100 flex-1">
        <!-- Floating Top Right Action & Info -->
        <div
          class="absolute top-3 right-3 flex items-center gap-2 select-none"
          style="z-index: 1000;"
        >
          <!-- Floating Road Distance Pill -->
          <div class="bg-white/95 backdrop-blur-xs border border-blue-200 text-blue-950 px-3 py-1.5 rounded-lg shadow-md flex items-center gap-2 text-xs font-bold">
            <Loader2 v-if="isRoutingLoading" class="h-4 w-4 text-blue-600 animate-spin" />
            <Car v-else class="h-4 w-4 text-blue-600 shrink-0" />
            <span>Rute Jalan:</span>
            <span class="bg-blue-600 text-white px-2 py-0.5 rounded-md font-mono text-[11px]">
              {{ formattedRoadDistance }}
            </span>
            <span v-if="formattedDuration" class="text-blue-700 font-semibold text-[11px] hidden sm:inline">
              ~{{ formattedDuration }}
            </span>
          </div>

          <!-- Reset Center Button -->
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="fitBounds"
            className="h-8 bg-white/95 hover:bg-white shadow-md text-xs flex items-center gap-1.5 border-slate-300 font-semibold cursor-pointer text-slate-800"
            title="Pusatkan Peta ke Rute Penuh"
          >
            <Maximize2 class="h-3.5 w-3.5 text-primary" />
            <span>Pusatkan</span>
          </Button>
        </div>

        <!-- Leaflet Map Container -->
        <div ref="mapContainer" class="w-full h-full min-h-[400px] lg:min-h-[500px] relative z-0"></div>
      </div>
    </div>

    <!-- Right Column: 3 Stacked Cards (lg:col-span-5 xl:col-span-4) -->
    <div class="lg:col-span-5 xl:col-span-4 flex flex-col justify-between gap-4">
      <!-- Card 1: Domisili Kepala SPPG -->
      <div class="p-4 rounded-xl bg-white border border-blue-100 shadow-2xs space-y-2.5 flex-1 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <div class="h-7 w-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <User class="h-4 w-4" />
              </div>
              <span class="text-xs font-bold text-slate-900">Domisili Kepala SPPG</span>
            </div>
            <Badge variant="info" className="text-[10px] py-0">Titik Awal</Badge>
          </div>
          <p class="text-xs text-slate-600 font-medium line-clamp-2 leading-relaxed">
            {{ domisiliAddress || 'Alamat domisili' }}
          </p>
        </div>
        <div class="pt-2 text-[11px] font-mono text-slate-500 flex justify-between border-t border-slate-100">
          <span>Koordinat:</span>
          <span class="font-semibold text-slate-700">{{ Number(domisiliLat).toFixed(6) }}, {{ Number(domisiliLng).toFixed(6) }}</span>
        </div>
      </div>

      <!-- Card 2: Unit SPPG -->
      <div class="p-4 rounded-xl bg-white border border-emerald-100 shadow-2xs space-y-2.5 flex-1 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <div class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <Building2 class="h-4 w-4" />
              </div>
              <span class="text-xs font-bold text-slate-900">Lokasi Unit SPPG</span>
            </div>
            <Badge variant="success" className="text-[10px] py-0">Titik Tujuan</Badge>
          </div>
          <p class="text-xs text-slate-600 font-medium line-clamp-2 leading-relaxed">
            {{ unitAddress || 'Alamat operasional unit SPPG' }}
          </p>
        </div>
        <div class="pt-2 text-[11px] font-mono text-slate-500 flex justify-between border-t border-slate-100">
          <span>Koordinat:</span>
          <span class="font-semibold text-slate-700">{{ Number(unitLat).toFixed(6) }}, {{ Number(unitLng).toFixed(6) }}</span>
        </div>
      </div>

      <!-- Card 3: Analisis Rute & Jarak Riil -->
      <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50/80 via-indigo-50/40 to-white border border-blue-200 shadow-2xs space-y-3 flex-1 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <div class="h-7 w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                <Navigation class="h-4 w-4" />
              </div>
              <span class="text-xs font-bold text-blue-950">Analisis Rute & Jarak</span>
            </div>
            <span class="text-[10px] font-bold text-blue-700 bg-blue-100/90 px-2 py-0.5 rounded-full flex items-center gap-1">
              <Car class="h-3 w-3" />
              Jalan Riil
            </span>
          </div>

          <!-- Metrik 1: Rute Jalan Riil -->
          <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs mb-2">
            <div class="flex items-center justify-between">
              <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-blue-700">{{ formattedRoadDistance }}</span>
                <span class="text-xs text-slate-500 font-medium">via rute jalan</span>
              </div>
              <div v-if="formattedDuration" class="flex items-center gap-1 text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-1 rounded-md">
                <Clock class="h-3.5 w-3.5 text-blue-600" />
                <span>~{{ formattedDuration }}</span>
              </div>
            </div>
          </div>

          <!-- Metrik 2: Jarak Lurus (Geodesik) -->
          <div class="flex items-center justify-between text-xs text-slate-600 px-1 py-0.5">
            <span class="flex items-center gap-1">
              <span class="inline-block w-2.5 h-0.5 border-b-2 border-dashed border-indigo-500"></span>
              Jarak Garis Lurus (Radius):
            </span>
            <span class="font-bold text-indigo-700">{{ formattedStraightDistance }}</span>
          </div>

          <p class="text-[11px] text-slate-500 leading-snug mt-2">
            Garis biru mengikuti liku jalan raya sesungguhnya (rute berkendara), sedangkan garis putus-putus ungu menunjukkan radius lurus pemindahan.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 10px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  padding: 4px;
}
:deep(.custom-distance-badge) {
  pointer-events: none;
}
</style>
