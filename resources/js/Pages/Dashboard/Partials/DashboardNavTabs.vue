<script setup>
import { Building2, User, Map } from "lucide-vue-next";

defineProps({
    activeTab: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["update:activeTab", "tabChange"]);

function selectTab(tab) {
    emit("update:activeTab", tab);
    emit("tabChange", tab);
}
</script>

<template>
    <!-- Navigation Tabs -->
    <div
        class="flex items-center justify-start gap-2 border-b border-slate-200 pb-2 overflow-x-auto select-none mb-6"
    >
        <button
            @click="selectTab('overview')"
            :class="[
                'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                activeTab === 'overview'
                    ? 'bg-white text-primary shadow-xs border border-slate-200'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
            ]"
        >
            <Building2 class="h-4 w-4 shrink-0" />
            <span>Data Unit SPPG</span>
        </button>

        <button
            @click="selectTab('profile')"
            :class="[
                'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                activeTab === 'profile'
                    ? 'bg-white text-primary shadow-xs border border-slate-200'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
            ]"
        >
            <User class="h-4 w-4 shrink-0" />
            <span>Data Kepala SPPG</span>
        </button>

        <button
            @click="selectTab('maps')"
            :class="[
                'px-3.5 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-lg transition-colors cursor-pointer flex items-center justify-start text-left gap-2 shrink-0 whitespace-nowrap',
                activeTab === 'maps'
                    ? 'bg-white text-primary shadow-xs border border-slate-200'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-white/60',
            ]"
        >
            <Map class="h-4 w-4 shrink-0" />
            <span>Peta & Geospasial</span>
        </button>
    </div>
</template>
