import jsPDF from "jspdf";
import html2canvas from "html2canvas";
import { bgnLogoBase64 } from "./bgnLogoBase64.js";

/**
 * Capture an HTMLElement into high resolution Canvas
 * Scale 2.5 produces ~400 DPI ultra-sharp print resolution
 * while keeping memory footprint and execution time ultra-fast.
 */
async function captureElementToCanvas(element) {
    if (!element) {
        throw new Error(
            "Elemen kartu label tidak ditemukan untuk proses render.",
        );
    }

    const elementW = 555;
    const elementH = 370;

    return await html2canvas(element, {
        scale: 2.5, // 2.5x gives ~400 DPI crystal clarity & blazing speed
        useCORS: true,
        allowTaint: true,
        backgroundColor: "#ffffff",
        logging: false,
        scrollX: 0,
        scrollY: 0,
        windowWidth: 555,
        windowHeight: 370,
        width: 555,
        height: 370,
    });
}

/**
 * Format remaining seconds into detailed Indonesian time text:
 * detik, menit, jam
 */
export function formatDetailedEta(totalSeconds) {
    const sec = Math.max(0, Math.ceil(totalSeconds));
    if (sec <= 0) return "Hampir selesai...";
    if (sec === 1) return "Estimasi: ~1 detik lagi";
    if (sec < 60) return `Estimasi: ~${sec} detik lagi`;

    const hours = Math.floor(sec / 3600);
    const minutes = Math.floor((sec % 3600) / 60);
    const remainingSec = sec % 60;

    if (hours > 0) {
        if (minutes > 0) {
            return `Estimasi: ~${hours} jam ${minutes} menit lagi`;
        }
        return `Estimasi: ~${hours} jam lagi`;
    }

    if (remainingSec > 0) {
        return `Estimasi: ~${minutes} menit ${remainingSec} detik lagi`;
    }
    return `Estimasi: ~${minutes} menit lagi`;
}

/**
 * Pre-render unique target group templates once.
 * Supports both Normal and Allergy label variations.
 * Progressively updates Phase 1 progress (0% - 75%) with real-time ETA.
 */
async function preRenderUniqueTemplates({
    printableItems = [],
    printableKelompokList = [],
    getRenderElement,
    startTime = Date.now(),
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const items =
        printableItems.length > 0
            ? printableItems
            : printableKelompokList.map((k) => ({
                  kelompok: k,
                  tipeLabel: "normal",
                  jenisAlergi: "",
                  tagAlergi: "",
              }));

    function makeTemplateKey(itemOrKelompok) {
        if (!itemOrKelompok) return "fallback_key";
        const isItem =
            typeof itemOrKelompok === "object" &&
            ("tipeLabel" in itemOrKelompok || "kelompok" in itemOrKelompok);
        const k = isItem ? itemOrKelompok.kelompok : itemOrKelompok;
        const tipe = isItem ? itemOrKelompok.tipeLabel || "normal" : "normal";
        const subTambahan = isItem ? itemOrKelompok.subTipeTambahan || "" : "";
        const alergi = isItem ? itemOrKelompok.jenisAlergi || "" : "";
        const tag = isItem ? itemOrKelompok.tagAlergi || "" : "";
        const kId = k?.id !== undefined && k?.id !== null ? k.id : (k?.nama_kelompok || k?.nama || "0");
        const menuSignature = isItem && Array.isArray(itemOrKelompok.menuItems)
            ? itemOrKelompok.menuItems.join("|")
            : "";
        const giziSignature = isItem && itemOrKelompok.giziData
            ? `${itemOrKelompok.giziData.energi_pb || ''}_${itemOrKelompok.giziData.energi_pk || ''}_${itemOrKelompok.giziData.prot_pb || ''}_${itemOrKelompok.giziData.prot_pk || ''}`
            : "";
        return `${kId}_${tipe}_${subTambahan}_${alergi}_${tag}_${menuSignature}_${giziSignature}`;
    }

    const uniqueMap = new Map();
    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        const key = makeTemplateKey(item);
        if (!uniqueMap.has(key)) {
            uniqueMap.set(key, {
                key,
                renderItem: item,
                kelompok: item.kelompok,
                alias: `tpl_${uniqueMap.size}`,
            });
        }
    }

    const uniqueList = Array.from(uniqueMap.values());
    const totalUnique = uniqueList.length;
    const renderedTemplates = new Map();

    for (let t = 0; t < totalUnique; t++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = uniqueList[t];

        const elapsedSec = (Date.now() - startTime) / 1000;
        const avgPerTpl = t > 0 ? elapsedSec / t : 0.25;
        const remainingTpl = totalUnique - t;
        const etaSeconds = remainingTpl * avgPerTpl;

        const percentage = Math.max(
            5,
            Math.min(75, Math.round(((t + 1) / totalUnique) * 75)),
        );

        onProgress({
            phase: "template",
            current: t + 1,
            total: totalUnique,
            percentage,
            message: `Menyiapkan template desain label (${t + 1} dari ${totalUnique})...`,
            etaText: formatDetailedEta(etaSeconds),
            speedText:
                t > 0
                    ? `${(t / Math.max(0.1, elapsedSec)).toFixed(1)} desain/dtk`
                    : "",
        });

        const element = await getRenderElement(item.renderItem || item.kelompok);
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const canvas = await captureElementToCanvas(element);
        const imgData = canvas.toDataURL("image/jpeg", 0.95);
        renderedTemplates.set(item.key, { imgData, alias: item.alias });
    }

    const fallback = Array.from(renderedTemplates.values())[0];

    return {
        getTemplate: (itemOrKelompok, idx) => {
            if (!itemOrKelompok) return fallback;
            const key = makeTemplateKey(itemOrKelompok);
            return renderedTemplates.get(key) || fallback;
        },
    };
}

/**
 * Download Single Mode PDF (Exact 9cm x 6cm Fixed Landscape per Page)
 * Supports thousands of labels with ultra-fast aliased image insertion & ETA.
 */
export async function downloadPdfSingleMode({
    printableItems = [],
    printableKelompokList = [],
    customCount = null,
    getRenderElement,
    filename = "Label_BGN_9x6cm_Tunggal.pdf",
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total =
        customCount && Number(customCount) > 0
            ? parseInt(customCount, 10)
            : list.length;

    const pageW = 90;
    const pageH = 60;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: [pageW, pageH],
        compress: true,
    });

    const startTime = Date.now();

    const { getTemplate } = await preRenderUniqueTemplates({
        printableItems,
        printableKelompokList,
        getRenderElement,
        startTime,
        isCancelled,
        onProgress,
    });

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    const phase2Start = Date.now();
    const updateInterval = Math.max(1, Math.min(25, Math.floor(total / 50)));

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        const template = getTemplate(item, i);

        if (i > 0) {
            doc.addPage([pageW, pageH], "landscape");
        }

        // Add aliased image to PDF
        doc.addImage(
            template.imgData,
            "JPEG",
            0,
            0,
            pageW,
            pageH,
            template.alias,
            "FAST",
        );

        if (i % updateInterval === 0 || i === total - 1) {
            const elapsedPhase2 = (Date.now() - phase2Start) / 1000;
            const speed = (i + 1) / Math.max(0.05, elapsedPhase2);
            const remaining = total - (i + 1);
            const etaSec = Math.ceil(remaining / Math.max(1, speed));
            const percentage = Math.min(
                95,
                75 + Math.round(((i + 1) / total) * 20),
            );

            onProgress({
                phase: "assembly",
                current: i + 1,
                total,
                totalPages: total,
                currentPage: i + 1,
                percentage,
                etaText: formatDetailedEta(etaSec),
                speedText: `${Math.round(speed)} label/dtk`,
                message: `Menyusun dokumen PDF label (${i + 1} dari ${total})...`,
            });

            // Yield to browser thread for smooth UI update
            await new Promise((resolve) => setTimeout(resolve, 0));
        }
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    onProgress({
        phase: "saving",
        current: total,
        total,
        totalPages: total,
        currentPage: total,
        percentage: 98,
        etaText: "Menyimpan file...",
        speedText: "",
        message: "Menyimpan dokumen PDF 9x6cm...",
    });

    doc.save(filename);

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages: total,
        currentPage: total,
        percentage: 100,
        etaText: "Selesai!",
        speedText: "",
        message: `File PDF 9x6cm (${total} Label) berhasil didownload!`,
    });
}

/**
 * Direct Print Single Mode (Opens Print Dialog with Exact 90mm x 60mm Full Page)
 */
export async function printPdfSingleMode({
    printableItems = [],
    printableKelompokList = [],
    getRenderElement,
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total = list.length;
    const pageW = 90;
    const pageH = 60;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: [pageW, pageH],
        compress: true,
    });

    const startTime = Date.now();

    const { getTemplate } = await preRenderUniqueTemplates({
        printableItems,
        printableKelompokList,
        getRenderElement,
        startTime,
        isCancelled,
        onProgress,
    });

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    const phase2Start = Date.now();

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        const template = getTemplate(item, i);

        if (i > 0) {
            doc.addPage([pageW, pageH], "landscape");
        }

        doc.addImage(
            template.imgData,
            "JPEG",
            0,
            0,
            pageW,
            pageH,
            template.alias,
            "FAST",
        );

        const elapsedPhase2 = (Date.now() - phase2Start) / 1000;
        const speed = (i + 1) / Math.max(0.05, elapsedPhase2);
        const remaining = total - (i + 1);
        const etaSec = Math.ceil(remaining / Math.max(1, speed));
        const percentage = Math.min(
            95,
            75 + Math.round(((i + 1) / total) * 20),
        );

        onProgress({
            phase: "assembly",
            current: i + 1,
            total,
            totalPages: total,
            currentPage: i + 1,
            percentage,
            etaText: formatDetailedEta(etaSec),
            speedText: `${Math.round(speed)} label/dtk`,
            message: `Menyiapkan label ${i + 1} dari ${total} untuk dicetak...`,
        });

        await new Promise((resolve) => setTimeout(resolve, 0));
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages: total,
        currentPage: total,
        percentage: 100,
        etaText: "Siap cetak!",
        speedText: "",
        message: "Membuka dialog cetak 9x6cm...",
    });

    doc.autoPrint();
    const blobUrl = doc.output("bloburl");
    const printFrame = document.createElement("iframe");
    printFrame.style.position = "fixed";
    printFrame.style.right = "0";
    printFrame.style.bottom = "0";
    printFrame.style.width = "0";
    printFrame.style.height = "0";
    printFrame.style.border = "0";
    printFrame.src = blobUrl;
    document.body.appendChild(printFrame);
    printFrame.onload = () => {
        try {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        } catch (e) {
            window.open(blobUrl, "_blank");
        }
    };
}

/**
 * Identifier unik untuk mengelompokkan jenis label.
 * Jika tipe label, sub-tipe tambahan, jenis alergi, atau kelompok sasaran berbeda,
 * maka akan menghasilkan group key yang berbeda sehingga pada mode A4 grid
 * label akan dipisahkan halamannya (tidak disambung isian per labelnya).
 */
export function getLabelGroupKey(item) {
    if (!item) return "";
    const isItem =
        typeof item === "object" &&
        ("tipeLabel" in item || "kelompok" in item);
    const k = isItem ? item.kelompok : item;
    const tipe = isItem ? item.tipeLabel || "normal" : "normal";
    const subTambahan = isItem ? item.subTipeTambahan || "" : "";
    const alergi = isItem ? (item.jenisAlergi || "").trim().toLowerCase() : "";
    const tag = isItem ? (item.tagAlergi || "").trim() : "";
    const kId =
        k?.id !== undefined && k?.id !== null
            ? String(k.id)
            : String(k?.nama_kelompok || k?.nama || "0");

    if (tipe === "tambahan") {
        return `tambahan_${subTambahan || tag || "umum"}`;
    }
    if (tipe === "alergi") {
        return `alergi_${kId}_${alergi}_${tag}`;
    }
    return `normal_${kId}`;
}

/**
 * Menghitung total lembar A4 secara akurat dengan pemisahan halaman
 * antar kelompok sasaran dan antar jenis label (normal, variasi alergi, organoleptik, sampel, buffer).
 */
export function calculateA4TotalPages(list, totalCount = null, labelsPerPage = 9) {
    if (!list || list.length === 0) return 1;
    const total =
        totalCount && Number(totalCount) > 0
            ? parseInt(totalCount, 10)
            : list.length;
    let simPages = 1;
    let simSlot = 0;
    for (let i = 0; i < total; i++) {
        const curr = list[i % list.length];
        const prev = i > 0 ? list[(i - 1) % list.length] : null;
        const isTransition =
            prev && getLabelGroupKey(prev) !== getLabelGroupKey(curr);

        if (isTransition && simSlot !== 0) {
            simPages++;
            simSlot = 0;
        } else if (simSlot >= labelsPerPage) {
            simPages++;
            simSlot = 0;
        }
        simSlot++;
    }
    return Math.max(1, simPages);
}

/**
 * Download A4 Sheet Mode PDF (9cm x 6cm Labels on A4 Landscape: 9 Labels / Page: 3 cols x 3 rows)
 * Ultra-fast generation for 1000s of labels using aliased image embedding + real-time ETA.
 */
export async function downloadPdfA4GridMode({
    printableItems = [],
    printableKelompokList = [],
    customCount = null,
    getRenderElement,
    filename = "Label_BGN_Lembar_A4_9PerHalaman.pdf",
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total =
        customCount && Number(customCount) > 0
            ? parseInt(customCount, 10)
            : list.length;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: "a4", // 297mm x 210mm
        compress: true,
    });

    // 90mm x 60mm labels on A4 Landscape (297mm x 210mm)
    // 3 Columns x 3 Rows = 9 labels per A4 Landscape page
    const labelW = 90;
    const labelH = 60;
    const gapX = 3.5;
    const startX = 10; // (297 - (90*3 + 3.5*2)) / 2 = 10mm
    const gapY = 3.5;
    const startY = 11.5; // (210 - (60*3 + 3.5*2)) / 2 = 11.5mm
    const labelsPerPage = 9;

    // Hitung total halaman dengan pemisahan lembar untuk setiap jenis label & kelompok
    // (Agar tiap jenis label/kelompok terisolasi pada lembar stiker tersendiri, tidak bercampur/disambung)
    const totalPages = calculateA4TotalPages(list, total, labelsPerPage);

    const startTime = Date.now();

    const { getTemplate } = await preRenderUniqueTemplates({
        printableItems,
        printableKelompokList,
        getRenderElement,
        startTime,
        isCancelled,
        onProgress,
    });

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    const phase2Start = Date.now();
    const updateInterval = Math.max(
        1,
        Math.min(18, Math.floor(total / 40)),
    ); // Update every 1-2 pages

    let currentSlot = 0;
    let currentPage = 1;

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        const prevItem = i > 0 ? list[(i - 1) % list.length] : null;
        const isTransition =
            prevItem &&
            getLabelGroupKey(prevItem) !== getLabelGroupKey(item);

        // Pisahkan ke lembar A4 baru jika berganti jenis label atau kelompok penerima
        // atau jika 1 lembar (9 slot) sudah penuh
        if (isTransition && currentSlot !== 0) {
            doc.addPage("a4", "landscape");
            currentSlot = 0;
            currentPage++;
        } else if (currentSlot >= labelsPerPage) {
            doc.addPage("a4", "landscape");
            currentSlot = 0;
            currentPage++;
        }

        const template = getTemplate(item, i);

        const col = currentSlot % 3;
        const row = Math.floor(currentSlot / 3);

        const x = startX + col * (labelW + gapX);
        const y = startY + row * (labelH + gapY);

        doc.addImage(
            template.imgData,
            "JPEG",
            x,
            y,
            labelW,
            labelH,
            template.alias,
            "FAST",
        );

        currentSlot++;

        if (i % updateInterval === 0 || i === total - 1) {
            const elapsedPhase2 = (Date.now() - phase2Start) / 1000;
            const speed = (i + 1) / Math.max(0.05, elapsedPhase2);
            const remaining = total - (i + 1);
            const etaSec = Math.ceil(remaining / Math.max(1, speed));
            const percentage = Math.min(
                95,
                75 + Math.round(((i + 1) / total) * 20),
            );

            const phaseNote =
                item.tipeLabel === "alergi"
                    ? ` (Lembar Alergi: ${item.jenisAlergi || "Khusus"})`
                    : item.tipeLabel === "tambahan"
                      ? ` (Lembar Tambahan: ${item.tagAlergi || "Porsi Tambahan"})`
                      : "";

            onProgress({
                phase: "assembly",
                current: i + 1,
                total,
                totalPages,
                currentPage,
                percentage,
                etaText: formatDetailedEta(etaSec),
                speedText: `${Math.round(speed)} label/dtk`,
                message: `Menyusun lembar A4 label ${i + 1} dari ${total} (Hal. ${currentPage}/${totalPages}${phaseNote})...`,
            });

            // Yield to event loop to keep browser animations silky smooth
            await new Promise((resolve) => setTimeout(resolve, 0));
        }
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    onProgress({
        phase: "saving",
        current: total,
        total,
        totalPages,
        currentPage: totalPages,
        percentage: 98,
        etaText: "Menyimpan file...",
        speedText: "",
        message: `Menyimpan dokumen PDF Lembar A4 (${totalPages} Halaman)...`,
    });

    doc.save(filename);

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages,
        currentPage: totalPages,
        percentage: 100,
        etaText: "Selesai!",
        speedText: "",
        message: `File PDF Lembar A4 (${total} Label, ${totalPages} Hal) berhasil didownload!`,
    });
}

// ============================================================================
// TEMPLATE 02 (Standar Layout Excel BGN - Pure Vector & Text PDF)
// ============================================================================

function formatTglTpl02(tgl) {
    if (!tgl) return "27-10-2026";
    const parts = String(tgl).split("-");
    if (parts.length === 3 && parts[0].length === 4) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return String(tgl);
}

function formatWaktuTpl02(waktuMaksimal, jamExpired, zonaWaktu, tampilkanJamExpired) {
    if (tampilkanJamExpired && jamExpired && String(jamExpired).trim()) {
        const z = zonaWaktu ? ` ${zonaWaktu}` : "";
        return `${String(jamExpired).trim()}${z}`;
    }
    if (waktuMaksimal && String(waktuMaksimal).trim()) {
        return String(waktuMaksimal).trim();
    }
    const j = jamExpired || "10:00";
    const z = zonaWaktu ? ` ${zonaWaktu}` : "";
    return `${j}${z}`;
}

function shouldMergeWaktuBatas(waktuText) {
    if (!waktuText) return false;
    const str = String(waktuText).trim();
    if (str.length > 12) return true;
    if (/jam\s+setelah|setelah|diterima|maksimal|segera/i.test(str)) return true;
    const isShortTime = /^\d{1,2}[:.]\d{2}(\s*(WITA|WIB|WIT))?$/i.test(str);
    return !isShortTime;
}

export function getSocialMediaTpl02(namaSppg) {
    const raw = (namaSppg || "").toLowerCase();
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
        fb: `Facebook: ${namaSppg || "SPPG BGN"}`,
    };
}

/**
 * Draw single Template 02 Card using Pure Text & Vector jsPDF elements
 * Exactly matching layout from label-template-02.xlsx
 */
export function drawTemplate02Card(doc, x, y, item = {}, globalConfig = {}) {
    const cardW = 88.0;
    const cardH = 61.05;

    // Col widths (Sum = 88.0 mm)
    // 5 kolom dibuat seragam dan seimbang (Col D dan Col E / Porsi Kecil & Porsi Besar sama lebar)
    const wA = 17.60;
    const wB = 17.60;
    const wAB = wA + wB; // 35.20 mm
    const wC = 17.60;
    const wD = 17.60;
    const wE = 17.60;
    const wCDE = wC + wD + wE; // 52.80 mm
    const wBC = wB + wC; // 35.20 mm

    // Row heights (Sum = 61.05 mm)
    const hR1 = 14.10;   // Row 1: Area Perekat
    const hR2_3 = 10.00; // Row 2-3: Header Instansi & Logo
    const hR4 = 6.40;    // Row 4: Menu
    const hR5 = 6.40;    // Row 5: Tgl & Batas Aman
    const hR6 = 3.50;    // Row 6: Header Gizi
    const hR_gizi = 2.85; // Rows 7-11: 5 baris gizi (5 * 2.85 = 14.25 mm)
    const hR6_11 = hR6 + (hR_gizi * 5); // 17.75 mm
    const hR12 = 6.40;   // Row 12: Warning banner

    // Helper hex to RGB
    const hexToRgb = (hex, defaultRgb = [255, 255, 255]) => {
        if (!hex || hex === "transparent" || hex === "none" || hex === "white") return [255, 255, 255];
        const clean = String(hex).replace("#", "").trim();
        if (clean.length === 3) {
            const r = parseInt(clean[0] + clean[0], 16);
            const g = parseInt(clean[1] + clean[1], 16);
            const b = parseInt(clean[2] + clean[2], 16);
            return isNaN(r) || isNaN(g) || isNaN(b) ? defaultRgb : [r, g, b];
        }
        if (clean.length === 6) {
            const num = parseInt(clean, 16);
            if (isNaN(num)) return defaultRgb;
            return [(num >> 16) & 255, (num >> 8) & 255, num & 255];
        }
        return defaultRgb;
    };

    // Colors from globalConfig or defaults (default is pure white / uncolored [255, 255, 255])
    const customColors = globalConfig.template02Colors || globalConfig.customColors || {};
    const colAreaPerekat = hexToRgb(customColors.areaPerekat, [255, 255, 255]);
    const colHeaderSppg  = hexToRgb(customColors.headerSppg, [255, 255, 255]);
    const colMenuHeader  = hexToRgb(customColors.menuLabel, [255, 255, 255]);
    const colMenuBody    = hexToRgb(customColors.menuValue, [255, 255, 255]);
    const colTglHeader   = hexToRgb(customColors.tglLabel, [255, 255, 255]);
    const colTglBody     = hexToRgb(customColors.tglValue, [255, 255, 255]);
    const colBatasHeader = hexToRgb(customColors.batasKonsumsi, [255, 255, 255]);
    const colBatasBody   = hexToRgb(customColors.batasKonsumsiVal, [255, 255, 255]);
    const colGreenSoft   = hexToRgb(customColors.kontak, [255, 255, 255]);
    const colYellowHead  = hexToRgb(customColors.giziHeader, [255, 255, 255]);
    const colYellowBody  = hexToRgb(customColors.giziBody, [255, 255, 255]);
    const colGoldWarning = hexToRgb(customColors.footer, [255, 255, 255]);

    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.2);

    // ================= 1. ROW 1: AREA PEREKAT LABEL =================
    doc.setFillColor(...colAreaPerekat);
    doc.rect(x, y, cardW, hR1, "FD");
    doc.setFont("helvetica", "italic");
    doc.setFontSize(4.8);
    doc.setTextColor(130, 130, 130);
    doc.text("- - - - - - - - - AREA PEREKAT LABEL - - - - - - - - -", x + (cardW / 2), y + (hR1 / 2) + 0.8, { align: "center" });

    // ================= 2. ROW 2-3: LOGO & HEADER SPPG =================
    const yR2 = y + hR1;
    doc.setFillColor(...colHeaderSppg);
    doc.rect(x, yR2, cardW, hR2_3, "FD");

    // Logo BGN on the left
    try {
        if (bgnLogoBase64) {
            doc.addImage(bgnLogoBase64, "PNG", x + 2, yR2 + 1, 14.5, 8, "BGN_LOGO_TPL2", "FAST");
        }
    } catch (e) {
        // Fallback gracefully
    }

    // SPPG Header text on the right
    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "normal");
    doc.setFontSize(5.2);
    doc.text("SATUAN PELAYANAN PEMENUHAN GIZI", x + cardW - 2, yR2 + 4.2, { align: "right" });

    doc.setFont("helvetica", "bold");
    const namaSppgText = (globalConfig.namaSppg || "SPPG BULELENG SUKASADA TEGALLINGGAH").toUpperCase();
    const lenSppg = namaSppgText.length;
    let sppgFontSize = 6.8;
    if (lenSppg > 48) {
        sppgFontSize = 5.2;
    } else if (lenSppg > 36) {
        sppgFontSize = 6.0;
    } else if (lenSppg > 25) {
        sppgFontSize = 6.8;
    } else {
        sppgFontSize = 7.6;
    }
    doc.setFontSize(sppgFontSize);
    doc.text(namaSppgText, x + cardW - 2, yR2 + 7.4, { align: "right" });

    // ================= 3. ROW 4: MENU & SUB-MENU =================
    const yR4 = yR2 + hR2_3;
    doc.setFillColor(...colMenuHeader);
    doc.rect(x, yR4, wAB, hR4, "FD");

    let menuTypeTitle = "NORMAL";
    if (item.tipeLabel === "alergi") {
        const j = item.jenisAlergi ? String(item.jenisAlergi).toUpperCase() : "";
        menuTypeTitle = `ALERGI ${j}`.trim();
    } else if (item.tipeLabel === "tambahan") {
        if (item.subTipeTambahan === "organoleptik") menuTypeTitle = "UJI ORGANOLEPTIK";
        else if (item.subTipeTambahan === "sampel") menuTypeTitle = "SAMPEL MAKANAN";
        else if (item.subTipeTambahan === "buffer") menuTypeTitle = "BUFFER PRODUKSI";
        else menuTypeTitle = "TAMBAHAN";
    }

    doc.setFont("helvetica", "bold");
    doc.setFontSize(5.0);
    doc.text("MENU", x + 1.5, yR4 + 2.7);

    // Auto Font Scaling untuk Judul Tipe Menu (Normal/Alergi/Tambahan)
    const lenType = menuTypeTitle.length;
    let typeFontSize = 5.5;
    if (lenType > 28) {
        typeFontSize = 3.8;
    } else if (lenType > 20) {
        typeFontSize = 4.3;
    } else if (lenType > 12) {
        typeFontSize = 4.9;
    }
    doc.setFontSize(typeFontSize);
    doc.text(menuTypeTitle, x + 1.5, yR4 + 5.1);

    doc.setFillColor(...colMenuBody);
    doc.rect(x + wAB, yR4, wCDE, hR4, "FD");

    const menuItems = Array.isArray(item.menuItems) && item.menuItems.length > 0
        ? item.menuItems
        : (globalConfig.menuItems || ["Nasi Putih", "Telur Ceplok Asam Manis", "Tahu Bumbu Kuning", "Tumis Wortel & Kol", "Anggur Muscat"]);
    const menuString = menuItems.join(" - ");

    doc.setFont("helvetica", "normal");
    // Auto Font Scaling untuk Sub-menu agar bila kepanjangan tidak terpotong
    let menuFontSize = 5.2;
    let lineSpacing = 2.1;
    const len = menuString.length;
    if (len > 130) {
        menuFontSize = 4.0;
        lineSpacing = 1.6;
    } else if (len > 90) {
        menuFontSize = 4.4;
        lineSpacing = 1.8;
    } else if (len > 55) {
        menuFontSize = 4.8;
        lineSpacing = 1.95;
    }
    doc.setFontSize(menuFontSize);
    const menuLines = doc.splitTextToSize(menuString, wCDE - 3);
    const capHeightMm = menuFontSize * 0.3528 * 0.72;
    const totalLinesH = (menuLines.length - 1) * lineSpacing;
    const startMenuY = yR4 + (hR4 / 2) - (totalLinesH / 2) + (capHeightMm / 2);
    doc.text(menuLines, x + wAB + 1.5, Math.max(yR4 + 1.6, startMenuY));

    // ================= 4. ROW 5: TANGGAL PRODUKSI & BATAS AMAN =================
    const yR5 = yR4 + hR4;
    doc.setFillColor(...colTglHeader);
    doc.rect(x, yR5, wA, hR5, "FD");
    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(5);

    const isShowingJamProduksi = Boolean(
        globalConfig.tampilkanJamProduksi &&
        globalConfig.jamProduksi &&
        String(globalConfig.jamProduksi).trim()
    );
    const labelProduksiHeader = isShowingJamProduksi ? "WAKTU\nPRODUKSI" : "TANGGAL\nPRODUKSI";
    doc.text(labelProduksiHeader, x + 1.5, yR5 + 2.8);

    doc.setFillColor(...colTglBody);
    doc.rect(x + wA, yR5, wBC, hR5, "FD");
    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "bold");
    let tglText = formatTglTpl02(globalConfig.tanggalProduksi);
    if (isShowingJamProduksi) {
        const jam = String(globalConfig.jamProduksi).trim();
        const zona = globalConfig.zonaWaktu ? ` ${globalConfig.zonaWaktu}` : "";
        tglText = `${tglText} ${jam}${zona}`;
        doc.setFontSize(6.2);
    } else {
        doc.setFontSize(8);
    }
    doc.text(tglText, x + wA + (wBC / 2), yR5 + 4.3, { align: "center" });

    const waktuMaksimalToUse = item?.waktuMaksimal || globalConfig.waktuMaksimal;
    const jamExpiredToUse = item?.jamExpired || globalConfig.jamExpired;
    const tampilkanJamExpiredToUse = item?.tampilkanJamExpired !== undefined
        ? item.tampilkanJamExpired
        : globalConfig.tampilkanJamExpired;
    const waktuText = formatWaktuTpl02(waktuMaksimalToUse, jamExpiredToUse, globalConfig.zonaWaktu, tampilkanJamExpiredToUse);
    const wDE = wD + wE; // 35.05 mm
    const isTambahan = item?.tipeLabel === "tambahan";
    const isMergedWaktu = shouldMergeWaktuBatas(waktuText);

    if (isTambahan) {
        // KHUSUS PORSI TAMBAHAN: Di-merge & Kosongkan tanpa teks apapun
        doc.setFillColor(...colBatasHeader);
        doc.rect(x + wA + wBC, yR5, wDE, hR5, "FD");
    } else if (isMergedWaktu) {
        // MERGE: Kotak D & E digabung menjadi satu banner rapi
        doc.setFillColor(...colBatasHeader);
        doc.rect(x + wA + wBC, yR5, wDE, hR5, "FD");
        doc.setTextColor(0, 0, 0);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(4.6);
        doc.text("WAKTU BATAS AMAN KONSUMSI", x + wA + wBC + (wDE / 2), yR5 + 2.4, { align: "center" });

        doc.setFont("helvetica", "bold");
        doc.setFontSize(6.8);
        doc.text(waktuText, x + wA + wBC + (wDE / 2), yR5 + 5.0, { align: "center" });
    } else {
        // TIDAK DI-MERGE (Format jam singkat e.g. 10:00 WITA)
        doc.setFillColor(...colBatasHeader);
        doc.rect(x + wA + wBC, yR5, wD, hR5, "FD");
        doc.setTextColor(0, 0, 0);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(4.8);
        doc.text("WAKTU BATAS\nAMAN KONSUMSI", x + wA + wBC + 1.5, yR5 + 2.8);

        doc.setFillColor(...colBatasBody);
        doc.rect(x + wA + wBC + wD, yR5, wE, hR5, "FD");
        doc.setTextColor(0, 0, 0);
        doc.setFont("helvetica", "bold");
        const lenWaktu = String(waktuText).length;
        let waktuFontSize = 7.5;
        if (lenWaktu > 12) {
            waktuFontSize = 6.0;
        } else if (lenWaktu > 9) {
            waktuFontSize = 6.9;
        }
        doc.setFontSize(waktuFontSize);
        doc.text(waktuText, x + wA + wBC + wD + (wE / 2), yR5 + 4.25, { align: "center" });
    }

    // ================= 5. ROW 6-11: KONTAK PENGADUAN & GIZI =================
    const yR6 = yR5 + hR5;
    doc.setFillColor(...colGreenSoft);
    doc.rect(x, yR6, wAB, hR6_11, "FD");

    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(5.5);
    doc.text("KONTAK PENGADUAN", x + 2, yR6 + 3.2);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(4.8);
    const sm = getSocialMediaTpl02(globalConfig.namaSppg);
    let contactLines = [
        globalConfig.kontakBaris1?.trim() || "Email: halo@bgn.go.id",
        globalConfig.kontakBaris2?.trim() || sm.ig,
        globalConfig.kontakBaris3?.trim() || sm.tiktok,
        globalConfig.kontakBaris4?.trim() || sm.fb,
    ];
    let cY = yR6 + 6.4;
    contactLines.forEach((cl) => {
        doc.text(cl, x + 2, cY);
        cY += 2.8;
    });

    // C6:E6: Header Tabel Gizi
    doc.setFillColor(...colYellowHead);
    doc.rect(x + wAB, yR6, wC, hR6, "FD");
    doc.rect(x + wAB + wC, yR6, wD, hR6, "FD");
    doc.rect(x + wAB + wC + wD, yR6, wE, hR6, "FD");

    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(4.8);
    doc.text("KANDUNGAN GIZI", x + wAB + (wC / 2), yR6 + 2.4, { align: "center" });
    doc.text("PORSI KECIL", x + wAB + wC + (wD / 2), yR6 + 2.4, { align: "center" });
    doc.text("PORSI BESAR", x + wAB + wC + wD + (wE / 2), yR6 + 2.4, { align: "center" });

    const gd = item.giziData || globalConfig.giziData || {};
    const formatVal = (v) => {
        if (v === undefined || v === null || String(v).trim() === "") return "-";
        return String(v);
    };

    const giziRows = [
        { label: "Energi (Kkal)", pk: formatVal(gd.energi_pk), pb: formatVal(gd.energi_pb) },
        { label: "Karbohidrat (g)", pk: formatVal(gd.karbo_pk), pb: formatVal(gd.karbo_pb) },
        { label: "Protein (g)", pk: formatVal(gd.prot_pk), pb: formatVal(gd.prot_pb) },
        { label: "Lemak (g)", pk: formatVal(gd.lmk_pk), pb: formatVal(gd.lmk_pb) },
        { label: "Serat (g)", pk: formatVal(gd.serat_pk), pb: formatVal(gd.serat_pb) },
    ];

    // 1. Gambar seluruh kotak sel gizi terlebih dahulu agar state fill color murni konsisten kuning pastel (#FFF2CC)
    for (let i = 0; i < giziRows.length; i++) {
        const rowY = yR6 + hR6 + (i * hR_gizi);
        doc.setFillColor(...colYellowBody);
        doc.setDrawColor(0, 0, 0);
        doc.setLineWidth(0.2);
        doc.rect(x + wAB, rowY, wC, hR_gizi, "FD");
        doc.rect(x + wAB + wC, rowY, wD, hR_gizi, "FD");
        doc.rect(x + wAB + wC + wD, rowY, wE, hR_gizi, "FD");
    }

    // 2. Tulis teks pada sel gizi (tanpa merusak background warna sel)
    doc.setTextColor(0, 0, 0);
    giziRows.forEach((gr, i) => {
        const rowY = yR6 + hR6 + (i * hR_gizi);
        doc.setFont("helvetica", "normal");
        doc.setFontSize(4.8);
        doc.text(gr.label, x + wAB + wC - 1.5, rowY + 2.1, { align: "right" });

        doc.text(String(gr.pk || "-"), x + wAB + wC + (wD / 2), rowY + 2.1, { align: "center" });
        doc.text(String(gr.pb || "-"), x + wAB + wC + wD + (wE / 2), rowY + 2.1, { align: "center" });
    });

    // ================= 6. ROW 12: FOOTER PERINGATAN =================
    const yR12 = yR6 + hR6_11;
    doc.setFillColor(...colGoldWarning);
    doc.setLineWidth(0.3);
    doc.rect(x, yR12, cardW, hR12, "FD");

    doc.setFont("helvetica", "bold");
    doc.setFontSize(5.2);
    const teks1 = globalConfig.teksLaranganHeader || "TIDAK BOLEH DIBAWA PULANG.";
    const teks2 = globalConfig.teksLaranganSub || "SEGERA KONSUMSI SETELAH DITERIMA!";
    doc.text(teks1, x + (cardW / 2), yR12 + 2.6, { align: "center" });
    doc.text(teks2, x + (cardW / 2), yR12 + 5.2, { align: "center" });
}

/**
 * Download Template 02 - Pure Text Vector PDF (A4 Sheet Mode: 3 cols x 3 rows = 9 labels)
 */
export async function downloadPdfTemplate02A4GridMode({
    printableItems = [],
    printableKelompokList = [],
    customCount = null,
    globalConfig = {},
    filename = "Label_BGN_Template02_A4.pdf",
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total =
        customCount && Number(customCount) > 0
            ? parseInt(customCount, 10)
            : list.length;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: "a4",
        compress: true,
    });

    // Grid coordinates on A4 Landscape (297 x 210 mm)
    const cardW = 88.0;
    const cardH = 61.05;
    const gapX = 5.0;
    const startX = 11.5;
    const gapY = 4.0;
    const startY = 9.5;
    const labelsPerPage = 9;

    const totalPages = calculateA4TotalPages(list, total, labelsPerPage);
    const startTime = Date.now();

    let currentSlot = 0;
    let currentPage = 1;

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        const prevItem = i > 0 ? list[(i - 1) % list.length] : null;
        const isTransition =
            prevItem &&
            getLabelGroupKey(prevItem) !== getLabelGroupKey(item);

        if (isTransition && currentSlot !== 0) {
            doc.addPage("a4", "landscape");
            currentSlot = 0;
            currentPage++;
        } else if (currentSlot >= labelsPerPage) {
            doc.addPage("a4", "landscape");
            currentSlot = 0;
            currentPage++;
        }

        const col = currentSlot % 3;
        const row = Math.floor(currentSlot / 3);

        const x = startX + col * (cardW + gapX);
        const y = startY + row * (cardH + gapY);

        drawTemplate02Card(doc, x, y, item, globalConfig);

        currentSlot++;

        if (i % 9 === 0 || i === total - 1) {
            const elapsed = (Date.now() - startTime) / 1000;
            const speed = (i + 1) / Math.max(0.05, elapsed);
            const remaining = total - (i + 1);
            const etaSec = Math.ceil(remaining / Math.max(1, speed));
            const percentage = Math.min(
                95,
                Math.round(((i + 1) / total) * 95),
            );

            onProgress({
                phase: "assembly",
                current: i + 1,
                total,
                totalPages,
                currentPage,
                percentage,
                etaText: formatDetailedEta(etaSec),
                speedText: `${Math.round(speed)} label/dtk`,
                message: `Menyusun teks vektor A4 label ${i + 1} dari ${total} (Hal. ${currentPage}/${totalPages})...`,
            });

            await new Promise((resolve) => setTimeout(resolve, 0));
        }
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    onProgress({
        phase: "saving",
        current: total,
        total,
        totalPages,
        currentPage: totalPages,
        percentage: 98,
        etaText: "Menyimpan file...",
        speedText: "",
        message: `Menyimpan file PDF Teks Murni Template 02 (${totalPages} Halaman)...`,
    });

    doc.save(filename);

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages,
        currentPage: totalPages,
        percentage: 100,
        etaText: "Selesai!",
        speedText: "",
        message: `File PDF Template 02 (${total} Label, ${totalPages} Hal) berhasil didownload!`,
    });
}

/**
 * Download Template 02 - Pure Text Vector PDF (Single Label: 88mm x 61mm per Page)
 */
export async function downloadPdfTemplate02SingleMode({
    printableItems = [],
    printableKelompokList = [],
    customCount = null,
    globalConfig = {},
    filename = "Label_BGN_Template02_Tunggal.pdf",
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total =
        customCount && Number(customCount) > 0
            ? parseInt(customCount, 10)
            : list.length;

    const pageW = 88.0;
    const pageH = 61.05;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: [pageW, pageH],
        compress: true,
    });

    const startTime = Date.now();

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        if (i > 0) {
            doc.addPage([pageW, pageH], "landscape");
        }

        drawTemplate02Card(doc, 0, 0, item, globalConfig);

        if (i % 25 === 0 || i === total - 1) {
            const elapsed = (Date.now() - startTime) / 1000;
            const speed = (i + 1) / Math.max(0.05, elapsed);
            const remaining = total - (i + 1);
            const etaSec = Math.ceil(remaining / Math.max(1, speed));
            const percentage = Math.min(
                95,
                Math.round(((i + 1) / total) * 95),
            );

            onProgress({
                phase: "assembly",
                current: i + 1,
                total,
                totalPages: total,
                currentPage: i + 1,
                percentage,
                etaText: formatDetailedEta(etaSec),
                speedText: `${Math.round(speed)} label/dtk`,
                message: `Menyusun teks vektor label ${i + 1} dari ${total}...`,
            });

            await new Promise((resolve) => setTimeout(resolve, 0));
        }
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    doc.save(filename);

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages: total,
        currentPage: total,
        percentage: 100,
        etaText: "Selesai!",
        speedText: "",
        message: `File PDF Template 02 Tunggal (${total} Label) berhasil didownload!`,
    });
}

/**
 * Direct Print Template 02 - Pure Text Vector PDF
 */
export async function printPdfTemplate02SingleMode({
    printableItems = [],
    printableKelompokList = [],
    globalConfig = {},
    isCancelled = () => false,
    onProgress = () => {},
}) {
    const list = printableItems.length > 0 ? printableItems : printableKelompokList;
    if (!list || list.length === 0) {
        throw new Error("Tidak ada kelompok sasaran yang dipilih.");
    }

    const total = list.length;
    const pageW = 88.0;
    const pageH = 61.05;

    const doc = new jsPDF({
        orientation: "landscape",
        unit: "mm",
        format: [pageW, pageH],
        compress: true,
    });

    const startTime = Date.now();

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        if (i > 0) {
            doc.addPage([pageW, pageH], "landscape");
        }

        drawTemplate02Card(doc, 0, 0, item, globalConfig);

        const elapsed = (Date.now() - startTime) / 1000;
        const speed = (i + 1) / Math.max(0.05, elapsed);
        const remaining = total - (i + 1);
        const etaSec = Math.ceil(remaining / Math.max(1, speed));
        const percentage = Math.min(
            95,
            Math.round(((i + 1) / total) * 95),
        );

        onProgress({
            phase: "assembly",
            current: i + 1,
            total,
            totalPages: total,
            currentPage: i + 1,
            percentage,
            etaText: formatDetailedEta(etaSec),
            speedText: `${Math.round(speed)} label/dtk`,
            message: `Menyiapkan label teks vektor ${i + 1} dari ${total} untuk dicetak...`,
        });

        await new Promise((resolve) => setTimeout(resolve, 0));
    }

    if (isCancelled && isCancelled()) {
        const err = new Error("Proses dibatalkan.");
        err.name = "AbortError";
        throw err;
    }

    onProgress({
        phase: "done",
        current: total,
        total,
        totalPages: total,
        currentPage: total,
        percentage: 100,
        etaText: "Siap cetak!",
        speedText: "",
        message: "Membuka dialog cetak Template 02...",
    });

    doc.autoPrint();
    const blobUrl = doc.output("bloburl");
    const printFrame = document.createElement("iframe");
    printFrame.style.position = "fixed";
    printFrame.style.right = "0";
    printFrame.style.bottom = "0";
    printFrame.style.width = "0";
    printFrame.style.height = "0";
    printFrame.style.border = "0";
    printFrame.src = blobUrl;
    document.body.appendChild(printFrame);
    printFrame.onload = () => {
        try {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        } catch (e) {
            window.open(blobUrl, "_blank");
        }
    };
}
