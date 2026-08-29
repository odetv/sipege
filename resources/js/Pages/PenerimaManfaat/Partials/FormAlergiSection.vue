<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Label from "@/Components/ui/Label.vue";
import { HeartPulse, CheckCircle2, Trash2 } from "lucide-vue-next";
import { ALERGI_OPTIONS } from "@/Services/penerimaManfaatConfig";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    customAlergiInput: {
        type: String,
        default: "",
    },
    showCustomAlergiInput: {
        type: Boolean,
        default: false,
    },
    totalAlergiPK: {
        type: Number,
        default: 0,
    },
    totalAlergiPB: {
        type: Number,
        default: 0,
    },
    grandTotalAlergi: {
        type: Number,
        default: 0,
    },
    isAlergiSelected: {
        type: Function,
        required: true,
    },
    toggleAlergi: {
        type: Function,
        required: true,
    },
    getSelectableAlergiOptions: {
        type: Function,
        required: true,
    },
    addCustomAlergi: {
        type: Function,
        required: true,
    },
    removeAlergi: {
        type: Function,
        required: true,
    },
});

defineEmits(["update:customAlergiInput"]);
</script>

<template>
    <!-- 5. DATA ALERGI MAKANAN & KEBUTUHAN KHUSUS -->
    <Card className="bg-white border-slate-200/80 shadow-xs">
        <CardHeader
            className="border-b border-slate-100 p-5 bg-slate-50/50"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"
                    >
                        <HeartPulse class="h-4 w-4" />
                    </div>
                    <div>
                        <CardTitle
                            className="text-base font-bold text-slate-900"
                        >
                            5. Data Alergi Makanan & Kebutuhan Khusus
                        </CardTitle>
                        <CardDescription
                            className="text-xs text-slate-500 mt-0.5"
                        >
                            Klasifikasi jumlah porsi makanan khusus
                            alergi (Porsi Kecil & Porsi Besar) yang
                            dirinci per satuan jenis alergen.
                        </CardDescription>
                    </div>
                </div>

                <!-- Summary Badges Total Alergi -->
                <div class="flex items-center gap-2 flex-wrap">
                    <span
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs"
                    >
                        PK: {{ totalAlergiPK }}
                    </span>
                    <span
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs"
                    >
                        PB: {{ totalAlergiPB }}
                    </span>
                    <span
                        class="px-2.5 py-1 rounded-lg text-xs font-black bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs"
                    >
                        Total: {{ grandTotalAlergi }}
                    </span>
                </div>
            </div>
        </CardHeader>
        <CardContent className="p-5 sm:p-6 space-y-6">
            <!-- Pilihan Cepat Jenis Alergen -->
            <div class="space-y-3">
                <div>
                    <Label class="text-xs font-bold text-slate-800">
                        Pilih Jenis Alergi / Pantangan Makanan
                    </Label>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Klik tombol di bawah untuk menambah jenis
                        alergi ke tabel rincian porsi di bawah ini:
                    </p>
                </div>

                <!-- Tag Pills List -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-for="al in ALERGI_OPTIONS"
                        :key="al.value"
                        type="button"
                        @click="toggleAlergi(al.value)"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all cursor-pointer flex items-center gap-1.5',
                            isAlergiSelected(al.value)
                                ? 'bg-rose-50 border-rose-300 text-rose-700 shadow-2xs font-bold'
                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300',
                        ]"
                    >
                        <CheckCircle2
                            v-if="isAlergiSelected(al.value)"
                            class="h-3.5 w-3.5 text-rose-600 shrink-0"
                        />
                        <span>{{ al.label }}</span>
                    </button>
                </div>

                <!-- Input Kustom Alergi Lainnya (Hanya muncul jika pill 'Lainnya' dipilih) -->
                <div
                    v-if="showCustomAlergiInput"
                    class="pt-2 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2 animate-in fade-in slide-in-from-top-2 duration-200"
                >
                    <div>
                        <Label
                            class="text-xs font-bold text-slate-800"
                        >
                            Tambah Alergen Khusus / Catatan Tambahan (Kustom)
                        </Label>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Ketik nama alergi/pantangan khusus lalu klik Tambah untuk memasukkannya ke tabel:
                        </p>
                    </div>
                    <div
                        class="flex items-stretch gap-2 max-w-lg"
                    >
                        <input
                            :value="customAlergiInput"
                            @input="$emit('update:customAlergiInput', $event.target.value)"
                            @keyup.enter.prevent="addCustomAlergi"
                            type="text"
                            placeholder="Ketik nama pantangan lain (misal: Kiwi, Madu, Gandum)..."
                            class="flex-1 h-10 px-3.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all text-slate-900 shadow-2xs"
                        />
                        <button
                            type="button"
                            @click="addCustomAlergi"
                            class="h-10 px-5 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white transition-colors cursor-pointer shrink-0 shadow-2xs inline-flex items-center justify-center"
                        >
                            Tambah
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABEL KLASIFIKASI JUMLAH PORSI PER SATUAN JENIS ALERGI -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <Label
                            class="text-xs font-bold text-slate-900"
                        >
                            Rincian Jumlah Porsi per Satuan Jenis
                            Alergi
                        </Label>
                        <p
                            class="text-[11px] text-slate-500 mt-0.5"
                        >
                            Tentukan jenis alergi serta jumlah porsi kecil (PK) dan
                            porsi besar (PB) untuk masing-masing baris.
                        </p>
                    </div>
                </div>

                <!-- Tabel jika ada alergi yang dipilih -->
                <div
                    v-if="
                        form.keterangan_alergi &&
                        form.keterangan_alergi.length > 0
                    "
                    class="border border-slate-200 rounded-xl overflow-x-auto shadow-2xs mt-3"
                >
                    <table
                        class="w-full min-w-[600px] text-left text-xs border-collapse"
                    >
                        <thead>
                            <tr
                                class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase"
                            >
                                <th
                                    class="py-3 px-4 w-12 text-center"
                                >
                                    No
                                </th>
                                <th class="py-3 px-4 min-w-[220px]">
                                    Jenis Alergi / Pantangan
                                </th>
                                <th
                                    class="py-3 px-4 text-center w-40"
                                >
                                    Porsi Kecil (PK)
                                </th>
                                <th
                                    class="py-3 px-4 text-center w-40"
                                >
                                    Porsi Besar (PB)
                                </th>
                                <th
                                    class="py-3 px-4 text-center w-32"
                                >
                                    Subtotal
                                </th>
                                <th
                                    class="py-3 px-4 text-center w-16"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 bg-white"
                        >
                            <tr
                                v-for="(
                                    item, idx
                                ) in form.keterangan_alergi"
                                :key="idx"
                                class="hover:bg-slate-50/60 transition-colors"
                            >
                                <td
                                    class="py-3 px-4 text-center text-slate-400 font-semibold"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="py-2.5 px-4 min-w-[220px]">
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="inline-flex h-2.5 w-2.5 rounded-full bg-rose-500 shrink-0"
                                        ></span>
                                        <select
                                            v-model="item.jenis_alergi"
                                            class="w-full h-9 px-2.5 text-xs font-bold rounded-lg border border-slate-200 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer shadow-2xs"
                                        >
                                            <option
                                                v-for="al in getSelectableAlergiOptions(item.jenis_alergi)"
                                                :key="al.value"
                                                :value="al.value"
                                            >
                                                {{ al.label }}
                                            </option>
                                        </select>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <input
                                            v-model.number="
                                                item.porsi_kecil
                                            "
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            class="w-24 h-9 px-2.5 text-center text-xs font-bold rounded-lg border border-amber-200 bg-amber-50/30 text-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                                        />
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <input
                                            v-model.number="
                                                item.porsi_besar
                                            "
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            class="w-24 h-9 px-2.5 text-center text-xs font-bold rounded-lg border border-blue-200 bg-blue-50/30 text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                        />
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-extrabold bg-slate-100 text-slate-800"
                                    >
                                        {{
                                            (Number(
                                                item.porsi_kecil,
                                            ) || 0) +
                                            (Number(
                                                item.porsi_besar,
                                            ) || 0)
                                        }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="removeAlergi(idx)"
                                        class="h-8 w-8 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                        title="Hapus Jenis Alergi"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr
                                class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                            >
                                <td
                                    colspan="2"
                                    class="py-3 px-4 text-right uppercase tracking-wider text-slate-600"
                                >
                                    Total Porsi Alergi
                                    Terklasifikasi
                                </td>
                                <td
                                    class="py-3 px-4 text-center text-amber-800 font-extrabold"
                                >
                                    {{ totalAlergiPK }}
                                </td>
                                <td
                                    class="py-3 px-4 text-center text-blue-800 font-extrabold"
                                >
                                    {{ totalAlergiPB }}
                                </td>
                                <td
                                    class="py-3 px-4 text-center text-rose-700 font-black text-sm"
                                >
                                    {{ grandTotalAlergi }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- State Kosong jika belum memilih alergi -->
                <div
                    v-else
                    class="p-6 rounded-xl bg-slate-50/70 border border-dashed border-slate-200 text-center space-y-1.5 mt-2"
                >
                    <HeartPulse
                        class="h-6 w-6 text-slate-300 mx-auto"
                    />
                    <p class="text-xs font-semibold text-slate-600">
                        Belum ada jenis alergi yang ditambahkan
                    </p>
                    <p
                        class="text-[11px] text-slate-400 max-w-md mx-auto"
                    >
                        Gunakan tombol opsi di atas untuk menentukan
                        kategori alergi jika kelompok ini memiliki
                        penerima dengan kebutuhan khusus.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
