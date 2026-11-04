<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;

            if (dialog.value && !dialog.value.open) {
                dialog.value.showModal();
            }
        } else {
            document.body.style.overflow = '';

            setTimeout(() => {
                if (dialog.value && dialog.value.open) {
                    dialog.value.close();
                }
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);

    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
        'full': 'sm:max-w-[96vw]',
        'landscape': 'sm:max-w-[95vw] lg:max-w-[92vw] xl:max-w-[90vw]',
    }[props.maxWidth] || (props.maxWidth?.startsWith('max-w-') ? props.maxWidth : 'sm:max-w-2xl');
});
</script>

<template>
    <Teleport to="body">
        <dialog
            class="z-[99999] m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent p-0 border-none"
            ref="dialog"
            style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
        >
            <div
                class="fixed inset-0 z-[99999] overflow-y-auto p-3 sm:p-5 lg:p-6 flex min-h-full items-center justify-center"
                scroll-region
                style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-show="show"
                        class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity cursor-pointer"
                        style="top: 0; left: 0; right: 0; bottom: 0; margin: 0;"
                        @click="close"
                    />
                </Transition>

                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-show="show"
                        class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all w-full max-w-[95vw] border border-slate-200 my-auto animate-in fade-in zoom-in-95 duration-200"
                        :class="maxWidthClass"
                    >
                        <slot v-if="showSlot" />
                    </div>
                </Transition>
            </div>
        </dialog>
    </Teleport>
</template>
