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
        default: 2,
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
});

const topIsolasiHeightPx = computed(() => {
    const cm = parseFloat(props.tinggiIsolasiCm);
    const validCm = isNaN(cm) || cm < 0 ? 2 : cm;
    // 370px = 6cm, so 1cm = 61.667px
    return Math.round(validCm * 61.667);
});

// Scale factor based on available main label card height (Baseline 2cm = 246.67px)
const scaleRatio = computed(() => {
    const availableHeight = 370 - topIsolasiHeightPx.value;
    return Math.max(0.85, Math.min(1.45, availableHeight / 246.67));
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
        cardPadding: `${Math.round(6 * s)}px 12px ${Math.round(7 * s)}px 12px`,
        logoHeight: Math.round(44 * Math.min(1.25, s)) + "px",
        headerMarginBottom: Math.round(4 * s) + "px",
        instansiSubFontSize: (8 * Math.min(1.2, s)).toFixed(1) + "px",
        sppgTitleFontSize: (12.5 * Math.min(1.25, s)).toFixed(1) + "px",
        goldLineHeight: Math.round(2 * Math.min(1.4, s)) + "px",
        goldLineMarginBottom: Math.round(5 * s) + "px",
        bannerMinHeight: Math.round(32 * s) + "px",
        bannerMarginBottom: Math.round(6 * s) + "px",
        menuBadgeFontSize: (10.5 * Math.min(1.25, s)).toFixed(1) + "px",
        menuBadgePadding: `0 ${Math.round(12 * Math.min(1.25, s))}px`,
        bodyGap: Math.round(8 * s) + "px",
        leftColGap: Math.round(5 * s) + "px",
        tglLabelFontSize: (9 * s).toFixed(1) + "px",
        tglLabelMarginBottom: Math.round(5 * s) + "px",
        tglBoxHeight: Math.round(26 * s) + "px",
        tglBoxFontSize: (isShowingTime ? 10.5 * s : 13.5 * s).toFixed(1) + "px",
        waktuBoxHeight: Math.round(36 * s) + "px",
        waktuHeaderFontSize: (7.5 * s).toFixed(1) + "px",
        waktuSubFontSize: (isShowingExpiredTime ? 10 * s : 11 * s).toFixed(1) + "px",
        laranganBoxHeight: Math.round(38 * s) + "px",
        laranganHeaderFontSize: (7.5 * s).toFixed(1) + "px",
        laranganSubFontSize: (9.5 * s).toFixed(1) + "px",
        rightColWidth: Math.round(176 * Math.min(1.2, s)) + "px",
        rightColPaddingLeft: Math.round(8 * s) + "px",
        giziTitleFontSize: (9.5 * s).toFixed(1) + "px",
        giziTitleMarginBottom: Math.round(4.5 * s) + "px",
        tableSpacingY: Math.round(2.5 * s) + "px",
        tablePorsiColWidth: Math.round(36 * Math.min(1.2, s)) + "px",
        tableRowHeight: Math.round(15 * s) + "px",
        tableHeaderRowHeight: Math.round(19 * s) + "px",
        tableLabelFontSize: (7.5 * s).toFixed(1) + "px",
        tableUnitFontSize: (6 * s).toFixed(1) + "px",
        tableValueFontSize: (7.5 * s).toFixed(1) + "px",
        tableHeaderFontSize: (6.5 * s).toFixed(1) + "px",
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
    let basePadding = "2px 8px 5px 8px";

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
    if (
        props.tampilkanJamExpired &&
        props.jamExpired &&
        props.jamExpired.trim()
    ) {
        return "BATAS MAKSIMAL KONSUMSI";
    }
    return "WAKTU MAKSIMAL KONSUMSI";
});
</script>

<template>
    <!-- Label Kemasan Standar 9cm x 6cm (Lebar 555px, Tinggi 370px -> Rasio 3:2 Exact) -->
    <div
        class="bgn-label-card"
        style="
            width: 555px;
            max-width: 100%;
            height: 370px;
            background-color: #ffffff;
            color: #1e3a8a;
            font-family:
                Arial, &quot;Helvetica Neue&quot;, Helvetica, sans-serif !important;
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
                border: '1.5px dashed #94a3b8',
                borderBottom: 'none',
                borderRadius: '8px 8px 0 0',
                backgroundColor: '#f8fafc',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '4px 10px',
                boxSizing: 'border-box',
                flexShrink: 0,
            }"
        >
            <div
                style="
                    font-size: 8.5px;
                    font-weight: 800;
                    color: #64748b;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                    line-height: 1;
                    white-space: nowrap;
                    text-align: center;
                "
            >
                - - - - - - - - - - AREA TEMPEL ISOLASI / PEREKAT KEMASAN - - -
                - - - - - - -
            </div>
        </div>

        <!-- ================= KARTU UTAMA RATA BAWAH DENGAN BORDER BIRU RESMI BGN ================= -->
        <div
            :style="{
                border: '2.5px solid #164282',
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
                        src="/images/BGN_LOGOTYPE_MAIN.png"
                        alt="Badan Gizi Nasional"
                        :style="{
                            height: dyn.logoHeight,
                            maxWidth: '220px',
                            objectFit: 'contain',
                            objectPosition: 'left center',
                        }"
                        onerror="this.src = '/images/BGN_LOGO_MAIN.png'"
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
                            color: '#64748b',
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
                            color: '#164282',
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
                    backgroundColor: '#c29046',
                    borderRadius: '2px',
                    marginBottom: dyn.goldLineMarginBottom,
                }"
            ></div>

            <!-- ================= 2. BANNER MENU MEMANJANG ================= -->
            <div
                :style="{
                    display: 'flex',
                    alignItems: 'stretch',
                    backgroundColor: '#4a85d9',
                    borderRadius: '5px',
                    color: '#ffffff',
                    marginBottom: dyn.bannerMarginBottom,
                    minHeight: dyn.bannerMinHeight,
                    overflow: 'hidden',
                    boxSizing: 'border-box',
                }"
            >
                <div
                    :style="{
                        backgroundColor: '#3b72c2',
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
                            transform: translateY(-3px);
                        "
                        >MENU</span
                    >
                </div>
                <div
                    :style="{
                        padding: menuFontSizeStyle.padding,
                        fontSize: menuFontSizeStyle.fontSize,
                        lineHeight: menuFontSizeStyle.lineHeight,
                        fontWeight: '800',
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
                            transform: translateY(-3px);
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
                                color: '#1e293b',
                                lineHeight: '1',
                                marginBottom: dyn.tglLabelMarginBottom,
                                paddingLeft: '1px',
                                display: 'block',
                                transform: 'translateY(-1.5px)',
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
                                backgroundColor: '#f1f6fd',
                                border: '1.2px solid #c7dcf8',
                                borderRadius: '5px',
                                fontSize: dyn.tglBoxFontSize,
                                fontWeight: '900',
                                color: '#164282',
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
                                    transform: translateY(-3.5px);
                                "
                                >{{ formattedDateDisplay }}</span
                            >
                        </div>
                    </div>

                    <!-- 2. Box Oranye WAKTU MAKSIMAL KONSUMSI -->
                    <div
                        :style="{
                            backgroundColor: '#fff2e8',
                            border: '1px solid #ffd9c0',
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
                        <div
                            :style="{
                                fontSize: dyn.waktuHeaderFontSize,
                                fontWeight: '900',
                                color: '#c2410c',
                                letterSpacing: '0.2px',
                                textTransform: 'uppercase',
                                lineHeight: '1',
                                margin: '0',
                                marginBottom: '2px',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                transform: 'translateY(-3px)',
                            }"
                        >
                            {{ headerWaktuMaksimalDisplay }}
                        </div>
                        <div
                            :style="{
                                fontSize: dyn.waktuSubFontSize,
                                fontWeight: '900',
                                color: '#9a2c0c',
                                lineHeight: '1',
                                margin: '0',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                transform: 'translateY(-3px)',
                            }"
                        >
                            {{ formattedWaktuMaksimalDisplay }}
                        </div>
                    </div>

                    <!-- 3. Box Merah LARANGAN KONSUMSI DI TEMPAT -->
                    <div
                        :style="{
                            backgroundColor: '#fff2f2',
                            border: '1.2px solid #fca5a5',
                            borderLeft: '4.5px solid #dc2626',
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
                                color: '#991b1b',
                                letterSpacing: '-0.1px',
                                textTransform: 'uppercase',
                                lineHeight: '1',
                                margin: '0',
                                marginBottom: '2px',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                width: '100%',
                                transform: 'translateY(-3px)',
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
                                color: '#991b1b',
                                letterSpacing: '0.1px',
                                textTransform: 'uppercase',
                                lineHeight: '1',
                                margin: '0',
                                padding: '0',
                                whiteSpace: 'nowrap',
                                width: '100%',
                                transform: 'translateY(-3px)',
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
                        borderLeft: '1px dashed #cbd5e1',
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
                                color: '#1e293b',
                                lineHeight: '1',
                                whiteSpace: 'nowrap',
                                transform: 'translateY(-2px)',
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
                                        color: '#334155',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-2px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Energi</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: '#64748b',
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.energi_pk || "363,5"
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.energi_pb || "534"
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
                                        color: '#334155',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-2px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Karbohidrat</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: '#64748b',
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.karbo_pk || "41,2"
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.karbo_pb || "68"
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
                                        color: '#334155',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-2px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Protein</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: '#64748b',
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.prot_pk ||
                                                giziData.protein_pk ||
                                                "22,1"
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.prot_pb ||
                                                giziData.protein_pb ||
                                                "30,1"
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
                                        color: '#334155',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-2px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Lemak</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: '#64748b',
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.lmk_pk ||
                                                giziData.lemak_pk ||
                                                "12"
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.lmk_pb ||
                                                giziData.lemak_pb ||
                                                "16,2"
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
                                        color: '#334155',
                                        whiteSpace: 'nowrap',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            height: 100%;
                                            transform: translateY(-2px);
                                            line-height: 1;
                                        "
                                    >
                                        <span style="font-weight: 700"
                                            >Serat</span
                                        >
                                        <span
                                            :style="{
                                                fontSize: dyn.tableUnitFontSize,
                                                color: '#64748b',
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.serat_pk || "2,6"
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
                                        backgroundColor: '#f1f6fd',
                                        border: '1px solid #c7dcf8',
                                        borderRadius: '3.5px',
                                        fontSize: dyn.tableValueFontSize,
                                        fontWeight: '900',
                                        color: '#164282',
                                    }"
                                >
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            height: 100%;
                                            width: 100%;
                                            transform: translateY(-2px);
                                        "
                                    >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                transform: translateY(-1.5px);
                                            "
                                            >{{
                                                giziData.serat_pb || "5,1"
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
                                        backgroundColor: '#5a94e2',
                                        borderRadius: '3.5px',
                                        color: '#ffffff',
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
                                            transform: translateY(-2px);
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
                                        backgroundColor: '#5a94e2',
                                        borderRadius: '3.5px',
                                        color: '#ffffff',
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
                                            transform: translateY(-2px);
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
        </div>
    </div>
</template>

<style scoped>
.bgn-label-card {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif !important;
    box-sizing: border-box !important;
}

.bgn-label-card * {
    box-sizing: border-box !important;
}
</style>
