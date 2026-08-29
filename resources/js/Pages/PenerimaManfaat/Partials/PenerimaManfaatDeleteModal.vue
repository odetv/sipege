<script setup>
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/ui/Button.vue";
import { Trash2 } from "lucide-vue-next";

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    deletingKelompok: {
        type: Object,
        default: null,
    },
    isDeleting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "confirm"]);
</script>

<template>
    <!-- ================= MODAL KONFIRMASI HAPUS ================= -->
    <Modal :show="isOpen" @close="emit('close')" max-width="md">
        <div v-if="deletingKelompok" class="p-6 space-y-4">
            <div
                class="h-12 w-12 rounded-full bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mx-auto"
            >
                <Trash2 class="h-6 w-6" />
            </div>
            <div class="text-center space-y-1">
                <h3 class="text-base font-bold text-slate-900">
                    Hapus Kelompok Penerima Manfaat?
                </h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Anda yakin ingin menghapus data kelompok
                    <strong class="text-slate-800"
                        >"{{ deletingKelompok.nama_kelompok }}"</strong
                    >? Seluruh rincian jumlah penerima manfaat terkait juga
                    akan dihapus permanen.
                </p>
            </div>

            <div class="flex items-center justify-center gap-3 pt-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="emit('close')"
                    :disabled="isDeleting"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="emit('confirm')"
                    :disabled="isDeleting"
                    className="bg-rose-600 hover:bg-rose-700 text-white"
                >
                    {{ isDeleting ? "Menghapus..." : "Ya, Hapus Kelompok" }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
