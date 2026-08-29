<script setup>
import { ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

// Partials
import GiziTkpiTab from "@/Pages/Gizi/Partials/GiziTkpiTab.vue";
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
    activeTab: {
        type: String,
        default: "tkpi",
    },
    initialStep: {
        type: String,
        default: null,
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

// Sub-Menu Utama Gizi SPPG
// 'tkpi' | 'analisa-pm' | 'daftar-menu' | 'rancang-menu' | 'buat-menu' | 'kalender-menu'
const activeSubMenu = ref(props.activeTab || "tkpi");

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
            <!-- 1. SUB MENU 1: TKPI -->
            <GiziTkpiTab
                v-if="activeSubMenu === 'tkpi'"
                :tkpi-list="tkpiList"
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
                :tkpi-list="tkpiList"
                :stats="stats"
                :initial-step="initialStep"
            />

            <!-- 5. SUB MENU 5: KALENDER MENU -->
            <GiziKalenderMenuTab
                v-if="activeSubMenu === 'kalender-menu'"
                @open-rancang-menu="selectSubMenu('rancang-menu')"
            />
        </div>
    </AppLayout>
</template>
