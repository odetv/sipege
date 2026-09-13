import jsPDF from "jspdf";
import html2canvas from "html2canvas";

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

    const rect = element.getBoundingClientRect();
    const elementW = Math.round(rect.width) || 555;
    const elementH = Math.round(rect.height) || 370;

    return await html2canvas(element, {
        scale: 2.5, // 2.5x gives ~400 DPI crystal clarity & blazing speed
        useCORS: true,
        allowTaint: true,
        backgroundColor: "#ffffff",
        logging: false,
        scrollX: 0,
        scrollY: 0,
        windowWidth: elementW,
        windowHeight: elementH,
        width: elementW,
        height: elementH,
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

    const uniqueMap = new Map();
    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        const k = item.kelompok;
        const tipe = item.tipeLabel || "normal";
        const alergi = item.jenisAlergi || "";
        const key = `${k?.id || i}_${tipe}_${alergi}_${k?.nama_kelompok || k?.nama || ""}`;
        if (!uniqueMap.has(key)) {
            uniqueMap.set(key, {
                key,
                renderItem: item,
                kelompok: k,
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
            const isItem = typeof itemOrKelompok === "object" && ("tipeLabel" in itemOrKelompok || "kelompok" in itemOrKelompok);
            const k = isItem ? itemOrKelompok.kelompok : itemOrKelompok;
            const tipe = isItem ? (itemOrKelompok.tipeLabel || "normal") : "normal";
            const alergi = isItem ? (itemOrKelompok.jenisAlergi || "") : "";
            const key = `${k?.id || idx}_${tipe}_${alergi}_${k?.nama_kelompok || k?.nama || ""}`;
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
    const totalPages = Math.ceil(total / labelsPerPage);

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

    for (let i = 0; i < total; i++) {
        if (isCancelled && isCancelled()) {
            const err = new Error("Proses dibatalkan.");
            err.name = "AbortError";
            throw err;
        }

        const item = list[i % list.length];
        const template = getTemplate(item, i);

        const pageIndex = Math.floor(i / labelsPerPage);
        const slotIndex = i % labelsPerPage;

        if (pageIndex > 0 && slotIndex === 0) {
            doc.addPage("a4", "landscape");
        }

        const col = slotIndex % 3;
        const row = Math.floor(slotIndex / 3);

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

        if (i % updateInterval === 0 || i === total - 1) {
            const elapsedPhase2 = (Date.now() - phase2Start) / 1000;
            const speed = (i + 1) / Math.max(0.05, elapsedPhase2);
            const remaining = total - (i + 1);
            const etaSec = Math.ceil(remaining / Math.max(1, speed));
            const percentage = Math.min(
                95,
                75 + Math.round(((i + 1) / total) * 20),
            );
            const currentPage = Math.floor(i / labelsPerPage) + 1;

            onProgress({
                phase: "assembly",
                current: i + 1,
                total,
                totalPages,
                currentPage,
                percentage,
                etaText: formatDetailedEta(etaSec),
                speedText: `${Math.round(speed)} label/dtk`,
                message: `Menyusun lembar A4 label ${i + 1} dari ${total} (Hal. ${currentPage}/${totalPages})...`,
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
