<script setup>
import { ref, computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import Badge from "@/Components/ui/Badge.vue";
import { Sliders, Search, CheckCircle2, Receipt, Wrench, Fuel } from "lucide-vue-next";

const props = defineProps({
    bpOperasionalList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        required: true,
    },
    formatTanggalIndo: {
        type: Function,
        default: (tgl) => tgl || "-",
    },
});

const searchQuery = ref("");

const filteredList = computed(() => {
    if (!searchQuery.value || !searchQuery.value.trim()) {
        return props.bpOperasionalList;
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.bpOperasionalList.filter((item) => {
        const no = String(item.nomor_transaksi || "").toLowerCase();
        const nama = String(item.nama_transaksi || "").toLowerCase();
        const vendor = String(item.vendor || "").toLowerCase();
        const ket = String(item.keterangan || "").toLowerCase();
        return no.includes(q) || nama.includes(q) || vendor.includes(q) || ket.includes(q);
    });
});

const totalRealisasi = computed(() => {
    return props.bpOperasionalList.reduce((acc, it) => acc + (Number(it.nominal) || 0), 0);
});
</script>

<template>
    <div class="space-y-6">
        <!-- KPI SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <Fuel class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Realisasi Operasional</div>
                    <div class="text-xl font-black text-slate-900 mt-0.5">
                        {{ formatRupiah(totalRealisasi) }}
                    </div>
                    <div class="text-[11px] text-emerald-600 font-medium mt-0.5 flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" /> Transaksi Sah Disetujui
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20">
                    <Receipt class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah Transaksi Riil</div>
                    <div class="text-xl font-black text-slate-900 mt-0.5">
                        {{ bpOperasionalList.length }} <span class="text-sm font-semibold text-slate-500">Item</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                        Dari Buku Transaksi Keuangan
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs flex items-center gap-4 sm:col-span-2 lg:col-span-1">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 border border-slate-200">
                    <Sliders class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alokasi Anggaran</div>
                    <div class="text-xl font-black text-slate-900 mt-0.5">
                        Pos 20%
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                        Gaji, BBM, Gas LPG, Utilitas & Distribusi
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE CARD -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <CardTitle className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                        <Sliders class="h-5 w-5 text-amber-600" />
                        <span>Buku Pembantu (BP) Pembelanjaan Biaya Operasional</span>
                    </CardTitle>
                    <CardDescription class="text-xs sm:text-sm mt-0.5">
                        Daftar riil pengeluaran biaya operasional, gaji/insentif, dan utilitas yang telah berstatus <strong>Disetujui</strong> di Buku Transaksi.
                    </CardDescription>
                </div>

                <!-- Search Filter -->
                <div class="w-full md:w-72 relative">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari transaksi operasional, vendor..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white"
                    />
                </div>
            </CardHeader>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">No. Transaksi</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Rincian Operasional</th>
                            <th class="py-3.5 px-4">Penerima / Vendor</th>
                            <th class="py-3.5 px-4 text-center">Volume / Satuan</th>
                            <th class="py-3.5 px-4 text-right">Nominal Pengeluaran</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(item, idx) in filteredList"
                            :key="item.id || idx"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 text-center text-slate-400 font-mono text-[11px]">
                                {{ idx + 1 }}
                            </td>
                            <td class="p-3.5 font-mono font-bold text-primary whitespace-nowrap">
                                {{ item.nomor_transaksi || "-" }}
                            </td>
                            <td class="p-3.5 whitespace-nowrap text-slate-600">
                                {{ formatTanggalIndo(item.tanggal) }}
                            </td>
                            <td class="p-3.5 font-medium text-slate-900">
                                <div>{{ item.nama_transaksi }}</div>
                                <div v-if="item.keterangan && item.keterangan !== '-'" class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">
                                    {{ item.keterangan }}
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-600 whitespace-nowrap">
                                <span class="font-medium text-slate-800">{{ item.vendor || "-" }}</span>
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ item.volume !== null && item.volume !== undefined ? item.volume : (item.qty || 1) }}</span>
                                <span class="text-slate-500 ml-1 font-normal">{{ item.satuan || "Kegiatan" }}</span>
                            </td>
                            <td class="p-3.5 text-right font-black text-amber-700 whitespace-nowrap">
                                {{ formatRupiah(item.nominal) }}
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap">
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-200 font-bold text-[10.5px]"
                                >
                                    {{ item.status || "Disetujui" }}
                                </Badge>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredList.length === 0">
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <Sliders class="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
                                    <div class="text-sm font-bold text-slate-700">
                                        {{ searchQuery ? 'Tidak ada data yang cocok' : 'Belum Ada Transaksi Biaya Operasional' }}
                                    </div>
                                    <div class="text-xs text-slate-500 leading-relaxed">
                                        {{ searchQuery ? 'Coba ubah kata kunci pencarian Anda.' : 'Data biaya operasional riil akan otomatis tercantum di sini setelah transaksi kategori Operasional disetujui di menu Persetujuan/Transaksi.' }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer Summary -->
            <div v-if="filteredList.length > 0" class="p-4 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-600 gap-2">
                <div>
                    Menampilkan <strong>{{ filteredList.length }}</strong> dari <strong>{{ bpOperasionalList.length }}</strong> transaksi operasional riil
                </div>
                <div class="font-bold text-slate-900 text-sm">
                    Total: <span class="text-amber-700 font-black">{{ formatRupiah(filteredList.reduce((acc, it) => acc + (Number(it.nominal) || 0), 0)) }}</span>
                </div>
            </div>
        </Card>
    </div>
</template>
