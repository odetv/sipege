<script setup>
import { ChevronRight, Menu, PanelLeftClose, PanelLeftOpen } from "lucide-vue-next";

const props = defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
    subtitle: {
        type: String,
        default: "",
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["toggleCollapse", "openMobile"]);
</script>

<template>
    <header
        class="h-16 bg-white border-b border-slate-200/90 px-4 sm:px-6 lg:px-8 flex items-center justify-between shrink-0"
    >
        <div class="flex items-center gap-3">
            <!-- Desktop Toggle Expand/Collapse Sidebar Button -->
            <button
                type="button"
                @click="$emit('toggleCollapse')"
                class="hidden lg:flex items-center justify-center h-9 w-9 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200/80 transition-colors cursor-pointer"
                :title="isCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
                aria-label="Toggle Sidebar"
            >
                <PanelLeftOpen v-if="isCollapsed" class="h-4 w-4 text-primary" />
                <PanelLeftClose v-else class="h-4 w-4" />
            </button>

            <!-- Mobile Menu Button -->
            <button
                type="button"
                @click="$emit('openMobile')"
                class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden cursor-pointer"
                aria-label="Buka Menu"
            >
                <Menu class="h-5 w-5" />
            </button>

            <!-- Breadcrumbs -->
            <slot name="breadcrumbs">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="font-bold text-slate-900">{{ title }}</span>
                    <template v-if="subtitle || unitSppg?.nama">
                        <ChevronRight class="h-3.5 w-3.5 text-slate-400 hidden sm:inline" />
                        <span class="text-xs text-slate-500 hidden sm:inline">
                            {{ subtitle || unitSppg?.nama }}
                        </span>
                    </template>
                </div>
            </slot>
        </div>

        <!-- Right Side Actions Slot -->
        <div class="flex items-center gap-3">
            <slot name="actions" />
        </div>
    </header>
</template>
