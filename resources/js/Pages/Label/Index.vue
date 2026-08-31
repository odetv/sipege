<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import LabelCetakTab from "./Partials/LabelCetakTab.vue";
import LabelTemplateTab from "./Partials/LabelTemplateTab.vue";
import { bgnStandardElements, getDefaultTemplatePresets } from "./labelPresets.js";
import { Printer, Layout, Sparkles } from "lucide-vue-next";

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
});

// Sub-menu Navigation: 'cetak' | 'template'
const activeSubMenu = ref("cetak");

// Default Presets for Label Templates
const defaultTemplatePresets = getDefaultTemplatePresets();

// Persistent Templates state from localStorage
const templates = ref(getDefaultTemplatePresets());
const activeTemplateId = ref("bgn_standard_4_3");

function sanitizeTemplateList(list) {
    if (!Array.isArray(list) || list.length === 0) {
        return getDefaultTemplatePresets();
    }
    const seenIds = new Set();
    const seenNames = new Set();
    const unique = [];

    for (const item of list) {
        if (!item || !item.id) continue;
        const normalizedName = (item.name || "").trim().toLowerCase();
        
        // Prevent duplicate IDs or duplicate template preset names
        if (!seenIds.has(item.id) && !seenNames.has(normalizedName)) {
            seenIds.add(item.id);
            seenNames.add(normalizedName);
            unique.push({
                ...item,
                elements: Array.isArray(item.elements) ? item.elements : [],
            });
        }
    }
    return unique.length > 0 ? unique : getDefaultTemplatePresets();
}

onMounted(() => {
    try {
        const savedTemplates = localStorage.getItem("sipege_label_templates");
        if (savedTemplates) {
            const parsed = JSON.parse(savedTemplates);
            if (Array.isArray(parsed) && parsed.length > 0) {
                templates.value = sanitizeTemplateList(parsed);
            }
        }
        const savedActiveId = localStorage.getItem(
            "sipege_active_label_template_id",
        );
        if (savedActiveId && templates.value.some(t => t.id === savedActiveId)) {
            activeTemplateId.value = savedActiveId;
        } else {
            activeTemplateId.value = templates.value[0]?.id || "bgn_standard_4_3";
        }
    } catch (e) {
        console.error("Failed to load saved label templates:", e);
    }
});

function saveTemplatesToStorage() {
    try {
        localStorage.setItem(
            "sipege_label_templates",
            JSON.stringify(templates.value),
        );
        localStorage.setItem(
            "sipege_active_label_template_id",
            activeTemplateId.value,
        );
    } catch (e) {
        console.error("Failed to persist label templates:", e);
    }
}

watch(
    [templates, activeTemplateId],
    () => {
        saveTemplatesToStorage();
    },
    { deep: true },
);

const activeTemplate = computed(() => {
    return (
        templates.value.find((t) => t.id === activeTemplateId.value) ||
        templates.value[0] ||
        defaultTemplatePresets[0]
    );
});
</script>

<template>
    <AppLayout
        title="Label"
        subtitle="Canva Interactive Editor & Generator Label Kemasan Box Makanan SPPG BGN"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Label" />

        <div class="space-y-4">
            <!-- Top Sub-menu Navigation Bar (Disembunyikan saat print) -->
            <div class="print:hidden">
                <div
                    class="bg-white p-1.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between gap-2 flex-wrap"
                >
                    <!-- Nav Tabs Switcher -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            type="button"
                            @click="activeSubMenu = 'cetak'"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2',
                                activeSubMenu === 'cetak'
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                            ]"
                        >
                            <Printer class="h-4 w-4" />
                            <span>Cetak Label</span>
                        </button>

                        <button
                            type="button"
                            @click="activeSubMenu = 'template'"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2',
                                activeSubMenu === 'template'
                                    ? 'bg-primary text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100',
                            ]"
                        >
                            <Layout class="h-4 w-4" />
                            <span>🎨 Canva Template Designer</span>
                        </button>
                    </div>

                    <!-- Active Template Indicator -->
                    <div
                        class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs"
                    >
                        <span class="text-slate-500 font-semibold"
                            >Template Aktif Cetak:</span
                        >
                        <span
                            class="font-extrabold text-primary flex items-center gap-1"
                        >
                            <Sparkles class="h-3.5 w-3.5 text-primary" />
                            <span>{{ activeTemplate.name }}</span>
                        </span>
                        <span
                            class="bg-slate-200 text-slate-700 text-[10px] font-mono font-bold px-1.5 py-0.5 rounded"
                        >
                            Rasio {{ activeTemplate.aspect_ratio || "4:3" }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab 1: CETAK LABEL -->
            <div v-show="activeSubMenu === 'cetak'">
                <LabelCetakTab
                    :user="user"
                    :unit-sppg="unitSppg"
                    :kelompok-list="kelompokList"
                    :work-orders="workOrders"
                    :initial-active-wo="initialActiveWo"
                    :active-template="activeTemplate"
                    @go-to-template="activeSubMenu = 'template'"
                />
            </div>

            <!-- Tab 2: CANVA TEMPLATE & LAYOUT DESIGNER -->
            <div v-show="activeSubMenu === 'template'">
                <LabelTemplateTab
                    :unit-sppg="unitSppg"
                    v-model:templates="templates"
                    v-model:active-template-id="activeTemplateId"
                    @save-templates="saveTemplatesToStorage"
                />
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Sembunyikan elemen layout yang tidak perlu saat cetak */
    body {
        background: #ffffff !important;
        font-family: inherit !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    aside,
    header,
    footer,
    .print\:hidden {
        display: none !important;
    }
    main,
    .max-w-7xl,
    .space-y-4 {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .bgn-label-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-bottom: 20px !important;
    }
}
</style>
