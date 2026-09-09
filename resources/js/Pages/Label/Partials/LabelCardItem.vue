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
});

const topIsolasiHeightPx = computed(() => {
    const cm = parseFloat(props.tinggiIsolasiCm);
    const validCm = isNaN(cm) || cm <= 0 ? 2 : cm;
    // 370px = 6cm, so 1cm = 61.667px
    return Math.round(validCm * 61.667);
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
    const len = formattedMenuText.value.length;
    if (len > 120) {
        return {
            fontSize: "8px",
            lineHeight: "1.15",
            padding: "2px 8px 5px 8px",
        };
    } else if (len > 80) {
        return {
            fontSize: "8.8px",
            lineHeight: "1.2",
            padding: "2px 8px 5px 8px",
        };
    } else if (len > 50) {
        return {
            fontSize: "9.5px",
            lineHeight: "1.2",
            padding: "2px 8px 5px 8px",
        };
    }
    return {
        fontSize: "10.5px",
        lineHeight: "1.2",
        padding: "2px 8px 5px 8px",
    };
});

const formattedDateDisplay = computed(() => {
    if (!props.tanggalProduksi) {
        const now = new Date();
        const dd = String(now.getDate()).padStart(2, "0");
        const mm = String(now.getMonth() + 1).padStart(2, "0");
        const yyyy = now.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
    }
    try {
        const parts = props.tanggalProduksi.split("-");
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(props.tanggalProduksi);
        if (isNaN(d.getTime())) return props.tanggalProduksi;
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const yyyy = d.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
    } catch {
        return props.tanggalProduksi;
    }
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
                minHeight: '20px',
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
            style="
                border: 2.5px solid #164282;
                border-radius: 0 0 12px 12px;
                padding: 6px 12px 7px 12px;
                background-color: #ffffff;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                flex: 1;
                justify-content: space-between;
            "
        >
            <!-- ================= 1. HEADER LOGO & INSTANSI ================= -->
            <div
                style="
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                    margin-bottom: 4px;
                "
            >
                <!-- Kiri: Logo Resmi BGN Bergambar + Teks (BGN_LOGOTYPE_MAIN.png) -->
                <div style="display: flex; align-items: center">
                    <img
                        src="/images/BGN_LOGOTYPE_MAIN.png"
                        alt="Badan Gizi Nasional"
                        style="
                            height: 44px;
                            max-width: 220px;
                            object-fit: contain;
                            object-position: left center;
                        "
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
                        style="
                            font-size: 8px;
                            font-weight: 800;
                            color: #64748b;
                            letter-spacing: 0.5px;
                            text-transform: uppercase;
                            line-height: 1.1;
                            margin-bottom: 2px;
                        "
                    >
                        SATUAN PELAYANAN PEMENUHAN GIZI
                    </div>
                    <div
                        style="
                            font-size: 12.5px;
                            font-weight: 900;
                            color: #164282;
                            letter-spacing: -0.2px;
                            text-transform: uppercase;
                            line-height: 1.1;
                        "
                    >
                        {{ displaySppg }}
                    </div>
                </div>
            </div>

            <!-- Garis Pemisah Emas / Kuning Kecoklatan -->
            <div
                style="
                    height: 2px;
                    background-color: #c29046;
                    border-radius: 2px;
                    margin-bottom: 5px;
                "
            ></div>

            <!-- ================= 2. BANNER MENU MEMANJANG ================= -->
            <div
                style="
                    display: flex;
                    align-items: stretch;
                    background-color: #4a85d9;
                    border-radius: 5px;
                    color: #ffffff;
                    margin-bottom: 6px;
                    min-height: 32px;
                    overflow: hidden;
                    box-sizing: border-box;
                "
            >
                <div
                    style="
                        background-color: #3b72c2;
                        font-size: 10.5px;
                        font-weight: 900;
                        padding: 0 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        letter-spacing: 0.5px;
                        line-height: 1;
                        white-space: nowrap;
                        border-radius: 5px 0 0 5px;
                    "
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

            <!-- ================= 3. BODY CONTENT: 2 KOLOM (RASIO 65% : 35%) ================= -->
            <div style="display: flex; gap: 8px; align-items: flex-start">
                <!-- KOLOM KIRI: 3 KOTAK TINGGI PRESISI SEIMBANG DENGAN KOLOM KANAN, TEKS TIDAK KEPOTONG -->
                <div
                    style="
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        gap: 5px;
                    "
                >
                    <!-- 1. Bagian Tanggal Produksi -->
                    <div style="display: flex; flex-direction: column">
                        <div
                            style="
                                font-size: 9px;
                                font-weight: 800;
                                color: #1e293b;
                                line-height: 1;
                                margin-bottom: 5px;
                                padding-left: 1px;
                                display: block;
                                transform: translateY(-1.5px);
                            "
                        >
                            Tanggal Produksi
                        </div>
                        <div
                            style="
                                background-color: #f1f6fd;
                                border: 1.2px solid #c7dcf8;
                                border-radius: 5px;
                                font-size: 13.5px;
                                font-weight: 900;
                                color: #164282;
                                text-align: center;
                                letter-spacing: 0.5px;
                                height: 26px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                box-sizing: border-box;
                            "
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
                        style="
                            background-color: #fff2e8;
                            border: 1px solid #ffd9c0;
                            border-radius: 5px;
                            padding: 0 6px;
                            text-align: center;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            height: 36px;
                        "
                    >
                        <div
                            style="
                                font-size: 7.5px;
                                font-weight: 900;
                                color: #c2410c;
                                letter-spacing: 0.2px;
                                text-transform: uppercase;
                                line-height: 1;
                                margin: 0;
                                margin-bottom: 2px;
                                padding: 0;
                                white-space: nowrap;
                                transform: translateY(-3px);
                            "
                        >
                            WAKTU MAKSIMAL KONSUMSI
                        </div>
                        <div
                            style="
                                font-size: 11px;
                                font-weight: 900;
                                color: #9a2c0c;
                                line-height: 1;
                                margin: 0;
                                padding: 0;
                                white-space: nowrap;
                                transform: translateY(-3px);
                            "
                        >
                            {{ waktuMaksimal || "2 JAM SETELAH DITERIMA!" }}
                        </div>
                    </div>

                    <!-- 3. Box Merah LARANGAN KONSUMSI DI TEMPAT -->
                    <div
                        style="
                            background-color: #fff2f2;
                            border: 1.2px solid #fca5a5;
                            border-left: 4.5px solid #dc2626;
                            border-radius: 5px;
                            padding: 0 8px;
                            text-align: left;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: flex-start;
                            height: 38px;
                        "
                    >
                        <div
                            style="
                                font-size: 7.5px;
                                font-weight: 900;
                                color: #991b1b;
                                letter-spacing: -0.1px;
                                text-transform: uppercase;
                                line-height: 1;
                                margin: 0;
                                margin-bottom: 2px;
                                padding: 0;
                                white-space: nowrap;
                                width: 100%;
                                transform: translateY(-3px);
                            "
                        >
                            {{
                                teksLaranganHeader ||
                                "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT."
                            }}
                        </div>
                        <div
                            style="
                                font-size: 9.5px;
                                font-weight: 900;
                                color: #991b1b;
                                letter-spacing: 0.1px;
                                text-transform: uppercase;
                                line-height: 1;
                                margin: 0;
                                padding: 0;
                                white-space: nowrap;
                                width: 100%;
                                transform: translateY(-3px);
                            "
                        >
                            {{ teksLaranganSub || "DILARANG MEMBAWA PULANG!" }}
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: TABEL KANDUNGAN GIZI (BATAS PINGGIR KANAN SEJAJAR UJUNG HEADER) -->
                <div
                    style="
                        width: 176px;
                        flex: 0 0 176px;
                        display: flex;
                        flex-direction: column;
                        border-left: 1px dashed #cbd5e1;
                        padding-left: 8px;
                        box-sizing: border-box;
                    "
                >
                    <!-- Header Kandungan Gizi -->
                    <div
                        style="
                            display: flex;
                            align-items: center;
                            margin-bottom: 4.5px;
                        "
                    >
                        <span
                            style="
                                font-size: 9.5px;
                                font-weight: 900;
                                color: #1e293b;
                                line-height: 1;
                                white-space: nowrap;
                                transform: translateY(-2px);
                                display: block;
                            "
                        >
                            Kandungan Gizi
                        </span>
                    </div>

                    <!-- Tabel Kandungan Gizi (Pasti Sejajar & Pas di Tengah) -->
                    <table
                        style="
                            width: 100%;
                            border-collapse: separate;
                            border-spacing: 0 2.5px;
                            table-layout: fixed;
                            margin: 0;
                            padding: 0;
                        "
                    >
                        <colgroup>
                            <col style="width: auto" />
                            <col style="width: 36px" />
                            <col style="width: 3px" />
                            <col style="width: 36px" />
                        </colgroup>
                        <tbody>
                            <!-- 1. Energi -->
                            <tr>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: left;
                                        padding: 0;
                                        font-size: 7.5px;
                                        font-weight: 700;
                                        color: #334155;
                                        white-space: nowrap;
                                    "
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
                                            style="
                                                font-size: 6px;
                                                color: #64748b;
                                                font-weight: normal;
                                                margin-left: 2px;
                                            "
                                            >(Kkal)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: left;
                                        padding: 0;
                                        font-size: 7.5px;
                                        font-weight: 700;
                                        color: #334155;
                                        white-space: nowrap;
                                    "
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
                                            style="
                                                font-size: 6px;
                                                color: #64748b;
                                                font-weight: normal;
                                                margin-left: 2px;
                                            "
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: left;
                                        padding: 0;
                                        font-size: 7.5px;
                                        font-weight: 700;
                                        color: #334155;
                                        white-space: nowrap;
                                    "
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
                                            style="
                                                font-size: 6px;
                                                color: #64748b;
                                                font-weight: normal;
                                                margin-left: 2px;
                                            "
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: left;
                                        padding: 0;
                                        font-size: 7.5px;
                                        font-weight: 700;
                                        color: #334155;
                                        white-space: nowrap;
                                    "
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
                                            style="
                                                font-size: 6px;
                                                color: #64748b;
                                                font-weight: normal;
                                                margin-left: 2px;
                                            "
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: left;
                                        padding: 0;
                                        font-size: 7.5px;
                                        font-weight: 700;
                                        color: #334155;
                                        white-space: nowrap;
                                    "
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
                                            style="
                                                font-size: 6px;
                                                color: #64748b;
                                                font-weight: normal;
                                                margin-left: 2px;
                                            "
                                            >(g)</span
                                        >
                                    </div>
                                </td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 15px;
                                        background-color: #f1f6fd;
                                        border: 1px solid #c7dcf8;
                                        border-radius: 3.5px;
                                        font-size: 7.5px;
                                        font-weight: 900;
                                        color: #164282;
                                    "
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
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 19px;
                                        background-color: #5a94e2;
                                        border-radius: 3.5px;
                                        color: #ffffff;
                                        font-size: 6.5px;
                                        font-weight: 900;
                                        line-height: 1;
                                    "
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
                                            style="
                                                display: block;
                                                line-height: 1;
                                                font-size: 6px;
                                                font-weight: 900;
                                            "
                                            >Porsi</span
                                        >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                font-size: 6px;
                                                font-weight: 900;
                                                margin-top: 1px;
                                            "
                                            >Kecil</span
                                        >
                                    </div>
                                </td>
                                <td></td>
                                <td
                                    style="
                                        vertical-align: middle;
                                        text-align: center;
                                        padding: 0;
                                        height: 19px;
                                        background-color: #5a94e2;
                                        border-radius: 3.5px;
                                        color: #ffffff;
                                        font-size: 6.5px;
                                        font-weight: 900;
                                        line-height: 1;
                                    "
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
                                            style="
                                                display: block;
                                                line-height: 1;
                                                font-size: 6px;
                                                font-weight: 900;
                                            "
                                            >Porsi</span
                                        >
                                        <span
                                            style="
                                                display: block;
                                                line-height: 1;
                                                font-size: 6px;
                                                font-weight: 900;
                                                margin-top: 1px;
                                            "
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
