import ExcelJS from "exceljs";

const RESTRICTED_CHARACTERS = [
    ",", "-", "(", ")", "/", ":", "'", "+", "?",
    "%", "&", "*", "!", "@", "#", "$", "^", "_",
    "=", "[", "]", "{", "}", ";", '"', "<", ">",
    "`", "~", "|", "\\",
];

export function sanitizeBniText(text, maxLen = 40) {
    if (!text) return "";
    let cleaned = String(text);
    RESTRICTED_CHARACTERS.forEach((c) => {
        cleaned = cleaned.split(c).join(" ");
    });
    cleaned = cleaned.replace(/\s+/g, " ").trim();
    return cleaned.slice(0, maxLen);
}

export function splitBniRemarks(fullRemark) {
    const cleaned = sanitizeBniText(fullRemark || "", 83);
    if (!cleaned) {
        return { remark1: "", remark2: "" };
    }
    if (cleaned.length <= 33) {
        return { remark1: cleaned, remark2: "" };
    }
    const slice33 = cleaned.slice(0, 33);
    const lastSpace = slice33.lastIndexOf(" ");
    if (lastSpace > 15) {
        const rem1 = cleaned.slice(0, lastSpace).trim();
        const rem2 = cleaned.slice(lastSpace + 1, lastSpace + 1 + 50).trim();
        return { remark1: rem1, remark2: rem2 };
    }
    const rem1 = cleaned.slice(0, 33).trim();
    const rem2 = cleaned.slice(33, 33 + 50).trim();
    return { remark1: rem1, remark2: rem2 };
}

/**
 * Download BNI Direct Excel Template pre-populated with active payroll items.
 */
export async function downloadBniDirectTemplateExcel({
    items = [],
    rekDebet = "5268080021123800",
    tglTransaksi = "",
    remark = "",
    templateUrl = "/templates/BNIDIRECT-EXCEL_TEMPLATE_v1.9.6.xlsx",
}) {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, "0");

    const YYYY = now.getFullYear();
    const MM = pad(now.getMonth() + 1);
    const DD = pad(now.getDate());
    const HH = pad(now.getHours());
    const mm = pad(now.getMinutes());
    const ss = pad(now.getSeconds());

    const timestampCreation = `${YYYY}/${MM}/${DD}_${HH}:${mm}:${ss}`;
    const timestampFile = `${YYYY}${MM}${DD}_${HH}${mm}${ss}`;

    const cleanRekDebet = String(rekDebet).replace(/\D/g, "").slice(0, 16) || "5268080021123800";
    const cleanTglTransaksi = tglTransaksi
        ? tglTransaksi.replace(/-/g, "")
        : `${YYYY}${MM}${DD}`;
    const { remark1: cleanRemark1, remark2: cleanRemark2 } = splitBniRemarks(remark);

    const validItems = items.filter((r) => (parseInt(r.amount, 10) || 0) > 0);
    const totalRecords = validItems.length;
    const totalAmount = validItems.reduce((sum, r) => sum + (parseInt(r.amount, 10) || 0), 0);

    const workbook = new ExcelJS.Workbook();
    let loadedFromTemplate = false;

    try {
        const resp = await fetch(templateUrl);
        if (resp.ok) {
            const arrayBuffer = await resp.arrayBuffer();
            await workbook.xlsx.load(arrayBuffer);
            loadedFromTemplate = true;
        }
    } catch (e) {
        console.warn("Gagal memuat template resmi dari URL, fallback ke pembuatan manual:", e);
    }

    let ws = workbook.getWorksheet("Inhouse");

    if (!loadedFromTemplate || !ws) {
        // Fallback: buat sheet Inhouse persis format resmi BNI Direct
        ws = workbook.addWorksheet("Inhouse");

        // Column widths
        const colWidths = [18.5, 32, 16, 25, 25, 15, 15, 20, 20, 15, 15, 15, 15, 15, 15, 15, 12, 25, 15, 10];
        colWidths.forEach((w, idx) => {
            ws.getColumn(idx + 1).width = w;
        });

        // Instructions
        ws.getCell("B1").value = "Langkah Penggunaan:";
        ws.getCell("C1").value = "1. Lakukan pengisian tabel di bawah, kolom merah wajib diisi";
        ws.getCell("C2").value = "2. Klik tombol Create CSV. Lokasi File CSV = Lokasi Template Excel";
        ws.getCell("C3").value = "3. Total Record Max 5000";

        // Row 5 & 6
        ws.getCell("A5").value = "File Creation(auto)";
        ws.getCell("A5").font = { bold: true };
        ws.getCell("A5").fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFC4D79B" } };
        ws.getCell("C5").value = "Nama File";
        ws.getCell("C5").font = { bold: true };
        ws.getCell("C5").fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFC4D79B" } };

        // Row 7 Headers
        const r7Headers = [
            { col: 1, text: "P(auto)", red: false },
            { col: 2, text: "Tgl Transaksi", red: false },
            { col: 3, text: "Rek. Debet(16)", red: true },
            { col: 4, text: "Total Record(auto)", red: false },
            { col: 5, text: "Total Amount(auto)", red: false },
        ];
        r7Headers.forEach((h) => {
            const cell = ws.getRow(7).getCell(h.col);
            cell.value = h.text;
            cell.font = { bold: true, color: { argb: h.red ? "FFFF0000" : "FF000000" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFC4D79B" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        // Row 9 Column Headers
        const r9Headers = [
            { text: "Rek. Tujuan(16)", red: true },
            { text: "Nama Penerima(40)", red: true },
            { text: "Amount", red: true },
            { text: "Remark1(33)", red: false },
            { text: "Remark2(50)", red: false },
            { text: "Remark3(50)", red: false },
            { text: "KODEBANK(8)--(M)", red: false },
            { text: "NAMA BANK TUJUAN(100)--(M)", red: false },
            { text: "NAMA CABANG(100)", red: false },
            { text: "ALAMAT BANK1(50)", red: false },
            { text: "ALAMAT BANK2(50)", red: false },
            { text: "ALAMAT BANK3(50)", red: false },
            { text: "NAMA KOTA(100)", red: false },
            { text: "NAMA NEGARA(100)", red: false },
            { text: "WARGA NEGARA(40)", red: false },
            { text: "KODE WN(40)", red: false },
            { text: "EMAIL FLAG(1)", red: false },
            { text: "Email(100)", red: false },
            { text: "Reff Num(16)", red: false },
            { text: "FLAG(1)", red: false },
        ];
        r9Headers.forEach((h, idx) => {
            const cell = ws.getRow(9).getCell(idx + 1);
            cell.value = h.text;
            cell.font = { bold: true, color: { argb: h.red ? "FFFF0000" : "FF000000" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFC4D79B" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });
    }

    // Set Creation Timestamp at A6
    ws.getCell("A6").value = timestampCreation;
    ws.getCell("A6").alignment = { horizontal: "left" };

    // Set Row 8 Meta Values
    ws.getCell("A8").value = "P";
    ws.getCell("A8").alignment = { horizontal: "center" };

    ws.getCell("B8").value = cleanTglTransaksi;
    ws.getCell("B8").alignment = { horizontal: "center" };
    ws.getCell("B8").numFmt = "@";

    ws.getCell("C8").value = cleanRekDebet;
    ws.getCell("C8").alignment = { horizontal: "center" };
    ws.getCell("C8").numFmt = "@";

    ws.getCell("D8").value = totalRecords;
    ws.getCell("D8").alignment = { horizontal: "right" };
    ws.getCell("D8").numFmt = "#,##0";

    ws.getCell("E8").value = totalAmount;
    ws.getCell("E8").alignment = { horizontal: "right" };
    ws.getCell("E8").numFmt = "#,##0.00";

    // Clear old sample data rows (up to row 100)
    for (let r = 10; r <= 150; r++) {
        const row = ws.getRow(r);
        for (let c = 1; c <= 20; c++) {
            row.getCell(c).value = null;
        }
    }

    const thinBorder = {
        top: { style: "thin", color: { argb: "FFD3D3D3" } },
        left: { style: "thin", color: { argb: "FFD3D3D3" } },
        bottom: { style: "thin", color: { argb: "FFD3D3D3" } },
        right: { style: "thin", color: { argb: "FFD3D3D3" } },
    };

    // Populate data rows starting at row 10
    validItems.forEach((r, idx) => {
        const rowNum = 10 + idx;
        const row = ws.getRow(rowNum);

        const rekTujuan = String(r.petugas?.nomor_rekening || "").replace(/\D/g, "").slice(0, 16);
        const namaClean = sanitizeBniText(r.petugas?.nama || "", 40);
        const amount = parseInt(r.amount, 10) || 0;
        const email = (r.petugas?.email || "").trim();
        const hasEmail = email.includes("@") && email.includes(".");

        // Col 1: Rek. Tujuan(16)
        const cellRek = row.getCell(1);
        cellRek.value = rekTujuan;
        cellRek.numFmt = "@";
        cellRek.alignment = { horizontal: "left" };
        cellRek.border = thinBorder;

        // Col 2: Nama Penerima(40)
        const cellNama = row.getCell(2);
        cellNama.value = namaClean;
        cellNama.alignment = { horizontal: "left" };
        cellNama.border = thinBorder;

        // Col 3: Amount
        const cellAmount = row.getCell(3);
        cellAmount.value = amount;
        cellAmount.numFmt = "#,##0.00";
        cellAmount.alignment = { horizontal: "right" };
        cellAmount.border = thinBorder;

        // Col 4: Remark1(33)
        const cellRem1 = row.getCell(4);
        cellRem1.value = cleanRemark1;
        cellRem1.alignment = { horizontal: "left" };
        cellRem1.border = thinBorder;

        // Col 5: Remark2(50)
        const cellRem2 = row.getCell(5);
        cellRem2.value = cleanRemark2;
        cellRem2.alignment = { horizontal: "left" };
        cellRem2.border = thinBorder;

        // Col 6..16: Optional blanks
        for (let c = 6; c <= 16; c++) {
            const cell = row.getCell(c);
            cell.value = "";
            cell.border = thinBorder;
        }

        // Col 17: EMAIL FLAG(1)
        const cellEmailFlag = row.getCell(17);
        cellEmailFlag.value = hasEmail ? "Y" : "N";
        cellEmailFlag.alignment = { horizontal: "center" };
        cellEmailFlag.border = thinBorder;

        // Col 18: Email(100)
        const cellEmail = row.getCell(18);
        cellEmail.value = hasEmail ? email : "";
        cellEmail.alignment = { horizontal: "left" };
        cellEmail.border = thinBorder;

        // Col 19: Reff Num(16)
        const cellReff = row.getCell(19);
        cellReff.value = "";
        cellReff.border = thinBorder;

        // Col 20: FLAG(1)
        const cellFlag = row.getCell(20);
        cellFlag.value = "N";
        cellFlag.alignment = { horizontal: "center" };
        cellFlag.border = thinBorder;
    });

    // Write to buffer and trigger download
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const filename = `BNIDIRECT-EXCEL_TEMPLATE_Terisi_${timestampFile}.xlsx`;

    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    return {
        filename,
        totalRecords,
        totalAmount,
    };
}
