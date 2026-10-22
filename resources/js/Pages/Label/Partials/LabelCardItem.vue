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
        default: "12:14",
    },
    menuItems: {
        type: Array,
        default: () => [
            "Nasi Putih",
            "Ayam Geprek & Sambel",
            "Tempe Kecap",
            "Sayur Kol & Wortel",
            "Semangka",
        ],
    },
    giziData: {
        type: Object,
        default: () => ({
            energi_pk: "363,5",
            energi_pb: "534",
            karbo_pk: "41,2",
            karbo_pb: "68",
            prot_pk: "22,1",
            prot_pb: "30,1",
            lmk_pk: "12",
            lmk_pb: "16,2",
            serat_pk: "2,6",
            serat_pb: "5,1",
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
        default: "2 JAM SETELAH DITERIMA!",
    },
    teksLaranganHeader: {
        type: String,
        default: "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.",
    },
    teksLaranganSub: {
        type: String,
        default: "DILARANG MEMBAWA PULANG!",
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
        default: "normal", // 'normal' | 'alergi'
    },
    jenisAlergi: {
        type: String,
        default: "",
    },
    tagAlergi: {
        type: String,
        default: "",
    },
    subTipeTambahan: {
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

const getColor = (key, defaultColor = "#ffffff") => {
    if (!props.customColors) return defaultColor;
    const val = props.customColors[key];
    if (val === undefined || val === null || val === "" || val === "transparent" || val === "none") {
        return defaultColor;
    }
    return val;
};

const isColored = computed(() => {
    if (!props.customColors) return false;
    return Object.values(props.customColors).some(
        (val) => val && val.toLowerCase() !== "#ffffff" && val !== "transparent" && val !== "none"
    );
});

const areaPerekatBg = computed(() => {
    const c = getColor("areaPerekat", "#ffffff");
    return c;
});

const garisEmasBg = computed(() => {
    const c = getColor("garisEmas", "#ffffff");
    return c === "#ffffff" ? "#000000" : c;
});

const menuBadgeBg = computed(() => {
    return getColor("menuBadge", "#ffffff");
});

const menuBodyBg = computed(() => {
    return getColor("menuBody", "#ffffff");
});

const tglBoxBg = computed(() => {
    return getColor("tglBox", "#ffffff");
});

const waktuBoxBg = computed(() => {
    return getColor("waktuBox", "#ffffff");
});

const laranganBoxBg = computed(() => {
    return getColor("laranganBox", "#ffffff");
});

const kontakBoxBg = computed(() => {
    return getColor("kontakBox", "#ffffff");
});

const giziBoxBg = computed(() => {
    return getColor("giziBox", "#ffffff");
});

const giziCellBg = computed(() => giziBoxBg.value);
const giziCellBorder = computed(() => giziBoxBg.value === '#ffffff' ? '1px solid #000000' : '1px solid #c7dcf8');
const giziCellColor = computed(() => giziBoxBg.value === '#ffffff' ? '#000000' : '#164282');

const footerPorsiBg = computed(() => {
    return getColor("footerPorsi", "#ffffff");
});

const footerPorsiCellBg = computed(() => footerPorsiBg.value !== '#ffffff' ? footerPorsiBg.value : '#ffffff');
const footerPorsiCellColor = computed(() => footerPorsiBg.value !== '#ffffff' ? '#ffffff' : '#000000');
const footerPorsiCellBorder = computed(() => footerPorsiBg.value !== '#ffffff' ? 'none' : '1px solid #000000');

const topIsolasiHeightPx = computed(() => {
    const cm = parseFloat(props.tinggiIsolasiCm);
    const validCm = isNaN(cm) || cm < 0 ? 1.5 : cm;
    // 370px = 6cm, so 1cm = 61.667px
    return Math.round(validCm * 61.667);
});

// Scale factor based on available main label card height (Baseline 1.5cm = 277.5px)
const scaleRatio = computed(() => {
    const availableHeight = 370 - topIsolasiHeightPx.value;
    return Math.max(0.85, Math.min(1.45, availableHeight / 277.5));
});

const dyn = computed(() => {
    const s = scaleRatio.value;
    const isShowingTime =
        props.tampilkanJamProduksi &&
        props.jamProduksi &&
        props.jamProduksi.trim();
    const isShowingExpiredTime =
        props.tampilkanJamExpired &&
        props.jamExpired &&
        props.jamExpired.trim();

    return {
        scale: s,
        cardPadding: `${Math.round(5 * s)}px 12px ${Math.round(5 * s)}px 12px`,
        logoHeight: Math.round(42 * Math.min(1.25, s)) + "px",
        headerMarginBottom: Math.round(3 * s) + "px",
        instansiSubFontSize: (7.8 * Math.min(1.2, s)).toFixed(1) + "px",
        sppgTitleFontSize: (12 * Math.min(1.25, s)).toFixed(1) + "px",
        goldLineHeight: Math.round(2 * Math.min(1.4, s)) + "px",
        goldLineMarginBottom: Math.round(4 * s) + "px",
        bannerMinHeight: Math.round(30 * s) + "px",
        bannerMarginBottom: Math.round(4 * s) + "px",
        menuBadgeFontSize: (10 * Math.min(1.25, s)).toFixed(1) + "px",
        menuBadgePadding: `0 ${Math.round(10 * Math.min(1.25, s))}px`,
        bodyGap: Math.round(8 * s) + "px",
        leftColGap: Math.round(4 * s) + "px",
        tglLabelFontSize: (8.8 * s).toFixed(1) + "px",
        tglLabelMarginBottom: Math.round(3 * s) + "px",
        tglBoxHeight: Math.round(25 * s) + "px",
        tglBoxFontSize: (isShowingTime ? 10.5 * s : 13.5 * s).toFixed(1) + "px",
        waktuBoxHeight: Math.round(44 * s) + "px",
        waktuHeaderFontSize: (8.8 * s).toFixed(1) + "px",
        waktuSubFontSize: (isShowingExpiredTime ? 13.5 * s : 14.5 * s).toFixed(1) + "px",
        laranganBoxHeight: Math.round(40 * s) + "px",
        laranganHeaderFontSize: (9 * s).toFixed(1) + "px",
        laranganSubFontSize: (11.8 * s).toFixed(1) + "px",
        rightColWidth: Math.round(176 * Math.min(1.2, s)) + "px",
        rightColPaddingLeft: Math.round(8 * s) + "px",
        giziTitleFontSize: (9.5 * s).toFixed(1) + "px",
        giziTitleMarginBottom: Math.round(3 * s) + "px",
        tableSpacingY: Math.round(2 * s) + "px",
        tablePorsiColWidth: Math.round(36 * Math.min(1.2, s)) + "px",
        tableRowHeight: Math.round(17.5 * s) + "px",
        tableHeaderRowHeight: Math.round(19 * s) + "px",
        tableLabelFontSize: (8.5 * s).toFixed(1) + "px",
        tableUnitFontSize: (6.5 * s).toFixed(1) + "px",
        tableValueFontSize: (9 * s).toFixed(1) + "px",
        tableHeaderFontSize: (7 * s).toFixed(1) + "px",
        kontakBoxHeight: Math.round(27 * s) + "px",
        kontakMarginTop: Math.round(4 * s) + "px",
        kontakBadgeFontSize: (7.6 * s).toFixed(1) + "px",
        kontakBadgePadding: `0 ${Math.round(6 * s)}px`,
        kontakTextFontSize: (8.2 * s).toFixed(1) + "px",
    };
});

const displaySppg = computed(() => {
    if (props.namaSppg && props.namaSppg.trim()) {
        const val = props.namaSppg.trim().toUpperCase();
        return val.startsWith("SPPG") ? val : `SPPG ${val}`;
    }
    if (props.unitSppg?.nama) {
        const val = props.unitSppg.nama.trim().toUpperCase();
        return val.startsWith("SPPG") ? val : `SPPG ${val}`;
    }
    return "SPPG BULELENG SUKASADA TEGALLINGGAH";
});

const socialMediaHandle = computed(() => {
    const raw = displaySppg.value.toLowerCase();
    if (raw.includes("tegallinggah")) {
        return {
            ig: "Instagram: @sppg.tegallinggah",
            tiktok: "Tiktok: @sppg.tegallinggah",
            fb: "Facebook: SPPG Tegallinggah",
        };
    }
    const words = raw.replace(/^sppg\s+/, "").trim().split(/\s+/);
    const suffix = words.length > 0 ? words[words.length - 1] : "bgn";
    const slug = suffix.replace(/[^a-z0-9]/g, "");
    return {
        ig: `Instagram: @sppg.${slug}`,
        tiktok: `Tiktok: @sppg.${slug}`,
        fb: `Facebook: ${displaySppg.value}`,
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

const formattedMenuText = computed(() => {
    if (Array.isArray(props.menuItems) && props.menuItems.length > 0) {
        return props.menuItems
            .map((it) => it.trim())
            .filter(Boolean)
            .join(" - ");
    }
    return "Nasi Putih - Ayam Geprek & Sambel - Tempe Kecap - Sayur Kol & Wortel - Semangka";
});

const menuFontSizeStyle = computed(() => {
    const s = scaleRatio.value;
    const len = formattedMenuText.value.length;
    let baseSize = 10.5;
    let baseLineHeight = "1.2";
    let basePadding = "0px 8px 0px 8px";

    if (len > 120) {
        baseSize = 8;
        baseLineHeight = "1.15";
    } else if (len > 80) {
        baseSize = 8.8;
    } else if (len > 50) {
        baseSize = 9.5;
    }

    const scaledSize = (baseSize * Math.min(1.25, s)).toFixed(1) + "px";
    return {
        fontSize: scaledSize,
        lineHeight: baseLineHeight,
        padding: basePadding,
    };
});

const formattedDateDisplay = computed(() => {
    let dateStr = "";
    if (!props.tanggalProduksi) {
        const now = new Date();
        const dd = String(now.getDate()).padStart(2, "0");
        const mm = String(now.getMonth() + 1).padStart(2, "0");
        const yyyy = now.getFullYear();
        dateStr = `${dd}/${mm}/${yyyy}`;
    } else {
        try {
            const parts = props.tanggalProduksi.split("-");
            if (parts.length === 3) {
                dateStr = `${parts[2]}/${parts[1]}/${parts[0]}`;
            } else {
                const d = new Date(props.tanggalProduksi);
                if (isNaN(d.getTime())) {
                    dateStr = props.tanggalProduksi;
                } else {
                    const dd = String(d.getDate()).padStart(2, "0");
                    const mm = String(d.getMonth() + 1).padStart(2, "0");
                    const yyyy = d.getFullYear();
                    dateStr = `${dd}/${mm}/${yyyy}`;
                }
            }
        } catch {
            dateStr = props.tanggalProduksi;
        }
    }

    if (
        props.tampilkanJamProduksi &&
        props.jamProduksi &&
        props.jamProduksi.trim()
    ) {
        const zona = props.zonaWaktu ? ` ${props.zonaWaktu}` : "";
        return `${dateStr} • ${props.jamProduksi.trim()}${zona}`;
    }
    return dateStr;
});

const formattedWaktuMaksimalDisplay = computed(() => {
    if (isTambahan.value) return "";
    if (
        props.tampilkanJamExpired &&
        props.jamExpired &&
        props.jamExpired.trim()
    ) {
        const zona = props.zonaWaktu ? ` ${props.zonaWaktu}` : "";
        return `SEBELUM JAM ${props.jamExpired.trim()}${zona}`;
    }
    return props.waktuMaksimal || "2 JAM SETELAH DITERIMA!";
});

const headerWaktuMaksimalDisplay = computed(() => {
    if (isTambahan.value) return "";
    if (
        props.tampilkanJamExpired &&
        props.jamExpired &&
        props.jamExpired.trim()
    ) {
        return "BATAS MAKSIMAL KONSUMSI";
    }
    return "WAKTU MAKSIMAL KONSUMSI";
});

const isAlergi = computed(() => props.tipeLabel === "alergi");
const isTambahan = computed(() => props.tipeLabel === "tambahan");

const bannerBadgeText = computed(() => {
    const stripEmojis = (str) => {
        if (!str) return "";
        return String(str)
            .replace(/[\u{1F000}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{FE00}-\u{FE0F}]/gu, "")
            .replace(/[^\x20-\x7E\u00A0-\u00FF]/g, "")
            .replace(/\s+/g, " ")
            .trim();
    };

    if (props.tagAlergi && props.tagAlergi.trim()) {
        return stripEmojis(props.tagAlergi)
            .toUpperCase()
            .replace(/KHUSUS\s+ALERGI/g, "ALERGI");
    }
    if (isAlergi.value) {
        if (props.jenisAlergi && props.jenisAlergi.trim()) {
            return `ALERGI: ${props.jenisAlergi.trim().toUpperCase()}`;
        }
        return "MENU ALERGI";
    }
    if (isTambahan.value) {
        if (props.subTipeTambahan === "organoleptik") return "UJI ORGANOLEPTIK";
        if (props.subTipeTambahan === "sampel") return "SAMPEL MAKANAN";
        if (props.subTipeTambahan === "buffer") return "BUFFER PRODUKSI";
        return "PORSI TAMBAHAN";
    }
    return "MENU";
});

const resolvedGizi = computed(() => {
    const g = props.giziData || {};
    const getVal = (...keys) => {
        for (const k of keys) {
            const v = g[k];
            if (v !== undefined && v !== null && String(v).trim() !== "") {
                return String(v).trim().replace(".", ",");
            }
        }
        return null;
    };
    return {
        energi_pk: getVal("energi_pk", "energy_pk") ?? "363,5",
        energi_pb: getVal("energi_pb", "energy_pb") ?? "534",
        karbo_pk: getVal("karbo_pk", "karbohidrat_pk") ?? "41,2",
        karbo_pb: getVal("karbo_pb", "karbohidrat_pb") ?? "68",
        prot_pk: getVal("prot_pk", "protein_pk") ?? "22,1",
        prot_pb: getVal("prot_pb", "protein_pb") ?? "30,1",
        lmk_pk: getVal("lmk_pk", "lemak_pk") ?? "12",
        lmk_pb: getVal("lmk_pb", "lemak_pb") ?? "16,2",
        serat_pk: getVal("serat_pk") ?? "2,6",
        serat_pb: getVal("serat_pb") ?? "5,1",
    };
});
</script>

<template>
    <!-- Label Kemasan Standar 9cm x 6cm (Lebar 555px, Tinggi 370px -> Rasio 3:2 Exact) -->
    <div
        class="bgn-label-card"
        style="
            width: 555px;
            min-width: 555px;
            max-width: 555px;
            height: 370px;
            min-height: 370px;
            max-height: 370px;
            background-color: #ffffff;
            color: #1e3a8a;
            font-family:
                'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
            box-sizing: border-box;
            user-select: none;
            overflow: hidden;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        "
    >
        <!-- ================= 0. AREA TEMPEL ISOLASI / PEREKAT (TINGGI DINAMIS SESUAI CM) ================= -->
        <div
            :style="{
                height: topIsolasiHeightPx + 'px',
                minHeight: '10px',
                maxHeight: '190px',
                border: areaPerekatBg === '#ffffff' ? '1px dashed #000000' : (isAlergi ? '1px dashed #f97316' : '1px dashed #94a3b8'),
                borderBottom: 'none',
                borderRadius: '8px 8px 0 0',
                backgroundColor: areaPerekatBg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '4px 10px',
                boxSizing: 'border-box',
                flexShrink: 0,
            }"
        >
            <div
                :style="{
                    fontSize: '8.5px',
                    fontWeight: '800',
                    color: areaPerekatBg === '#ffffff' ? '#000000' : (isAlergi ? '#c2410c' : '#64748b'),
                    letterSpacing: '1px',
                    textTransform: 'uppercase',
                    lineHeight: '1',
                    whiteSpace: 'nowrap',
                    textAlign: 'center',
                }"
            >
                {{ isAlergi ? '- - - - - - - - - - AREA PEREKAT LABEL • KEMASAN PORSI ALERGI - - - - - - - - - -' : '- - - - - - - - - - AREA PEREKAT LABEL - - - - - - - - - -' }}
            </div>
        </div>

        <!-- ================= KARTU UTAMA RATA BAWAH DENGAN BORDER BIRU RESMI BGN / ORANYE ALERGI ================= -->
        <div
            :style="{
                border: isColored ? (isAlergi ? '1.6px solid #ea580c' : '1.6px solid #164282') : '1.6px solid #000000',
                borderRadius: '0 0 12px 12px',
                padding: dyn.cardPadding,
                backgroundColor: '#ffffff',
                boxSizing: 'border-box',
                display: 'flex',
                flexDirection: 'column',
                flex: '1',
                justifyContent: 'space-between',
            }"
        >
            <!-- ================= 1. HEADER LOGO & INSTANSI ================= -->
            <div
                :style="{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: '8px',
                    marginBottom: dyn.headerMarginBottom,
                }"
            >
                <!-- Kiri: Logo Resmi BGN Bergambar + Teks (BGN_LOGOTYPE_MAIN.png) -->
                <div style="display: flex; align-items: center">
                    <img
                        src="/images/logo/BGN_LOGOTYPE_MAIN.png"
                        alt="Badan Gizi Nasional"
                        :style="{
                            height: dyn.logoHeight,
                            maxWidth: '220px',
                            objectFit: 'contain',
                            objectPosition: 'left center',
                        }"
                        onerror="this.src = '/images/logo/BGN_LOGO_MAIN.png'"
                    />
                </div>

                <!-- Kanan: Satuan Pelayanan & Nama SPPG -->
                <div
                    style="
                        text-align: right;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                    "
                >
                    <div
                        :style="{
                            fontSize: dyn.instansiSubFontSize,
                            fontWeight: '800',
                            color: isColored ? '#64748b' : '#000000',
                            letterSpacing: '0.5px',
                            textTransform: 'uppercase',
                            lineHeight: '1.1',
                            marginBottom: '2px',
                        }"
                    >
                        SATUAN PELAYANAN PEMENUHAN GIZI
                    </div>
                    <div
                        :style="{
                            fontSize: dyn.sppgTitleFontSize,
                            fontWeight: '900',
                            color: isColored ? (isAlergi ? '#9a3412' : '#164282') : '#000000',
                            letterSpacing: '-0.2px',
                            textTransform: 'uppercase',
                            lineHeight: '1.1',
                        }"
                    >
                        {{ displaySppg }}
                    </div>
                </div>
            </div>

            <!-- Garis Pemisah Emas / Kuning Kecoklatan -->
            <div
                :style="{
                    height: dyn.goldLineHeight,
                    backgroundColor: garisEmasBg,
                    borderRadius: '2px',
                    marginBottom: dyn.goldLineMarginBottom,
                }"
            ></div>

            <!-- ================= 2. BANNER MENU MEMANJANG ================= -->
            <div
                :style="{
                    display: 'flex',
                    alignItems: 'stretch',
                    backgroundColor: menuBodyBg !== '#ffffff' ? menuBodyBg : '#ffffff',
                    border: menuBodyBg === '#ffffff' ? '1.2px solid #000000' : '1px solid #bfdbfe',
                    borderRadius: '5px',
                    color: menuBodyBg !== '#ffffff' ? (isColored ? '#1e3a8a' : '#000000') : '#000000',
                    marginBottom: dyn.bannerMarginBottom,
                    minHeight: dyn.bannerMinHeight,
                    overflow: 'hidden',
                    boxSizing: 'border-box',
                }"
            >
                <div
                    :style="{
                        backgroundColor: menuBadgeBg !== '#ffffff' ? menuBadgeBg : '#ffffff',
                        color: menuBadgeBg !== '#ffffff' ? '#ffffff' : '#000000',
                        borderRight: menuBadgeBg === '#ffffff' ? '1.2px solid #000000' : 'none',
                        fontSize: dyn.menuBadgeFontSize,
                        fontWeight: '900',
                        padding: dyn.menuBadgePadding,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        letterSpacing: '0.5px',
                        lineHeight: '1',
                        whiteSpace: 'nowrap',
                        borderRadius: '5px 0 0 5px',
                    }"
                >
                    <span
                        style="
                            display: block;
                            line-height: 1;
                            transform: translateY(-6px);
                        "
                        >{{ bannerBadgeText }}</span
                    >
                </div>
                <div
                    :style="{
                        padding: menuFontSizeStyle.padding,
                        fontSize: menuFontSizeStyle.fontSize,
                        lineHeight: menuFontSizeStyle.lineHeight,
                        fontWeight: '800',
                        color: menuBodyBg !== '#ffffff' ? (isColored ? '#1e3a8a' : '#000000') : '#000000',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'flex-start',
                        flex: 1,
                        whiteSpace: 'normal',
                        wordBreak: 'break-word',
                        overflowWrap: 'break-word',
                    }"
                >
                    <span
                        style="
                            display: block;
                            width: 100%;
                            line-height: inherit;
                            vertical-align: middle;
                            transform: translateY(-6px);
                        "
                    >
                        {{ formattedMenuText }}
                    </span>
                </div>
            </div>

            <!-- ================= 3. BODY CONTENT: 2 KOLOM ================= -->
            <div
                :style="{
                    display: 'flex',
                    gap: dyn.bodyGap,
                    alignItems: 'flex-start',
                    flex: '1',
                }"
            >
                <!-- KOLOM KIRI: 3 KOTAK TINGGI PRESISI SEIMBANG DENGAN KOLOM KANAN -->
                <div
                    :style="{
                        flex: '1',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: dyn.leftColGap,
                    }"
                >
                    <!-- 1. Bagian Tanggal Produksi -->
                    <div style="display: flex; flex-direction: column">
                        <div
                            :style="{
                                fontSize: dyn.tglLabelFontSize,
                                fontWeight: '800',
                                color: '#000000',
                                lineHeight: '1',
                                marginBottom: dyn.tglLabelMarginBottom,
                                paddingLeft: '1px',
                                display: 'block',
                                transform: 'translateY(-3.5px)',
                            }"
                        >
                            {{
                                tampilkanJamProduksi &&
                                jamProduksi &&
                                jamProduksi.trim()
                                    ? "Tanggal & Jam Produksi"
                                    : "Tanggal Produksi"
                            }}
                        </div>
                        <div
                            :style="{
                                backgroundColor: tglBoxBg,
                                border: tglBoxBg === '#ffffff' ? '1.2px solid #000000' : '1.2px solid #c7dcf8',
                                borderRadius: '5px',
                                fontSize: dyn.tglBoxFontSize,
                                fontWeight: '900',
                                color: tglBoxBg === '#ffffff' ? '#000000' : '#164282',
                                textAlign: 'center',
                                letterSpacing: '0.5px',
                                height: dyn.tglBoxHeight,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                boxSizing: 'border-box',
                            }"
                        >
                            <span
                                style="
                                    display: block;
                                    line-height: 1;
                                    transform: translateY(-7.5px);
                                "
                                >{{ formattedDateDisplay }}</span
                            >
                        </div>
                    </div>

                    <!-- 2. Box Oranye WAKTU MAKSIMAL KONSUMSI -->
                    <div
                        :style="{
                            backgroundColor: waktuBoxBg,
                            border: waktuBoxBg === '#ffffff' ? '1.2px solid #000000' : '1.2px solid #ffd9c0',
                            borderRadius: '5px',
                            padding: '0 6px',
                            textAlign: 'center',
                            boxSizing: 'border-box',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'center',
                            alignItems: 'center',
                            height: dyn.waktuBoxHeight,
                        }"
                    >
                        <template v-if="!isTambahan">
                            <div
                                :style="{
                                    fontSize: dyn.waktuHeaderFontSize,
                                    fontWeight: '900',
                                    color: waktuBoxBg === '#ffffff' ? '#000000' : '#c2410c',
                                    letterSpacing: '0.2px',
                                    textTransform: 'uppercase',
                                    lineHeight: '1',
                                    margin: '0',
                                    marginBottom: '3px',
                                    padding: '0',
                                    whiteSpace: 'nowrap',
                                    transform: 'translateY(-6.5px)',
                                }"
                            >
                                {{ headerWaktuMaksimalDisplay }}
                            </div>
                            <div
                                :style="{
                                    fontSize: dyn.waktuSubFontSize,
                                    fontWeight: '900',
                                    color: waktuBoxBg === '#ffffff' ? '#000000' : '#9a2c0c',
                                    lineHeight: '1',
                                    margin: '0',
                                    padding: '0',
                                    whiteSpace: 'nowrap',
                                    transform: 'translateY(-6.5px)',
                                }"
                            >
                                {{ formattedWaktuMaksimalDisplay }}
                            </div>
                        </template>
                    </div>

                    <!-- 3. Box Merah LARANGAN KONSUMSI DI TEMPAT -->
                    <div
                        :style="{
                            backgroundColor: laranganBoxBg,
                            border: laranganBoxBg === '#ffffff' ? '1.2px solid #000000' : '1.2px solid #fca5a5',
                            borderLeft: laranganBoxBg === '#ffffff' ? '4.5px solid #000000' : '4.5px solid #dc2626',
                            borderRadius: '5px',
                            padding: '0 8px',
                            textAlign: 'left',
                            boxSizing: 'border-box',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'center',
                            alignItems: 'flex-start',
                            height: dyn.laranganBoxHeight,
                        }"
                    >
                        <div
                            :style="{
                                fontSize: dyn.laranganHeaderFontSize,
                                fontWeight: '900',
                                color: laranganBoxBg === '#ffffff' ? '#000000' : '#991b1b',
                                letterSpacing: '-0.1px',
                                textTransform: 'uppercase',
                                lineHeight: '1',
                                margin: '0',
                                marginBottom: '3px',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                width: '100%',
                                transform: 'translateY(-6px)',
                            }"
                        >
                            {{
                                teksLaranganHeader ||
                                "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT."
                            }}
                        </div>
                        <div
                            :style="{
                                fontSize: dyn.laranganSubFontSize,
                                fontWeight: '900',
                                color: laranganBoxBg === '#ffffff' ? '#000000' : '#991b1b',
                                letterSpacing: '0.1px',
                                textTransform: 'uppercase',
                                lineHeight: '1',
                                margin: '0',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                width: '100%',
                                transform: 'translateY(-6px)',
                            }"
                        >
                            {{ teksLaranganSub || "DILARANG MEMBAWA PULANG!" }}
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: TABEL KANDUNGAN GIZI (BATAS PINGGIR KANAN SEJAJAR UJUNG HEADER) -->
                <div
                    :style="{
                        width: dyn.rightColWidth,
                        flex: `0 0 ${dyn.rightColWidth}`,
                        display: 'flex',
                        flexDirection: 'column',
                        borderLeft: isColored ? '1px dashed #cbd5e1' : '1px dashed #000000',
                        paddingLeft: dyn.rightColPaddingLeft,
                        boxSizing: 'border-box',
                    }"
                >
                    <!-- Header Kandungan Gizi -->
                    <div
                        :style="{
                            display: 'flex',
                            alignItems: 'center',
                            marginBottom: dyn.giziTitleMarginBottom,
                        }"
                    >
                        <span
                            :style="{
                                fontSize: dyn.giziTitleFontSize,
                                fontWeight: '900',
                                color: '#000000',
                                lineHeight: '1',
                                whiteSpace: 'nowrap',
                                transform: 'translateY(-3.5px)',
                                display: 'block',
                            }"
                        >
                            Kandungan Gizi
                        </span>
                    </div>

                    <!-- Tabel Kandungan Gizi (Pasti Sejajar & Pas di Tengah) -->
                    <table
                        :style="{
                            width: '100%',
                            borderCollapse: 'separate',
                            borderSpacing: `0 ${dyn.tableSpacingY}`,
                            tableLayout: 'fixed',
                            margin: '0',
                            padding: '0',
                        }"
                    >
                        <colgroup>
                            <col style="width: auto" />
                            <col :style="{ width: dyn.tablePorsiColWidth }" />
                            <col style="width: 3px" />
                            <col :style="{ width: dyn.tablePorsiColWidth }" />
                        </colgroup>
                        <tbody>
                            <!-- 1. Energi -->
                            <tr>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'left',
                                        padding: '0',
                                        fontSize: dyn.tableLabelFontSize,
                                        fontWeight: '700',
                                        color: '#000000',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-3.5px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Energi</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: isColored ? '#64748b' : '#000000',
                                                fontWeight: 'normal',
                                                marginLeft: '2px',
                                            }"
                                            >(Kkal)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.energi_pk
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.energi_pb
                                            }}</span
                                        >
                                    </div>
                                </td>
                            </tr>

                            <!-- 2. Karbohidrat -->
                            <tr>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'left',
                                        padding: '0',
                                        fontSize: dyn.tableLabelFontSize,
                                        fontWeight: '700',
                                        color: '#000000',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-3.5px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: isColored ? '#64748b' : '#000000',
                                                fontWeight: 'normal',
                                                marginLeft: '2px',
                                            }"
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.karbo_pk
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.karbo_pb
                                            }}</span
                                        >
                                    </div>
                                </td>
                            </tr>

                            <!-- 3. Protein -->
                            <tr>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'left',
                                        padding: '0',
                                        fontSize: dyn.tableLabelFontSize,
                                        fontWeight: '700',
                                        color: '#000000',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-3.5px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Protein</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: isColored ? '#64748b' : '#000000',
                                                fontWeight: 'normal',
                                                marginLeft: '2px',
                                            }"
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.prot_pk
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.prot_pb
                                            }}</span
                                        >
                                    </div>
                                </td>
                            </tr>

                            <!-- 4. Lemak -->
                            <tr>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'left',
                                        padding: '0',
                                        fontSize: dyn.tableLabelFontSize,
                                        fontWeight: '700',
                                        color: '#000000',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-3.5px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Lemak</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: isColored ? '#64748b' : '#000000',
                                                fontWeight: 'normal',
                                                marginLeft: '2px',
                                            }"
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.lmk_pk
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.lmk_pb
                                            }}</span
                                        >
                                    </div>
                                </td>
                            </tr>

                            <!-- 5. Serat -->
                            <tr>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'left',
                                        padding: '0',
                                        fontSize: dyn.tableLabelFontSize,
                                        fontWeight: '700',
                                        color: '#000000',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-3.5px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Serat</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: isColored ? '#64748b' : '#000000',
                                                fontWeight: 'normal',
                                                marginLeft: '2px',
                                            }"
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.serat_pk
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableRowHeight,
                                        backgroundColor: giziCellBg,
                                        border: giziCellBorder,
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: giziCellColor,
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1px);
                                            "
                                            >{{
                                                resolvedGizi.serat_pb
                                            }}</span
                                        >
                                    </div>
                                </td>
                            </tr>

                            <!-- 6. Footer: Porsi Kecil & Porsi Besar -->
                            <tr>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableHeaderRowHeight,
                                        backgroundColor: footerPorsiCellBg,
                                        borderRadius: '3.5px',
                                        color: footerPorsiCellColor,
                                        border: footerPorsiCellBorder,
                                        fontSize: dyn.tableHeaderFontSize,
                                        fontWeight: '900',
                                        lineHeight: '1',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4.5px);
                                        "
                                    >
                                        <span
                                            :style="{
                                                display: 'block',
                                                lineHeight: '1',
                                                fontSize: (6 * dyn.scale).toFixed(1) + 'px',
                                                fontWeight: '900',
                                            }"
                                            >Porsi</span
                                        >
                                        <span
                                            :style="{
                                                display: 'block',
                                                lineHeight: '1',
                                                fontSize: (6 * dyn.scale).toFixed(1) + 'px',
                                                fontWeight: '900',
                                                marginTop: '1px',
                                            }"
                                            >Kecil</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    :style="{
                                        verticalAlign: 'middle',
                                        textAlign: 'center',
                                        padding: '0',
                                        height: dyn.tableHeaderRowHeight,
                                        backgroundColor: footerPorsiCellBg,
                                        borderRadius: '3.5px',
                                        color: footerPorsiCellColor,
                                        border: footerPorsiCellBorder,
                                        fontSize: dyn.tableHeaderFontSize,
                                        fontWeight: '900',
                                        lineHeight: '1',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-4.5px);
                                        "
                                    >
                                        <span
                                            :style="{
                                                display: 'block',
                                                lineHeight: '1',
                                                fontSize: (6 * dyn.scale).toFixed(1) + 'px',
                                                fontWeight: '900',
                                            }"
                                            >Porsi</span
                                        >
                                        <span
                                            :style="{
                                                display: 'block',
                                                lineHeight: '1',
                                                fontSize: (6 * dyn.scale).toFixed(1) + 'px',
                                                fontWeight: '900',
                                                marginTop: '1px',
                                            }"
                                            >Besar</span
                                        >
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= 4. KONTAK PENGADUAN MEMANJANG (KIRI KANAN FULL) ================= -->
            <div
                :style="{
                    width: '100%',
                    backgroundColor: kontakBoxBg,
                    border: kontakBoxBg === '#ffffff' ? (isColored ? '1px solid #cbd5e1' : '1px solid #000000') : '1px solid #c7dcf8',
                    borderRadius: '4px',
                    display: 'flex',
                    alignItems: 'stretch',
                    height: dyn.kontakBoxHeight,
                    marginTop: dyn.kontakMarginTop,
                    overflow: 'hidden',
                    boxSizing: 'border-box',
                    flexShrink: 0,
                }"
            >
                <!-- Badge Kiri: KONTAK PENGADUAN -->
                <div
                    :style="{
                        backgroundColor: isColored ? (kontakBoxBg !== '#ffffff' ? '#164282' : '#3b72c2') : '#ffffff',
                        color: isColored ? '#ffffff' : '#000000',
                        borderRight: isColored && kontakBoxBg !== '#ffffff' ? 'none' : (isColored ? '1px solid #3b72c2' : '1px solid #000000'),
                        fontSize: dyn.kontakBadgeFontSize,
                        fontWeight: '900',
                        padding: dyn.kontakBadgePadding,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        letterSpacing: '0.4px',
                        whiteSpace: 'nowrap',
                        flexShrink: 0,
                        lineHeight: '1',
                    }"
                >
                    <span style="display: block; line-height: 1; transform: translateY(-0.5px);">
                        KONTAK PENGADUAN
                    </span>
                </div>

                <!-- Isi Kontak: 2 Kolom x 2 Baris (Email & TikTok di Kiri, Instagram & Facebook di Kanan) -->
                <div
                    :style="{
                        display: 'flex',
                        alignItems: 'stretch',
                        flex: '1',
                        overflow: 'hidden',
                        fontSize: dyn.kontakTextFontSize,
                        fontWeight: '700',
                        color: isColored ? '#164282' : '#000000',
                        lineHeight: '1.25',
                    }"
                >
                    <!-- Kolom 1 (Kiri): Email & TikTok -->
                    <div
                        style="
                            flex: 1;
                            min-width: 0;
                            display: flex;
                            flex-direction: column;
                            justify-content: space-around;
                            padding: 2px 6px;
                            overflow: hidden;
                        "
                    >
                        <div style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center;">
                            {{ contactDisplayLines[0] }}
                        </div>
                        <div style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center;">
                            {{ contactDisplayLines[2] }}
                        </div>
                    </div>

                    <!-- Divider Tengah Vertikal -->
                    <div
                        :style="{
                            width: '1px',
                            backgroundColor: isColored ? '#cbd5e1' : '#000000',
                            margin: '3px 0',
                            flexShrink: 0,
                        }"
                    ></div>

                    <!-- Kolom 2 (Kanan): Instagram & Facebook -->
                    <div
                        style="
                            flex: 1;
                            min-width: 0;
                            display: flex;
                            flex-direction: column;
                            justify-content: space-around;
                            padding: 2px 6px;
                            overflow: hidden;
                        "
                    >
                        <div style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center;">
                            {{ contactDisplayLines[1] }}
                        </div>
                        <div style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: center;">
                            {{ contactDisplayLines[3] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bgn-label-card {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
    box-sizing: border-box !important;
}

.bgn-label-card * {
    box-sizing: border-box !important;
    font-family: inherit;
}
</style>
