<script setup>
import { usePage } from "@inertiajs/vue3";
import AppLayout from "./AppLayout.vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const page = usePage();
</script>

<template>
    <AppLayout :title="title" :user="page.props.auth?.user">
        <template #breadcrumbs v-if="$slots.header">
            <slot name="header" />
        </template>
        <slot />
    </AppLayout>
</template>
