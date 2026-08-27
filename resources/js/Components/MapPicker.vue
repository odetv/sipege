<script setup>
import { onMounted, ref, watch, onBeforeUnmount, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin, Navigation, Crosshair } from 'lucide-vue-next';
import Button from '@/Components/ui/Button.vue';

// Fix leaflet default icon marker path issue in bundlers
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

const props = defineProps({
  latitude: {
    type: [Number, String],
    default: null,
  },
  longitude: {
    type: [Number, String],
    default: null,
  },
  defaultCenter: {
    type: Array,
    default: () => [-8.1568224, 115.0972345], // Default viewport (e.g. Bali / Sukasada)
  },
  defaultZoom: {
    type: Number,
    default: 13,
  },
  label: {
    type: String,
    default: 'Pilih Titik Lokasi Peta',
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  height: {
    type: String,
    default: '260px',
  },
});

const emit = defineEmits(['update:latitude', 'update:longitude']);

const mapContainer = ref(null);
let map = null;
let marker = null;
let resizeObserver = null;
const isLocating = ref(false);
const locationError = ref('');

function isValidCoord(val) {
  return val !== null && val !== undefined && val !== '' && !isNaN(Number(val));
}

const latVal = ref(isValidCoord(props.latitude) ? Number(props.latitude) : null);
const lngVal = ref(isValidCoord(props.longitude) ? Number(props.longitude) : null);

const hasPoint = computed(() => {
  return latVal.value !== null && lngVal.value !== null;
});

const customIcon = L.divIcon({
  className: 'custom-map-marker',
  html: `
    <div style="background-color: #2563eb; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3); border: 2px solid #ffffff;">
      <div style="width: 10px; height: 10px; background-color: #ffffff; border-radius: 50%; transform: rotate(45deg);"></div>
    </div>
  `,
  iconSize: [32, 32],
  iconAnchor: [16, 32],
  popupAnchor: [0, -32],
});

function invalidate() {
  if (
    map &&
    map._container &&
    map._mapPane &&
    mapContainer.value &&
    mapContainer.value.offsetWidth > 0 &&
    mapContainer.value.offsetHeight > 0
  ) {
    try {
      map.invalidateSize();
    } catch (e) {}
  }
}

function setOrUpdateMarker(lat, lng, shouldPan = false) {
  if (!map) return;
  const numLat = Number(lat);
  const numLng = Number(lng);

  if (marker) {
    marker.setLatLng([numLat, numLng]);
    if (marker.dragging) {
      if (props.readonly) {
        marker.dragging.disable();
      } else {
        marker.dragging.enable();
      }
    }
  } else {
    marker = L.marker([numLat, numLng], {
      icon: customIcon,
      draggable: !props.readonly,
    }).addTo(map);

    marker.on('dragend', (event) => {
      if (props.readonly) return;
      const position = event.target.getLatLng();
      updateCoordinates(position.lat, position.lng);
    });
  }

  if (shouldPan) {
    map.panTo([numLat, numLng]);
  }
}

function removeMarker() {
  if (marker && map) {
    map.removeLayer(marker);
    marker = null;
  }
  latVal.value = null;
  lngVal.value = null;
}

function initMap() {
  if (!mapContainer.value) return;

  const initialLat = isValidCoord(props.latitude) ? Number(props.latitude) : props.defaultCenter[0];
  const initialLng = isValidCoord(props.longitude) ? Number(props.longitude) : props.defaultCenter[1];

  map = L.map(mapContainer.value, {
    zoomControl: true,
    attributionControl: false,
  }).setView([initialLat, initialLng], props.defaultZoom);

  // Google Maps Standard Road Map Layer
  L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 20,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
  }).addTo(map);

  // Always bind click event on map; gate it by !props.readonly inside the callback
  map.on('click', (event) => {
    if (props.readonly) return;
    const { lat, lng } = event.latlng;
    setOrUpdateMarker(lat, lng);
    updateCoordinates(lat, lng);
  });

  // If initial coordinates exist, place the marker
  if (isValidCoord(props.latitude) && isValidCoord(props.longitude)) {
    setOrUpdateMarker(props.latitude, props.longitude);
  }

  // Auto-resize when element becomes visible or changes dimension
  if (window.ResizeObserver && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      invalidate();
    });
    resizeObserver.observe(mapContainer.value);
  }

  // Periodic invalidate initial checks
  setTimeout(invalidate, 100);
  setTimeout(invalidate, 300);
  setTimeout(invalidate, 600);
  setTimeout(invalidate, 1000);
}

function updateCoordinates(lat, lng) {
  const roundedLat = Number(Number(lat).toFixed(7));
  const roundedLng = Number(Number(lng).toFixed(7));
  latVal.value = roundedLat;
  lngVal.value = roundedLng;
  emit('update:latitude', roundedLat);
  emit('update:longitude', roundedLng);
}

function getCurrentLocation() {
  locationError.value = '';
  if (!navigator.geolocation) {
    locationError.value = 'Browser tidak mendukung geolokasi.';
    return;
  }

  isLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      setOrUpdateMarker(lat, lng, true);
      updateCoordinates(lat, lng);
      if (map) {
        map.setView([lat, lng], 16);
        map.invalidateSize();
      }
      isLocating.value = false;
    },
    (err) => {
      console.warn('Geolocation error:', err);
      isLocating.value = false;
      locationError.value = 'Gagal mengakses GPS. Pastikan izin lokasi aktif.';
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

function centerToMarker() {
  if (map) {
    if (marker) {
      map.setView(marker.getLatLng(), 15);
    } else {
      map.setView([props.defaultCenter[0], props.defaultCenter[1]], props.defaultZoom);
    }
    map.invalidateSize();
  }
}

watch(
  () => props.readonly,
  (newReadonly) => {
    if (marker && marker.dragging) {
      if (newReadonly) {
        marker.dragging.disable();
      } else {
        marker.dragging.enable();
      }
    }
    if (map) {
      map.invalidateSize();
    }
  }
);

watch(
  () => [props.latitude, props.longitude],
  ([newLat, newLng]) => {
    if (isValidCoord(newLat) && isValidCoord(newLng)) {
      latVal.value = Number(newLat);
      lngVal.value = Number(newLng);
      if (map) {
        setOrUpdateMarker(newLat, newLng);
      }
    } else {
      removeMarker();
    }
  }
);

defineExpose({
  refresh: invalidate,
  center: centerToMarker,
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
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
        <MapPin class="h-3.5 w-3.5 text-primary" />
        <span>{{ label }}</span>
      </div>

      <div class="flex items-center gap-2">
        <span
          v-if="hasPoint"
          class="text-[11px] font-mono bg-emerald-50 border border-emerald-200 text-emerald-800 px-2 py-0.5 rounded font-semibold"
        >
          {{ latVal.toFixed(6) }}, {{ lngVal.toFixed(6) }}
        </span>
        <span
          v-else
          class="text-[11px] font-medium bg-amber-50 border border-amber-200 text-amber-800 px-2 py-0.5 rounded"
        >
          Belum ditentukan (Klik peta)
        </span>

        <Button
          v-if="!readonly"
          type="button"
          variant="outline"
          size="sm"
          @click="getCurrentLocation"
          :disabled="isLocating"
          className="h-7 px-2 text-xs flex items-center gap-1 bg-white hover:bg-slate-50"
        >
          <Crosshair class="h-3.5 w-3.5 text-primary animate-pulse" v-if="isLocating" />
          <Navigation class="h-3.5 w-3.5 text-primary" v-else />
          <span>{{ isLocating ? 'Mencari...' : 'Lokasi Saya' }}</span>
        </Button>
      </div>
    </div>

    <!-- Map container with floating controls -->
    <div class="relative w-full rounded-lg border border-slate-200 shadow-inner overflow-hidden">
      <!-- Floating Center Button on Top-Right -->
      <div class="absolute top-2.5 right-2.5 select-none" style="z-index: 400;">
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="centerToMarker"
          className="h-7 px-2.5 bg-white/95 hover:bg-white shadow-xs text-xs flex items-center gap-1 border-slate-300 font-semibold cursor-pointer text-slate-700"
          title="Pusatkan ke Titik Lokasi"
        >
          <Crosshair class="h-3.5 w-3.5 text-primary" />
          <span>Pusatkan</span>
        </Button>
      </div>

      <div
        ref="mapContainer"
        class="w-full relative z-0"
        :style="{ height: height }"
      ></div>
    </div>

    <p v-if="locationError" class="text-xs text-destructive font-medium">
      {{ locationError }}
    </p>

    <p v-if="!readonly" class="text-[11px] text-slate-500 flex items-center gap-1">
      <span>* Klik pada area peta atau gunakan tombol "Lokasi Saya" untuk menentukan pin poin koordinat Anda.</span>
    </p>
  </div>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>
