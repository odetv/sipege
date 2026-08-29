<script setup>
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Label from "@/Components/ui/Label.vue";
import MapPicker from "@/Components/MapPicker.vue";
import { MapPin, AlertCircle, Loader2 } from "lucide-vue-next";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    defaultCenter: {
        type: Array,
        default: () => [-8.409518, 115.188916],
    },
    provinceList: {
        type: Array,
        default: () => [],
    },
    regencyList: {
        type: Array,
        default: () => [],
    },
    districtList: {
        type: Array,
        default: () => [],
    },
    villageList: {
        type: Array,
        default: () => [],
    },
    isProvincesLoading: {
        type: Boolean,
        default: false,
    },
    isRegenciesLoading: {
        type: Boolean,
        default: false,
    },
    isDistrictsLoading: {
        type: Boolean,
        default: false,
    },
    isVillagesLoading: {
        type: Boolean,
        default: false,
    },
    selectedProvinceCode: {
        type: String,
        default: "",
    },
    selectedRegencyCode: {
        type: String,
        default: "",
    },
    selectedDistrictCode: {
        type: String,
        default: "",
    },
    selectedVillageCode: {
        type: String,
        default: "",
    },
    getFieldError: {
        type: Function,
        required: true,
    },
    clearFieldError: {
        type: Function,
        required: true,
    },
    cleanKabupatenName: {
        type: Function,
        default: (name) => (name ? name.replace(/^Kabupaten\s+/i, "").replace(/^Kota\s+/i, "") : ""),
    },
});

defineEmits([
    "update:selectedProvinceCode",
    "update:selectedRegencyCode",
    "update:selectedDistrictCode",
    "update:selectedVillageCode",
    "provinceChange",
    "regencyChange",
    "districtChange",
    "villageChange",
]);
</script>

<template>
    <!-- 3. WILAYAH & TITIK LOKASI PETA (SPLIT 2 BAGIAN) -->
    <Card className="bg-white border-slate-200/80 shadow-xs">
        <CardHeader
            className="border-b border-slate-100 p-5 bg-slate-50/50"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0"
                >
                    <MapPin class="h-4 w-4" />
                </div>
                <div>
                    <CardTitle
                        className="text-base font-bold text-slate-900"
                    >
                        3. Wilayah & Titik Lokasi Peta
                    </CardTitle>
                    <CardDescription
                        className="text-xs text-slate-500 mt-0.5"
                    >
                        Alamat administrasi wilayah dan penentuan
                        titik koordinat geografis di peta.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent className="p-5 sm:p-6">
            <div
                class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
            >
                <!-- SISI KIRI: Form Input Wilayah & Alamat Lengkap (6 Kolom) -->
                <div class="lg:col-span-6 space-y-4">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <!-- Provinsi -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label
                                for="provinsi"
                                class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                            >
                                <span>Provinsi</span>
                                <span class="text-rose-500">*</span>
                                <Loader2
                                    v-if="isProvincesLoading"
                                    class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                />
                            </Label>
                            <select
                                id="provinsi"
                                :value="selectedProvinceCode"
                                @input="$emit('update:selectedProvinceCode', $event.target.value)"
                                @change="$emit('provinceChange')"
                                :disabled="isProvincesLoading"
                                class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-800 cursor-pointer disabled:bg-slate-50 disabled:text-slate-400"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError('provinsi'),
                                }"
                                required
                            >
                                <option value="">
                                    {{
                                        isProvincesLoading
                                            ? "Memuat Provinsi..."
                                            : "Pilih Provinsi..."
                                    }}
                                </option>
                                <option
                                    v-for="prov in provinceList"
                                    :key="prov.code"
                                    :value="prov.code"
                                >
                                    {{ prov.name }}
                                </option>
                            </select>
                            <p
                                v-if="getFieldError('provinsi')"
                                class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                            >
                                <AlertCircle
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    getFieldError("provinsi")
                                }}</span>
                            </p>
                        </div>

                        <!-- Kabupaten/Kota (Hanya Menampilkan Nama Langsung) -->
                        <div class="space-y-1.5">
                            <Label
                                for="kabupaten"
                                class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                            >
                                <span>Kabupaten / Kota</span>
                                <span class="text-rose-500">*</span>
                                <Loader2
                                    v-if="isRegenciesLoading"
                                    class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                />
                            </Label>
                            <select
                                id="kabupaten"
                                :value="selectedRegencyCode"
                                @input="$emit('update:selectedRegencyCode', $event.target.value)"
                                @change="$emit('regencyChange')"
                                :disabled="
                                    !selectedProvinceCode ||
                                    isRegenciesLoading ||
                                    regencyList.length === 0
                                "
                                class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError('kabupaten'),
                                }"
                                required
                            >
                                <option value="">
                                    {{
                                        isRegenciesLoading
                                            ? "Memuat Kabupaten..."
                                            : "Pilih Kabupaten/Kota..."
                                    }}
                                </option>
                                <option
                                    v-for="reg in regencyList"
                                    :key="reg.code"
                                    :value="reg.code"
                                >
                                    {{
                                        cleanKabupatenName(reg.name)
                                    }}
                                </option>
                            </select>
                            <p
                                v-if="getFieldError('kabupaten')"
                                class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                            >
                                <AlertCircle
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    getFieldError("kabupaten")
                                }}</span>
                            </p>
                        </div>

                        <!-- Kecamatan -->
                        <div class="space-y-1.5">
                            <Label
                                for="kecamatan"
                                class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                            >
                                <span>Kecamatan</span>
                                <span class="text-rose-500">*</span>
                                <Loader2
                                    v-if="isDistrictsLoading"
                                    class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                />
                            </Label>
                            <select
                                id="kecamatan"
                                :value="selectedDistrictCode"
                                @input="$emit('update:selectedDistrictCode', $event.target.value)"
                                @change="$emit('districtChange')"
                                :disabled="
                                    !selectedRegencyCode ||
                                    isDistrictsLoading ||
                                    districtList.length === 0
                                "
                                class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError('kecamatan'),
                                }"
                                required
                            >
                                <option value="">
                                    {{
                                        isDistrictsLoading
                                            ? "Memuat Kecamatan..."
                                            : "Pilih Kecamatan..."
                                    }}
                                </option>
                                <option
                                    v-for="dist in districtList"
                                    :key="dist.code"
                                    :value="dist.code"
                                >
                                    {{ dist.name }}
                                </option>
                            </select>
                            <p
                                v-if="getFieldError('kecamatan')"
                                class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                            >
                                <AlertCircle
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    getFieldError("kecamatan")
                                }}</span>
                            </p>
                        </div>

                        <!-- Desa/Kelurahan -->
                        <div class="space-y-1.5">
                            <Label
                                for="desa_kelurahan"
                                class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                            >
                                <span>Desa / Kelurahan</span>
                                <span class="text-rose-500">*</span>
                                <Loader2
                                    v-if="isVillagesLoading"
                                    class="h-3.5 w-3.5 animate-spin text-primary ml-1"
                                />
                            </Label>
                            <select
                                id="desa_kelurahan"
                                :value="selectedVillageCode"
                                @input="$emit('update:selectedVillageCode', $event.target.value)"
                                @change="$emit('villageChange')"
                                :disabled="
                                    !selectedDistrictCode ||
                                    isVillagesLoading ||
                                    villageList.length === 0
                                "
                                class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all disabled:bg-slate-50 disabled:text-slate-400 text-slate-800 cursor-pointer"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError(
                                            'desa_kelurahan',
                                        ),
                                }"
                                required
                            >
                                <option value="">
                                    {{
                                        isVillagesLoading
                                            ? "Memuat Desa..."
                                            : "Pilih Desa/Kelurahan..."
                                    }}
                                </option>
                                <option
                                    v-for="vil in villageList"
                                    :key="vil.code"
                                    :value="vil.code"
                                >
                                    {{ vil.name }}
                                </option>
                            </select>
                            <p
                                v-if="
                                    getFieldError('desa_kelurahan')
                                "
                                class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                            >
                                <AlertCircle
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    getFieldError("desa_kelurahan")
                                }}</span>
                            </p>
                        </div>

                        <!-- Kode Pos -->
                        <div class="space-y-1.5">
                            <Label
                                for="kode_pos"
                                class="text-xs font-semibold text-slate-700"
                            >
                                Kode Pos
                                <span class="text-rose-500">*</span>
                            </Label>
                            <input
                                id="kode_pos"
                                v-model="form.kode_pos"
                                @input="clearFieldError('kode_pos')"
                                type="text"
                                maxlength="5"
                                placeholder="5 digit angka"
                                class="w-full h-11 px-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900"
                                :class="{
                                    'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                        getFieldError('kode_pos'),
                                }"
                                required
                            />
                            <p
                                v-if="getFieldError('kode_pos')"
                                class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                            >
                                <AlertCircle
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span>{{
                                    getFieldError("kode_pos")
                                }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="space-y-1.5">
                        <Label
                            for="alamat_lengkap"
                            class="text-xs font-semibold text-slate-700"
                        >
                            Alamat Lengkap (Jalan / Dusun / RT / RW)
                            <span class="text-rose-500">*</span>
                        </Label>
                        <textarea
                            id="alamat_lengkap"
                            v-model="form.alamat_lengkap"
                            @input="
                                clearFieldError('alamat_lengkap')
                            "
                            rows="3"
                            placeholder="Contoh: Jl. Mayor Metra No. 12, Banjar Dinas Anyar"
                            class="w-full p-3.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-900 leading-relaxed"
                            :class="{
                                'border-rose-400 focus:ring-rose-400/20 focus:border-rose-500':
                                    getFieldError('alamat_lengkap'),
                            }"
                            required
                        ></textarea>
                        <p
                            v-if="getFieldError('alamat_lengkap')"
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("alamat_lengkap")
                            }}</span>
                        </p>
                    </div>
                </div>

                <!-- SISI KANAN: Map Picker Komponen (6 Kolom) -->
                <div class="lg:col-span-6 space-y-2">
                    <div id="latitude"></div>
                    <MapPicker
                        v-model:latitude="form.latitude"
                        v-model:longitude="form.longitude"
                        :default-center="defaultCenter"
                        :default-zoom="10"
                        label="Titik Koordinat Lokasi Kelompok *"
                        height="370px"
                    />
                    <div
                        v-if="
                            getFieldError('latitude') ||
                            getFieldError('longitude')
                        "
                    >
                        <p
                            class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1"
                        >
                            <AlertCircle
                                class="h-3.5 w-3.5 shrink-0"
                            />
                            <span>{{
                                getFieldError("latitude") ||
                                getFieldError("longitude")
                            }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
