<script setup>
import { computed } from "vue";
import {
    Calendar,
    Clock,
    Hourglass,
    MapPin,
    Flame,
    AlertTriangle,
} from "lucide-vue-next";

const props = defineProps({
    templateConfig: {
        type: Object,
        required: true,
    },
    kelompok: {
        type: Object,
        default: () => ({
            nama_kelompok: "RA Baitul Mutaallim",
            total_penerima: 45,
            total_porsi_kecil: 25,
            total_porsi_besar: 20,
        }),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    tanggalProduksi: {
        type: String,
        default: "",
    },
    jamProduksi: {
        type: String,
        default: "07:00",
    },
    batasKonsumsi: {
        type: String,
        default: "09:00",
    },
    petunjukMenu: {
        type: String,
        default:
            "Nasi Putih - Dori Finger with Yellow Mayonaise - Steam Tahu - Buncis & Jagung Manis - Buah Pepaya",
    },
    giziData: {
        type: Object,
        default: () => ({
            energi_pk: "386.3",
            energi_pb: "547.3",
            karbo_pk: "50.9",
            karbo_pb: "80",
            protein_pk: "18.3",
            protein_pb: "21.6",
            lemak_pk: "13",
            lemak_pb: "17.2",
            serat_pk: "3.6",
            serat_pb: "6.4",
        }),
    },
    isCanvasPreview: {
        type: Boolean,
        default: false,
    },
});

function formatDateSlash(dateStr) {
    if (!dateStr) return "-";
    try {
        const parts = String(dateStr).split("-");
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    } catch {
        return dateStr;
    }
}

function formatJam(val) {
    if (!val) return "-";
    return String(val).replace(":", ".");
}

const aspectRatioClass = computed(() => {
    switch (props.templateConfig.aspect_ratio) {
        case "4/3":
            return "aspect-[4/3]";
        case "16/9":
            return "aspect-[16/9]";
        case "1/1":
            return "aspect-square";
        case "3/2":
            return "aspect-[3/2]";
        default:
            return "aspect-[4/3]";
    }
});

const elements = computed(() => {
    return props.templateConfig.elements || [];
});

const hasCustomElements = computed(() => {
    return Array.isArray(elements.value) && elements.value.length > 0;
});
</script>

<template>
    <div
        :class="[
            'bgn-label-card w-full max-w-[640px] mx-auto bg-white shadow-md relative overflow-hidden print:shadow-none print:break-inside-avoid print:my-4 print:w-full',
            aspectRatioClass,
        ]"
        :style="{
            borderWidth: templateConfig.border_width || '3px',
            borderColor: templateConfig.border_color || '#1E4B8B',
            borderRadius: templateConfig.border_radius || '1rem',
            borderStyle: 'solid',
            backgroundColor: templateConfig.canvas_bg || '#FFFFFF',
            padding: templateConfig.canvas_padding || '6px',
        }"
    >
        <!-- ================= MODE 1: CANVA FREE-FORM DRAGGABLE ELEMENTS ================= -->
        <div v-if="hasCustomElements" class="w-full h-full relative overflow-hidden select-none">
            <div
                v-for="el in elements"
                :key="el.id"
                v-show="el.visible !== false"
                class="absolute flex flex-col pointer-events-none"
                :style="{
                    left: `${el.x}%`,
                    top: `${el.y}%`,
                    width: `${el.width}%`,
                    height: `${el.height}%`,
                    zIndex: el.zIndex || 1,
                    backgroundColor: el.backgroundColor || 'transparent',
                    borderColor: el.borderColor || 'transparent',
                    borderWidth: el.borderWidth ? `${el.borderWidth}px` : '0px',
                    borderRadius: el.borderRadius ? `${el.borderRadius}px` : '0px',
                    borderStyle: el.borderWidth ? 'solid' : 'none',
                    color: el.color || 'inherit',
                    fontSize: el.fontSize ? `${el.fontSize}px` : 'inherit',
                    fontWeight: el.fontWeight || 'normal',
                    textAlign: el.textAlign || 'left',
                    padding: el.padding ? `${el.padding}px` : '0px',
                }"
            >
                <!-- Type: LOGO BGN -->
                <div v-if="el.type === 'logo'" class="w-full h-full flex items-center justify-start select-none overflow-hidden">
                    <img
                        v-if="el.imageUrl"
                        :src="el.imageUrl"
                        alt="Logo BGN"
                        class="h-full w-full object-contain object-left"
                    />
                    <!-- Fallback CSS Emblem -->
                    <div
                        v-else
                        class="h-full max-h-12 aspect-square rounded-full bg-[#1E3A8A] border-2 border-[#D4A017] p-0.5 flex items-center justify-center shrink-0 shadow-xs relative"
                    >
                        <div class="h-full w-full rounded-full bg-[#0D6538] border border-[#D4A017] flex items-center justify-center text-center">
                            <span class="text-[#FBBF24] text-xs font-black">★</span>
                        </div>
                    </div>
                </div>

                <!-- Type: IMAGE / GAMBAR KUSTOM -->
                <div v-else-if="el.type === 'image'" class="w-full h-full flex items-center justify-center overflow-hidden">
                    <img
                        v-if="el.imageUrl"
                        :src="el.imageUrl"
                        alt="Gambar"
                        class="w-full h-full object-contain"
                    />
                    <div v-else class="w-full h-full border-2 border-dashed border-slate-300 rounded flex items-center justify-center text-slate-400 text-[10px]">
                        Gambar Kosong
                    </div>
                </div>

                <!-- Type: SPPG HEADER TEXT -->
                <div v-else-if="el.type === 'sppg_header'" class="w-full h-full flex flex-col justify-center" :style="{ textAlign: el.textAlign || 'right' }">
                    <span class="block text-[8.5px] sm:text-[9.5px] font-bold text-slate-500 uppercase tracking-wider">
                        SATUAN PELAYANAN PEMENUHAN GIZI
                    </span>
                    <h2 class="font-black text-[#1E3A8A] text-xs sm:text-sm md:text-[14px] leading-tight uppercase mt-0.5 truncate">
                        {{ unitSppg?.nama ? (unitSppg.nama.startsWith('SPPG') ? unitSppg.nama : 'SPPG ' + unitSppg.nama) : 'SPPG BULELENG SUKASADA TEGALLINGGAH' }}
                    </h2>
                </div>

                <!-- Type: DIVIDER LINE -->
                <div v-else-if="el.type === 'divider'" class="w-full h-full rounded-full" :style="{ backgroundColor: el.backgroundColor || '#C5921D' }"></div>

                <!-- Type: BADGE -->
                <div v-else-if="el.type === 'badge'" class="w-full h-full flex items-center justify-center text-center font-black uppercase tracking-wide rounded-lg shadow-2xs text-white" :style="{ backgroundColor: el.backgroundColor || '#4E88C7', fontSize: el.fontSize ? `${el.fontSize}px` : '10px' }">
                    {{ el.text || 'LABEL MAKANAN BERGIZI GRATIS' }}
                </div>

                <!-- Type: TANGGAL PRODUKSI -->
                <div v-else-if="el.type === 'tanggal'" class="w-full h-full flex flex-col justify-between">
                    <label class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5">
                        <Calendar class="h-2.5 w-2.5 text-slate-800" />
                        <span>Tanggal Produksi</span>
                    </label>
                    <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-2 text-center text-xs sm:text-sm font-black text-slate-900 shadow-2xs">
                        {{ formatDateSlash(tanggalProduksi) }}
                    </div>
                </div>

                <!-- Type: JAM PRODUKSI -->
                <div v-else-if="el.type === 'jam'" class="w-full h-full flex flex-col justify-between">
                    <label class="text-[9px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate">
                        <Clock class="h-2.5 w-2.5 text-slate-800 shrink-0" />
                        <span>Jam Produksi</span>
                    </label>
                    <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[10.5px] sm:text-xs font-black text-slate-900 shadow-2xs">
                        {{ formatJam(jamProduksi) }}
                    </div>
                </div>

                <!-- Type: BATAS KONSUMSI -->
                <div v-else-if="el.type === 'batas'" class="w-full h-full flex flex-col justify-between">
                    <label class="text-[9px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate">
                        <Hourglass class="h-2.5 w-2.5 text-slate-800 shrink-0" />
                        <span>Batas Konsumsi</span>
                    </label>
                    <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[10.5px] sm:text-xs font-black text-slate-900 shadow-2xs">
                        {{ formatJam(batasKonsumsi) }}
                    </div>
                </div>

                <!-- Type: TUJUAN PENGANTARAN -->
                <div v-else-if="el.type === 'tujuan'" class="w-full h-full flex flex-col justify-between">
                    <label class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5">
                        <MapPin class="h-2.5 w-2.5 text-slate-800" />
                        <span>Tujuan Pengantaran</span>
                    </label>
                    <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1.5 px-2 text-left text-[10.5px] sm:text-xs font-black text-[#1E3A8A] shadow-2xs leading-tight truncate">
                        {{ kelompok.nama_kelompok }}
                    </div>
                </div>

                <!-- Type: MENU BOX -->
                <div v-else-if="el.type === 'menu'" class="w-full h-full rounded-xl p-1 flex items-stretch gap-1.5 text-white shadow-2xs" :style="{ backgroundColor: el.backgroundColor || '#4E88C7' }">
                    <div class="bg-black/20 rounded-lg px-2 flex items-center justify-center font-black text-[9.5px] uppercase tracking-wider shrink-0">
                        MENU
                    </div>
                    <div class="flex items-center text-[9px] sm:text-[9.5px] font-bold leading-tight py-0.5 pr-1 text-white/95 line-clamp-3">
                        {{ petunjukMenu }}
                    </div>
                </div>

                <!-- Type: NUTRITION TABLE -->
                <div v-else-if="el.type === 'nutrition_table'" class="w-full h-full flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1 text-[9.5px] font-extrabold text-slate-900 mb-0.5">
                            <Flame class="h-2.5 w-2.5 text-slate-800" />
                            <span>Kandungan Gizi</span>
                        </div>
                        <div class="border-b border-dashed border-slate-300 mb-1"></div>
                    </div>

                    <div class="space-y-0.5 text-xs flex-1 flex flex-col justify-between">
                        <!-- Energi -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9.5px] font-bold text-slate-800">
                                Energi <span class="text-[8px] text-slate-500 font-normal">(Kkal)</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.energi_pk }}
                                </div>
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.energi_pb }}
                                </div>
                            </div>
                        </div>

                        <!-- Karbohidrat -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9.5px] font-bold text-slate-800">
                                Karbohidrat <span class="text-[8px] text-slate-500 font-normal">(g)</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.karbo_pk }}
                                </div>
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.karbo_pb }}
                                </div>
                            </div>
                        </div>

                        <!-- Protein -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9.5px] font-bold text-slate-800">
                                Protein <span class="text-[8px] text-slate-500 font-normal">(g)</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.protein_pk }}
                                </div>
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.protein_pb }}
                                </div>
                            </div>
                        </div>

                        <!-- Lemak -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9.5px] font-bold text-slate-800">
                                Lemak <span class="text-[8px] text-slate-500 font-normal">(g)</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.lemak_pk }}
                                </div>
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.lemak_pb }}
                                </div>
                            </div>
                        </div>

                        <!-- Serat -->
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9.5px] font-bold text-slate-800">
                                Serat <span class="text-[8px] text-slate-500 font-normal">(g)</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.serat_pk }}
                                </div>
                                <div class="w-10 sm:w-11 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[9.5px] font-bold text-slate-900">
                                    {{ giziData.serat_pb }}
                                </div>
                            </div>
                        </div>

                        <!-- Headers PK / PB -->
                        <div class="flex items-center justify-end gap-1 pt-0.5">
                            <div class="w-10 sm:w-11 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8px] font-extrabold leading-tight">
                                Porsi<br />Kecil
                            </div>
                            <div class="w-10 sm:w-11 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8px] font-extrabold leading-tight">
                                Porsi<br />Besar
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type: WARNING BANNER -->
                <div v-else-if="el.type === 'warning'" class="w-full h-full bg-[#FFF5F5] border border-[#FCA5A5] rounded-xl p-1.5 sm:p-2 flex items-center gap-2 relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 sm:w-2 bg-[#DC2626]"></div>
                    <div class="pl-1.5 shrink-0">
                        <div class="h-6 w-6 rounded-lg flex items-center justify-center text-[#DC2626]">
                            <AlertTriangle class="h-5 w-5" stroke-width="2.5" />
                        </div>
                    </div>
                    <div class="leading-tight">
                        <p class="text-[#DC2626] font-extrabold text-[9px] sm:text-[10px] tracking-tight uppercase">
                            {{ el.text || 'MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.' }}
                        </p>
                        <p class="text-[#DC2626] font-black text-[11px] sm:text-xs tracking-wide uppercase mt-0.5">
                            {{ el.subtitle || 'DILARANG MEMBAWA PULANG!' }}
                        </p>
                    </div>
                </div>

                <!-- Type: TEXT / CUSTOM SHAPE -->
                <div v-else class="w-full h-full flex items-center justify-center break-words overflow-hidden" :style="{ color: el.color || 'inherit', fontSize: el.fontSize ? `${el.fontSize}px` : 'inherit', fontWeight: el.fontWeight || 'normal', textAlign: el.textAlign || 'left' }">
                    {{ el.text || '' }}
                </div>
            </div>
        </div>

        <!-- ================= MODE 2: FALLBACK STRUCTURED LAYOUT ================= -->
        <div v-else class="w-full h-full flex flex-col justify-between p-4 sm:p-5">
            <!-- Header -->
            <div class="shrink-0 pb-1">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <img
                            src="/images/BGN_LOGOTYPE_MAIN.png"
                            alt="Badan Gizi Nasional"
                            class="h-10 sm:h-11 w-auto object-contain"
                        />
                    </div>
                    <div class="text-right">
                        <span class="block text-[9px] sm:text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            SATUAN PELAYANAN PEMENUHAN GIZI
                        </span>
                        <h2 class="font-black text-[#1E3A8A] text-xs sm:text-sm md:text-[15px] leading-tight uppercase mt-0.5 truncate max-w-[340px]">
                            {{ unitSppg?.nama ? (unitSppg.nama.startsWith('SPPG') ? unitSppg.nama : 'SPPG ' + unitSppg.nama) : 'SPPG BULELENG SUKASADA TEGALLINGGAH' }}
                        </h2>
                    </div>
                </div>
                <div class="h-[2px] bg-[#C5921D] rounded-full mt-2 mb-2"></div>
            </div>

            <!-- Body Grid -->
            <div class="grid grid-cols-2 gap-3 flex-1 my-auto items-stretch min-h-0">
                <div class="flex flex-col justify-between space-y-1.5 min-w-0">
                    <div class="bg-[#4E88C7] text-white text-center py-1 px-2.5 rounded-lg font-black text-[10px] sm:text-[11px] uppercase tracking-wide shadow-2xs">
                        LABEL MAKANAN BERGIZI GRATIS
                    </div>
                    <div>
                        <label class="text-[10px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5">
                            <Calendar class="h-3 w-3 text-slate-800" />
                            <span>Tanggal Produksi</span>
                        </label>
                        <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-2.5 text-center text-xs sm:text-sm font-black text-slate-900 shadow-2xs">
                            {{ formatDateSlash(tanggalProduksi) }}
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5">
                        <div>
                            <label class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate">
                                <Clock class="h-2.5 w-2.5 text-slate-800 shrink-0" />
                                <span>Jam Produksi</span>
                            </label>
                            <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[11px] sm:text-xs font-black text-slate-900 shadow-2xs">
                                {{ formatJam(jamProduksi) }}
                            </div>
                        </div>
                        <div>
                            <label class="text-[9.5px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5 truncate">
                                <Hourglass class="h-2.5 w-2.5 text-slate-800 shrink-0" />
                                <span>Batas Konsumsi</span>
                            </label>
                            <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1 px-1.5 text-center text-[11px] sm:text-xs font-black text-slate-900 shadow-2xs">
                                {{ formatJam(batasKonsumsi) }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-extrabold text-slate-800 flex items-center gap-1 mb-0.5">
                            <MapPin class="h-3 w-3 text-slate-800" />
                            <span>Tujuan Pengantaran</span>
                        </label>
                        <div class="bg-[#EDF4FC] border border-[#BFD8F2] rounded-lg py-1.5 px-2.5 text-left text-[11px] sm:text-xs font-black text-[#1E3A8A] shadow-2xs leading-tight truncate">
                            {{ kelompok.nama_kelompok }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-between space-y-1.5 min-w-0">
                    <div class="bg-[#4E88C7] rounded-xl p-1 flex items-stretch gap-1.5 text-white shadow-2xs min-h-[44px]">
                        <div class="bg-[#3D74B0] rounded-lg px-2 flex items-center justify-center font-black text-[10px] uppercase tracking-wider shrink-0">
                            MENU
                        </div>
                        <div class="flex items-center text-[9.5px] sm:text-[10px] font-bold leading-tight py-0.5 pr-1 text-white/95 line-clamp-3">
                            {{ petunjukMenu }}
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-1 text-[10px] font-extrabold text-slate-900 mb-0.5">
                            <Flame class="h-3 w-3 text-slate-800" />
                            <span>Kandungan Gizi</span>
                        </div>
                        <div class="border-b border-dashed border-slate-300 mb-1"></div>
                        <div class="space-y-0.5 text-xs">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-bold text-slate-800">
                                    Energi <span class="text-[8.5px] text-slate-500 font-normal">(Kkal)</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.energi_pk }}
                                    </div>
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.energi_pb }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-bold text-slate-800">
                                    Karbohidrat <span class="text-[8.5px] text-slate-500 font-normal">(g)</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.karbo_pk }}
                                    </div>
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.karbo_pb }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-bold text-slate-800">
                                    Protein <span class="text-[8.5px] text-slate-500 font-normal">(g)</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.protein_pk }}
                                    </div>
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.protein_pb }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-bold text-slate-800">
                                    Lemak <span class="text-[8.5px] text-slate-500 font-normal">(g)</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.lemak_pk }}
                                    </div>
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.lemak_pb }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-bold text-slate-800">
                                    Serat <span class="text-[8.5px] text-slate-500 font-normal">(g)</span>
                                </span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.serat_pk }}
                                    </div>
                                    <div class="w-11 sm:w-12 bg-[#EDF4FC] border border-[#BFD8F2] rounded py-0.5 text-center text-[10px] font-bold text-slate-900">
                                        {{ giziData.serat_pb }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-1 pt-0.5">
                                <div class="w-11 sm:w-12 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8.5px] font-extrabold leading-tight">
                                    Porsi<br />Kecil
                                </div>
                                <div class="w-11 sm:w-12 bg-[#5A92CF] text-white rounded py-0.5 text-center text-[8.5px] font-extrabold leading-tight">
                                    Porsi<br />Besar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Warning -->
            <div class="shrink-0 bg-[#FFF5F5] border border-[#FCA5A5] rounded-xl p-2 sm:p-2.5 flex items-center gap-2.5 relative overflow-hidden mt-1">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 sm:w-2 bg-[#DC2626]"></div>
                <div class="pl-1.5 shrink-0">
                    <div class="h-7 w-7 rounded-lg flex items-center justify-center text-[#DC2626]">
                        <AlertTriangle class="h-5 w-5 sm:h-6 sm:w-6" stroke-width="2.5" />
                    </div>
                </div>
                <div class="leading-none">
                    <p class="text-[#DC2626] font-extrabold text-[9.5px] sm:text-[10.5px] tracking-tight uppercase">
                        MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.
                    </p>
                    <p class="text-[#DC2626] font-black text-xs sm:text-sm tracking-wide uppercase mt-0.5">
                        DILARANG MEMBAWA PULANG!
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
