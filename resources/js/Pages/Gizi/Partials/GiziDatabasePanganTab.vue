<script setup>
import { ref, computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Database,
    Search,
    ChevronLeft,
    ChevronRight,
    RotateCcw,
} from "lucide-vue-next";

const props = defineProps({
    tkpiList: {
        type: Array,
        default: () => [],
    },
    tkpiDatasets: {
        type: Object,
        default: () => ({
            fta: [],
            csv: [],
        }),
    },
    selectedSource: {
        type: String,
        default: "fta",
    },
});

const emit = defineEmits(["update-source"]);

const tkpiItems = computed(() => props.tkpiList || []);

// State & Logika Database Pangan (Paginasi & Filter)
const tkpiSearchQuery = ref("");
const tkpiCategoryFilter = ref("Semua");

const tkpiCurrentPage = ref(1);
const tkpiPerPage = ref(15);

const filteredTkpiList = computed(() => {
    return tkpiItems.value.filter((item) => {
        const matchesCategory =
            tkpiCategoryFilter.value === "Semua" ||
            item.kategori === tkpiCategoryFilter.value;
        const query = tkpiSearchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (item.nama && item.nama.toLowerCase().includes(query)) ||
            (item.id && item.id.toLowerCase().includes(query)) ||
            (item.kategori && item.kategori.toLowerCase().includes(query));
        return matchesCategory && matchesSearch;
    });
});

const tkpiTotalPages = computed(() => {
    return Math.ceil(filteredTkpiList.value.length / tkpiPerPage.value) || 1;
});

const paginatedTkpiList = computed(() => {
    const start = (tkpiCurrentPage.value - 1) * tkpiPerPage.value;
    return filteredTkpiList.value.slice(start, start + tkpiPerPage.value);
});

function prevTkpiPage() {
    if (tkpiCurrentPage.value > 1) tkpiCurrentPage.value--;
}

function nextTkpiPage() {
    if (tkpiCurrentPage.value < tkpiTotalPages.value) tkpiCurrentPage.value++;
}

const tkpiCategoryList = computed(() => {
    const cats = new Set(tkpiItems.value.map((i) => i.kategori));
    return ["Semua", ...Array.from(cats)];
});

function formatRupiah(val) {
    if (!val && val !== 0) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header Info & Actions -->
        <Card className="bg-white border-slate-200 shadow-xs">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div>
                        <CardTitle
                            class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                        >
                            <Database class="h-5 w-5 text-primary" />
                            <span>Master Database Pangan</span>
                        </CardTitle>
                        <CardDescription
                            class="text-xs sm:text-sm mt-0.5"
                        >
                            Pilih sumber database acuan untuk seluruh modul perencanaan & rancang formula menu:
                            <strong class="text-slate-800">{{
                                selectedSource === 'fta'
                                    ? 'NutriSurvey Indo (indo.fta - 1.105 Bahan)'
                                    : 'TKPI 2020 Kemenkes (tkpi2020.csv - 1.146 Bahan)'
                            }}</strong>.
                        </CardDescription>
                    </div>

                    <!-- Source Dataset Switcher -->
                    <div class="flex items-center gap-1.5 bg-slate-100/90 p-1 rounded-xl border border-slate-200 shrink-0 self-start md:self-auto">
                        <button
                            type="button"
                            @click="emit('update-source', 'fta')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                                selectedSource === 'fta'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                            title="Gunakan Database NutriSurvey Indo (.fta)"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="selectedSource === 'fta' ? 'bg-primary' : 'bg-slate-300'"
                            ></span>
                            <span>NutriSurvey Indo (.fta)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="selectedSource === 'fta' ? 'bg-primary/10 text-primary' : 'bg-slate-200 text-slate-600'"
                            >
                                {{ tkpiDatasets.fta?.length || 1105 }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="emit('update-source', 'csv')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                                selectedSource === 'csv'
                                    ? 'bg-white text-primary shadow-xs border border-slate-200/80 font-black'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                            title="Gunakan Database TKPI 2020 Kemenkes (.csv)"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="selectedSource === 'csv' ? 'bg-primary' : 'bg-slate-300'"
                            ></span>
                            <span>TKPI 2020 (.csv)</span>
                            <span
                                class="text-[10px] px-1.5 py-0.5 rounded font-mono"
                                :class="selectedSource === 'csv' ? 'bg-primary/10 text-primary' : 'bg-slate-200 text-slate-600'"
                            >
                                {{ tkpiDatasets.csv?.length || 1146 }}
                            </span>
                        </button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-5 space-y-4">
                <!-- Stat Summary Grid (Sejajar 4 Card) -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5"
                >
                    <div
                        class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[10px] font-bold text-blue-700 uppercase tracking-wider"
                        >
                            TOTAL BAHAN TERDAFTAR
                        </p>
                        <h4
                            class="text-xl font-black text-blue-950 mt-1"
                        >
                            {{ tkpiItems.length }}
                            <span
                                class="text-xs font-medium text-slate-500"
                                >Bahan</span
                            >
                        </h4>
                    </div>
                    <div
                        class="p-3.5 bg-emerald-50/60 rounded-xl border border-emerald-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider"
                        >
                            KATEGORI PANGAN
                        </p>
                        <h4
                            class="text-xl font-black text-emerald-950 mt-1"
                        >
                            {{ tkpiCategoryList.length - 1 }}
                            <span
                                class="text-xs font-medium text-slate-500"
                                >Kelompok</span
                            >
                        </h4>
                    </div>
                    <div
                        class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[10px] font-bold text-amber-700 uppercase tracking-wider"
                        >
                            RATA-RATA BDD
                        </p>
                        <h4
                            class="text-xl font-black text-amber-950 mt-1"
                        >
                            88.2%
                            <span
                                class="text-xs font-medium text-slate-500"
                                >Dapat Dimakan</span
                            >
                        </h4>
                    </div>
                    <div
                        class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100 text-center flex flex-col justify-center"
                    >
                        <p
                            class="text-[10px] font-bold text-purple-700 uppercase tracking-wider"
                        >
                            SUMBER DATA
                        </p>
                        <h4
                            class="text-sm sm:text-base font-black text-purple-950 mt-1"
                        >
                            {{ selectedSource === 'fta' ? 'indo.fta (NutriSurvey)' : 'tkpi2020.csv (Kemenkes)' }}
                            <span
                                class="text-xs font-medium text-slate-500"
                                >({{ tkpiItems.length }} Bahan)</span
                            >
                        </h4>
                    </div>
                </div>

                <!-- Search & Filter Controls -->
                <div class="pt-2 space-y-3">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="relative w-full sm:w-80 shrink-0">
                            <Search
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400"
                            />
                            <input
                                type="text"
                                v-model="tkpiSearchQuery"
                                placeholder="Cari bahan makanan / kode..."
                                class="w-full pl-9 pr-3 py-1.5 text-xs font-medium rounded-lg border-slate-300 focus:ring-primary focus:border-primary"
                            />
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            Filter Kategori ({{
                                tkpiCategoryList.length - 1
                            }}
                            kelompok)
                        </div>
                    </div>

                    <!-- Horizontal Category Pills dengan Gap & Padding Aman ke Scrollbar -->
                    <div
                        class="flex items-center gap-2 overflow-x-auto w-full pb-3.5 pt-1"
                    >
                        <button
                            v-for="cat in tkpiCategoryList"
                            :key="cat"
                            type="button"
                            @click="tkpiCategoryFilter = cat"
                            :class="[
                                'px-3.5 py-1.5 text-xs rounded-full font-bold border transition-all cursor-pointer whitespace-nowrap shrink-0 shadow-2xs',
                                tkpiCategoryFilter === cat
                                    ? 'bg-primary text-white border-primary shadow-xs font-extrabold'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
                            ]"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Tabel Data Bahan Pangan -->
        <div class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none">
                            <th class="py-3.5 px-3">Kode</th>
                            <th class="py-3.5 px-3">Nama Bahan Pangan</th>
                            <th class="py-3.5 px-3">Kategori</th>
                            <th class="py-3.5 px-3 text-right">Energi (Kkal)</th>
                            <th class="py-3.5 px-3 text-right">Protein (g)</th>
                            <th class="py-3.5 px-3 text-right">Lemak (g)</th>
                            <th class="py-3.5 px-3 text-right">Karbohidrat (g)</th>
                            <th class="py-3.5 px-3 text-right">Serat (g)</th>
                            <th class="py-3.5 px-3 text-center">BDD (%)</th>
                            <th class="py-3.5 px-3 text-right">Harga Master</th>
                            <th class="py-3.5 px-3 text-center">Alergen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="item in paginatedTkpiList"
                            :key="item.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3 font-mono font-bold text-slate-500 text-[11px]">
                                {{ item.id }}
                            </td>
                            <td class="p-3 font-bold text-slate-900">
                                {{ item.nama }}
                            </td>
                            <td class="p-3">
                                <Badge
                                    variant="outline"
                                    className="text-[10px] font-semibold bg-slate-50"
                                >
                                    {{ item.kategori }}
                                </Badge>
                            </td>
                            <td class="p-3 text-right font-bold text-amber-800">
                                {{ item.energi }}
                            </td>
                            <td class="p-3 text-right font-semibold text-blue-800">
                                {{ item.protein }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ item.lemak }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ item.karbohidrat }}
                            </td>
                            <td class="p-3 text-right text-slate-700">
                                {{ item.serat }}
                            </td>
                            <td class="p-3 text-center font-bold text-emerald-800">
                                {{ item.bdd }}%
                            </td>
                            <td class="p-3 text-right font-bold text-slate-900">
                                {{ formatRupiah(item.harga_master) }}
                                /kg
                            </td>
                            <td class="p-3 text-center">
                                <span
                                    v-if="item.alergen"
                                    class="text-[10.5px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200"
                                >
                                    {{ item.alergen }}
                                </span>
                                <span v-else class="text-slate-400">-</span>
                            </td>
                        </tr>
                        <tr v-if="filteredTkpiList.length === 0">
                            <td
                                colspan="11"
                                class="p-8 text-center text-slate-400 font-semibold"
                            >
                                Tidak ada data bahan pangan yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div
                class="p-3 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-600"
            >
                <span>
                    Menampilkan
                    <strong>{{ (tkpiCurrentPage - 1) * tkpiPerPage + 1 }}</strong>
                    -
                    <strong>{{ Math.min(tkpiCurrentPage * tkpiPerPage, filteredTkpiList.length) }}</strong>
                    dari
                    <strong>{{ filteredTkpiList.length }}</strong> bahan
                </span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="prevTkpiPage"
                        :disabled="tkpiCurrentPage === 1"
                        class="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed font-medium cursor-pointer"
                    >
                        Sebelumnya
                    </button>
                    <span class="font-bold text-slate-800">
                        Hal {{ tkpiCurrentPage }} / {{ tkpiTotalPages }}
                    </span>
                    <button
                        type="button"
                        @click="nextTkpiPage"
                        :disabled="tkpiCurrentPage >= tkpiTotalPages"
                        class="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed font-medium cursor-pointer"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
