<script setup>
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import {
    ClipboardPaste,
    Lightbulb,
    Check,
    AlertCircle,
    Flame,
    Sparkles,
} from 'lucide-vue-next';
import {
    NUTRISI_META,
    parsePastedNutritionText,
} from '@/Services/giziPasteHelper';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    targetTitle: {
        type: String,
        default: 'Porsi Standar',
    },
    initialText: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'apply']);

const inputText = ref('');

watch(
    () => props.show,
    (val) => {
        if (val) {
            inputText.value = props.initialText || '';
        } else {
            inputText.value = '';
        }
    },
    { immediate: true }
);

// Hasil parsing otomatis secara realtime
const parseResult = computed(() => {
    return parsePastedNutritionText(inputText.value, 'energi');
});

function handleApply() {
    if (parseResult.value.count > 0) {
        emit('apply', parseResult.value.values);
    }
}

function handleClose() {
    emit('close');
}
</script>

<template>
    <Modal :show="show" @close="handleClose" max-width="lg">
        <div class="p-5 sm:p-6 space-y-4">
            <!-- Header Modal -->
            <div class="flex items-start gap-3.5">
                <div
                    class="h-11 w-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20 shadow-2xs"
                >
                    <ClipboardPaste class="h-6 w-6 stroke-[2.2]" />
                </div>
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-base font-black text-slate-900 leading-snug">
                            Tempel Data Kandungan Gizi
                        </h3>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200"
                        >
                            {{ targetTitle }}
                        </span>
                    </div>
                    <p class="text-xs font-semibold text-slate-500">
                        Salin 5 nilai zat gizi dari Excel, spreadsheet, tabel, atau teks dan tempel di sini.
                    </p>
                </div>
            </div>

            <!-- Textarea Input Paste -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">
                        Tempel Teks / Data Excel di Sini:
                    </label>
                    <span
                        v-if="parseResult.count > 0"
                        class="text-[11px] font-black text-emerald-600 flex items-center gap-1"
                    >
                        <Check class="h-3.5 w-3.5" />
                        <span>{{ parseResult.count }} Nilai Gizi Terdeteksi</span>
                    </span>
                </div>

                <textarea
                    v-model="inputText"
                    rows="4"
                    placeholder="Contoh dari sel Excel (1 baris/kolom):&#10;550&#10;18.5&#10;16&#10;75&#10;6.5&#10;&#10;Atau format teks:&#10;Energi: 550 kkal, Protein: 18.5 g, Lemak: 16 g, Karbohidrat: 75 g, Serat: 6.5 g"
                    class="w-full text-xs font-medium rounded-xl border border-slate-300 p-3 text-slate-800 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none shadow-2xs font-mono"
                    autofocus
                ></textarea>
            </div>

            <!-- Live Preview Grid -->
            <div class="space-y-1.5">
                <span class="text-[11px] font-black text-slate-600 uppercase tracking-wider block">
                    Pratinjau Nilai yang Terdeteksi:
                </span>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                    <div
                        v-for="item in NUTRISI_META"
                        :key="item.key"
                        :class="[
                            'p-2 rounded-xl border text-center transition-all',
                            parseResult.values[item.key] !== undefined
                                ? 'bg-emerald-50/80 border-emerald-300 ring-1 ring-emerald-200 shadow-2xs'
                                : 'bg-slate-50 border-slate-200 opacity-60',
                        ]"
                    >
                        <div class="text-sm">{{ item.icon }}</div>
                        <div class="text-[10px] font-bold text-slate-600 truncate mt-0.5">
                            {{ item.label }}
                        </div>
                        <div class="text-xs font-black text-slate-900 mt-0.5">
                            <span v-if="parseResult.values[item.key] !== undefined">
                                {{ parseResult.values[item.key] }}
                            </span>
                            <span v-else class="text-slate-400 font-normal">-</span>
                            <span class="text-[9px] text-slate-400 font-normal ml-0.5">
                                {{ item.unit }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips & Bantuan Cerdas -->
            <div
                class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 space-y-1"
            >
                <p class="font-bold text-slate-800 flex items-center gap-1.5">
                    <Lightbulb class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                    <span>Format yang Didukung:</span>
                </p>
                <ul class="list-disc list-inside space-y-0.5 text-slate-600 pl-1 text-[10.5px]">
                    <li>
                        Salin 5 sel dari Excel (kolom vertikal atau baris horizontal terpisah tab).
                    </li>
                    <li>
                        Format teks berlabel (e.g. <em>Energi 550, Prot 18.5, Fat 16, Karbo 75, Serat 6.5</em>).
                    </li>
                    <li>
                        Mendukung koma desimal Indonesia (contoh: <code>18,5</code> otomatis menjadi <code>18.5</code>).
                    </li>
                    <li>
                        <strong>Tips Praktis:</strong> Anda juga bisa langsung menekan <kbd class="px-1 py-0.5 bg-slate-200 rounded text-[9.5px] font-mono">Ctrl + V</kbd> pada kolom input gizi mana pun!
                    </li>
                </ul>
            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button
                    type="button"
                    @click="handleClose"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="handleApply"
                    :disabled="parseResult.count === 0"
                    class="px-4 py-2.5 rounded-xl text-xs font-black bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs transition-all cursor-pointer flex items-center gap-1.5"
                >
                    <Check class="h-4 w-4" />
                    <span>
                        Terapkan Nilai Gizi
                        <template v-if="parseResult.count > 0">
                            ({{ parseResult.count }})
                        </template>
                    </span>
                </button>
            </div>
        </div>
    </Modal>
</template>
