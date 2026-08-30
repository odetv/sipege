<script setup>
import { ref, computed } from "vue";
import { Boxes, Search, AlertCircle, CheckCircle2, TrendingDown, ArrowUpRight, ArrowDownRight, Package, Filter, Download } from "lucide-vue-next";

const props = defineProps({
    poList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (val) => "Rp " + Number(val || 0).toLocaleString("id-ID"),
    },
});

const searchQuery = ref("");
const selectedFilter = ref("semua");

// Simulasi agregasi data persediaan bahan baku dari PO yang telah terverifikasi
const dummyStokItems = [
    {
        id: 1,
        nama: "Beras Putih Medium",
        kategori: "Serealia & Karbohidrat",
        stok_masuk: 250,
        stok_keluar: 114.3,
        sisa_stok: 135.7,
        satuan: "kg",
        status: "Aman",
        nilai_aset: 2171200,
        terakhir_update: "30 Agu 2026",
    },
    {
        id: 2,
        nama: "Daging Ayam Broiler Segar",
        kategori: "Protein Hewani",
        stok_masuk: 80,
        stok_keluar: 65.5,
        sisa_stok: 14.5,
        satuan: "kg",
        status: "Kritis",
        nilai_aset: 551000,
        terakhir_update: "30 Agu 2026",
    },
    {
        id: 3,
        nama: "Telur Ayam Ras",
        kategori: "Protein Hewani",
        stok_masuk: 50,
        stok_keluar: 35.0,
        sisa_stok: 15.0,
        satuan: "kg",
        status: "Aman",
        nilai_aset: 435000,
        terakhir_update: "29 Agu 2026",
    },
    {
        id: 4,
        nama: "Minyak Goreng Sawit",
        kategori: "Lemak & Minyak",
        stok_masuk: 40,
        stok_keluar: 12.0,
        sisa_stok: 28.0,
        satuan: "liter",
        status: "Aman",
        nilai_aset: 490000,
        terakhir_update: "30 Agu 2026",
    },
    {
        id: 5,
        nama: "Tempe Kedelai Segar",
        kategori: "Protein Nabati",
        stok_masuk: 30,
        stok_keluar: 25.0,
        sisa_stok: 5.0,
        satuan: "kg",
        status: "Kritis",
        nilai_aset: 75000,
        terakhir_update: "30 Agu 2026",
    },
];

const filteredStok = computed(() => {
    return dummyStokItems.filter((item) => {
        const matchesSearch = item.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            item.kategori.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = selectedFilter.value === "semua" ||
            (selectedFilter.value === "kritis" && item.status === "Kritis") ||
            (selectedFilter.value === "aman" && item.status === "Aman");
        return matchesSearch && matchesStatus;
    });
});

const totalAsetStok = computed(() => {
    return dummyStokItems.reduce((acc, it) => acc + it.nilai_aset, 0);
});

const totalItemKritis = computed(() => {
    return dummyStokItems.filter(it => it.status === "Kritis").length;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header Banner -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <Boxes class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-800 tracking-tight">Manajemen Stok & Persediaan Pangan</h2>
                        <p class="text-xs text-slate-500 font-medium">Monitoring keluar masuk dan sisa persediaan bahan baku di gudang SPPG</p>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Cepat -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-left">
                    <span class="text-[10.5px] font-bold text-slate-400 block uppercase">Total Nilai Persediaan</span>
                    <span class="text-sm font-mono font-black text-slate-800">{{ formatRupiah(totalAsetStok) }}</span>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-left">
                    <span class="text-[10.5px] font-bold text-rose-500 block uppercase">Stok Kritis / Menipis</span>
                    <span class="text-sm font-mono font-black text-rose-700">{{ totalItemKritis }} Bahan</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama bahan pangan..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-2xs font-medium"
                />
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <select
                    v-model="selectedFilter"
                    class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white font-bold text-slate-700 shadow-2xs focus:outline-none"
                >
                    <option value="semua">Semua Status Stok</option>
                    <option value="kritis">Stok Kritis Saja</option>
                    <option value="aman">Stok Aman</option>
                </select>
            </div>
        </div>

        <!-- Tabel Persediaan Stok -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                            <th class="p-3.5 text-center w-12">No</th>
                            <th class="py-3.5 px-4">Nama Bahan Pangan</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4 text-right">Stok Masuk</th>
                            <th class="py-3.5 px-4 text-right">Terpakai (Keluar)</th>
                            <th class="py-3.5 px-4 text-right">Sisa Stok Gudang</th>
                            <th class="py-3.5 px-4 text-right">Nilai Persediaan</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(item, idx) in filteredStok" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-3.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                            <td class="p-3.5 font-bold text-slate-800">
                                {{ item.nama }}
                                <span class="block text-[10px] text-slate-400 font-medium mt-0.5">Pembaruan: {{ item.terakhir_update }}</span>
                            </td>
                            <td class="p-3.5 text-slate-600 font-medium">{{ item.kategori }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-slate-700">{{ item.stok_masuk }} {{ item.satuan }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-rose-700">-{{ item.stok_keluar }} {{ item.satuan }}</td>
                            <td class="p-3.5 text-right font-mono font-black text-slate-900">{{ item.sisa_stok }} {{ item.satuan }}</td>
                            <td class="p-3.5 text-right font-mono font-bold text-emerald-800">{{ formatRupiah(item.nilai_aset) }}</td>
                            <td class="p-3.5 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-md text-[10.5px] font-bold inline-flex items-center gap-1',
                                        item.status === 'Kritis' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                    ]"
                                >
                                    <AlertCircle v-if="item.status === 'Kritis'" class="h-3 w-3" />
                                    <CheckCircle2 v-else class="h-3 w-3" />
                                    <span>{{ item.status }}</span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="filteredStok.length === 0">
                            <td colspan="8" class="p-8 text-center text-slate-400 font-medium">
                                Tidak ada data stok bahan pangan yang sesuai filter pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
