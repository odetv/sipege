<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

// Partials
import GiziDatabasePanganTab from "@/Pages/Gizi/Partials/GiziDatabasePanganTab.vue";
import GiziAnalisaPmTab from "@/Pages/Gizi/Partials/GiziAnalisaPmTab.vue";
import GiziDaftarMenuTab from "@/Pages/Gizi/Partials/GiziDaftarMenuTab.vue";
import GiziRancangMenuTab from "@/Pages/Gizi/Partials/GiziRancangMenuTab.vue";
import GiziKalenderMenuTab from "@/Pages/Gizi/Partials/GiziKalenderMenuTab.vue";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    tkpiList: {
        type: Array,
        default: () => [],
    },
    tkpiDatasets: {
        type: Object,
        default: () => ({
            fta: [],
            csv: [],
        }),
    },
    activeTab: {
        type: String,
        default: "database-pangan",
    },
    initialStep: {
        type: String,
        default: null,
    },
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    activeWorkOrder: {
        type: Object,
        default: null,
    },
    defaultSource: {
        type: String,
        default: "fta",
    },
    stats: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
            kategori_breakdown: {},
        }),
    },
});

// Sumber Dataset TKPI Aktif (Default: 'fta' NutriSurvey Indo FTA atau dari Work Order yang diedit)
const initialSource = (props.activeWorkOrder && props.activeWorkOrder.database_pangan)
    ? props.activeWorkOrder.database_pangan
    : (props.defaultSource || (typeof window !== "undefined" ? localStorage.getItem("sipege_tkpi_source") || "fta" : "fta"));

const selectedTkpiSource = ref(initialSource);

watch(
    () => props.activeWorkOrder,
    (wo) => {
        if (wo && wo.database_pangan) {
            selectedTkpiSource.value = wo.database_pangan;
            if (typeof window !== "undefined") {
                localStorage.setItem("sipege_tkpi_source", wo.database_pangan);
            }
        }
    },
    { immediate: true }
);

function handleTkpiSourceChange(newSource) {
    selectedTkpiSource.value = newSource;
    if (typeof window !== "undefined") {
        localStorage.setItem("sipege_tkpi_source", newSource);
    }
}

const activeTkpiList = computed(() => {
    if (props.tkpiDatasets && props.tkpiDatasets[selectedTkpiSource.value] && props.tkpiDatasets[selectedTkpiSource.value].length > 0) {
        return props.tkpiDatasets[selectedTkpiSource.value];
    }
    return props.tkpiList || [];
});

// Sub-Menu Utama Gizi SPPG
// 'database-pangan' | 'analisa-pm' | 'daftar-menu' | 'rancang-menu' | 'buat-menu' | 'kalender-menu'
const activeSubMenu = ref(props.activeTab || "database-pangan");

watch(
    () => props.activeTab,
    (val) => {
        if (val) activeSubMenu.value = val;
    },
);

function selectSubMenu(tabId) {
    activeSubMenu.value = tabId;
}
</script>

<template>
    <AppLayout
        title="Gizi"
        subtitle="Perhitungan Kebutuhan dan Produksi Makan Bergizi Gratis (MBG)"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Gizi" />

        <div class="space-y-6">
            <!-- 1. SUB MENU 1: DATABASE PANGAN -->
            <GiziDatabasePanganTab
                v-if="activeSubMenu === 'database-pangan' || activeSubMenu === 'tkpi'"
                :tkpi-list="activeTkpiList"
                :tkpi-datasets="tkpiDatasets"
                :selected-source="selectedTkpiSource"
                @update-source="handleTkpiSourceChange"
            />

            <!-- 2. SUB MENU 2: ANALISA PM -->
            <GiziAnalisaPmTab
                v-if="activeSubMenu === 'analisa-pm'"
                :kelompok-list="kelompokList"
                :stats="stats"
            />

            <!-- 3. SUB MENU 3: DAFTAR MENU -->
            <GiziDaftarMenuTab
                v-if="activeSubMenu === 'daftar-menu'"
                :work-orders-list="workOrdersList"
                @open-rancang-menu="selectSubMenu('rancang-menu')"
            />

            <!-- 4. SUB MENU 4: RANCANG MENU (PERENCANAAN PRODUKSI, FORMULA GIZI, PEMBELIAN BAHAN) -->
            <GiziRancangMenuTab
                v-if="
                    activeSubMenu === 'rancang-menu' ||
                    activeSubMenu === 'buat-menu'
                "
                :user="user"
                :unit-sppg="unitSppg"
                :kelompok-list="kelompokList"
                :tkpi-list="activeTkpiList"
                :selected-source="selectedTkpiSource"
                :stats="stats"
                :initial-step="initialStep"
                :work-orders-list="workOrdersList"
                :active-work-order="activeWorkOrder"
                @update-source="handleTkpiSourceChange"
            />

            <!-- 5. SUB MENU 5: KALENDER MENU -->
            <GiziKalenderMenuTab
                v-if="activeSubMenu === 'kalender-menu'"
                :work-orders-list="workOrdersList"
                @open-rancang-menu="selectSubMenu('rancang-menu')"
            />
        </div>
    </AppLayout>
</template>
