<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { Users, Info, AlertCircle, Trash2, Plus, RotateCcw } from "lucide-vue-next";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    totalLakiLaki: {
        type: Number,
        default: 0,
    },
    totalPerempuan: {
        type: Number,
        default: 0,
    },
    totalPorsiKecil: {
        type: Number,
        default: 0,
    },
    totalPorsiBesar: {
        type: Number,
        default: 0,
    },
    grandTotal: {
        type: Number,
        default: 0,
    },
    isAllSubkategoriAdded: {
        type: Boolean,
        default: false,
    },
    getAvailableSubKategoriForRow: {
        type: Function,
        required: true,
    },
    onSubKategoriChange: {
        type: Function,
        required: true,
    },
    addCustomSubkategori: {
        type: Function,
        required: true,
    },
    removeSubkategori: {
        type: Function,
        required: true,
    },
    resetRincianToDefault: {
        type: Function,
        required: true,
    },
    getJenisPorsiBySubKategori: {
        type: Function,
        required: true,
    },
    getFieldError: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <!-- 4. RINCIAN JUMLAH PENERIMA MANFAAT -->
    <Card className="bg-white border-slate-200/80 shadow-xs">
        <CardHeader
            className="border-b border-slate-100 p-5 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"
                >
                    <Users class="h-4 w-4" />
                </div>
                <div>
                    <CardTitle
                        className="text-base font-bold text-slate-900"
                    >
                        4. Rincian Jumlah Penerima Manfaat
                    </CardTitle>
                    <CardDescription
                        className="text-xs text-slate-500 mt-0.5"
                    >
                        Pemetaan jumlah penerima manfaat ({{
                            form.kategori ||
                            "Pilih kategori terlebih dahulu"
                        }}).
                    </CardDescription>
                </div>
            </div>

            <!-- Summary Badges (Termasuk Porsi Kecil & Porsi Besar) -->
            <div
                class="flex flex-wrap items-center gap-2 self-start sm:self-auto text-xs"
            >
                <span
                    class="px-2.5 py-1 font-bold rounded-lg bg-amber-50 text-amber-800 border border-amber-200"
                >
                    Porsi Kecil: {{ totalPorsiKecil }}
                </span>
                <span
                    class="px-2.5 py-1 font-bold rounded-lg bg-blue-50 text-blue-800 border border-blue-200"
                >
                    Porsi Besar: {{ totalPorsiBesar }}
                </span>
                <span
                    class="px-2.5 py-1 font-bold rounded-lg bg-sky-50 text-sky-700 border border-sky-200"
                >
                    L: {{ totalLakiLaki }}
                </span>
                <span
                    class="px-2.5 py-1 font-bold rounded-lg bg-pink-50 text-pink-700 border border-pink-200"
                >
                    P: {{ totalPerempuan }}
                </span>
                <span
                    class="px-3.5 py-1 font-extrabold rounded-lg bg-primary text-white shadow-xs"
                >
                    Total: {{ grandTotal }}
                </span>
            </div>
        </CardHeader>
        <CardContent className="p-5 sm:p-6 space-y-5">
            <!-- Error Rincian -->
            <div
                v-if="getFieldError('rincian')"
                class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-2.5 text-xs font-semibold"
            >
                <AlertCircle
                    class="h-4 w-4 shrink-0 text-rose-600"
                />
                <span>{{ getFieldError("rincian") }}</span>
            </div>

            <!-- Notice jika belum memilih kategori -->
            <div
                v-if="!form.kategori"
                class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center gap-3 text-xs"
            >
                <Info class="h-5 w-5 text-blue-600 shrink-0" />
                <span
                    >Silakan pilih
                    <strong>Kategori Jenjang / Lembaga</strong> di
                    bagian 1 terlebih dahulu untuk memunculkan
                    pilihan subkategori.</span
                >
            </div>

            <div
                v-else
                class="border border-slate-200 rounded-xl overflow-x-auto shadow-2xs"
            >
                <table
                    class="w-full min-w-[650px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="h-14 bg-slate-50/90 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider"
                        >
                            <th class="px-4 py-4 w-12 text-center">
                                No
                            </th>
                            <th class="px-5 py-4 min-w-[200px]">
                                Sub Kategori Penerima
                            </th>
                            <th class="px-4 py-4 w-36 text-center">
                                Jenis Porsi
                            </th>
                            <th class="px-5 py-4 text-center w-36">
                                Laki-Laki (L)
                            </th>
                            <th class="px-5 py-4 text-center w-36">
                                Perempuan (P)
                            </th>
                            <th class="px-4 py-4 text-center w-28">
                                Subtotal
                            </th>
                            <th
                                class="px-4 py-4 text-center w-12"
                            ></th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 bg-white"
                    >
                        <tr
                            v-for="(item, idx) in form.rincian"
                            :key="idx"
                            class="hover:bg-slate-50/60 transition-colors h-16"
                        >
                            <td
                                class="px-4 py-4 text-center font-medium text-slate-400"
                            >
                                {{ idx + 1 }}
                            </td>

                            <!-- Select Sub Kategori -->
                            <td class="px-5 py-4">
                                <select
                                    v-model="item.sub_kategori"
                                    @change="
                                        onSubKategoriChange(item)
                                    "
                                    class="w-full h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-slate-800 cursor-pointer"
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih Sub Kategori...
                                    </option>
                                    <option
                                        v-for="opt in getAvailableSubKategoriForRow(
                                            idx,
                                        )"
                                        :key="opt"
                                        :value="opt"
                                    >
                                        {{ opt }}
                                    </option>
                                </select>
                            </td>

                            <!-- Jenis Porsi (Fixed Badge Berdasarkan Subkategori) -->
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="
                                        getJenisPorsiBySubKategori(
                                            item.sub_kategori,
                                            form.kategori,
                                        ) === 'Porsi Kecil'
                                    "
                                    class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs select-none"
                                >
                                    Porsi Kecil
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center justify-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs select-none"
                                >
                                    Porsi Besar
                                </span>
                            </td>

                            <!-- Input Laki-Laki -->
                            <td class="px-5 py-4 text-center">
                                <input
                                    v-model.number="
                                        item.jumlah_laki_laki
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full h-10 text-center px-3 text-xs font-bold rounded-lg border border-sky-200 bg-sky-50/40 text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                                />
                            </td>

                            <!-- Input Perempuan -->
                            <td class="px-5 py-4 text-center">
                                <input
                                    v-model.number="
                                        item.jumlah_perempuan
                                    "
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full h-10 text-center px-3 text-xs font-bold rounded-lg border border-pink-200 bg-pink-50/40 text-pink-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500"
                                />
                            </td>

                            <!-- Subtotal -->
                            <td
                                class="px-4 py-4 text-center font-extrabold text-xs text-slate-800"
                            >
                                {{
                                    (Number(
                                        item.jumlah_laki_laki,
                                    ) || 0) +
                                    (Number(
                                        item.jumlah_perempuan,
                                    ) || 0)
                                }}
                            </td>

                            <!-- Hapus Baris -->
                            <td class="px-4 py-4 text-center">
                                <button
                                    type="button"
                                    @click="removeSubkategori(idx)"
                                    class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer"
                                    title="Hapus baris ini"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>

                        <tr v-if="form.rincian.length === 0">
                            <td
                                colspan="7"
                                class="py-10 text-center text-slate-400 text-xs"
                            >
                                Belum ada baris subkategori. Klik
                                tombol di bawah untuk menambah
                                baris.
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr
                            class="h-14 bg-slate-50 font-bold border-t border-slate-200 text-xs"
                        >
                            <td
                                colspan="3"
                                class="px-6 py-4 text-right uppercase tracking-wider text-slate-600"
                            >
                                Total Penerima Manfaat
                            </td>
                            <td
                                class="px-5 py-4 text-center text-sky-700 font-bold"
                            >
                                {{ totalLakiLaki }}
                            </td>
                            <td
                                class="px-5 py-4 text-center text-pink-700 font-bold"
                            >
                                {{ totalPerempuan }}
                            </td>
                            <td
                                class="px-4 py-4 text-center text-primary font-black text-sm"
                            >
                                {{ grandTotal }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Action Buttons di Bawah Tabel -->
            <div
                v-if="form.kategori"
                class="flex flex-wrap items-center justify-between gap-3 pt-2"
            >
                <button
                    type="button"
                    @click="addCustomSubkategori"
                    :disabled="isAllSubkategoriAdded"
                    class="inline-flex items-center justify-center gap-2 h-11 px-5 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50/60 text-slate-700 shadow-2xs transition-colors cursor-pointer shrink-0 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white"
                    :title="
                        isAllSubkategoriAdded
                            ? 'Semua sub kategori untuk jenjang ini telah ditambahkan'
                            : 'Tambah baris sub kategori lainnya'
                    "
                >
                    <Plus class="h-3.5 w-3.5" />
                    <span>Tambah Baris Sub Kategori Lainnya</span>
                </button>

                <button
                    type="button"
                    @click="resetRincianToDefault"
                    class="inline-flex items-center justify-center gap-1.5 h-11 px-4 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors cursor-pointer shrink-0 whitespace-nowrap"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    <span
                        >Reset Rincian ke Default ({{
                            form.kategori
                        }})</span
                    >
                </button>
            </div>
        </CardContent>
    </Card>
</template>
