<script setup>
import {
    ref,
    onMounted,
    onBeforeUnmount,
    computed,
    watch,
    nextTick,
} from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import Button from "@/Components/ui/Button.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Maximize2,
    LocateFixed,
    User,
    Building2,
    Navigation,
    Car,
    Clock,
    Loader2,
    School,
    Users,
    MapPin,
    Sparkles,
    Utensils,
    Layers,
    Filter,
    Eye,
    EyeOff,
    Check,
    RotateCcw,
    Route,
    Spline,
    X,
    Palette,
} from "lucide-vue-next";

// Fix leaflet default icon marker path issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl:
        "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
    iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
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
        default: "Lokasi Kepala SPPG",
    },
    domisiliAddress: {
        type: String,
        default: "",
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
        default: "Unit SPPG",
    },
    unitAddress: {
        type: String,
        default: "",
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    height: {
        type: String,
        default: "540px",
    },
    isFullscreen: {
        type: Boolean,
        default: false,
    },
});

const mapContainer = ref(null);
const filterDropdownRef = ref(null);
const isFilterDropdownOpen = ref(false);

let map = null;
let domisiliLayerGroup = null;
let unitLayerGroup = null;
let circleRadiusLayerGroup = null;
let roadReachabilityLayerGroup = null;
let routeLayerGroup = null;
let kelompokLayerGroup = null;

let markerDomisili = null;
let markerUnit = null;
let straightPolyline = null;
let straightDistanceMarker = null;
let roadPolylineCasing = null;
let roadPolyline = null;
let roadDistanceMarker = null;
let circleDomisili = null;
let circleUnit = null;
let baseTileLayer = null;
let resizeObserver = null;

const kelompokMarkerMap = new Map();

// ================= MAP THEMES (BASEMAP) =================
const mapThemes = [
    {
        id: "google_roadmap",
        name: "Google Jalan",
        shortName: "Jalan",
        icon: "🗺️",
        url: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
        subdomains: ["mt0", "mt1", "mt2", "mt3"],
        maxZoom: 20,
    },
    {
        id: "google_hybrid",
        name: "Google Satelit",
        shortName: "Satelit",
        icon: "🛰️",
        url: "https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}",
        subdomains: ["mt0", "mt1", "mt2", "mt3"],
        maxZoom: 20,
    },
    {
        id: "google_terrain",
        name: "Google Medan",
        shortName: "Medan",
        icon: "⛰️",
        url: "https://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}",
        subdomains: ["mt0", "mt1", "mt2", "mt3"],
        maxZoom: 20,
    },
    {
        id: "carto_light",
        name: "Terang Bersih",
        shortName: "Terang",
        icon: "☀️",
        url: "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png",
        subdomains: ["a", "b", "c", "d"],
        maxZoom: 20,
    },
    {
        id: "carto_dark",
        name: "Mode Gelap",
        shortName: "Gelap",
        icon: "🌙",
        url: "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",
        subdomains: ["a", "b", "c", "d"],
        maxZoom: 20,
    },
    {
        id: "osm",
        name: "OpenStreetMap",
        shortName: "OSM",
        icon: "🌐",
        url: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        subdomains: ["a", "b", "c"],
        maxZoom: 19,
    },
];

const currentMapTheme = ref("osm");

function switchMapTheme(themeId) {
    if (!map) return;
    const theme = mapThemes.find((t) => t.id === themeId);
    if (!theme) return;
    currentMapTheme.value = themeId;

    if (baseTileLayer) {
        map.removeLayer(baseTileLayer);
    }

    baseTileLayer = L.tileLayer(theme.url, {
        maxZoom: theme.maxZoom,
        subdomains: theme.subdomains || ["a", "b", "c"],
        attribution: false,
    }).addTo(map);

    baseTileLayer.bringToBack();
}

// ================= ROUTE MODE STATE =================
// 'road' = Jalan Riil (Navigasi / Driving Route OSRM)
// 'straight' = Garis Tegak Lurus / Geodesik
const routeMode = ref("straight");

// ================= FILTER STATE =================
// Default: munculkan semua koneksi & titik (Radius Bulat & Radius Riil default MATI)
const filterSettings = ref({
    showUnit: true,
    showDomisili: true,
    showCircleRadius: false,
    showRoadReachability: false,
    showRoute: true,
    showConnections: true,
    showPenerima: true,
    showRouteDistBadge: true,
    showPenerimaDistBadge: true,
});

// Granular per-item hidden set (hide specific penerima manfaat)
const hiddenKelompokIds = ref(new Set());

// Category filters
const selectedKategoriFilters = ref([]);

// Routing State (Kepala SPPG -> Unit SPPG)
const isRoutingLoading = ref(false);
const roadDistance = ref(null); // in km
const roadDuration = ref(null); // in seconds
const roadCoordinates = ref([]);

// Multi-Routing State (Unit SPPG -> Setiap Penerima Manfaat)
const kelompokRoadRoutes = ref({}); // key: kelompokId => { coords, distance, duration }
const isFetchingKelompokRoutes = ref(false);

// Reachability State: Blok Wilayah 6 KM & Jaringan Jalan Riil
const sppgReachabilityData = ref({
    branches: [],
    boundaryPolygon: [],
    isLoading: false,
});

// Haversine formula calculation helper
function calculateDistance(lat1, lon1, lat2, lon2) {
    const nLat1 = Number(lat1);
    const nLon1 = Number(lon1);
    const nLat2 = Number(lat2);
    const nLon2 = Number(lon2);

    if (isNaN(nLat1) || isNaN(nLon1) || isNaN(nLat2) || isNaN(nLon2)) return 0;

    const R = 6371; // Earth radius in km
    const dLat = ((nLat2 - nLat1) * Math.PI) / 180;
    const dLon = ((nLon2 - nLon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((nLat1 * Math.PI) / 180) *
            Math.cos((nLat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Number((R * c).toFixed(2));
}

function formatDist(km) {
    if (km < 1) {
        return `${Math.round(km * 1000)} m`;
    }
    return `${km.toFixed(1)} km`;
}

function formatDurationHelper(seconds) {
    if (!seconds) return null;
    const mins = Math.ceil(seconds / 60);
    if (mins < 60) {
        return `${mins} mnt`;
    }
    const hours = Math.floor(mins / 60);
    const remainingMins = mins % 60;
    return `${hours} jam ${remainingMins} mnt`;
}

// Distance from Domisili to Unit SPPG
const distanceKm = computed(() => {
    return calculateDistance(
        props.domisiliLat,
        props.domisiliLng,
        props.unitLat,
        props.unitLng,
    );
});

const formattedStraightDistance = computed(() => {
    return formatDist(distanceKm.value);
});

const formattedRoadDistance = computed(() => {
    if (roadDistance.value === null) return formattedStraightDistance.value;
    return formatDist(roadDistance.value);
});

const formattedDuration = computed(() => {
    return formatDurationHelper(roadDuration.value);
});

// Summary totals of connected penerima manfaat
const validKelompokList = computed(() => {
    if (!Array.isArray(props.kelompokList)) return [];
    return props.kelompokList.filter(
        (k) =>
            k &&
            k.latitude &&
            k.longitude &&
            !isNaN(Number(k.latitude)) &&
            !isNaN(Number(k.longitude)),
    );
});

// Unique categories present
const uniqueKategoriList = computed(() => {
    const categories = validKelompokList.value
        .map((k) => k.kategori)
        .filter(Boolean);
    return Array.from(new Set(categories));
});

// Initialize selected categories to all categories
watch(
    uniqueKategoriList,
    (newKats) => {
        if (newKats.length > 0 && selectedKategoriFilters.value.length === 0) {
            selectedKategoriFilters.value = [...newKats];
        }
    },
    { immediate: true },
);

function isKelompokVisible(kelompok) {
    if (!filterSettings.value.showPenerima) return false;
    const id = kelompok.id || kelompok.uid;
    if (hiddenKelompokIds.value.has(id)) return false;
    if (selectedKategoriFilters.value.length > 0 && kelompok.kategori) {
        if (!selectedKategoriFilters.value.includes(kelompok.kategori))
            return false;
    }
    return true;
}

const visibleKelompokList = computed(() => {
    return validKelompokList.value.filter((k) => isKelompokVisible(k));
});

const totalPenerimaManfaat = computed(() => {
    return visibleKelompokList.value.reduce(
        (sum, k) => sum + (Number(k.total_penerima) || 0),
        0,
    );
});

const totalPorsiKecil = computed(() => {
    return visibleKelompokList.value.reduce(
        (sum, k) => sum + (Number(k.total_porsi_kecil) || 0),
        0,
    );
});

const totalPorsiBesar = computed(() => {
    return visibleKelompokList.value.reduce(
        (sum, k) => sum + (Number(k.total_porsi_besar) || 0),
        0,
    );
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterSettings.value.showUnit) count++;
    if (filterSettings.value.showDomisili) count++;
    if (filterSettings.value.showCircleRadius) count++;
    if (filterSettings.value.showRoadReachability) count++;
    if (filterSettings.value.showRoute) count++;
    if (filterSettings.value.showConnections) count++;
    if (filterSettings.value.showPenerima) count++;
    if (filterSettings.value.showRouteDistBadge) count++;
    if (filterSettings.value.showPenerimaDistBadge) count++;
    return count;
});

const isAllFiltersSelected = computed({
    get() {
        return activeFilterCount.value === 9;
    },
    set(val) {
        toggleAllFilters(val);
    },
});

function toggleAllFilters(enableAll) {
    filterSettings.value.showUnit = enableAll;
    filterSettings.value.showDomisili = enableAll;
    filterSettings.value.showCircleRadius = enableAll;
    filterSettings.value.showRoadReachability = enableAll;
    filterSettings.value.showRoute = enableAll;
    filterSettings.value.showConnections = enableAll;
    filterSettings.value.showPenerima = enableAll;
    filterSettings.value.showRouteDistBadge = enableAll;
    filterSettings.value.showPenerimaDistBadge = enableAll;
    if (enableAll) {
        hiddenKelompokIds.value = new Set();
        selectedKategoriFilters.value = [...uniqueKategoriList.value];
    }
}

function toggleSingleKelompok(id) {
    const newSet = new Set(hiddenKelompokIds.value);
    if (newSet.has(id)) {
        newSet.delete(id);
    } else {
        newSet.add(id);
    }
    hiddenKelompokIds.value = newSet;
}

function toggleKategoriFilter(kat) {
    const idx = selectedKategoriFilters.value.indexOf(kat);
    if (idx > -1) {
        selectedKategoriFilters.value.splice(idx, 1);
    } else {
        selectedKategoriFilters.value.push(kat);
    }
}

// Custom Pins
function createDomisiliIcon(nama) {
    const text = nama || props.domisiliLabel || "-";
    return L.divIcon({
        className: "custom-domisili-marker",
        html: `
        <div style="position: relative; display: flex; flex-direction: column; align-items: center; width: max-content; transform: translate(-50%, -36px);">
          <div style="background-color: #2563eb; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4); border: 2.5px solid #ffffff;">
            <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
              <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
          </div>
          <div style="background: #1e3a8a; text-align: center; color: white; font-size: 10px; font-weight: 700; padding: 2.5px 8px; border-radius: 9999px; margin-top: 4px; white-space: nowrap; box-shadow: 0 2px 5px rgba(0,0,0,0.25);">
            Kepala SPPG
            <br/>
            ${text}
          </div>
        </div>
      `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
        popupAnchor: [0, -36],
    });
}

function createUnitIcon(nama) {
    const text = nama || props.unitLabel || "Unit SPPG";
    return L.divIcon({
        className: "custom-unit-marker",
        html: `
        <div style="position: relative; display: flex; flex-direction: column; align-items: center; width: max-content; transform: translate(-50%, -38px);">
          <div style="background-color: #059669; width: 38px; height: 38px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.5); border: 2.5px solid #ffffff;">
            <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
              <svg style="width: 18px; height: 18px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
          </div>
          <div style="background: #064e3b; color: white; font-size: 10px; font-weight: 800; padding: 2.5px 8px; border-radius: 9999px; margin-top: 4px; white-space: nowrap; box-shadow: 0 2px 5px rgba(0,0,0,0.25);">
            ${text}
          </div>
        </div>
      `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
        popupAnchor: [0, -38],
    });
}

function createPenerimaIcon(nama, kategori) {
    const isPosyandu = kategori === "Posyandu";
    const bgColor = isPosyandu ? "#e11d48" : "#d97706";
    const labelBg = isPosyandu ? "#881337" : "#78350f";

    return L.divIcon({
        className: "custom-penerima-marker",
        html: `
      <div style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; width: max-content; transform: translate(-50%, -34px);">
        <div style="background-color: ${bgColor}; width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.28); border: 2px solid #ffffff;">
          <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
            <svg style="width: 15px; height: 15px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
          </div>
        </div>
        <div style="background: ${labelBg}; color: white; font-size: 9.5px; font-weight: 800; padding: 2.5px 8px; border-radius: 9999px; margin-top: 3px; white-space: nowrap; box-shadow: 0 2px 5px rgba(0,0,0,0.25);">
          ${nama || "Penerima"}
        </div>
      </div>
    `,
        iconSize: [0, 0],
        iconAnchor: [0, 0],
        popupAnchor: [0, -34],
    });
}

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

    const activeTheme =
        mapThemes.find((t) => t.id === currentMapTheme.value) || mapThemes[0];
    baseTileLayer = L.tileLayer(activeTheme.url, {
        maxZoom: activeTheme.maxZoom,
        subdomains: activeTheme.subdomains,
        attribution: false,
    }).addTo(map);

    // Layer Groups for clean toggling
    domisiliLayerGroup = L.layerGroup().addTo(map);
    unitLayerGroup = L.layerGroup().addTo(map);
    circleRadiusLayerGroup = L.layerGroup();
    roadReachabilityLayerGroup = L.layerGroup();
    routeLayerGroup = L.layerGroup().addTo(map);
    kelompokLayerGroup = L.layerGroup().addTo(map);

    // If defaults are enabled, add to map
    if (filterSettings.value.showCircleRadius)
        circleRadiusLayerGroup.addTo(map);
    if (filterSettings.value.showRoadReachability)
        roadReachabilityLayerGroup.addTo(map);

    // 1. Domisili Layers
    markerDomisili = L.marker([lat1, lng1], {
        icon: createDomisiliIcon(props.domisiliLabel),
    }).bindPopup(`
      <div style="padding: 4px; font-family: inherit;">
        <strong style="color: #2563eb; font-size: 13px; display: block; margin-bottom: 2px;">📍 ${props.domisiliLabel}</strong>
        <p style="margin: 0; font-size: 11px; color: #475569;">${props.domisiliAddress || "Lokasi domisili penanggung jawab"}</p>
        <span style="font-family: monospace; font-size: 10px; color: #64748b; margin-top: 4px; display: block;">${lat1.toFixed(6)}, ${lng1.toFixed(6)}</span>
      </div>
    `);
    domisiliLayerGroup.addLayer(markerDomisili);

    circleDomisili = L.circle([lat1, lng1], {
        radius: 300,
        color: "#2563eb",
        weight: 1,
        fillColor: "#3b82f6",
        fillOpacity: 0.08,
    });
    domisiliLayerGroup.addLayer(circleDomisili);

    // 2. Unit SPPG Layers
    markerUnit = L.marker([lat2, lng2], {
        icon: createUnitIcon(props.unitLabel),
    }).bindPopup(`
      <div style="padding: 4px; font-family: inherit;">
        <strong style="color: #059669; font-size: 13px; display: block; margin-bottom: 2px;">🏢 ${props.unitLabel} (Pusat SPPG)</strong>
        <p style="margin: 0; font-size: 11px; color: #475569;">${props.unitAddress || "Lokasi operasional unit SPPG"}</p>
        <span style="font-family: monospace; font-size: 10px; color: #64748b; margin-top: 4px; display: block;">${lat2.toFixed(6)}, ${lng2.toFixed(6)}</span>
      </div>
    `);
    unitLayerGroup.addLayer(markerUnit);

    // 2a. Radius Bulat (Lingkaran 6 KM Geodesik)
    circleUnit = L.circle([lat2, lng2], {
        radius: 6000,
        color: "#059669",
        weight: 1.5,
        dashArray: "6, 6",
        fillColor: "#10b981",
        fillOpacity: 0.05,
    }).bindPopup(`
      <div style="padding: 4px; font-family: inherit; text-align: center;">
        <strong style="color: #059669; font-size: 12.5px; display: block; margin-bottom: 2px;">⭕ Radius Bulat SPPG: 6.0 km</strong>
        <p style="margin: 0; font-size: 11px; color: #475569;">Batas radius lingkaran garis lurus (geodesik) 6 km dari Unit SPPG.</p>
      </div>
    `);
    circleRadiusLayerGroup.addLayer(circleUnit);

    // 3. Render Routes, Reachability Network & Penjaringan Jalan 6 KM
    renderHeadRoute();
    renderPenerimaManfaatNetwork();
    fetchSppg6KmReachabilityNetwork();

    // Fetch initial routes
    fetchDrivingRoute();
    if (routeMode.value === "road") {
        fetchAllKelompokRoadRoutes();
    }

    // Fit bounds agar semua titik terlihat sempurna
    fitBounds();

    setTimeout(safeInvalidateSize, 100);
    setTimeout(safeInvalidateSize, 300);
}

// ================= KEPALA SPPG -> UNIT SPPG ROUTE =================
function renderHeadRoute() {
    if (!map || !routeLayerGroup) return;

    routeLayerGroup.clearLayers();

    const lat1 = Number(props.domisiliLat);
    const lng1 = Number(props.domisiliLng);
    const lat2 = Number(props.unitLat);
    const lng2 = Number(props.unitLng);

    if (isNaN(lat1) || isNaN(lng1) || isNaN(lat2) || isNaN(lng2)) return;

    // JIKA MODE JALAN RIIL & DATA SUDAH TERSEDIA
    if (routeMode.value === "road" && roadCoordinates.value.length > 0) {
        // Outer Casing
        roadPolylineCasing = L.polyline(roadCoordinates.value, {
            color: "#1d4ed8",
            weight: 7,
            opacity: 0.85,
            lineCap: "round",
            lineJoin: "round",
        });
        routeLayerGroup.addLayer(roadPolylineCasing);

        // Inner Road Polyline
        roadPolyline = L.polyline(roadCoordinates.value, {
            color: "#3b82f6",
            weight: 5,
            opacity: 0.95,
            lineCap: "round",
            lineJoin: "round",
        });
        routeLayerGroup.addLayer(roadPolyline);

        const durationText = formattedDuration.value
            ? ` • ${formattedDuration.value}`
            : "";
        roadPolyline.bindPopup(`
      <div style="text-align: center; padding: 5px; font-family: inherit;">
        <span style="font-size: 10px; color: #1e40af; font-weight: 700; text-transform: uppercase;">Rute Jalan Riil</span>
        <strong style="font-size: 13px; color: #1d4ed8; display: block; margin: 2px 0;">🚗 ${formattedRoadDistance.value}${durationText}</strong>
        <span style="font-size: 11px; color: #64748b;">Kepala SPPG ➔ Unit SPPG</span>
      </div>
    `);

        // Floating midpoint badge
        const midIndex = Math.floor(roadCoordinates.value.length / 2);
        const midCoord = roadCoordinates.value[midIndex] || [
            (lat1 + lat2) / 2,
            (lng1 + lng2) / 2,
        ];

        if (filterSettings.value.showRouteDistBadge) {
            const iconMidpoint = L.divIcon({
                className: "custom-distance-badge",
                html: `
            <div style="background: #ffffff; color: #1e3a8a; border: 2px solid #2563eb; border-radius: 9999px; padding: 3px 10px; font-size: 11px; font-weight: 800; white-space: nowrap; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); display: inline-flex; align-items: center; gap: 5px; transform: translate(-50%, -50%); width: max-content;">
              <span style="font-size: 11px;">🚗</span>
              <span style="color: #1e3a8a; font-weight: 800;">${formattedRoadDistance.value}</span>
              ${formattedDuration.value ? `<span style="color: #475569; font-weight: 600; font-size: 10px; margin-left: 2px;">(${formattedDuration.value})</span>` : ""}
            </div>
          `,
                iconSize: [0, 0],
                iconAnchor: [0, 0],
            });

            roadDistanceMarker = L.marker(midCoord, {
                icon: iconMidpoint,
                interactive: false,
            });
            routeLayerGroup.addLayer(roadDistanceMarker);
        }
    } else {
        // MODE GARIS LURUS (GEODESIK)
        straightPolyline = L.polyline(
            [
                [lat1, lng1],
                [lat2, lng2],
            ],
            {
                color: "#4f46e5",
                weight: 3,
                opacity: 0.85,
                dashArray: "6, 6",
                lineCap: "round",
                lineJoin: "round",
            },
        );
        straightPolyline.bindPopup(`
      <div style="text-align: center; padding: 5px; font-family: inherit;">
        <span style="font-size: 10px; color: #4338ca; font-weight: 700; text-transform: uppercase;">Jarak Garis Lurus</span>
        <strong style="font-size: 13px; color: #4f46e5; display: block; margin: 2px 0;">📏 ${formattedStraightDistance.value}</strong>
        <span style="font-size: 11px; color: #64748b;">Kepala SPPG ➔ Unit SPPG</span>
      </div>
    `);
        routeLayerGroup.addLayer(straightPolyline);

        // Straight line midpoint distance badge
        const midPoint = [(lat1 + lat2) / 2, (lng1 + lng2) / 2];
        if (filterSettings.value.showRouteDistBadge) {
            const iconStraight = L.divIcon({
                className: "custom-distance-badge",
                html: `
            <div style="background: #ffffff; color: #3730a3; border: 2px solid #6366f1; border-radius: 9999px; padding: 3px 10px; font-size: 11px; font-weight: 800; white-space: nowrap; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35); display: inline-flex; align-items: center; gap: 5px; transform: translate(-50%, -50%); width: max-content;">
              <span style="color: #6366f1; font-size: 11px;">📏</span>
              <span>${formattedStraightDistance.value}</span>
            </div>
          `,
                iconSize: [0, 0],
                iconAnchor: [0, 0],
            });

            straightDistanceMarker = L.marker(midPoint, {
                icon: iconStraight,
                interactive: false,
            });
            routeLayerGroup.addLayer(straightDistanceMarker);
        }
    }
}

// Fetch Driving Route from OSRM for Kepala SPPG -> Unit
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
        if (!response.ok) throw new Error("OSRM API request failed");
        const data = await response.json();

        if (data.code === "Ok" && data.routes && data.routes.length > 0) {
            const primaryRoute = data.routes[0];
            const coords = primaryRoute.geometry.coordinates.map(
                ([lng, lat]) => [lat, lng],
            );
            roadCoordinates.value = coords;
            roadDistance.value = primaryRoute.distance / 1000; // in km
            roadDuration.value = primaryRoute.duration; // in seconds

            if (routeMode.value === "road") {
                renderHeadRoute();
                fitBounds();
            }
        }
    } catch (err) {
        console.warn(
            "Gagal memuat rute jalan riil OSRM (Kepala -> Unit):",
            err,
        );
    } finally {
        isRoutingLoading.value = false;
    }
}

// ================= PENJARINGAN RUTE JALAN RIIL 6 KM KE SEMUA ARAH (ISODISTANCE REACHABILITY) =================
async function fetchSppg6KmReachabilityNetwork() {
    const uLat = Number(props.unitLat);
    const uLng = Number(props.unitLng);
    if (isNaN(uLat) || isNaN(uLng)) return;

    if (sppgReachabilityData.value.branches.length > 0) {
        renderSppg6KmReachability();
        return;
    }

    sppgReachabilityData.value.isLoading = true;

    // 24 arah mata angin (setiap 15 derajat) mengelilingi 360 derajat titik SPPG
    const angles = [];
    for (let a = 0; a < 360; a += 15) {
        angles.push(a);
    }

    // Grid probe multi-arah dengan jarak 6.5 km dan 8 km
    const probeTargets = [];

    angles.forEach((angle) => {
        const rad = (angle * Math.PI) / 180;
        // Probe jarak 7.0 km untuk menjaring jalan sampai 6.0 km
        const dLat = (7.0 / 6371) * Math.cos(rad) * (180 / Math.PI);
        const dLng =
            ((7.0 / (6371 * Math.cos((uLat * Math.PI) / 180))) *
                Math.sin(rad) *
                180) /
            Math.PI;
        probeTargets.push({
            angle,
            lat: uLat + dLat,
            lng: uLng + dLng,
        });
    });

    // Tambahan target koridor jalan regional utama (Timur/Singaraja, Barat/Lovina, Utara/Pantai, Selatan/Pegunungan)
    const regionalCorridors = [
        // Timur / Sukasada / Sambangan / Singaraja
        { angle: 60, lat: uLat + 0.02, lng: uLng + 0.045 },
        { angle: 75, lat: uLat + 0.01, lng: uLng + 0.05 },
        { angle: 90, lat: uLat, lng: uLng + 0.05 },
        { angle: 105, lat: uLat - 0.015, lng: uLng + 0.045 },
        { angle: 120, lat: uLat - 0.03, lng: uLng + 0.04 },
        // Selatan / Tegallinggah / Gitgit / Pegadungan
        { angle: 150, lat: uLat - 0.045, lng: uLng + 0.02 },
        { angle: 180, lat: uLat - 0.05, lng: uLng },
        { angle: 210, lat: uLat - 0.045, lng: uLng - 0.02 },
        // Barat / Anturan / Kayuputih / Banjar / Selat
        { angle: 240, lat: uLat - 0.025, lng: uLng - 0.045 },
        { angle: 270, lat: uLat, lng: uLng - 0.05 },
        { angle: 285, lat: uLat + 0.015, lng: uLng - 0.048 },
        // Utara / Tukadmungga / Pemaron / Baktiseraga
        { angle: 315, lat: uLat + 0.038, lng: uLng - 0.025 },
        { angle: 345, lat: uLat + 0.04, lng: uLng - 0.01 },
        { angle: 0, lat: uLat + 0.04, lng: uLng },
        { angle: 15, lat: uLat + 0.04, lng: uLng + 0.015 },
        { angle: 30, lat: uLat + 0.035, lng: uLng + 0.03 },
    ];

    regionalCorridors.forEach((c) => probeTargets.push(c));

    const branches = [];
    const reachedPoints = [];

    const fetchPromises = probeTargets.map(async (target) => {
        try {
            const url = `https://router.project-osrm.org/route/v1/driving/${uLng},${uLat};${target.lng},${target.lat}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            if (!res.ok) return null;
            const data = await res.json();
            if (data.code === "Ok" && data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                const rawCoords = route.geometry.coordinates.map(
                    ([lng, lat]) => [lat, lng],
                );

                // Potong rute persis pada jarak kumulatif jalan 6.0 km
                const maxRoadDistKm = 6.0;
                let cumDist = 0;
                const sliced = [rawCoords[0]];

                for (let i = 0; i < rawCoords.length - 1; i++) {
                    const p1 = rawCoords[i];
                    const p2 = rawCoords[i + 1];
                    const segDist = calculateDistance(
                        p1[0],
                        p1[1],
                        p2[0],
                        p2[1],
                    );
                    if (cumDist + segDist <= maxRoadDistKm) {
                        cumDist += segDist;
                        sliced.push(p2);
                    } else {
                        // Interpolasi titik ujung pada 6.0 km
                        const remain = maxRoadDistKm - cumDist;
                        const ratio = segDist > 0 ? remain / segDist : 0;
                        const cutLat = p1[0] + (p2[0] - p1[0]) * ratio;
                        const cutLng = p1[1] + (p2[1] - p1[1]) * ratio;
                        sliced.push([cutLat, cutLng]);
                        break;
                    }
                }

                if (sliced.length > 1) {
                    const endPt = sliced[sliced.length - 1];
                    // Hitung sudut bearing aktual dari pusat ke titik ujung
                    const dY = endPt[0] - uLat;
                    const dX =
                        (endPt[1] - uLng) * Math.cos((uLat * Math.PI) / 180);
                    let actualBearing = (Math.atan2(dX, dY) * 180) / Math.PI;
                    if (actualBearing < 0) actualBearing += 360;

                    return {
                        bearing: actualBearing,
                        sliced,
                        endPoint: endPt,
                    };
                }
            }
        } catch (e) {
            // Ignore single probe failures
        }
        return null;
    });

    const results = await Promise.allSettled(fetchPromises);
    const validResults = results
        .filter((r) => r.status === "fulfilled" && r.value !== null)
        .map((r) => r.value);

    validResults.forEach((res) => {
        branches.push(res.sliced);
        reachedPoints.push(res);
    });

    // Bangun poligon 36-sektor melingkar 360 derajat yang seimbang (Kiri, Kanan, Atas, Bawah)
    const numSectors = 36;
    const sectorStep = 360 / numSectors;
    const boundaryPolygon = [];
    const baseEffectiveDistKm = 4.6; // Rata-rata jarak lurus setara 6.0 km jalan riil berliku

    for (let s = 0; s < numSectors; s++) {
        const sectorAngle = s * sectorStep;
        const nextSectorAngle = (s + 1) * sectorStep;

        // Cari titik jalan yang jatuh dalam sektor ini
        const inSector = reachedPoints.filter((p) => {
            if (s === 0) {
                return (
                    p.bearing >= 360 - sectorStep / 2 ||
                    p.bearing < sectorStep / 2
                );
            }
            return (
                p.bearing >= sectorAngle - sectorStep / 2 &&
                p.bearing < sectorAngle + sectorStep / 2
            );
        });

        if (inSector.length > 0) {
            // Ambil titik terjauh yang terjangkau jalan pada sektor ini
            let maxDist = 0;
            let bestPt = inSector[0].endPoint;
            inSector.forEach((item) => {
                const dist = calculateDistance(
                    uLat,
                    uLng,
                    item.endPoint[0],
                    item.endPoint[1],
                );
                if (dist > maxDist) {
                    maxDist = dist;
                    bestPt = item.endPoint;
                }
            });
            boundaryPolygon.push(bestPt);
        } else {
            // Interpolasi titik batas realistis untuk sektor yang tidak memiliki jalan tembus langsung
            const rad = (sectorAngle * Math.PI) / 180;
            const dLat =
                (baseEffectiveDistKm / 6371) * Math.cos(rad) * (180 / Math.PI);
            const dLng =
                ((baseEffectiveDistKm /
                    (6371 * Math.cos((uLat * Math.PI) / 180))) *
                    Math.sin(rad) *
                    180) /
                Math.PI;
            boundaryPolygon.push([uLat + dLat, uLng + dLng]);
        }
    }

    sppgReachabilityData.value = {
        branches,
        boundaryPolygon,
        isLoading: false,
    };

    renderSppg6KmReachability();
}

function renderSppg6KmReachability() {
    if (!roadReachabilityLayerGroup) return;
    roadReachabilityLayerGroup.clearLayers();

    const { branches, boundaryPolygon } = sppgReachabilityData.value;
    const uLat = Number(props.unitLat);
    const uLng = Number(props.unitLng);

    if (boundaryPolygon && boundaryPolygon.length >= 3) {
        // 1. Poligon Blok Wilayah Cakupan 6 KM (Balanced 360° Reachable Area)
        const poly = L.polygon(boundaryPolygon, {
            color: "#059669",
            weight: 2.5,
            dashArray: "5, 5",
            fillColor: "#10b981",
            fillOpacity: 0.12,
        }).bindPopup(`
          <div style="text-align: center; padding: 5px; font-family: inherit;">
            <span style="font-size: 10px; color: #059669; font-weight: 800; text-transform: uppercase;">Analisis Geospasial Wilayah</span>
            <strong style="color: #047857; font-size: 13px; display: block; margin: 2px 0;">🎯 Blok Cakupan Wilayah 6.0 KM (Jalan Riil)</strong>
            <p style="margin: 0; font-size: 11px; color: #475569;">Batas wilayah jangkauan tempuh riil 6.0 km ke segala arah jaringan jalan dari Unit SPPG.</p>
          </div>
        `);
        roadReachabilityLayerGroup.addLayer(poly);
    }

    // 2. Penebalan Garis Jaringan Jalan Riil Maksimal 6 KM (Road Highlight Network ke Semua Arah)
    branches.forEach((coords) => {
        // Outer Glow line
        const casing = L.polyline(coords, {
            color: "#047857",
            weight: 5.5,
            opacity: 0.35,
            lineCap: "round",
            lineJoin: "round",
        });
        roadReachabilityLayerGroup.addLayer(casing);

        // Core Highlighted Road Line
        const core = L.polyline(coords, {
            color: "#10b981",
            weight: 3,
            opacity: 0.95,
            lineCap: "round",
            lineJoin: "round",
        }).bindPopup(`
          <div style="text-align: center; padding: 4px; font-family: inherit;">
            <strong style="color: #047857; font-size: 12px; display: block;">🛣️ Jaringan Jalan Riil SPPG</strong>
            <span style="font-size: 11px; color: #334155;">Jarak tempuh: maks. 6.0 km dari Unit SPPG</span>
          </div>
        `);
        roadReachabilityLayerGroup.addLayer(core);
    });
}

// ================= UNIT SPPG -> PENERIMA MANFAAT MULTI-ROUTES =================
async function fetchKelompokRoadRoute(kelompok) {
    const id = kelompok.id || kelompok.uid;
    if (kelompokRoadRoutes.value[id]) return kelompokRoadRoutes.value[id];

    const uLat = Number(props.unitLat);
    const uLng = Number(props.unitLng);
    const kLat = Number(kelompok.latitude);
    const kLng = Number(kelompok.longitude);

    if (isNaN(uLat) || isNaN(uLng) || isNaN(kLat) || isNaN(kLng)) return null;

    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${uLng},${uLat};${kLng},${kLat}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        if (!response.ok) throw new Error("OSRM error");
        const data = await response.json();
        if (data.code === "Ok" && data.routes && data.routes.length > 0) {
            const primary = data.routes[0];
            const coords = primary.geometry.coordinates.map(([lng, lat]) => [
                lat,
                lng,
            ]);
            const result = {
                coords,
                distance: primary.distance / 1000,
                duration: primary.duration,
            };
            kelompokRoadRoutes.value = {
                ...kelompokRoadRoutes.value,
                [id]: result,
            };
            return result;
        }
    } catch (err) {
        console.warn(
            `Gagal memuat rute jalan riil OSRM untuk ${kelompok.nama_kelompok}:`,
            err,
        );
    }
    return null;
}

async function fetchAllKelompokRoadRoutes() {
    isFetchingKelompokRoutes.value = true;
    const promises = validKelompokList.value.map((k) =>
        fetchKelompokRoadRoute(k),
    );
    await Promise.allSettled(promises);
    isFetchingKelompokRoutes.value = false;
    if (map && routeMode.value === "road") {
        renderPenerimaManfaatNetwork();
        fitBounds();
    }
}

// Render connection lines and markers from Unit SPPG to each Penerima Manfaat
function renderPenerimaManfaatNetwork() {
    if (!map || !kelompokLayerGroup) return;

    kelompokLayerGroup.clearLayers();
    kelompokMarkerMap.clear();

    if (
        !filterSettings.value.showPenerima &&
        !filterSettings.value.showConnections
    )
        return;

    const uLat = Number(props.unitLat);
    const uLng = Number(props.unitLng);

    visibleKelompokList.value.forEach((kelompok) => {
        const id = kelompok.id || kelompok.uid;
        const kLat = Number(kelompok.latitude);
        const kLng = Number(kelompok.longitude);
        const straightDist = calculateDistance(uLat, uLng, kLat, kLng);
        const straightDistFormatted = formatDist(straightDist);

        const roadData = kelompokRoadRoutes.value[id];
        const isRoadMode =
            routeMode.value === "road" &&
            roadData &&
            roadData.coords &&
            roadData.coords.length > 0;
        const activeDistFormatted = isRoadMode
            ? formatDist(roadData.distance)
            : straightDistFormatted;
        const activeDurationFormatted = isRoadMode
            ? formatDurationHelper(roadData.duration)
            : null;

        // 1. Tarik Garis Sambungan dari Unit SPPG ke Penerima Manfaat jika filter aktif
        if (
            filterSettings.value.showConnections &&
            !isNaN(uLat) &&
            !isNaN(uLng)
        ) {
            if (isRoadMode) {
                // MODE JALAN RIIL (Polyline Jalan Melengkung via OSRM)
                // Outer Casing line
                L.polyline(roadData.coords, {
                    color: "#92400e",
                    weight: 5,
                    opacity: 0.35,
                    lineCap: "round",
                    lineJoin: "round",
                }).addTo(kelompokLayerGroup);

                // Inner Polyline
                const roadLine = L.polyline(roadData.coords, {
                    color: "#f59e0b",
                    weight: 3.5,
                    opacity: 0.95,
                    lineCap: "round",
                    lineJoin: "round",
                }).addTo(kelompokLayerGroup);

                const durationBadge = activeDurationFormatted
                    ? ` • ~${activeDurationFormatted}`
                    : "";
                roadLine.bindPopup(`
          <div style="padding: 5px; font-family: inherit; text-align: center;">
            <span style="font-size: 10px; color: #92400e; font-weight: 700; text-transform: uppercase;">Rute Jalan Distribusi SPPG</span>
            <strong style="color: #1e293b; font-size: 13px; display: block; margin: 2px 0;">🚗 Unit SPPG ➔ ${kelompok.nama_kelompok}</strong>
            <div style="display: flex; justify-content: center; gap: 6px; font-size: 11px; margin-top: 4px;">
              <span style="background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-weight: 700;">Jarak: ${activeDistFormatted}${durationBadge}</span>
              <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700;">${kelompok.total_penerima} Penerima Manfaat</span>
            </div>
          </div>
        `);

                // Midpoint Distance Badge along road
                const midIndex = Math.floor(roadData.coords.length / 2);
                const midPoint = roadData.coords[midIndex] || [
                    (uLat + kLat) / 2,
                    (uLng + kLng) / 2,
                ];

                if (filterSettings.value.showPenerimaDistBadge) {
                    const distIcon = L.divIcon({
                        className: "custom-kelompok-dist-badge",
                        html: `
                <div style="background: #ffffff; color: #92400e; border: 1.5px solid #f59e0b; border-radius: 9999px; padding: 2px 8px; font-size: 10px; font-weight: 800; white-space: nowrap; box-shadow: 0 3px 8px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 4px; transform: translate(-50%, -50%); width: max-content;">
                  <span style="font-size: 10px;">🚗</span>
                  <span>${activeDistFormatted}</span>
                  ${activeDurationFormatted ? `<span style="color: #78350f; font-weight: 600; font-size: 9px; margin-left: 1px;">(~${activeDurationFormatted})</span>` : ""}
                </div>
              `,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0],
                    });

                    L.marker(midPoint, {
                        icon: distIcon,
                        interactive: false,
                    }).addTo(kelompokLayerGroup);
                }
            } else {
                // MODE GARIS TEGAK LURUS (Direct Line / Geodesik)
                // Outer Casing line
                L.polyline(
                    [
                        [uLat, uLng],
                        [kLat, kLng],
                    ],
                    {
                        color: "#92400e",
                        weight: 4,
                        opacity: 0.25,
                        lineCap: "round",
                    },
                ).addTo(kelompokLayerGroup);

                // Inner Dashed Line
                const line = L.polyline(
                    [
                        [uLat, uLng],
                        [kLat, kLng],
                    ],
                    {
                        color: "#f59e0b",
                        weight: 2.5,
                        opacity: 0.9,
                        dashArray: "6, 6",
                        lineCap: "round",
                    },
                ).addTo(kelompokLayerGroup);

                line.bindPopup(`
          <div style="padding: 5px; font-family: inherit; text-align: center;">
            <span style="font-size: 10px; color: #92400e; font-weight: 700; text-transform: uppercase;">Jalur Lurus Distribusi SPPG</span>
            <strong style="color: #1e293b; font-size: 13px; display: block; margin: 2px 0;">📏 Unit SPPG ➔ ${kelompok.nama_kelompok}</strong>
            <div style="display: flex; justify-content: center; gap: 8px; font-size: 11px; margin-top: 4px;">
              <span style="background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-weight: 700;">Jarak: ${straightDistFormatted}</span>
              <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700;">${kelompok.total_penerima} Penerima Manfaat</span>
            </div>
          </div>
        `);

                // Midpoint Distance Pill
                const midPoint = [(uLat + kLat) / 2, (uLng + kLng) / 2];
                if (filterSettings.value.showPenerimaDistBadge) {
                    const distIcon = L.divIcon({
                        className: "custom-kelompok-dist-badge",
                        html: `
                <div style="background: #ffffff; color: #92400e; border: 1.5px solid #f59e0b; border-radius: 9999px; padding: 2px 8px; font-size: 10px; font-weight: 800; white-space: nowrap; box-shadow: 0 3px 8px rgba(0,0,0,0.18); display: inline-flex; align-items: center; gap: 4px; transform: translate(-50%, -50%); width: max-content;">
                  <span style="font-size: 10px;">📏</span>
                  <span>${straightDistFormatted}</span>
                </div>
              `,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0],
                    });

                    L.marker(midPoint, {
                        icon: distIcon,
                        interactive: false,
                    }).addTo(kelompokLayerGroup);
                }
            }
        }

        // 2. Soft Area Circle & Marker Pin jika showPenerima aktif
        if (filterSettings.value.showPenerima) {
            L.circle([kLat, kLng], {
                radius: 150,
                color: "#d97706",
                weight: 1,
                fillColor: "#f59e0b",
                fillOpacity: 0.08,
            }).addTo(kelompokLayerGroup);

            const marker = L.marker([kLat, kLng], {
                icon: createPenerimaIcon(
                    kelompok.nama_kelompok,
                    kelompok.kategori,
                ),
            }).addTo(kelompokLayerGroup).bindPopup(`
          <div style="padding: 5px; font-family: inherit; min-width: 200px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 4px;">
              <strong style="color: #92400e; font-size: 13px;">🏫 ${kelompok.nama_kelompok}</strong>
              <span style="font-size: 10px; font-weight: 800; background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px;">
                ${kelompok.kategori || ""} ${kelompok.jenis_kepemilikan || ""}
              </span>
            </div>

            <p style="margin: 0; font-size: 11px; color: #475569; line-height: 1.3;">
              ${kelompok.alamat_lengkap || `${kelompok.desa_kelurahan || ""}, ${kelompok.kecamatan || ""}`}
            </p>

            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 10.5px;">
              <div style="background: #f8fafc; padding: 3px 5px; border-radius: 4px;">
                <span style="color: #64748b; display: block; font-size: 9.5px;">Jarak (${routeMode.value === "road" ? "Jalan" : "Lurus"}):</span>
                <strong style="color: #d97706;">${routeMode.value === "road" ? "🚗" : "📏"} ${activeDistFormatted}</strong>
              </div>
              <div style="background: #f8fafc; padding: 3px 5px; border-radius: 4px;">
                <span style="color: #64748b; display: block; font-size: 9.5px;">Total Penerima:</span>
                <strong style="color: #0284c7;">👥 ${kelompok.total_penerima || 0} Penerima Manfaat</strong>
              </div>
              <div style="background: #fef3c7; padding: 3px 5px; border-radius: 4px;">
                <span style="color: #92400e; display: block; font-size: 9.5px;">Porsi Kecil:</span>
                <strong style="color: #92400e;">🟡 ${kelompok.total_porsi_kecil || 0}</strong>
              </div>
              <div style="background: #eff6ff; padding: 3px 5px; border-radius: 4px;">
                <span style="color: #1e40af; display: block; font-size: 9.5px;">Porsi Besar:</span>
                <strong style="color: #1e40af;">🔵 ${kelompok.total_porsi_besar || 0}</strong>
              </div>
            </div>
          </div>
        `);

            kelompokMarkerMap.set(id, marker);
        }
    });
}

function focusKelompok(kelompok) {
    if (!map) return;
    const kLat = Number(kelompok.latitude);
    const kLng = Number(kelompok.longitude);
    if (isNaN(kLat) || isNaN(kLng)) return;

    const id = kelompok.id || kelompok.uid;
    if (hiddenKelompokIds.value.has(id)) {
        hiddenKelompokIds.value.delete(id);
        hiddenKelompokIds.value = new Set(hiddenKelompokIds.value);
    }
    if (!filterSettings.value.showPenerima) {
        filterSettings.value.showPenerima = true;
    }

    map.flyTo([kLat, kLng], 16, { duration: 1 });
    const marker = kelompokMarkerMap.get(id);
    if (marker) {
        setTimeout(() => marker.openPopup(), 1000);
    }
}

function getKelompokDisplayDist(kelompok) {
    const id = kelompok.id || kelompok.uid;
    if (routeMode.value === "road" && kelompokRoadRoutes.value[id]) {
        return formatDist(kelompokRoadRoutes.value[id].distance);
    }
    return formatDist(
        calculateDistance(
            props.unitLat,
            props.unitLng,
            kelompok.latitude,
            kelompok.longitude,
        ),
    );
}

function fitBounds() {
    if (
        !map ||
        !map._container ||
        !mapContainer.value ||
        mapContainer.value.offsetWidth === 0 ||
        mapContainer.value.offsetHeight === 0
    ) {
        return;
    }

    const points = [];

    // 1. Domisili
    if (filterSettings.value.showDomisili) {
        const lat1 = Number(props.domisiliLat);
        const lng1 = Number(props.domisiliLng);
        if (!isNaN(lat1) && !isNaN(lng1)) points.push([lat1, lng1]);
    }

    // 2. Unit SPPG
    if (filterSettings.value.showUnit) {
        const lat2 = Number(props.unitLat);
        const lng2 = Number(props.unitLng);
        if (!isNaN(lat2) && !isNaN(lng2)) points.push([lat2, lng2]);
    }

    // 3. Driving route coords
    if (filterSettings.value.showRoute) {
        if (routeMode.value === "road" && roadCoordinates.value.length > 0) {
            roadCoordinates.value.forEach((c) => points.push(c));
        }
    }

    // 4. Visible Penerima Manfaat coords
    if (
        filterSettings.value.showPenerima ||
        filterSettings.value.showConnections
    ) {
        visibleKelompokList.value.forEach((k) => {
            points.push([Number(k.latitude), Number(k.longitude)]);
        });
    }

    try {
        if (points.length > 0) {
            map.fitBounds(points, {
                padding: [50, 50],
                maxZoom: 16,
            });
        }
    } catch (e) {
        // Safe catch if map is not ready
    }
}

function safeInvalidateSize() {
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
        } catch (e) {
            // Suppress error when resizing unmounted/hidden map
        }
    }
}

// Watchers
watch(
    () => filterSettings.value.showRouteDistBadge,
    () => {
        if (map) {
            renderHeadRoute();
        }
    },
);

watch(
    () => filterSettings.value.showPenerimaDistBadge,
    () => {
        if (map) {
            renderPenerimaManfaatNetwork();
        }
    },
);

// Watchers
watch(routeMode, (newMode) => {
    if (newMode === "road") {
        if (roadCoordinates.value.length === 0) {
            fetchDrivingRoute();
        }
        fetchAllKelompokRoadRoutes();
    }
    if (map) {
        renderHeadRoute();
        renderPenerimaManfaatNetwork();
        fitBounds();
    }
});

watch(
    () => filterSettings.value.showDomisili,
    (val) => {
        if (!map || !domisiliLayerGroup) return;
        if (val) map.addLayer(domisiliLayerGroup);
        else map.removeLayer(domisiliLayerGroup);
        fitBounds();
    },
);

watch(
    () => filterSettings.value.showUnit,
    (val) => {
        if (!map || !unitLayerGroup) return;
        if (val) map.addLayer(unitLayerGroup);
        else map.removeLayer(unitLayerGroup);
        fitBounds();
    },
);

watch(
    () => filterSettings.value.showCircleRadius,
    (val) => {
        if (!map || !circleRadiusLayerGroup) return;
        if (val) map.addLayer(circleRadiusLayerGroup);
        else map.removeLayer(circleRadiusLayerGroup);
    },
);

watch(
    () => filterSettings.value.showRoadReachability,
    (val) => {
        if (!map || !roadReachabilityLayerGroup) return;
        if (val) {
            if (sppgReachabilityData.value.branches.length === 0) {
                fetchSppg6KmReachabilityNetwork();
            }
            map.addLayer(roadReachabilityLayerGroup);
        } else {
            map.removeLayer(roadReachabilityLayerGroup);
        }
    },
);

watch(
    () => filterSettings.value.showRoute,
    (val) => {
        if (!map || !routeLayerGroup) return;
        if (val) map.addLayer(routeLayerGroup);
        else map.removeLayer(routeLayerGroup);
        fitBounds();
    },
);

watch(
    [
        () => filterSettings.value.showConnections,
        () => filterSettings.value.showPenerima,
        () => hiddenKelompokIds.value.size,
        () => selectedKategoriFilters.value.length,
    ],
    () => {
        if (map) {
            renderPenerimaManfaatNetwork();
            fitBounds();
        }
    },
);

watch(
    () => props.domisiliLabel,
    (newLabel) => {
        if (markerDomisili) {
            markerDomisili.setIcon(createDomisiliIcon(newLabel));
        }
    },
);

watch(
    () => props.unitLabel,
    (newLabel) => {
        if (markerUnit) {
            markerUnit.setIcon(createUnitIcon(newLabel));
        }
    },
);

watch(
    () => props.isFullscreen,
    () => {
        nextTick(() => {
            [50, 150, 300, 500, 800].forEach((delay) => {
                setTimeout(() => {
                    safeInvalidateSize();
                    fitBounds();
                }, delay);
            });
        });
    },
);

watch(
    () => props.kelompokList,
    () => {
        if (map) {
            if (routeMode.value === "road") {
                fetchAllKelompokRoadRoutes();
            }
            renderPenerimaManfaatNetwork();
            fitBounds();
        }
    },
    { deep: true },
);

function handleClickOutside(event) {
    if (
        filterDropdownRef.value &&
        !filterDropdownRef.value.contains(event.target)
    ) {
        isFilterDropdownOpen.value = false;
    }
}

defineExpose({
    refresh: () => {
        safeInvalidateSize();
        fitBounds();
    },
});

onMounted(() => {
    initMap();
    document.addEventListener("click", handleClickOutside);
    if (window.ResizeObserver && mapContainer.value) {
        resizeObserver = new ResizeObserver(() => {
            safeInvalidateSize();
        });
        resizeObserver.observe(mapContainer.value);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    if (map) {
        try {
            map.remove();
        } catch (e) {}
        map = null;
    }
});
</script>

<template>
    <div
        class="w-full flex flex-col min-h-0"
        :class="isFullscreen ? 'h-full flex-1' : ''"
        :style="
            isFullscreen
                ? 'height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                : ''
        "
    >
        <div
            class="grid grid-cols-1 gap-4 lg:gap-6 items-stretch flex-1 min-h-0"
            :class="[
                isFullscreen
                    ? 'lg:grid-cols-12 h-full flex-1'
                    : 'lg:grid-cols-12',
            ]"
            :style="
                isFullscreen
                    ? 'height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                    : ''
            "
        >
            <!-- Left Column: Map Container (lg:col-span-7 xl:col-span-8) -->
            <div
                class="flex flex-col min-h-0"
                :class="[
                    isFullscreen
                        ? 'col-span-1 lg:col-span-7 xl:col-span-8 h-full flex-1'
                        : 'lg:col-span-7 xl:col-span-8 min-h-[440px] lg:min-h-[560px]',
                ]"
                :style="
                    isFullscreen
                        ? 'height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                        : ''
                "
            >
                <div
                    class="relative w-full rounded-xl border border-slate-200 shadow-xs overflow-hidden bg-slate-100 flex flex-col min-h-0"
                    :class="
                        isFullscreen
                            ? 'h-full flex-1 min-h-full'
                            : 'h-full min-h-[440px] lg:min-h-[560px] flex-1'
                    "
                    :style="
                        isFullscreen
                            ? 'height: 100% !important; min-height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                            : ''
                    "
                >
                    <!-- Floating Top Right Action & Info Bar -->
                    <div
                        class="absolute top-2.5 right-2.5 sm:top-3 sm:right-3 flex items-center gap-1.5 sm:gap-2 select-none flex-wrap justify-end pl-12"
                        style="z-index: 1000"
                    >
                        <!-- Floating Counter Badge -->
                        <div
                            v-if="validKelompokList.length > 0"
                            class="bg-white/95 backdrop-blur-xs border border-amber-300 text-amber-950 px-2 py-1 sm:px-2.5 sm:py-1.5 rounded-lg shadow-md flex items-center gap-1 sm:gap-1.5 text-[11px] sm:text-xs font-bold shrink-0"
                        >
                            <School
                                class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-amber-600 shrink-0"
                            />
                            <span
                                >{{ visibleKelompokList.length }}/{{
                                    validKelompokList.length
                                }}
                                KPM</span
                            >
                        </div>

                        <!-- Filter Layers & Titik Popover/Dropdown -->
                        <div ref="filterDropdownRef" class="relative">
                            <button
                                type="button"
                                @click.stop="
                                    isFilterDropdownOpen = !isFilterDropdownOpen
                                "
                                class="h-7 sm:h-8 px-2.5 sm:px-3 rounded-lg bg-white/95 hover:bg-white border border-slate-300 shadow-md text-[11px] sm:text-xs font-bold text-slate-800 flex items-center gap-1 sm:gap-1.5 cursor-pointer transition-all hover:border-primary shrink-0"
                                :class="{
                                    'ring-2 ring-primary/30 border-primary text-primary':
                                        isFilterDropdownOpen,
                                }"
                                title="Filter Titik dan Lapisan Peta"
                            >
                                <Filter
                                    class="h-3 w-3 sm:h-3.5 sm:w-3.5 text-primary"
                                />
                                <span>Filter</span>
                                <span
                                    class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded-full text-[9.5px] sm:text-[10px] font-mono font-bold"
                                >
                                    {{ activeFilterCount }}
                                </span>
                            </button>

                            <!-- Mobile Backdrop -->
                            <div
                                v-if="isFilterDropdownOpen"
                                class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-40 sm:hidden transition-opacity"
                                @click="isFilterDropdownOpen = false"
                            ></div>

                            <!-- Filter Panel Modal Dropdown -->
                            <div
                                v-if="isFilterDropdownOpen"
                                class="fixed sm:absolute left-4 right-4 top-1/2 -translate-y-1/2 sm:translate-y-0 sm:left-auto sm:right-0 sm:top-9.5 sm:w-80 bg-white rounded-2xl sm:rounded-xl shadow-2xl border border-slate-200 z-50 text-xs flex flex-col max-h-[80vh] sm:max-h-[460px] overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                            >
                                <!-- Header Sticky -->
                                <div
                                    class="flex items-center justify-between border-b border-slate-100 px-4 py-3 bg-white shrink-0"
                                >
                                    <span
                                        class="font-bold text-slate-900 flex items-center gap-2 text-xs sm:text-[13px]"
                                    >
                                        <Layers
                                            class="h-4 w-4 text-primary shrink-0"
                                        />
                                        Filter Titik & Lapisan
                                    </span>
                                    <button
                                        type="button"
                                        @click="isFilterDropdownOpen = false"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer inline-flex items-center justify-center -mr-1"
                                        title="Tutup Filter"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>

                                <!-- Scrollable Body -->
                                <div
                                    class="p-3.5 overflow-y-auto flex-1 space-y-3 overscroll-contain"
                                >
                                    <!-- Pilihan Tema Peta (Basemap) -->
                                    <div
                                        class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5"
                                            >
                                                <Palette
                                                    class="h-3.5 w-3.5 text-primary"
                                                />
                                                Tema Peta (Basemap):
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="text-[9.5px] py-0 font-bold bg-white"
                                            >
                                                {{
                                                    mapThemes.find(
                                                        (t) =>
                                                            t.id ===
                                                            currentMapTheme,
                                                    )?.name
                                                }}
                                            </Badge>
                                        </div>
                                        <div
                                            class="grid grid-cols-3 gap-1 p-0.5 bg-slate-200/70 rounded-md"
                                        >
                                            <button
                                                v-for="theme in mapThemes"
                                                :key="theme.id"
                                                type="button"
                                                @click="
                                                    switchMapTheme(theme.id)
                                                "
                                                :class="[
                                                    'py-1.5 px-1 rounded text-[10.5px] flex items-center justify-center gap-1 transition-all cursor-pointer font-bold',
                                                    currentMapTheme === theme.id
                                                        ? 'bg-white text-primary shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                ]"
                                                :title="theme.name"
                                            >
                                                <span>{{ theme.icon }}</span>
                                                <span>{{
                                                    theme.shortName
                                                }}</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Pilihan Tipe Jalur Rute (Semua Koneksi) -->
                                    <div
                                        class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[11px] font-bold text-slate-700 flex items-center gap-1"
                                            >
                                                <Route
                                                    class="h-3.5 w-3.5 text-primary"
                                                />
                                                Tipe Jalur Koneksi:
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="text-[9.5px] py-0 font-bold bg-white"
                                            >
                                                {{
                                                    routeMode === "road"
                                                        ? "Jalan Riil"
                                                        : "Garis Lurus"
                                                }}
                                            </Badge>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-1 p-0.5 bg-slate-200/70 rounded-md"
                                        >
                                            <button
                                                type="button"
                                                @click="routeMode = 'road'"
                                                :class="[
                                                    'py-1.5 px-2 rounded text-[11px] flex items-center justify-center gap-1.5 transition-all cursor-pointer font-bold',
                                                    routeMode === 'road'
                                                        ? 'bg-white text-blue-700 shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                ]"
                                            >
                                                <Car class="h-3 w-3" />
                                                <span>Jalan Riil</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="routeMode = 'straight'"
                                                :class="[
                                                    'py-1.5 px-2 rounded text-[11px] flex items-center justify-center gap-1.5 transition-all cursor-pointer font-bold',
                                                    routeMode === 'straight'
                                                        ? 'bg-white text-indigo-700 shadow-xs'
                                                        : 'text-slate-600 hover:text-slate-900',
                                                ]"
                                            >
                                                <Navigation class="h-3 w-3" />
                                                <span>Garis Lurus</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Main Layer Checkboxes -->
                                    <div class="space-y-1">
                                        <!-- Checkbox Pilih Semua -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg bg-slate-50/90 hover:bg-slate-100/80 cursor-pointer border border-slate-200/80 transition-colors mb-1.5"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-bold text-slate-900 text-xs"
                                                    >Pilih Semua</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="isAllFiltersSelected"
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 1. Radius Bulat (6 KM) -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full border-2 border-emerald-600 bg-emerald-50 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Radius Bulat (6 KM)</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showCircleRadius
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 2. Radius Blok (6KM) -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-emerald-500 border border-emerald-700 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Radius Blok (6KM)</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showRoadReachability
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 3. Lokasi Unit SPPG -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-emerald-600 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Lokasi Unit SPPG</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showUnit
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 4. Lokasi Kepala SPPG -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-blue-600 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Lokasi Kepala SPPG</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showDomisili
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 5. Rute Unit SPPG ke Kepala SPPG -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-indigo-500 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Rute Unit SPPG ke Kepala
                                                    SPPG</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showRoute
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 6. Titik KPM -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-amber-700 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Titik KPM</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showPenerima
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 7. Garis Koneksi KPM -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-3 h-3 rounded-full bg-amber-500 shrink-0 shadow-2xs"
                                                ></span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Garis Koneksi KPM</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showConnections
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>
                                    </div>

                                    <!-- Label Jarak / Badge Filter Section -->
                                    <div
                                        class="pt-2 border-t border-slate-100 space-y-1"
                                    >
                                        <span
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block px-1"
                                            >Label Jarak Tempuh:</span
                                        >

                                        <!-- 8. Jarak Tempuh Unit SPPG ke Kepala SPPG -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="text-xs">🚗</span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Jarak Tempuh Unit SPPG ke
                                                    Kepala SPPG</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showRouteDistBadge
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>

                                        <!-- 9. Jarak Tempuh Unit SPPG ke KPM -->
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-100 transition-colors"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span class="text-xs">🏷️</span>
                                                <span
                                                    class="font-semibold text-slate-800 text-xs"
                                                    >Jarak Tempuh Unit SPPG ke
                                                    KPM</span
                                                >
                                            </div>
                                            <input
                                                type="checkbox"
                                                v-model="
                                                    filterSettings.showPenerimaDistBadge
                                                "
                                                class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                            />
                                        </label>
                                    </div>

                                    <!-- Filter Kategori Penerima jika tersedia -->
                                    <div
                                        v-if="
                                            uniqueKategoriList.length > 1 &&
                                            filterSettings.showPenerima
                                        "
                                        class="pt-2.5 border-t border-slate-100 space-y-1.5"
                                    >
                                        <span
                                            class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider block"
                                            >Kategori Penerima:</span
                                        >
                                        <div class="flex flex-wrap gap-1.5">
                                            <button
                                                v-for="kat in uniqueKategoriList"
                                                :key="kat"
                                                type="button"
                                                @click="
                                                    toggleKategoriFilter(kat)
                                                "
                                                :class="[
                                                    'px-2 py-1 rounded-md text-[10.5px] font-bold border transition-all cursor-pointer flex items-center gap-1',
                                                    selectedKategoriFilters.includes(
                                                        kat,
                                                    )
                                                        ? 'bg-amber-50 text-amber-900 border-amber-300 shadow-2xs'
                                                        : 'bg-slate-100 text-slate-400 border-slate-200 line-through opacity-60',
                                                ]"
                                            >
                                                <Check
                                                    v-if="
                                                        selectedKategoriFilters.includes(
                                                            kat,
                                                        )
                                                    "
                                                    class="h-3 w-3 text-amber-600"
                                                />
                                                <span>{{ kat }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reset Center Button -->
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="fitBounds"
                            className="h-7 sm:h-8 px-2.5 sm:px-3 bg-white/95 hover:bg-white shadow-md text-[11px] sm:text-xs flex items-center gap-1 sm:gap-1.5 border-slate-300 font-semibold cursor-pointer text-slate-800 shrink-0"
                            title="Pusatkan Peta ke Jaringan Penuh"
                        >
                            <LocateFixed
                                class="h-3 w-3 sm:h-3.5 sm:w-3.5 text-primary"
                            />
                            <span>Pusatkan</span>
                        </Button>
                    </div>

                    <!-- Leaflet Map Container -->
                    <div
                        ref="mapContainer"
                        class="w-full relative z-0"
                        :class="
                            isFullscreen
                                ? 'h-full flex-1 min-h-full'
                                : 'h-full min-h-[440px] lg:min-h-[560px]'
                        "
                        :style="
                            isFullscreen
                                ? 'height: 100% !important; min-height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                                : { height: height || '560px' }
                        "
                    ></div>
                </div>
            </div>

            <!-- Right Column: Stacked Cards (lg:col-span-5 xl:col-span-4) -->
            <div
                class="flex flex-col gap-3 min-h-0"
                :class="[
                    isFullscreen
                        ? 'hidden lg:flex lg:col-span-5 xl:col-span-4 h-full flex-1 justify-start overflow-y-auto pr-1 custom-scrollbar'
                        : 'lg:col-span-5 xl:col-span-4 justify-between',
                ]"
                :style="
                    isFullscreen
                        ? 'height: 100% !important; max-height: 100% !important; flex: 1 1 0% !important;'
                        : ''
                "
            >
                <!-- Card 1: Domisili Kepala SPPG -->
                <div
                    class="p-3.5 rounded-xl bg-white border border-blue-100 shadow-2xs space-y-2 transition-opacity shrink-0"
                    :class="{
                        'opacity-50 grayscale-50': !filterSettings.showDomisili,
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center"
                            >
                                <User class="h-3.5 w-3.5" />
                            </div>
                            <span class="text-xs font-bold text-slate-900"
                                >Lokasi Kepala SPPG</span
                            >
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="
                                    filterSettings.showDomisili =
                                        !filterSettings.showDomisili
                                "
                                class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-700 cursor-pointer"
                                :title="
                                    filterSettings.showDomisili
                                        ? 'Sembunyikan dari peta'
                                        : 'Tampilkan di peta'
                                "
                            >
                                <Eye
                                    v-if="filterSettings.showDomisili"
                                    class="h-3.5 w-3.5 text-blue-600"
                                />
                                <EyeOff
                                    v-else
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </button>
                            <Badge variant="info" className="text-[10px] py-0"
                                >Titik Awal</Badge
                            >
                        </div>
                    </div>
                    <p
                        class="text-xs text-slate-600 font-medium leading-relaxed"
                    >
                        {{ domisiliAddress || "Alamat domisili" }}
                    </p>
                </div>

                <!-- Card 2: Unit SPPG -->
                <div
                    class="p-3.5 rounded-xl bg-white border border-emerald-100 shadow-2xs space-y-2 transition-opacity shrink-0"
                    :class="{
                        'opacity-50 grayscale-50': !filterSettings.showUnit,
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center"
                            >
                                <Building2 class="h-3.5 w-3.5" />
                            </div>
                            <span class="text-xs font-bold text-slate-900"
                                >Lokasi Unit SPPG</span
                            >
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="
                                    filterSettings.showUnit =
                                        !filterSettings.showUnit
                                "
                                class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-700 cursor-pointer"
                                :title="
                                    filterSettings.showUnit
                                        ? 'Sembunyikan dari peta'
                                        : 'Tampilkan di peta'
                                "
                            >
                                <Eye
                                    v-if="filterSettings.showUnit"
                                    class="h-3.5 w-3.5 text-emerald-600"
                                />
                                <EyeOff
                                    v-else
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </button>
                            <Badge
                                variant="success"
                                className="text-[10px] py-0"
                                >Titik Pusat</Badge
                            >
                        </div>
                    </div>
                    <p
                        class="text-xs text-slate-600 font-medium leading-relaxed"
                    >
                        {{ unitAddress || "Alamat operasional unit SPPG" }}
                    </p>
                </div>

                <!-- Card 3: Analisis Rute & Jarak Riil (Domisili -> Unit) -->
                <div
                    class="p-3.5 rounded-xl bg-gradient-to-br from-blue-50/80 via-indigo-50/40 to-white border border-blue-200 shadow-2xs space-y-2.5 transition-opacity shrink-0"
                    :class="{
                        'opacity-50 grayscale-50': !filterSettings.showRoute,
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <Navigation class="h-3.5 w-3.5 text-blue-700" />
                            <span class="text-xs font-bold text-blue-950"
                                >Rute Kepala SPPG ke Unit SPPG</span
                            >
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="
                                    filterSettings.showRoute =
                                        !filterSettings.showRoute
                                "
                                class="p-1 rounded hover:bg-blue-100 text-slate-400 hover:text-slate-700 cursor-pointer"
                                :title="
                                    filterSettings.showRoute
                                        ? 'Sembunyikan rute dari peta'
                                        : 'Tampilkan rute di peta'
                                "
                            >
                                <Eye
                                    v-if="filterSettings.showRoute"
                                    class="h-3.5 w-3.5 text-blue-600"
                                />
                                <EyeOff
                                    v-else
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </button>
                            <span
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full flex items-center gap-1"
                                :class="
                                    routeMode === 'road'
                                        ? 'text-blue-700 bg-blue-100/90'
                                        : 'text-indigo-700 bg-indigo-100/90'
                                "
                            >
                                <Car
                                    v-if="routeMode === 'road'"
                                    class="h-3 w-3"
                                />
                                <Navigation v-else class="h-3 w-3" />
                                {{
                                    routeMode === "road"
                                        ? "Jalan Riil"
                                        : "Garis Lurus"
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-white p-2.5 rounded-lg border border-blue-100 shadow-2xs flex items-center justify-between"
                    >
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl font-black text-blue-700">
                                {{
                                    routeMode === "road"
                                        ? formattedRoadDistance
                                        : formattedStraightDistance
                                }}
                            </span>
                            <span
                                class="text-[11px] text-slate-500 font-medium"
                            >
                                {{
                                    routeMode === "road"
                                        ? "via rute jalan"
                                        : "garis lurus"
                                }}
                            </span>
                        </div>
                        <div
                            v-if="routeMode === 'road' && formattedDuration"
                            class="flex items-center gap-1 text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md"
                        >
                            <Clock class="h-3 w-3 text-blue-600" />
                            <span>~{{ formattedDuration }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Jaringan Distribusi Penerima Manfaat (Tarik Garis) -->
                <div
                    class="p-3.5 rounded-xl bg-gradient-to-br from-amber-50/70 via-orange-50/30 to-white border border-amber-200 shadow-2xs flex flex-col justify-between"
                    :class="
                        isFullscreen
                            ? 'flex-1 min-h-0 overflow-hidden'
                            : 'space-y-3'
                    "
                    :style="
                        isFullscreen
                            ? 'flex: 1 1 0% !important; min-height: 0 !important; max-height: 100% !important; overflow: hidden !important;'
                            : ''
                    "
                >
                    <div class="flex-1 min-h-0 flex flex-col overflow-hidden">
                        <div
                            class="flex items-center justify-between mb-2 shrink-0"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-6 w-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center"
                                >
                                    <School class="h-3.5 w-3.5" />
                                </div>
                                <span class="text-xs font-bold text-amber-950"
                                    >Jaringan Kelompok Penerima Manfaat</span
                                >
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="
                                        filterSettings.showConnections =
                                            !filterSettings.showConnections
                                    "
                                    class="p-1 rounded hover:bg-amber-100 text-slate-400 hover:text-slate-700 cursor-pointer"
                                    :title="
                                        filterSettings.showConnections
                                            ? 'Sembunyikan garis distribusi'
                                            : 'Tampilkan garis distribusi'
                                    "
                                >
                                    <Eye
                                        v-if="filterSettings.showConnections"
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />
                                    <EyeOff
                                        v-else
                                        class="h-3.5 w-3.5 text-slate-400"
                                    />
                                </button>
                                <span
                                    class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full"
                                >
                                    {{ visibleKelompokList.length }}/{{
                                        validKelompokList.length
                                    }}
                                    Terhubung
                                </span>
                            </div>
                        </div>

                        <!-- Total Ringkasan Porsi & Penerima Manfaat (Hanya yang Aktif/Visible) -->
                        <div
                            class="grid grid-cols-3 gap-2 text-center mb-2.5 shrink-0"
                        >
                            <div
                                class="bg-white p-2 rounded-lg border border-amber-100 shadow-2xs"
                            >
                                <span
                                    class="text-[10px] text-slate-500 font-medium block"
                                    >Penerima Manfaat</span
                                >
                                <strong
                                    class="text-sm font-bold text-primary"
                                    >{{
                                        totalPenerimaManfaat.toLocaleString(
                                            "id-ID",
                                        )
                                    }}</strong
                                >
                            </div>
                            <div
                                class="bg-white p-2 rounded-lg border border-amber-100 shadow-2xs"
                            >
                                <span
                                    class="text-[10px] text-amber-700 font-medium block"
                                    >Porsi Kecil</span
                                >
                                <strong
                                    class="text-sm font-bold text-amber-700"
                                    >{{
                                        totalPorsiKecil.toLocaleString("id-ID")
                                    }}</strong
                                >
                            </div>
                            <div
                                class="bg-white p-2 rounded-lg border border-amber-100 shadow-2xs"
                            >
                                <span
                                    class="text-[10px] text-blue-700 font-medium block"
                                    >Porsi Besar</span
                                >
                                <strong
                                    class="text-sm font-bold text-blue-700"
                                    >{{
                                        totalPorsiBesar.toLocaleString("id-ID")
                                    }}</strong
                                >
                            </div>
                        </div>

                        <!-- List Kelompok Terhubung dengan Jarak Sesuai Mode Rute -->
                        <div
                            v-if="validKelompokList.length > 0"
                            class="overflow-y-auto pr-1 content-start gap-2"
                            :class="[
                                validKelompokList.length >= 6 && isFullscreen
                                    ? 'grid grid-cols-1 sm:grid-cols-2'
                                    : 'flex flex-col space-y-1.5',
                                isFullscreen ? 'flex-1 min-h-0' : 'max-h-48',
                            ]"
                            :style="
                                isFullscreen
                                    ? 'flex: 1 1 0% !important; min-height: 0 !important; max-height: 100% !important;'
                                    : ''
                            "
                        >
                            <div
                                v-for="k in validKelompokList"
                                :key="k.id || k.uid"
                                @click="focusKelompok(k)"
                                class="w-full text-left p-2.5 rounded-lg border transition-all flex items-center justify-between text-xs cursor-pointer group shadow-2xs"
                                :class="[
                                    isKelompokVisible(k)
                                        ? 'bg-white/90 hover:bg-amber-50/80 border-amber-100/80'
                                        : 'bg-slate-100/70 border-slate-200 opacity-60',
                                ]"
                            >
                                <div class="min-w-0 flex-1 pr-2">
                                    <p
                                        class="font-bold leading-snug break-words"
                                        :class="
                                            isKelompokVisible(k)
                                                ? 'text-slate-800 group-hover:text-amber-700'
                                                : 'text-slate-500 line-through'
                                        "
                                    >
                                        {{ k.nama_kelompok }}
                                    </p>
                                    <div
                                        class="flex items-center gap-1.5 text-[10px] text-slate-500 flex-wrap mt-0.5"
                                    >
                                        <span
                                            class="text-green-800 bg-green-100 px-1.5 py-0.2 rounded font-semibold border border-green-200/60"
                                            >{{ k.kategori }}</span
                                        >
                                        <span class="text-slate-300">•</span>
                                        <span
                                            class="text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded font-semibold border border-amber-200/60"
                                            >PK: {{ k.total_porsi_kecil || 0 }}
                                        </span>
                                        <span
                                            class="text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded font-semibold border border-blue-200/60"
                                            >PB: {{ k.total_porsi_besar || 0 }}
                                        </span>
                                        <span
                                            class="text-slate-700 bg-slate-50 px-1.5 py-0.2 rounded font-semibold border border-slate-400/60"
                                            >Total: {{ k.total_penerima || 0 }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span
                                        v-if="isKelompokVisible(k)"
                                        class="text-[10.5px] font-bold text-amber-700 bg-amber-50 group-hover:bg-amber-100 border border-amber-200 px-1.5 py-0.5 rounded"
                                    >
                                        {{ routeMode === "road" ? "🚗" : "📏" }}
                                        {{ getKelompokDisplayDist(k) }}
                                    </span>

                                    <!-- Eye toggle button -->
                                    <button
                                        type="button"
                                        @click.stop="
                                            toggleSingleKelompok(k.id || k.uid)
                                        "
                                        class="p-1 rounded hover:bg-slate-200/80 text-slate-400 transition-colors cursor-pointer"
                                        :title="
                                            isKelompokVisible(k)
                                                ? 'Sembunyikan titik ini dari peta'
                                                : 'Tampilkan titik ini di peta'
                                        "
                                    >
                                        <Eye
                                            v-if="isKelompokVisible(k)"
                                            class="h-3.5 w-3.5 text-emerald-600"
                                        />
                                        <EyeOff
                                            v-else
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-else
                            class="text-xs text-slate-400 italic text-center py-2 shrink-0"
                        >
                            Belum ada kelompok penerima manfaat dengan titik
                            koordinat terdaftar.
                        </p>
                    </div>

                    <p
                        class="text-[10.5px] text-slate-500 leading-snug shrink-0 pt-2 border-t border-amber-100/60"
                    >
                        Mode
                        {{
                            routeMode === "road"
                                ? "Jalan Riil mengikuti jaringan jalan sesungguhnya (OSRM)"
                                : "Garis Lurus memetakan radius jarak lurus langsung (geodesik)"
                        }}
                        untuk seluruh koneksi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.leaflet-tile) {
    max-width: none !important;
    max-height: none !important;
    visibility: visible !important;
    opacity: 1 !important;
}
:deep(.leaflet-container) {
    background-color: #f1f5f9 !important;
    width: 100% !important;
    height: 100% !important;
}
:deep(.leaflet-tile-pane) {
    opacity: 1 !important;
    z-index: 1 !important;
}
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 10px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    padding: 4px;
}
:deep(.custom-distance-badge),
:deep(.custom-kelompok-dist-badge) {
    pointer-events: none;
    background: transparent !important;
    border: none !important;
    overflow: visible !important;
    width: 0 !important;
    height: 0 !important;
}
:deep(.custom-unit-marker),
:deep(.custom-penerima-marker),
:deep(.custom-domisili-marker) {
    overflow: visible !important;
    background: transparent !important;
    border: none !important;
}
</style>
