<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import LabelCetakTab from "./Partials/LabelCetakTab.vue";

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
    workOrders: {
        type: Array,
        default: () => [],
    },
    initialActiveWo: {
        type: Object,
        default: null,
    },
    periodes: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout
        title="Label"
        subtitle="Generator Resmi & Cetak Label Kemasan Box Makanan SPPG Badan Gizi Nasional"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Label SPPG" />

        <div class="space-y-5">
            <LabelCetakTab
                :user="user"
                :unit-sppg="unitSppg"
                :kelompok-list="kelompokList"
                :work-orders="workOrders"
                :initial-active-wo="initialActiveWo"
            />
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: 90mm 60mm;
        margin: 0mm;
    }
    html, body {
        background: #ffffff !important;
        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 90mm !important;
        height: 60mm !important;
        overflow: hidden !important;
    }
    aside,
    header,
    footer,
    nav,
    .print\:hidden {
        display: none !important;
    }
    main,
    .max-w-7xl,
    .space-y-5,
    .space-y-6 {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 90mm !important;
        width: 90mm !important;
    }

    .bgn-print-page {
        width: 90mm !important;
        height: 60mm !important;
        min-width: 90mm !important;
        max-width: 90mm !important;
        min-height: 60mm !important;
        max-height: 60mm !important;
        position: relative !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    .bgn-print-page:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }
    .bgn-print-card-wrapper {
        width: 90mm !important;
        height: 60mm !important;
        margin: 0 !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }
    .bgn-print-card-wrapper .bgn-label-card {
        width: 90mm !important;
        height: 60mm !important;
        max-width: 90mm !important;
        max-height: 60mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
    }
}
</style>
