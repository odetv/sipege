<script setup>
import { ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import LabelCetakTab from "./Partials/LabelCetakTab.vue";
import LabelDaftarTab from "./Partials/LabelDaftarTab.vue";
import { ClipboardList, Edit3 } from "lucide-vue-next";

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
    savedLabels: {
        type: Array,
        default: () => [],
    },
    activeSubMenu: {
        type: String,
        default: "buat", // 'buat' | 'daftar'
    },
    editLabelId: {
        type: [String, Number],
        default: null,
    },
});

// Main Tab State: 'buat' | 'daftar'
const currentTab = ref(props.activeSubMenu === "daftar" ? "daftar" : "buat");

// Editing Label state
const editingLabel = ref(null);

watch(
    () => props.activeSubMenu,
    (newVal) => {
        if (newVal === "daftar") {
            currentTab.value = "daftar";
        } else {
            currentTab.value = "buat";
        }
    },
    { immediate: true },
);

watch(
    [() => props.editLabelId, () => props.savedLabels],
    ([editId, labels]) => {
        if (editId && Array.isArray(labels)) {
            const found = labels.find(
                (l) => String(l.id) === String(editId) || l.nomor_label === editId,
            );
            if (found) {
                editingLabel.value = found;
                currentTab.value = "buat";
            }
        }
    },
    { immediate: true },
);

function switchTab(tab) {
    currentTab.value = tab;
    if (tab === "daftar") {
        router.visit(route("label.daftar"), { preserveScroll: true, preserveState: true });
    } else {
        router.visit(route("label.buat"), { preserveScroll: true, preserveState: true });
    }
}

function handleEditLabel(label) {
    editingLabel.value = label;
    currentTab.value = "buat";
}

function handleCancelEdit() {
    editingLabel.value = null;
}

function handleGoToBuat() {
    editingLabel.value = null;
    currentTab.value = "buat";
}
</script>

<template>
    <AppLayout
        title="Label"
        subtitle="Generator Resmi & Arsip Cetak Label Kemasan Box Makanan SPPG Badan Gizi Nasional"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Label SPPG" />

        <div class="space-y-5">
            <!-- Top Sub-menu Navigation Bar (Disembunyikan saat print) -->
            <div class="print:hidden">
                <div
                    class="bg-white p-1.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between gap-3 flex-wrap"
                >
                    <!-- Main Sub-menu Switcher: Buat Label vs Daftar Label -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- 1. Buat Label -->
                        <button
                            type="button"
                            @click="switchTab('buat')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2',
                                currentTab === 'buat'
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                            ]"
                        >
                            <Edit3 class="h-4 w-4" />
                            <span>Buat Label</span>
                        </button>

                        <!-- 2. Daftar Label -->
                        <button
                            type="button"
                            @click="switchTab('daftar')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2',
                                currentTab === 'daftar'
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                            ]"
                        >
                            <ClipboardList class="h-4 w-4" />
                            <span>Daftar Label</span>
                            <span
                                v-if="(savedLabels || []).length > 0"
                                :class="[
                                    'px-1.5 py-0.5 rounded-full text-[10.5px] font-mono font-extrabold',
                                    currentTab === 'daftar'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-primary/10 text-primary',
                                ]"
                            >
                                {{ (savedLabels || []).length }}
                            </span>
                        </button>
                    </div>

                    <!-- Badge Info Standar Resmi -->
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-blue-50/80 border border-blue-200/80 rounded-xl text-xs text-blue-900 font-bold">
                        <span>🏛️ Format Standar Resmi Badan Gizi Nasional (BGN)</span>
                    </div>
                </div>
            </div>

            <!-- SUBMENU 1: BUAT LABEL -->
            <div v-show="currentTab === 'buat'">
                <LabelCetakTab
                    :user="user"
                    :unit-sppg="unitSppg"
                    :kelompok-list="kelompokList"
                    :work-orders="workOrders"
                    :initial-active-wo="initialActiveWo"
                    :editing-label="editingLabel"
                    @cancel-edit="handleCancelEdit"
                />
            </div>

            <!-- SUBMENU 2: DAFTAR LABEL -->
            <div v-show="currentTab === 'daftar'">
                <LabelDaftarTab
                    :user="user"
                    :unit-sppg="unitSppg"
                    :saved-labels="savedLabels"
                    @go-to-buat="handleGoToBuat"
                    @edit-label="handleEditLabel"
                />
            </div>
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
