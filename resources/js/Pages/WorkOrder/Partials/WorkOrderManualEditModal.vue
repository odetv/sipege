<script setup>
import { ref, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { X, FileSpreadsheet, ChefHat } from "lucide-vue-next";
import WorkOrderManualEditor from "./WorkOrderManualEditor.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    workOrder: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "saved"]);

watch(
    () => props.show,
    (val) => {
        if (typeof document !== "undefined") {
            document.body.style.overflow = val ? "hidden" : "";
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    if (typeof document !== "undefined") {
        document.body.style.overflow = "";
    }
});

const editorRef = ref(null);

function handleSaved() {
    emit("saved");
}

function handleClose() {
    if (editorRef.value?.isFormDirty) {
        editorRef.value.handleRequestClose();
        return;
    }
    emit("close");
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show && workOrder"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-5 bg-slate-900/65 backdrop-blur-xs animate-in fade-in duration-200"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200"
            >
                <!-- Header Modal with Clean Styling -->
                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                            <ChefHat class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm sm:text-base font-black text-white leading-tight">
                                    Editor Work Order Manual
                                </h3>
                                <span class="px-2 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 text-[11px] font-black text-amber-300">
                                    ✍️ Mode Manual
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5 font-medium">
                                {{ workOrder.nomor_wo }} &bull; {{ workOrder.nama_menu || 'Belum ada nama menu' }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="handleClose"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                        title="Tutup Modal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Content Container -->
                <div class="p-5 sm:p-6 overflow-y-auto flex-1 bg-slate-50/50">
                    <WorkOrderManualEditor
                        ref="editorRef"
                        :work-order="workOrder"
                        :kelompok-list="kelompokList"
                        :is-embedded="false"
                        @saved="handleSaved"
                        @back="emit('close')"
                    />
                </div>
            </div>
        </div>
    </Teleport>
</template>
