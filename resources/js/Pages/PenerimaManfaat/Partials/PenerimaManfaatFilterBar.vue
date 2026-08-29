<script setup>
import { ref } from "vue";
import { Search, Filter, RotateCcw, Columns3 } from "lucide-vue-next";
import {
    KATEGORI_OPTIONS,
    JENIS_KEPEMILIKAN_OPTIONS,
} from "@/Services/penerimaManfaatConfig";

const props = defineProps({
    searchQuery: {
        type: String,
        default: "",
    },
    selectedKategori: {
        type: String,
        default: "",
    },
    selectedKepemilikan: {
        type: String,
        default: "",
    },
    visibleColumns: {
        type: Object,
        required: true,
    },
    columnDefinitions: {
        type: Array,
        required: true,
    },
    visibleColumnCount: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits([
    "update:searchQuery",
    "update:selectedKategori",
    "update:selectedKepemilikan",
    "resetFilters",
    "toggleAllColumns",
    "resetColumns",
]);

const isColumnDropdownOpen = ref(false);

function onSearchInput(e) {
    emit("update:searchQuery", e.target.value);
}

function onKategoriChange(e) {
    emit("update:selectedKategori", e.target.value);
}

function onKepemilikanChange(e) {
    emit("update:selectedKepemilikan", e.target.value);
}

function doResetFilters() {
    emit("resetFilters");
}

function doToggleAllColumns(val) {
    emit("toggleAllColumns", val);
}

function doResetColumns() {
    emit("resetColumns");
}
</script>

<template>
    <!-- ================= FILTER & SEARCH BAR ================= -->
    <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80">
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-3"
        >
            <!-- Kiri: Pencarian Instan (Proporsional tidak terlalu lebar) -->
            <div class="relative w-full md:w-72 lg:w-80 shrink-0">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                >
                    <Search class="h-4 w-4 text-slate-400" />
                </div>
                <input
                    :value="searchQuery"
                    @input="onSearchInput"
                    type="text"
                    placeholder="Cari nama, kode, PIC, alamat..."
                    class="block w-full h-10 rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-xs placeholder-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-2xs"
                />
            </div>

            <!-- Kanan: Filter Dropdowns, Tampilkan Kolom & Reset -->
            <div
                class="flex flex-wrap sm:flex-nowrap items-center justify-start md:justify-end gap-2.5 w-full md:w-auto"
            >
                <!-- Filter Kategori -->
                <div class="relative flex-1 sm:flex-initial min-w-[130px]">
                    <select
                        :value="selectedKategori"
                        @change="onKategoriChange"
                        class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 pr-8 text-xs text-slate-700 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer shadow-2xs"
                    >
                        <option value="">Semua Kategori</option>
                        <option
                            v-for="kat in KATEGORI_OPTIONS"
                            :key="kat.value"
                            :value="kat.value"
                        >
                            {{ kat.value }}
                        </option>
                    </select>
                </div>

                <!-- Filter Kepemilikan -->
                <div class="relative flex-1 sm:flex-initial min-w-[130px]">
                    <select
                        :value="selectedKepemilikan"
                        @change="onKepemilikanChange"
                        class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 pr-8 text-xs text-slate-700 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer shadow-2xs"
                    >
                        <option value="">Semua Status</option>
                        <option
                            v-for="j in JENIS_KEPEMILIKAN_OPTIONS"
                            :key="j.value"
                            :value="j.value"
                        >
                            {{ j.label }}
                        </option>
                    </select>
                </div>

                <!-- Tombol Pilih Kolom Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        @click="isColumnDropdownOpen = !isColumnDropdownOpen"
                        class="h-10 px-3 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 flex items-center justify-center gap-1.5 transition-colors cursor-pointer shrink-0 shadow-2xs"
                        :class="{
                            'ring-2 ring-primary/20 border-primary text-primary':
                                isColumnDropdownOpen,
                        }"
                        title="Pilih kolom yang ingin ditampilkan"
                    >
                        <Columns3 class="h-3.5 w-3.5 text-slate-500" />
                        <span>Kolom</span>
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200"
                        >
                            {{ visibleColumnCount }}
                        </span>
                    </button>

                    <!-- Dropdown Content Tampilkan Kolom -->
                    <div
                        v-if="isColumnDropdownOpen"
                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 p-3 z-50 animate-in fade-in zoom-in-95 duration-150 space-y-2.5"
                    >
                        <div
                            class="flex items-center justify-between pb-2 border-b border-slate-100"
                        >
                            <span
                                class="text-xs font-bold text-slate-900 flex items-center gap-1.5"
                            >
                                <Columns3 class="h-3.5 w-3.5 text-primary" />
                                Tampilkan Kolom
                            </span>
                            <div class="flex items-center gap-1 text-[11px]">
                                <button
                                    type="button"
                                    @click="doToggleAllColumns(true)"
                                    class="text-primary hover:underline font-semibold cursor-pointer"
                                >
                                    Semua
                                </button>
                                <span class="text-slate-300">|</span>
                                <button
                                    type="button"
                                    @click="doResetColumns"
                                    class="text-slate-500 hover:underline cursor-pointer"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1 max-h-64 overflow-y-auto pr-0.5">
                            <label
                                v-for="col in columnDefinitions"
                                :key="col.key"
                                class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-slate-50 text-xs cursor-pointer select-none transition-colors"
                            >
                                <input
                                    type="checkbox"
                                    v-model="visibleColumns[col.key]"
                                    class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/20 cursor-pointer"
                                />
                                <span
                                    :class="
                                        visibleColumns[col.key]
                                            ? 'font-semibold text-slate-800'
                                            : 'text-slate-400'
                                    "
                                >
                                    {{ col.label }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tombol Reset Filter -->
                <button
                    type="button"
                    @click="doResetFilters"
                    class="h-10 px-3 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 flex items-center justify-center gap-1.5 transition-colors cursor-pointer shrink-0 shadow-2xs"
                    title="Reset Filter"
                >
                    <RotateCcw class="h-3.5 w-3.5 text-slate-500" />
                    <span>Reset</span>
                </button>
            </div>
        </div>
    </div>
</template>
