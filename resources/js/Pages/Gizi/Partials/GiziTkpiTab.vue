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
});

const tkpiItems = computed(() => props.tkpiList || []);

// State & Logika TKPI 2020 (Paginasi & Filter)
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
                                    <span
                                        >Database Standar Komposisi Pangan
                                        Indonesia (TKPI 2020)</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Memuat
                                    <strong
                                        >{{ tkpiItems.length }} bahan
                                        makanan</strong
                                    >
                                    resmi dari file master
                                    <code>database/data/tkpi2020.csv</code>
                                    (Kemenkes RI).
                                </CardDescription>
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
                                    class="text-base font-black text-purple-950 mt-1"
                                >
                                    tkpi2020.csv
                                    <span
                                        class="text-xs font-medium text-slate-500"
                                        >({{ tkpiItems.length }} Baris)</span
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
                                        placeholder="Cari bahan makanan / kode TKPI..."
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

                <!-- Tabel Data Bahan TKPI 2020 -->
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
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="item in paginatedTkpiList"
                                    :key="item.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td
                                        class="p-3 font-mono font-bold text-slate-500 text-[11px]"
                                    >
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
                                    <td
                                        class="p-3 text-right font-bold text-amber-800"
                                    >
                                        {{ item.energi }}
                                    </td>
                                    <td
                                        class="p-3 text-right font-semibold text-blue-800"
                                    >
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
                                    <td
                                        class="p-3 text-center font-bold text-emerald-800"
                                    >
                                        {{ item.bdd }}%
                                    </td>
                                    <td
                                        class="p-3 text-right font-bold text-slate-900"
                                    >
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
                                        <span v-else class="text-slate-400"
                                            >-</span
                                        >
                                    </td>
                                </tr>
                                <tr v-if="filteredTkpiList.length === 0">
                                    <td
                                        colspan="11"
                                        class="p-8 text-center text-slate-400 font-semibold"
                                    >
                                        Tidak ada data bahan pangan yang sesuai
                                        dengan pencarian.
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
                            <strong>{{
                                (tkpiCurrentPage - 1) * tkpiPerPage + 1
                            }}</strong>
                            -
                            <strong>{{
                                Math.min(
                                    tkpiCurrentPage * tkpiPerPage,
                                    filteredTkpiList.length,
                                )
                            }}</strong>
                            dari
                            <strong>{{ filteredTkpiList.length }}</strong> bahan
                        </span>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                @click="prevTkpiPage"
                                :disabled="tkpiCurrentPage === 1"
                                className="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100"
                            >
                                Sebelumnya
                            </Button>
                            <span class="font-bold text-slate-800"
                                >Hal {{ tkpiCurrentPage }} /
                                {{ tkpiTotalPages }}</span
                            >
                            <Button
                                type="button"
                                @click="nextTkpiPage"
                                :disabled="tkpiCurrentPage >= tkpiTotalPages"
                                className="h-7 px-2.5 text-xs bg-white border border-slate-300 text-slate-700 hover:bg-slate-100"
                            >
                                Selanjutnya
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

</template>
