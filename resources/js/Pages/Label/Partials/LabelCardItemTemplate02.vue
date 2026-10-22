<script setup>
import { computed } from "vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    namaSppg: {
        type: String,
        default: "",
    },
    zonaWaktu: {
        type: String,
        default: "WITA",
    },
    tanggalProduksi: {
        type: String,
        default: "",
    },
    jamProduksi: {
        type: String,
        default: "12:14",
    },
    tanggalExpired: {
        type: String,
        default: "",
    },
    jamExpired: {
        type: String,
        default: "10:00",
    },
    menuItems: {
        type: Array,
        default: () => [
            "Nasi Putih",
            "Telur Ceplok Asam Manis",
            "Tahu Bumbu Kuning",
            "Tumis Wortel & Kol",
            "Anggur Muscat",
        ],
    },
    giziData: {
        type: Object,
        default: () => ({
            energi_pk: "356",
            energi_pb: "539",
            karbo_pk: "60",
            karbo_pb: "87.6",
            prot_pk: "23",
            prot_pb: "30",
            lmk_pk: "3",
            lmk_pb: "4.8",
            serat_pk: "1.9",
            serat_pb: "3.3",
        }),
    },
    hargaItems: {
        type: Array,
        default: () => [],
    },
    kelompok: {
        type: Object,
        default: null,
    },
    tinggiIsolasiCm: {
        type: [Number, String],
        default: 1.5,
    },
    waktuMaksimal: {
        type: String,
        default: "",
    },
    teksLaranganHeader: {
        type: String,
        default: "TIDAK BOLEH DIBAWA PULANG.",
    },
    teksLaranganSub: {
        type: String,
        default: "SEGERA KONSUMSI SETELAH DITERIMA!",
    },
    tampilkanJamProduksi: {
        type: Boolean,
        default: false,
    },
    tampilkanJamExpired: {
        type: Boolean,
        default: false,
    },
    tipeLabel: {
        type: String,
        default: "normal", // 'normal' | 'alergi' | 'tambahan'
    },
    subTipeTambahan: {
        type: String,
        default: "", // 'organoleptik' | 'sampel' | 'buffer'
    },
    jenisAlergi: {
        type: String,
        default: "",
    },
    tagAlergi: {
        type: String,
        default: "",
    },
    kontakBaris1: {
        type: String,
        default: "",
    },
    kontakBaris2: {
        type: String,
        default: "",
    },
    kontakBaris3: {
        type: String,
        default: "",
    },
    kontakBaris4: {
        type: String,
        default: "",
    },
    customColors: {
        type: Object,
        default: () => ({}),
    },
});

const DEFAULT_COLORS = {
    areaPerekat: "#ffffff",
    headerSppg: "#ffffff",
    menuLabel: "#ffffff",
    menuValue: "#ffffff",
    tglLabel: "#ffffff",
    tglValue: "#ffffff",
    batasKonsumsi: "#ffffff",
    batasKonsumsiVal: "#ffffff",
    kontak: "#ffffff",
    giziHeader: "#ffffff",
    giziBody: "#ffffff",
    footer: "#ffffff",
};

const getBgColor = (key) => {
    if (!props.customColors) return "#ffffff";
    const val = props.customColors[key];
    if (val === undefined || val === null || val === "" || val === "transparent" || val === "none") {
        return "#ffffff";
    }
    return val;
};

const displayNameSppg = computed(() => {
    if (props.namaSppg && props.namaSppg.trim()) {
        return props.namaSppg.trim().toUpperCase();
    }
    if (props.unitSppg?.nama) {
        return props.unitSppg.nama.trim().toUpperCase();
    }
    return "SPPG BULELENG SUKASADA TEGALLINGGAH";
});

const isShowingJamProduksi = computed(() => {
    return Boolean(
        props.tampilkanJamProduksi &&
        props.jamProduksi &&
        String(props.jamProduksi).trim()
    );
});

const menuTypeLabel = computed(() => {
    if (props.tipeLabel === "alergi") {
        return props.jenisAlergi
            ? `ALERGI ${props.jenisAlergi.toUpperCase()}`
            : "ALERGI";
    }
    if (props.tipeLabel === "tambahan") {
        if (props.subTipeTambahan === "organoleptik") return "UJI ORGANOLEPTIK";
        if (props.subTipeTambahan === "sampel") return "SAMPEL MAKANAN";
        if (props.subTipeTambahan === "buffer") return "BUFFER PRODUKSI";
        return "TAMBAHAN";
    }
    return "NORMAL";
});

const menuItemsFormatted = computed(() => {
    if (Array.isArray(props.menuItems) && props.menuItems.length > 0) {
        const filtered = props.menuItems
            .map((it) => (typeof it === "string" ? it.trim() : String(it || "").trim()))
            .filter(Boolean);
        if (filtered.length > 0) {
            return filtered.join(" - ");
        }
    }
    return "-";
});

const formattedTanggalProduksi = computed(() => {
    if (!props.tanggalProduksi) return "-";
    // Check if YYYY-MM-DD
    let tgl = String(props.tanggalProduksi);
    const parts = tgl.split("-");
    if (parts.length === 3 && parts[0].length === 4) {
        tgl = `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    if (isShowingJamProduksi.value) {
        const jam = String(props.jamProduksi).trim();
        const zona = props.zonaWaktu ? ` ${props.zonaWaktu}` : "";
        return `${tgl} ${jam}${zona}`;
    }
    return tgl;
});

const formattedWaktuBatas = computed(() => {
    if (props.tampilkanJamExpired && props.jamExpired && String(props.jamExpired).trim()) {
        const zona = props.zonaWaktu ? ` ${props.zonaWaktu}` : "";
        return `${String(props.jamExpired).trim()}${zona}`;
    }
    if (props.waktuMaksimal && String(props.waktuMaksimal).trim()) {
        return String(props.waktuMaksimal).trim();
    }
    const jam = props.jamExpired || "10:00";
    const zona = props.zonaWaktu || "WITA";
    return `${jam} ${zona}`;
});

const isMergedWaktuBatas = computed(() => {
    const str = (formattedWaktuBatas.value || "").trim();
    if (str.length > 12) return true;
    if (/jam\s+setelah|setelah|diterima|maksimal|segera/i.test(str)) return true;
    const isShortTime = /^\d{1,2}[:.]\d{2}(\s*(WITA|WIB|WIT))?$/i.test(str);
    return !isShortTime;
});

const socialMediaHandle = computed(() => {
    // Ambil nama singkat dari nama SPPG untuk handle IG / Tiktok / FB
    const raw = displayNameSppg.value.toLowerCase();
    if (raw.includes("tegallinggah")) {
        return {
            ig: "Instagram: @sppg.tegallinggah",
            tiktok: "Tiktok: @sppg.tegallinggah",
            fb: "Facebook: SPPG Tegallinggah",
        };
    }
    // Ekstrak nama desa atau kata terakhir
    const words = raw.replace(/^sppg\s+/, "").trim().split(/\s+/);
    const suffix = words.length > 0 ? words[words.length - 1] : "bgn";
    const slug = suffix.replace(/[^a-z0-9]/g, "");
    return {
        ig: `Instagram: @sppg.${slug}`,
        tiktok: `Tiktok: @sppg.${slug}`,
        fb: `Facebook: ${displayNameSppg.value}`,
    };
});

const contactDisplayLines = computed(() => {
    const sm = socialMediaHandle.value;
    return [
        props.kontakBaris1?.trim() || "Email: halo@bgn.go.id",
        props.kontakBaris2?.trim() || sm.ig,
        props.kontakBaris3?.trim() || sm.tiktok,
        props.kontakBaris4?.trim() || sm.fb,
    ];
});

const giziDisplay = computed(() => {
    const gd = props.giziData || {};
    const formatVal = (v) => {
        if (v === undefined || v === null || String(v).trim() === "") return "-";
        return String(v);
    };
    return {
        energi_pk: formatVal(gd.energi_pk),
        energi_pb: formatVal(gd.energi_pb),
        karbo_pk: formatVal(gd.karbo_pk),
        karbo_pb: formatVal(gd.karbo_pb),
        prot_pk: formatVal(gd.prot_pk),
        prot_pb: formatVal(gd.prot_pb),
        lmk_pk: formatVal(gd.lmk_pk),
        lmk_pb: formatVal(gd.lmk_pb),
        serat_pk: formatVal(gd.serat_pk),
        serat_pb: formatVal(gd.serat_pb),
    };
});
const menuFontSizeClass = computed(() => {
    const len = menuItemsFormatted.value.length;
    if (len > 130) {
        return "text-[6.2px] leading-[1.2]";
    }
    if (len > 90) {
        return "text-[7px] leading-[1.2]";
    }
    if (len > 50) {
        return "text-[7.8px] leading-[1.2]";
    }
    return "text-[8.5px] leading-tight";
});

const sppgFontSizeClass = computed(() => {
    const len = displayNameSppg.value.length;
    if (len > 48) {
        return "text-[8px]";
    }
    if (len > 36) {
        return "text-[9.2px]";
    }
    if (len > 25) {
        return "text-[10.5px]";
    }
    return "text-[11.5px]";
});
const menuTypeFontSizeClass = computed(() => {
    const len = menuTypeLabel.value.length;
    if (len > 32) {
        return "text-[6.2px] tracking-tight";
    }
    if (len > 24) {
        return "text-[7px] tracking-tight";
    }
    if (len > 14) {
        return "text-[7.8px]";
    }
    return "text-[8.5px]";
});
</script>

<template>
    <div
        class="bgn-label-card-template02 w-[440px] min-w-[440px] max-w-[440px] h-[305px] min-h-[305px] max-h-[305px] bg-white border-2 border-slate-900 rounded-sm shadow-md overflow-hidden flex flex-col select-none text-slate-950 text-[10px]"
        style="width: 440px; min-width: 440px; max-width: 440px; height: 305px; min-height: 305px; max-height: 305px; aspect-ratio: 88 / 61.05;"
    >
        <!-- 1. Row 1: AREA PEREKAT LABEL (Excel Row 1, Height ~39.95pt / ~23%) -->
        <div
            class="h-[22%] border-b border-dashed border-slate-400 flex items-center justify-center px-2 shrink-0 transition-colors"
            :style="{ backgroundColor: getBgColor('areaPerekat') }"
        >
            <span
                class="text-[8px] font-mono italic tracking-wider text-slate-400 font-normal text-center select-none"
            >
                - - - - - - - - - AREA PEREKAT LABEL - - - - - - - - -
            </span>
        </div>

        <!-- 2. Row 2-3: LOGO BGN & HEADER SPPG (Excel Row 2-3, Height ~28.2pt / ~16%) -->
        <div
            class="h-[16%] border-b border-slate-900 flex items-center justify-between px-2 py-0.5 shrink-0 transition-colors"
            :style="{ backgroundColor: getBgColor('headerSppg') }"
        >
            <div class="flex items-center shrink-0 mr-2">
                <img
                    src="/images/logo/label_bgn_logo_template2.png"
                    alt="Logo BGN"
                    class="h-7 w-auto object-contain"
                />
            </div>
            <div class="text-right flex-1 min-w-0 flex flex-col justify-center items-end">
                <div class="text-[7.8px] text-slate-700 font-normal leading-none tracking-tight">
                    SATUAN PELAYANAN PEMENUHAN GIZI
                </div>
                <div
                    :class="[
                        'font-black text-slate-950 uppercase tracking-tight truncate w-full text-right mt-1 leading-none',
                        sppgFontSizeClass
                    ]"
                    :title="displayNameSppg"
                >
                    {{ displayNameSppg }}
                </div>
            </div>
        </div>

        <!-- 3. Row 4: MENU & SUB-MENU (Excel Row 4, Height ~18pt / ~10.5%) -->
        <div
            class="h-[11%] border-b border-slate-900 flex shrink-0"
        >
            <!-- Col A-B: MENU NORMAL / ALERGI (Width ~40%) -->
            <div
                class="w-[40%] border-r border-slate-900 px-1.5 py-0.5 flex flex-col justify-center leading-tight shrink-0 overflow-hidden transition-colors"
                :style="{ backgroundColor: getBgColor('menuLabel') }"
            >
                <span class="text-[7.8px] font-black text-slate-900 leading-none">
                    MENU
                </span>
                <span
                    :class="[
                        'font-black text-slate-900 leading-tight mt-0.5 truncate w-full',
                        menuTypeFontSizeClass
                    ]"
                    :title="menuTypeLabel"
                >
                    {{ menuTypeLabel }}
                </span>
            </div>
            <!-- Col C-E: Sub-menu items (Width ~60%) -->
            <div
                class="w-[60%] px-1.5 py-0 flex flex-col justify-center shrink-0 overflow-hidden h-full transition-colors"
                :style="{ backgroundColor: getBgColor('menuValue') }"
            >
                <div
                    :class="[
                        'font-normal text-slate-900 break-words line-clamp-3 w-full text-left my-auto',
                        menuFontSizeClass
                    ]"
                    :title="menuItemsFormatted"
                >
                    {{ menuItemsFormatted }}
                </div>
            </div>
        </div>

        <!-- 4. Row 5: TANGGAL PRODUKSI & BATAS AMAN KONSUMSI (Excel Row 5, Height ~18pt / ~10.5%) -->
        <div
            class="h-[11%] border-b border-slate-900 flex shrink-0"
        >
            <!-- Col A: TANGGAL / WAKTU PRODUKSI (~20%) -->
            <div
                class="w-[20%] border-r border-slate-900 px-1 py-0.5 flex flex-col justify-center leading-none text-[7.5px] font-black text-slate-900 shrink-0 transition-colors"
                :style="{ backgroundColor: getBgColor('tglLabel') }"
            >
                <template v-if="isShowingJamProduksi">
                    <span>WAKTU</span>
                    <span>PRODUKSI</span>
                </template>
                <template v-else>
                    <span>TANGGAL</span>
                    <span>PRODUKSI</span>
                </template>
            </div>
            <!-- Col B-C: Tanggal DD-MM-YYYY (atau DD-MM-YYYY HH:mm WITA) (~40%) -->
            <div
                class="w-[40%] border-r border-slate-900 px-1 py-0.5 flex items-center justify-center font-black text-slate-900 shrink-0 leading-tight text-center transition-colors"
                :class="isShowingJamProduksi ? 'text-[9.5px]' : 'text-[11px]'"
                :style="{ backgroundColor: getBgColor('tglValue') }"
            >
                {{ formattedTanggalProduksi }}
            </div>

            <!-- Khusus Porsi Tambahan: Di-merge & Kosong tanpa teks apapun -->
            <div
                v-if="props.tipeLabel === 'tambahan'"
                class="w-[40%] px-1 py-0.5 flex flex-col justify-center items-center shrink-0 transition-colors"
                :style="{ backgroundColor: getBgColor('batasKonsumsi') }"
            ></div>

            <!-- Jika di-merge (Teks batas aman berupa teks narasi / panjang) -->
            <div
                v-else-if="isMergedWaktuBatas"
                class="w-[40%] px-1 py-0.5 flex flex-col justify-center items-center leading-tight text-center shrink-0 overflow-hidden transition-colors"
                :style="{ backgroundColor: getBgColor('batasKonsumsi') }"
            >
                <span class="text-[6.5px] font-black uppercase text-slate-900 leading-none tracking-tight">
                    WAKTU BATAS AMAN KONSUMSI
                </span>
                <span class="text-[9px] font-black text-slate-950 uppercase leading-tight mt-0.5 truncate max-w-full">
                    {{ formattedWaktuBatas }}
                </span>
            </div>

            <!-- Jika TIDAK di-merge (Format jam singkat, misal 10:00 WITA) -->
            <template v-else>
                <!-- Col D: WAKTU BATAS AMAN KONSUMSI (~20%) -->
                <div
                    class="w-[20%] border-r border-slate-900 px-1 py-0.5 flex flex-col justify-center leading-none text-[6.5px] font-black text-slate-900 shrink-0 transition-colors"
                    :style="{ backgroundColor: getBgColor('batasKonsumsi') }"
                >
                    <span>WAKTU BATAS</span>
                    <span>AMAN KONSUMSI</span>
                </div>
                <!-- Col E: Jam Batas Aman (~20%) -->
                <div
                    class="w-[20%] px-0.5 py-0.5 flex items-center justify-center font-black text-slate-900 shrink-0 text-center tracking-tight overflow-hidden whitespace-nowrap transition-colors"
                    :class="formattedWaktuBatas && formattedWaktuBatas.length > 10 ? 'text-[9.5px]' : 'text-[10.5px]'"
                    :style="{ backgroundColor: getBgColor('batasKonsumsiVal') }"
                >
                    {{ formattedWaktuBatas }}
                </div>
            </template>
        </div>

        <!-- 5. Row 6-11: KONTAK PENGADUAN & KANDUNGAN GIZI (Height ~17.75pt / ~29%) -->
        <div
            class="h-[29%] border-b border-slate-900 flex shrink-0"
        >
            <!-- Col A-B: KONTAK PENGADUAN (Width ~40%) -->
            <div
                class="w-[40%] border-r border-slate-900 p-1.5 flex flex-col justify-between leading-tight shrink-0 transition-colors"
                :style="{ backgroundColor: getBgColor('kontak') }"
            >
                <div class="text-[8.5px] font-black text-slate-900">
                    KONTAK PENGADUAN
                </div>
                <div class="text-[7.5px] font-medium text-slate-800 space-y-0.5 leading-tight">
                    <div class="truncate">{{ contactDisplayLines[0] }}</div>
                    <div class="truncate">{{ contactDisplayLines[1] }}</div>
                    <div class="truncate">{{ contactDisplayLines[2] }}</div>
                    <div class="truncate">{{ contactDisplayLines[3] }}</div>
                </div>
            </div>

            <!-- Col C-E: TABEL GIZI (Width ~60%) -->
            <div class="w-[60%] flex flex-col shrink-0">
                <!-- Header Gizi (Row 6) -->
                <div
                    class="h-[20%] border-b border-slate-900 flex text-[7.5px] font-black text-slate-900 items-center shrink-0 transition-colors"
                    :style="{ backgroundColor: getBgColor('giziHeader') }"
                >
                    <div class="w-1/3 border-r border-slate-900 text-center leading-none py-0.5">
                        KANDUNGAN GIZI
                    </div>
                    <div class="w-1/3 border-r border-slate-900 text-center leading-none py-0.5">
                        PORSI KECIL
                    </div>
                    <div class="w-1/3 text-center leading-none py-0.5">
                        PORSI BESAR
                    </div>
                </div>

                <!-- 5 Rows Gizi (Rows 7-11) -->
                <div
                    class="h-[80%] flex flex-col justify-between text-[7.2px] text-slate-900 transition-colors"
                    :style="{ backgroundColor: getBgColor('giziBody') }"
                >
                    <!-- Row 7: Energi -->
                    <div class="flex items-center flex-1 border-b border-slate-900/40">
                        <div class="w-1/3 border-r border-slate-900/40 text-right pr-1 font-medium leading-none">
                            Energi <span class="text-[5px]">(Kkal)</span>
                        </div>
                        <div class="w-1/3 border-r border-slate-900/40 text-center font-normal leading-none">
                            {{ giziDisplay.energi_pk }}
                        </div>
                        <div class="w-1/3 text-center font-normal leading-none">
                            {{ giziDisplay.energi_pb }}
                        </div>
                    </div>
                    <!-- Row 8: Karbohidrat -->
                    <div class="flex items-center flex-1 border-b border-slate-900/40">
                        <div class="w-1/3 border-r border-slate-900/40 text-right pr-1 font-medium leading-none">
                            Karbohidrat <span class="text-[5px]">(g)</span>
                        </div>
                        <div class="w-1/3 border-r border-slate-900/40 text-center font-normal leading-none">
                            {{ giziDisplay.karbo_pk }}
                        </div>
                        <div class="w-1/3 text-center font-normal leading-none">
                            {{ giziDisplay.karbo_pb }}
                        </div>
                    </div>
                    <!-- Row 9: Protein -->
                    <div class="flex items-center flex-1 border-b border-slate-900/40">
                        <div class="w-1/3 border-r border-slate-900/40 text-right pr-1 font-medium leading-none">
                            Protein <span class="text-[5px]">(g)</span>
                        </div>
                        <div class="w-1/3 border-r border-slate-900/40 text-center font-normal leading-none">
                            {{ giziDisplay.prot_pk }}
                        </div>
                        <div class="w-1/3 text-center font-normal leading-none">
                            {{ giziDisplay.prot_pb }}
                        </div>
                    </div>
                    <!-- Row 10: Lemak -->
                    <div class="flex items-center flex-1 border-b border-slate-900/40">
                        <div class="w-1/3 border-r border-slate-900/40 text-right pr-1 font-medium leading-none">
                            Lemak <span class="text-[5px]">(g)</span>
                        </div>
                        <div class="w-1/3 border-r border-slate-900/40 text-center font-normal leading-none">
                            {{ giziDisplay.lmk_pk }}
                        </div>
                        <div class="w-1/3 text-center font-normal leading-none">
                            {{ giziDisplay.lmk_pb }}
                        </div>
                    </div>
                    <!-- Row 11: Serat -->
                    <div class="flex items-center flex-1">
                        <div class="w-1/3 border-r border-slate-900/40 text-right pr-1 font-medium leading-none">
                            Serat <span class="text-[5px]">(g)</span>
                        </div>
                        <div class="w-1/3 border-r border-slate-900/40 text-center font-normal leading-none">
                            {{ giziDisplay.serat_pk }}
                        </div>
                        <div class="w-1/3 text-center font-normal leading-none">
                            {{ giziDisplay.serat_pb }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Row 12: FOOTER PERINGATAN (Excel Row 12, Height ~18pt / ~10.5%) -->
        <div
            class="h-[11%] border-t border-slate-900 px-2 flex flex-col items-center justify-center text-center shrink-0 transition-colors"
            :style="{ backgroundColor: getBgColor('footer') }"
        >
            <span class="text-[8.5px] font-black text-slate-950 uppercase leading-none">
                {{ teksLaranganHeader || 'TIDAK BOLEH DIBAWA PULANG.' }}
            </span>
            <span class="text-[8.5px] font-black text-slate-950 uppercase leading-none mt-0.5">
                {{ teksLaranganSub || 'SEGERA KONSUMSI SETELAH DITERIMA!' }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.bgn-label-card-template02 {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
    box-sizing: border-box !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

.bgn-label-card-template02 * {
    box-sizing: border-box !important;
    font-family: inherit;
}
</style>
