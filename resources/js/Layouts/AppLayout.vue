<script setup>
import { ref, nextTick } from "vue";
import Sidebar from "./Partials/Sidebar.vue";
import Header from "./Partials/Header.vue";
import Footer from "./Partials/Footer.vue";

const props = defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
    subtitle: {
        type: String,
        default: "",
    },
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["toggleCollapse"]);

const isMobileSidebarOpen = ref(false);
const isCollapsed = ref(false);

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    emit("toggleCollapse", isCollapsed.value);
    nextTick(() => {
        setTimeout(() => {
            window.dispatchEvent(new Event("resize"));
        }, 310);
    });
}

function openMobileSidebar() {
    isMobileSidebarOpen.value = true;
}

function closeMobileSidebar() {
    isMobileSidebarOpen.value = false;
}

defineExpose({
    isCollapsed,
    isMobileSidebarOpen,
    toggleCollapse,
});
</script>

<template>
    <div
        class="h-screen w-screen overflow-hidden bg-slate-50/70 text-slate-900 flex"
    >
        <!-- Backdrop Mobile -->
        <div
            v-if="isMobileSidebarOpen"
            @click="closeMobileSidebar"
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden transition-opacity"
        ></div>

        <!-- Reusable Sidebar -->
        <Sidebar
            :user="user"
            :unit-sppg="unitSppg"
            :is-collapsed="isCollapsed"
            :is-mobile-open="isMobileSidebarOpen"
            @update:is-mobile-open="isMobileSidebarOpen = $event"
            @update:is-collapsed="isCollapsed = $event"
        />

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">
            <!-- Reusable Header -->
            <slot
                name="header"
                :is-collapsed="isCollapsed"
                :toggle-collapse="toggleCollapse"
                :open-mobile="openMobileSidebar"
            >
                <Header
                    :title="title"
                    :subtitle="subtitle"
                    :unit-sppg="unitSppg"
                    :is-collapsed="isCollapsed"
                    @toggle-collapse="toggleCollapse"
                    @open-mobile="openMobileSidebar"
                >
                    <template #breadcrumbs>
                        <slot name="breadcrumbs" />
                    </template>
                    <template #actions>
                        <slot name="header-actions" />
                    </template>
                </Header>
            </slot>

            <!-- Scrollable Page Container -->
            <div class="flex-1 flex flex-col justify-between overflow-y-auto min-h-0">
                <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6">
                    <slot :is-collapsed="isCollapsed" :toggle-collapse="toggleCollapse" />
                </main>

                <!-- Reusable Footer -->
                <slot name="footer">
                    <Footer />
                </slot>
            </div>
        </div>
    </div>
</template>
