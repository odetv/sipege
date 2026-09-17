<script setup>
import PetunjukIndex from "@/Pages/Petunjuk/Index.vue";

const props = defineProps({
    documents: {
        type: Array,
        default: () => [],
    },
    sops: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    activeType: {
        type: String,
        default: "sop",
    },
    selectedId: {
        type: [String, Number],
        default: null,
    },
});

const docs = props.documents.length > 0 ? props.documents : props.sops;
</script>

<template>
    <PetunjukIndex
        :documents="docs"
        :categories="categories"
        :summary="summary"
        :active-type="activeType || 'sop'"
        :selected-id="selectedId"
    />
</template>
