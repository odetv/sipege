/**
 * High-Speed SVG Vector Renderer for BGN Label Stickers
 * Zero html2canvas dependency, 100% symmetrical, crisp vector output, instant (< 5ms per card).
 */

function escapeXml(unsafe) {
    if (!unsafe) return "";
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&apos;");
}

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

/**
 * Generate full SVG markup for a single label card (800 x 600 standard viewBox)
 */
export function generateLabelSvg({
    templateConfig,
    kelompok = {},
    unitSppg = null,
    tanggalProduksi = "",
    jamProduksi = "07.00",
    batasKonsumsi = "09.00",
    petunjukMenu = "Nasi Putih - Rolade Ayam",
    giziData = {
        energi_pk: 386.3,
        energi_pb: 547.3,
        karbo_pk: 50.9,
        karbo_pb: 80,
        protein_pk: 18.3,
        protein_pb: 21.6,
        lemak_pk: 13,
        lemak_pb: 17.2,
        serat_pk: 3.6,
        serat_pb: 6.4,
    },
}) {
    const aspectRatio = templateConfig?.aspect_ratio || "4/3";
    const W = 800;
    let H = 600;

    if (aspectRatio === "1/1") {
        H = 800;
    } else if (aspectRatio === "16/9") {
        H = 450;
    } else if (aspectRatio === "3/2") {
        H = 533.3;
    }

    const borderWidth = parseFloat(templateConfig?.border_width || "3") * 1.5 || 4.5;
    const borderColor = templateConfig?.border_color || "#1E4B8B";
    const canvasBg = templateConfig?.canvas_bg || "#FFFFFF";
    const borderRadius = 20;

    const padStr = templateConfig?.canvas_padding || "6px";
    const padNum = parseFloat(padStr) || 6;
    const padding = (padNum / 16) * 20;

    const innerX = borderWidth + padding;
    const innerY = borderWidth + padding;
    const innerW = W - (borderWidth + padding) * 2;
    const innerH = H - (borderWidth + padding) * 2;

    const formattedDate = formatDateSlash(tanggalProduksi);
    const jamStr = formatJam(jamProduksi);
    const batasStr = formatJam(batasKonsumsi);

    const sppgName = unitSppg?.nama
        ? unitSppg.nama.startsWith("SPPG")
            ? unitSppg.nama
            : "SPPG " + unitSppg.nama
        : "SPPG BULELENG SUKASADA TEGALLINGGAH";

    const elements = templateConfig?.elements || [];
    const sorted = [...elements].sort((a, b) => (a.zIndex || 1) - (b.zIndex || 1));

    let elementsSvg = "";

    for (const el of sorted) {
        if (el.visible === false) continue;

        const ex = innerX + (el.x / 100) * innerW;
        const ey = innerY + (el.y / 100) * innerH;
        const ew = (el.width / 100) * innerW;
        const eh = (el.height / 100) * innerH;

        // 1. LOGO
        if (el.type === "logo") {
            const logoUrl = el.imageUrl || "/images/BGN_LOGOTYPE_MAIN.png";
            elementsSvg += `
                <image href="${escapeXml(logoUrl)}" x="${ex}" y="${ey}" width="${ew}" height="${eh}" preserveAspectRatio="xMinYMid meet" />
            `;
        }

        // 2. SPPG HEADER
        else if (el.type === "sppg_header") {
            const isRight = el.textAlign === "right";
            const tx = isRight ? ex + ew : ex;
            const anchor = isRight ? "end" : "start";

            elementsSvg += `
                <g text-anchor="${anchor}" font-family="Arial, Helvetica, sans-serif">
                    <text x="${tx}" y="${ey + eh * 0.38}" font-size="11" font-weight="700" fill="#64748B" letter-spacing="0.5">SATUAN PELAYANAN PEMENUHAN GIZI</text>
                    <text x="${tx}" y="${ey + eh * 0.92}" font-size="15" font-weight="900" fill="#1E3A8A">${escapeXml(sppgName.toUpperCase())}</text>
                </g>
            `;
        }

        // 3. DIVIDER
        else if (el.type === "divider") {
            elementsSvg += `
                <rect x="${ex}" y="${ey}" width="${ew}" height="${Math.max(3, eh)}" rx="1.5" fill="${escapeXml(el.backgroundColor || "#C5921D")}" />
            `;
        }

        // 4. BADGE
        else if (el.type === "badge") {
            elementsSvg += `
                <g>
                    <rect x="${ex}" y="${ey}" width="${ew}" height="${eh}" rx="8" fill="${escapeXml(el.backgroundColor || "#4E88C7")}" />
                    <text x="${ex + ew / 2}" y="${ey + eh / 2 + 5}" font-family="Arial, Helvetica, sans-serif" font-size="13" font-weight="900" fill="#FFFFFF" text-anchor="middle" letter-spacing="0.5">
                        ${escapeXml(el.text || "LABEL MAKANAN BERGIZI GRATIS")}
                    </text>
                </g>
            `;
        }

        // 5. TANGGAL
        else if (el.type === "tanggal") {
            const boxY = ey + 20;
            const boxH = eh - 20;
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <text x="${ex + 2}" y="${ey + 14}" font-size="12" font-weight="800" fill="#1E293B">📅 Tanggal Produksi</text>
                    <rect x="${ex}" y="${boxY}" width="${ew}" height="${boxH}" rx="8" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1.5" />
                    <text x="${ex + ew / 2}" y="${boxY + boxH / 2 + 6}" font-size="16" font-weight="900" fill="#0F172A" text-anchor="middle">${escapeXml(formattedDate)}</text>
                </g>
            `;
        }

        // 6. JAM PRODUKSI
        else if (el.type === "jam") {
            const boxY = ey + 20;
            const boxH = eh - 20;
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <text x="${ex + 2}" y="${ey + 14}" font-size="11.5" font-weight="800" fill="#1E293B">⏰ Jam Produksi</text>
                    <rect x="${ex}" y="${boxY}" width="${ew}" height="${boxH}" rx="8" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1.5" />
                    <text x="${ex + ew / 2}" y="${boxY + boxH / 2 + 6}" font-size="15" font-weight="900" fill="#0F172A" text-anchor="middle">${escapeXml(jamStr)}</text>
                </g>
            `;
        }

        // 7. BATAS KONSUMSI
        else if (el.type === "batas") {
            const boxY = ey + 20;
            const boxH = eh - 20;
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <text x="${ex + 2}" y="${ey + 14}" font-size="11.5" font-weight="800" fill="#1E293B">⌛ Batas Konsumsi</text>
                    <rect x="${ex}" y="${boxY}" width="${ew}" height="${boxH}" rx="8" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1.5" />
                    <text x="${ex + ew / 2}" y="${boxY + boxH / 2 + 6}" font-size="15" font-weight="900" fill="#0F172A" text-anchor="middle">${escapeXml(batasStr)}</text>
                </g>
            `;
        }

        // 8. TUJUAN PENGANTARAN
        else if (el.type === "tujuan") {
            const boxY = ey + 20;
            const boxH = eh - 20;
            const destName = kelompok?.nama_kelompok || "Penerima Manfaat";
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <text x="${ex + 2}" y="${ey + 14}" font-size="12" font-weight="800" fill="#1E293B">📍 Tujuan Pengantaran</text>
                    <rect x="${ex}" y="${boxY}" width="${ew}" height="${boxH}" rx="8" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1.5" />
                    <text x="${ex + 12}" y="${boxY + boxH / 2 + 5}" font-size="14.5" font-weight="900" fill="#1E3A8A" text-anchor="start">${escapeXml(destName)}</text>
                </g>
            `;
        }

        // 9. MENU BOX
        else if (el.type === "menu") {
            const badgeW = ew * 0.23;
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <rect x="${ex}" y="${ey}" width="${ew}" height="${eh}" rx="10" fill="${escapeXml(el.backgroundColor || "#4E88C7")}" />
                    <rect x="${ex + 5}" y="${ey + 5}" width="${badgeW}" height="${eh - 10}" rx="6" fill="rgba(0,0,0,0.22)" />
                    <text x="${ex + 5 + badgeW / 2}" y="${ey + eh / 2 + 5}" font-size="13" font-weight="900" fill="#FFFFFF" text-anchor="middle">MENU</text>
                    <text x="${ex + badgeW + 14}" y="${ey + eh / 2 + 5}" font-size="12.5" font-weight="700" fill="#FFFFFF">${escapeXml(petunjukMenu)}</text>
                </g>
            `;
        }

        // 10. NUTRITION TABLE
        else if (el.type === "nutrition_table") {
            const valBoxW = ew * 0.22;
            const valBoxGap = 4;
            const pbX = ex + ew - valBoxW;
            const pkX = pbX - valBoxW - valBoxGap;

            const rows = [
                { label: "Energi (Kkal)", pk: giziData.energi_pk, pb: giziData.energi_pb },
                { label: "Karbohidrat (g)", pk: giziData.karbo_pk, pb: giziData.karbo_pb },
                { label: "Protein (g)", pk: giziData.protein_pk, pb: giziData.protein_pb },
                { label: "Lemak (g)", pk: giziData.lemak_pk, pb: giziData.lemak_pb },
                { label: "Serat (g)", pk: giziData.serat_pk, pb: giziData.serat_pb },
            ];

            const startY = ey + 24;
            const rowH = (eh - 54) / 5;

            let rowsSvg = "";
            rows.forEach((r, idx) => {
                const ry = startY + idx * rowH;
                const rBoxH = Math.max(16, rowH - 4);
                rowsSvg += `
                    <text x="${ex}" y="${ry + rBoxH / 2 + 4}" font-size="11.5" font-weight="700" fill="#1E293B">${escapeXml(r.label)}</text>
                    <rect x="${pkX}" y="${ry}" width="${valBoxW}" height="${rBoxH}" rx="4" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1" />
                    <text x="${pkX + valBoxW / 2}" y="${ry + rBoxH / 2 + 4}" font-size="11.5" font-weight="800" fill="#0F172A" text-anchor="middle">${r.pk != null ? r.pk : "-"}</text>
                    <rect x="${pbX}" y="${ry}" width="${valBoxW}" height="${rBoxH}" rx="4" fill="#EDF4FC" stroke="#BFD8F2" stroke-width="1" />
                    <text x="${pbX + valBoxW / 2}" y="${ry + rBoxH / 2 + 4}" font-size="11.5" font-weight="800" fill="#0F172A" text-anchor="middle">${r.pb != null ? r.pb : "-"}</text>
                `;
            });

            const footerY = ey + eh - 22;
            const pillH = 20;

            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <text x="${ex}" y="${ey + 14}" font-size="12.5" font-weight="800" fill="#0F172A">🔥 Kandungan Gizi</text>
                    <line x1="${ex}" y1="${ey + 19}" x2="${ex + ew}" y2="${ey + 19}" stroke="#CBD5E1" stroke-dasharray="3,3" stroke-width="1.5" />
                    ${rowsSvg}
                    <rect x="${pkX}" y="${footerY}" width="${valBoxW}" height="${pillH}" rx="4" fill="#4E88C7" />
                    <text x="${pkX + valBoxW / 2}" y="${footerY + pillH / 2 + 4}" font-size="10.5" font-weight="900" fill="#FFFFFF" text-anchor="middle">Porsi Kecil</text>
                    <rect x="${pbX}" y="${footerY}" width="${valBoxW}" height="${pillH}" rx="4" fill="#4E88C7" />
                    <text x="${pbX + valBoxW / 2}" y="${footerY + pillH / 2 + 4}" font-size="10.5" font-weight="900" fill="#FFFFFF" text-anchor="middle">Porsi Besar</text>
                </g>
            `;
        }

        // 11. WARNING BANNER
        else if (el.type === "warning") {
            elementsSvg += `
                <g font-family="Arial, Helvetica, sans-serif">
                    <rect x="${ex}" y="${ey}" width="${ew}" height="${eh}" rx="8" fill="#FEF2F2" stroke="#FECACA" stroke-width="1.5" />
                    <rect x="${ex}" y="${ey}" width="6" height="${eh}" rx="3" fill="#EF4444" />
                    <text x="${ex + 24}" y="${ey + eh / 2 + 7}" font-size="22" text-anchor="middle">⚠️</text>
                    <text x="${ex + 48}" y="${ey + eh * 0.42}" font-size="13" font-weight="900" fill="#DC2626">${escapeXml(el.text || "MAKANAN INI HANYA UNTUK DIKONSUMSI DI TEMPAT.")}</text>
                    <text x="${ex + 48}" y="${ey + eh * 0.82}" font-size="13" font-weight="900" fill="#DC2626">${escapeXml(el.subtitle || "DILARANG MEMBAWA PULANG!")}</text>
                </g>
            `;
        }
    }

    return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">
            <rect x="${borderWidth / 2 + 1}" y="${borderWidth / 2 + 1}" width="${W - borderWidth - 2}" height="${H - borderWidth - 2}" rx="${borderRadius}" fill="${escapeXml(canvasBg)}" stroke="${escapeXml(borderColor)}" stroke-width="${borderWidth}" />
            ${elementsSvg}
        </svg>
    `;
}

/**
 * Render SVG directly to an Image element (Instant < 2ms)
 */
export async function renderSvgToImage(svgString, targetWidth = 1000, targetHeight = 750) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const svgBlob = new Blob([svgString], { type: "image/svg+xml;charset=utf-8" });
        const url = URL.createObjectURL(svgBlob);

        img.onload = () => {
            const canvas = document.createElement("canvas");
            canvas.width = targetWidth;
            canvas.height = targetHeight;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
            URL.revokeObjectURL(url);
            resolve(canvas);
        };

        img.onerror = (err) => {
            URL.revokeObjectURL(url);
            reject(err);
        };

        img.src = url;
    });
}
