<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Label from "@/Components/ui/Label.vue";
import { School, Users, AlertCircle } from "lucide-vue-next";
import {
    KATEGORI_OPTIONS,
    JENIS_KEPEMILIKAN_OPTIONS,
    TIPE_IDENTITAS_OPTIONS,
} from "@/Services/penerimaManfaatConfig";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    getFieldError: {
        type: Function,
        required: true,
    },
    clearFieldError: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(["kategoriChange"]);

function handleKategoriChange() {
    props.clearFieldError("kategori");
    emit("kategoriChange");
}
</script>

<template>
    <!-- 1. IDENTITAS & LEGALITAS KELOMPOK -->
    <Card className="bg-white border-slate-200/80 shadow-xs">
        <CardHeader
            className="border-b border-slate-100 p-5 bg-slate-50/50"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                >
                    <School class="h-4 w-4" />
                </div>
                <div>
                    <CardTitle
                        className="text-base font-bold text-slate-900"
                    >
                        1. Identitas Kelompok
                    </CardTitle>
                    <CardDescription
                        className="text-xs text-slate-500 mt-0.5"
                    >
                        Informasi nama lembaga, jenjang kategori,
                        status kepemilikan, dan informasi legalitas
                        lainnya.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent className="p-5 sm:p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Kelompok -->
                <div class="space-y-1.5 md:col-span-2">
                    <Label
                        for="nama_kelompok"
                        class="text-xs font-semibold text-slate-700"
                    >
                        Nama Kelompok Penerima Manfaat
                        <span class="text-rose-500">*</span>
                    </Label>
                    <input
                        id="nama_kelompok"
                        v-model="form.nama_kelompok"
                        @input="clearFieldError('nama_kelompok')"
                        type="text"
                        placeholder="Contoh: SD Negeri 1 Singaraja / Posyandu Melati Indah"
                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                        :class="{
                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                getFieldError('nama_kelompok'),
                        }"
                        required
                    />
                    <p
                        v-if="getFieldError('nama_kelompok')"
                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                    >
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{
                            getFieldError("nama_kelompok")
                        }}</span>
                    </p>
                </div>

                <!-- Kategori -->
                <div class="space-y-1.5">
                    <Label
                        for="kategori"
                        class="text-xs font-semibold text-slate-700"
                    >
                        Kategori Jenjang / Satuan
                        <span class="text-rose-500">*</span>
                    </Label>
                    <select
                        id="kategori"
                        v-model="form.kategori"
                        @change="handleKategoriChange"
                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                        :class="{
                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                getFieldError('kategori'),
                        }"
                        required
                    >
                        <option value="" disabled>
                            Pilih Kategori Jenjang...
                        </option>
                        <option
                            v-for="kat in KATEGORI_OPTIONS"
                            :key="kat.value"
                            :value="kat.value"
                        >
                            {{ kat.value }}
                        </option>
                    </select>
                    <p
                        v-if="getFieldError('kategori')"
                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                    >
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ getFieldError("kategori") }}</span>
                    </p>
                </div>

                <!-- Jenis Kepemilikan -->
                <div class="space-y-1.5">
                    <Label
                        for="jenis_kepemilikan"
                        class="text-xs font-semibold text-slate-700"
                    >
                        Jenis Kepemilikan
                        <span class="text-rose-500">*</span>
                    </Label>
                    <select
                        id="jenis_kepemilikan"
                        v-model="form.jenis_kepemilikan"
                        @change="clearFieldError('jenis_kepemilikan')"
                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                        :class="{
                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                getFieldError('jenis_kepemilikan'),
                        }"
                        required
                    >
                        <option value="" disabled>
                            Pilih Jenis Kepemilikan...
                        </option>
                        <option
                            v-for="j in JENIS_KEPEMILIKAN_OPTIONS"
                            :key="j.value"
                            :value="j.value"
                        >
                            {{ j.label }}
                        </option>
                    </select>
                    <p
                        v-if="getFieldError('jenis_kepemilikan')"
                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                    >
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{
                            getFieldError("jenis_kepemilikan")
                        }}</span>
                    </p>
                </div>

                <!-- Tipe Identitas -->
                <div class="space-y-1.5">
                    <Label
                        for="tipe_identitas"
                        class="text-xs font-semibold text-slate-700"
                    >
                        Tipe Nomor Identitas
                        <span class="text-rose-500">*</span>
                    </Label>
                    <select
                        id="tipe_identitas"
                        v-model="form.tipe_identitas"
                        @change="clearFieldError('tipe_identitas')"
                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer"
                        :class="{
                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                getFieldError('tipe_identitas'),
                        }"
                        required
                    >
                        <option value="" disabled>
                            Pilih Tipe Identitas...
                        </option>
                        <option
                            v-for="t in TIPE_IDENTITAS_OPTIONS"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.value }}
                        </option>
                    </select>
                    <p
                        v-if="getFieldError('tipe_identitas')"
                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                    >
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{
                            getFieldError("tipe_identitas")
                        }}</span>
                    </p>
                </div>

                <!-- Kode Identitas -->
                <div class="space-y-1.5">
                    <Label
                        for="kode_identitas"
                        class="text-xs font-semibold text-slate-700"
                    >
                        Kode / Nomor Identitas ({{
                            form.tipe_identitas || "-"
                        }}) <span class="text-rose-500">*</span>
                    </Label>
                    <input
                        id="kode_identitas"
                        v-model="form.kode_identitas"
                        @input="clearFieldError('kode_identitas')"
                        type="text"
                        placeholder="Contoh: 50101234 (Nomor NPSN / NSPP / NSM / Izin)"
                        class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                        :class="{
                            'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                getFieldError('kode_identitas'),
                        }"
                        required
                    />
                    <p
                        v-if="getFieldError('kode_identitas')"
                        class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                    >
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        <span>{{
                            getFieldError("kode_identitas")
                        }}</span>
                    </p>
                </div>

                <!-- Khusus Kategori Posyandu: Jumlah Kader Posyandu -->
                <div
                    v-if="form.kategori === 'Posyandu'"
                    class="sm:col-span-2 p-4 rounded-xl bg-blue-50/80 border border-blue-200"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="p-1 rounded-md bg-blue-100 text-blue-700">
                                    <Users class="h-4 w-4" />
                                </span>
                                <label
                                    for="jumlah_kader"
                                    class="text-xs font-bold text-blue-950"
                                >
                                    Jumlah Kader Posyandu
                                </label>
                                <span
                                    class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-200/70 text-blue-800"
                                >
                                    Penanggung Jawab
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-blue-700/90 leading-relaxed"
                            >
                                Kader bertindak sebagai penanggung jawab & pengelola posyandu (<strong>tidak dihitung</strong> sebagai Penerima Manfaat / PM).
                            </p>
                        </div>
                        <div class="w-full sm:w-44 shrink-0">
                            <div class="relative flex items-center">
                                <input
                                    id="jumlah_kader"
                                    v-model.number="form.jumlah_kader"
                                    @input="clearFieldError('jumlah_kader')"
                                    type="number"
                                    min="1"
                                    placeholder="Contoh: 5"
                                    class="w-full h-10 pl-3 pr-14 text-xs font-bold text-slate-800 bg-white rounded-lg border border-blue-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-right shadow-2xs"
                                    :class="{
                                        'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500 bg-rose-50/20':
                                            getFieldError('jumlah_kader'),
                                    }"
                                />
                                <span
                                    class="absolute right-3 pointer-events-none text-xs text-slate-400 font-medium select-none"
                                >
                                    Kader
                                </span>
                            </div>
                            <p
                                v-if="getFieldError('jumlah_kader')"
                                class="text-[11px] text-rose-500 font-semibold flex items-center gap-1 mt-1 text-right justify-end"
                            >
                                <AlertCircle class="h-3 w-3 shrink-0" />
                                <span>{{ getFieldError("jumlah_kader") }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
