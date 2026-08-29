<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import GeospatialMap from "@/Components/GeospatialMap.vue";
import { Map, Maximize2, Minimize2, AlertTriangle } from "lucide-vue-next";
import { cn } from "@/lib/utils";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    fullName: {
        type: String,
        required: true,
    },
    domisiliFullAddress: {
        type: String,
        default: "",
    },
    unitFullAddress: {
        type: String,
        default: "",
    },
});

const geoMapRef = ref(null);
const mapCardContainerRef = ref(null);
const isMapFullscreen = ref(false);

function refreshMap() {
    geoMapRef.value?.refresh();
}

function toggleMapFullscreen() {
    isMapFullscreen.value = !isMapFullscreen.value;

    if (isMapFullscreen.value) {
        if (!document.fullscreenElement && mapCardContainerRef.value) {
            const el =
                mapCardContainerRef.value.$el || mapCardContainerRef.value;
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(() => {});
            }
        }
    } else {
        if (document.fullscreenElement) {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
        }
    }

    nextTick(() => {
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 50);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 150);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 300);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 600);
    });
}

function handleFullscreenChange() {
    isMapFullscreen.value = !!document.fullscreenElement;
    nextTick(() => {
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 50);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 150);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 300);
        setTimeout(() => {
            geoMapRef.value?.refresh();
        }, 600);
    });
}

onMounted(() => {
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
});

onBeforeUnmount(() => {
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    document.removeEventListener(
        "webkitfullscreenchange",
        handleFullscreenChange,
    );
});

defineExpose({
    refreshMap,
    geoMapRef,
});
</script>

<template>
    <!-- ================= TAB 3: PETA & GEOSPASIAL ================= -->
    <div class="space-y-4">
        <Card
            ref="mapCardContainerRef"
            :className="
                cn(
                    'bg-white border-slate-200 shadow-sm overflow-hidden transition-all flex flex-col',
                    isMapFullscreen &&
                        '!fixed !inset-0 !z-50 !h-screen !w-screen !m-0 !p-0 !bg-slate-50 !rounded-none !border-none',
                )
            "
            :style="
                isMapFullscreen
                    ? 'height: 100vh !important; width: 100vw !important; max-height: 100vh !important; display: flex !important; flex-direction: column !important;'
                    : ''
            "
        >
            <CardHeader
                className="border-b border-slate-100 p-3.5 sm:p-4 bg-white shrink-0"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <CardTitle
                            className="text-base sm:text-lg font-bold flex items-center gap-2 text-slate-900"
                        >
                            <Map class="h-5 w-5 text-primary" />
                            <span>Peta Analisis Geospasial SPPG</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm mt-0.5"
                            >Visualisasi Jaringan Peta dan Geospasial
                            SPPG</CardDescription
                        >
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="toggleMapFullscreen"
                        className="shrink-0 h-9 px-3 bg-white hover:bg-slate-50 border-slate-300 shadow-xs font-semibold text-xs flex items-center gap-1.5 cursor-pointer text-slate-800"
                        :title="
                            isMapFullscreen
                                ? 'Keluar Layar Penuh (ESC)'
                                : 'Tampilkan Layar Penuh'
                        "
                    >
                        <Minimize2
                            v-if="isMapFullscreen"
                            class="h-4 w-4 text-primary"
                        />
                        <Maximize2 v-else class="h-4 w-4 text-primary" />
                        <span class="hidden sm:inline">{{
                            isMapFullscreen
                                ? "Keluar Layar Penuh"
                                : "Layar Penuh"
                        }}</span>
                    </Button>
                </div>
            </CardHeader>
            <CardContent
                :className="
                    cn(
                        'flex-1 flex flex-col min-h-0 overflow-hidden',
                        isMapFullscreen ? 'p-1 sm:p-4' : 'p-3 sm:p-4',
                    )
                "
                :style="
                    isMapFullscreen
                        ? 'flex: 1 1 0% !important; height: calc(100vh - 75px) !important; min-height: 0 !important;'
                        : ''
                "
            >
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
                    :kelompok-list="kelompokList"
                    :is-fullscreen="isMapFullscreen"
                    height="540px"
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
</template>
