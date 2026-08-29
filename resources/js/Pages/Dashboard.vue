<script setup>
import { ref, computed, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

import DashboardHeroBanner from "@/Pages/Dashboard/Partials/DashboardHeroBanner.vue";
import DashboardMetricCards from "@/Pages/Dashboard/Partials/DashboardMetricCards.vue";
import DashboardNavTabs from "@/Pages/Dashboard/Partials/DashboardNavTabs.vue";
import DashboardUnitTab from "@/Pages/Dashboard/Partials/DashboardUnitTab.vue";
import DashboardProfileTab from "@/Pages/Dashboard/Partials/DashboardProfileTab.vue";
import DashboardMapTab from "@/Pages/Dashboard/Partials/DashboardMapTab.vue";

import { formatNamaLengkap } from "@/Services/wilayah";

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
});

const activeTab = ref("overview");
const mapTabRef = ref(null);

// Computed Display
const fullName = computed(() => {
    if (props.user.nama_lengkap) return props.user.nama_lengkap;
    return formatNamaLengkap(
        props.user.nama,
        props.user.gelar_depan,
        props.user.gelar_belakang,
    );
});

const userInitials = computed(() => {
    const name = props.user.nama || props.user.name;
    if (!name) return "U";
    const words = name.trim().split(" ");
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

const statusVariant = computed(() => {
    const status = props.unitSppg?.status;
    if (status === "Operasional") return "success";
    if (status === "Belum Operasional") return "secondary";
    if (status?.startsWith("Suspend Ringan")) return "warning";
    return "destructive";
});

const domisiliFullAddress = computed(() => {
    const pos = props.user.kode_pos_domisili;
    const wilayah = [
        props.user.desa_kelurahan_domisili
            ? `Desa/Kelurahan ${props.user.desa_kelurahan_domisili}`
            : "",
        props.user.kecamatan_domisili
            ? `Kecamatan ${props.user.kecamatan_domisili}`
            : "",
        props.user.kabupaten_domisili
            ? `Kabupaten/Kota ${props.user.kabupaten_domisili}`
            : "",
        props.user.provinsi_domisili
            ? `Provinsi ${props.user.provinsi_domisili}${pos ? ` (${pos})` : ""}`
            : pos
              ? `(${pos})`
              : "",
    ]
        .filter(Boolean)
        .join(", ");

    if (props.user.alamat_lengkap_domisili) {
        return `${props.user.alamat_lengkap_domisili}, ${wilayah}`;
    }
    return wilayah;
});

const unitFullAddress = computed(() => {
    if (!props.unitSppg) return "";
    const wilayah = [
        props.unitSppg.desa_kelurahan
            ? `Desa/Kelurahan ${props.unitSppg.desa_kelurahan}`
            : "",
        props.unitSppg.kecamatan ? `Kecamatan ${props.unitSppg.kecamatan}` : "",
        props.unitSppg.kabupaten
            ? `Kabupaten/Kota ${props.unitSppg.kabupaten}`
            : "",
        props.unitSppg.provinsi
            ? `Provinsi ${props.unitSppg.provinsi}${props.unitSppg.kode_pos ? ` (${props.unitSppg.kode_pos})` : ""}`
            : props.unitSppg.kode_pos
              ? `(${props.unitSppg.kode_pos})`
              : "",
    ]
        .filter(Boolean)
        .join(", ");

    if (props.unitSppg.alamat_lengkap) {
        return `${props.unitSppg.alamat_lengkap}, ${wilayah}`;
    }
    return wilayah;
});

function onToggleCollapse() {
    nextTick(() => {
        setTimeout(() => {
            if (mapTabRef.value?.refreshMap) mapTabRef.value.refreshMap();
        }, 310);
    });
}

function switchTab(tab) {
    activeTab.value = tab;
    nextTick(() => {
        setTimeout(() => {
            window.dispatchEvent(new Event("resize"));
            if (tab === "maps" && mapTabRef.value?.refreshMap) {
                mapTabRef.value.refreshMap();
            }
        }, 150);
    });
}
</script>

<template>
    <Head title="Dashboard SPPG" />

    <AppLayout
        :user="user"
        :unit-sppg="unitSppg"
        @toggle-collapse="onToggleCollapse"
    >
        <!-- Welcome Hero Banner -->
        <DashboardHeroBanner
            :user="user"
            :unit-sppg="unitSppg"
            :full-name="fullName"
            :status-variant="statusVariant"
        />

        <!-- Metric Stat Cards -->
        <DashboardMetricCards
            :user="user"
            :unit-sppg="unitSppg"
            :status-variant="statusVariant"
        />

        <!-- Navigation Tabs -->
        <DashboardNavTabs
            v-model:activeTab="activeTab"
            @tab-change="switchTab"
        />

        <!-- ================= TAB 1: DATA UNIT SPPG ================= -->
        <DashboardUnitTab
            v-show="activeTab === 'overview'"
            :unit-sppg="unitSppg"
        />

        <!-- ================= TAB 2: PROFIL KEPALA SPPG ================= -->
        <DashboardProfileTab
            v-show="activeTab === 'profile'"
            :user="user"
            :full-name="fullName"
            :user-initials="userInitials"
            :domisili-full-address="domisiliFullAddress"
        />

        <!-- ================= TAB 3: PETA & GEOSPASIAL ================= -->
        <DashboardMapTab
            v-show="activeTab === 'maps'"
            ref="mapTabRef"
            :user="user"
            :unit-sppg="unitSppg"
            :kelompok-list="kelompokList"
            :full-name="fullName"
            :domisili-full-address="domisiliFullAddress"
            :unit-full-address="unitFullAddress"
        />
    </AppLayout>
</template>
