<script setup>
import Perencanaan from "./Perencanaan.vue";

defineProps({
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
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    editWorkOrder: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Perencanaan v-bind="$props" />
</template>
