import ExcelJS from "exceljs";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import {
    LOGO_BGN_RAW_BASE64,
    LOGO_YAYASAN_RAW_BASE64,
} from "@/Services/logoBase64Helper";

const MONTHS_INDO = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

export function formatTanggalIndo(dateObj) {
    if (!dateObj) return "-";
    try {
        const d = new Date(dateObj);
        if (isNaN(d.getTime())) return String(dateObj);
        const day = d.getDate();
        const month = MONTHS_INDO[d.getMonth()];
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
    } catch {
        return String(dateObj);
    }
}

export function formatTglDdMmYyyy(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return String(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    } catch {
        return String(dateStr);
    }
}

export function colToLetter(n) {
    let s = "";
    while (n > 0) {
        let m = (n - 1) % 26;
        s = String.fromCharCode(65 + m) + s;
        n = Math.floor((n - m) / 26);
    }
    return s;
}

/**
 * Helper untuk menerapkan Kop Surat Resmi SPPG di Excel
 * Sesuai contoh pada Rekap Kehadiran (Logo BGN kiri, Logo Yayasan kanan simetris dan teks dinamis)
 */
function applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg = null) {
    const kopConfig = getActiveKopConfig(unitSppg);
    const lastColLetter = colToLetter(maxCols);

    sheet.getRow(1).height = 24;
    sheet.getRow(2).height = 18;
    sheet.getRow(3).height = 15;
    sheet.getRow(4).height = 15;

    sheet.mergeCells(`A1:${lastColLetter}1`);
    sheet.getCell('A1').value = kopConfig?.nama_instansi_1 || 'SPPG BULELENG SUKASADA TEGALLINGGAH';
    sheet.getCell('A1').font = { name: 'Arial', size: 13, bold: true };
    sheet.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A2:${lastColLetter}2`);
    sheet.getCell('A2').value = kopConfig?.nama_instansi_2 || 'YAYASAN PESANTREN MIFTAHUL ULUM';
    sheet.getCell('A2').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A2').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A3:${lastColLetter}3`);
    sheet.getCell('A3').value = kopConfig?.alamat_lengkap || 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';
    sheet.getCell('A3').font = { name: 'Arial', size: 9 };
    sheet.getCell('A3').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A4:${lastColLetter}4`);
    sheet.getCell('A4').value = `E-mail: ${kopConfig?.email || 'sppgsukasadategallinggah@gmail.com'}${kopConfig?.telepon ? ' | Telp: ' + kopConfig.telepon : ''}`;
    sheet.getCell('A4').font = { name: 'Arial', size: 9, italic: true };
    sheet.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    // Garis pemisah bawah kop (hitam medium)
    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    // Offset logo simetris berdasarkan lebar pixel kolom
    const colPxOffsets = [0];
    let totalPx = 0;
    for (let c = 1; c <= maxCols; c++) {
        const w = sheet.getColumn(c).width || 12;
        const px = Math.floor((w * 7.5) + 5);
        totalPx += px;
        colPxOffsets.push(totalPx);
    }

    function findColAndOffset(targetPx) {
        for (let c = 0; c < colPxOffsets.length - 1; c++) {
            if (targetPx >= colPxOffsets[c] && targetPx < colPxOffsets[c + 1]) {
                const offPx = Math.round(targetPx - colPxOffsets[c]);
                return { nativeCol: c, nativeColOff: Math.max(0, offPx) * 9525 };
            }
        }
        return { nativeCol: Math.max(0, colPxOffsets.length - 2), nativeColOff: 0 };
    }

    const marginPx = 14;
    const logoW = 56;
    const logoH = 56;
    const targetBgnPx = marginPx;
    const targetYayasanPx = Math.max(0, totalPx - marginPx - logoW);

    const bgnPos = findColAndOffset(targetBgnPx);
    const yayasanPos = findColAndOffset(targetYayasanPx);
    const nativeRowOff = 8 * 9525;

    if (logoBgnId !== null) {
        try {
            sheet.addImage(logoBgnId, {
                tl: { nativeCol: bgnPos.nativeCol, nativeColOff: bgnPos.nativeColOff, nativeRow: 0, nativeRowOff },
                ext: { width: logoW, height: logoH },
            });
        } catch (e) {
            console.warn('Gagal menempelkan Logo BGN:', e);
        }
    }
    if (logoYayasanId !== null) {
        try {
            sheet.addImage(logoYayasanId, {
                tl: { nativeCol: yayasanPos.nativeCol, nativeColOff: yayasanPos.nativeColOff, nativeRow: 0, nativeRowOff },
                ext: { width: logoW, height: logoH },
            });
        } catch (e) {
            console.warn('Gagal menempelkan Logo Yayasan:', e);
        }
    }
}

/**
 * Download Laporan Residu & Limbah Harian dalam format Excel (ExcelJS)
 * Dilengkapi Kop resmi (Logo BGN & Logo Yayasan dinamis) dan 4 TTD sejajar 1 baris
 * (Kata "Mengetahui," pada Kepala SPPG diletakkan lebih tinggi di atas baris jabatan)
 */
export async function downloadRekapResiduExcel({
    residuList = [],
    summary = {},
    startDate = null,
    endDate = null,
    unitSppg = null,
    pejabatTtd = {},
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = "SIPEGE SPPG Buleleng";
    workbook.created = new Date();

    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        logoBgnId = workbook.addImage({
            base64: LOGO_BGN_RAW_BASE64,
            extension: "png",
        });
        logoYayasanId = workbook.addImage({
            base64: LOGO_YAYASAN_RAW_BASE64,
            extension: "png",
        });
    } catch (e) {
        console.warn("Gagal memuat logo ke workbook Excel:", e);
    }

    const sheet = workbook.addWorksheet("Pencatatan Limbah SPPG", {
        pageSetup: {
            orientation: "landscape",
            paperSize: 9,
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
        },
    });

    const maxCols = 10;
    const lastColLetter = colToLetter(maxCols);

    // ─── 1. LEBAR KOLOM (DITENTUKAN DULU AGAR OFFSET LOGO PRESISI) ───
    sheet.columns = [
        { width: 6 },   // 1. No
        { width: 14 },  // 2. Tanggal
        { width: 34 },  // 3. Nama Limbah
        { width: 12 },  // 4. Jumlah
        { width: 10 },  // 5. Satuan
        { width: 16 },  // 6. Jenis Limbah
        { width: 18 },  // 7. Sumber
        { width: 22 },  // 8. Metode Penanganan
        { width: 20 },  // 9. Status Bahaya
        { width: 30 },  // 10. Keterangan
    ];

    // ─── 2. TERAPKAN KOP SURAT RESMI IDENTIK REKAP KEHADIRAN ───
    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    // ─── 3. JUDUL DOKUMEN ───
    sheet.getRow(6).height = 20;
    sheet.mergeCells(`A6:${lastColLetter}6`);
    sheet.getCell("A6").value = "FORMULIR PENCATATAN RESIDU & LIMBAH HARIAN SPPG";
    sheet.getCell("A6").font = { name: "Arial", size: 12, bold: true, color: { argb: "FF065F46" } };
    sheet.getCell("A6").alignment = { horizontal: "center", vertical: "middle" };

    sheet.getRow(7).height = 16;
    sheet.mergeCells(`A7:${lastColLetter}7`);
    const rentangStr = startDate && endDate
        ? `Rentang Periode: ${formatTglDdMmYyyy(startDate)} s.d. ${formatTglDdMmYyyy(endDate)}`
        : `Tanggal Cetak: ${formatTanggalIndo(new Date())}`;
    sheet.getCell("A7").value = rentangStr;
    sheet.getCell("A7").font = { name: "Arial", size: 9.5, italic: true, color: { argb: "FF475569" } };
    sheet.getCell("A7").alignment = { horizontal: "center", vertical: "middle" };

    // ─── 4. HEADER TABEL ───
    const headerRow = sheet.getRow(9);
    headerRow.height = 26;
    const headers = [
        "NO",
        "TANGGAL",
        "NAMA LIMBAH",
        "JUMLAH",
        "SATUAN",
        "JENIS LIMBAH",
        "SUMBER LIMBAH",
        "METODE PENANGANAN",
        "STATUS BERBAHAYA",
        "KETERANGAN",
    ];

    headers.forEach((h, idx) => {
        const cell = headerRow.getCell(idx + 1);
        cell.value = h;
        cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FFFFFFFF" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF047857" } };
        cell.alignment = { horizontal: "center", vertical: "middle", wrapText: true };
        cell.border = {
            top: { style: "medium", color: { argb: "FF0F172A" } },
            bottom: { style: "medium", color: { argb: "FF0F172A" } },
            left: { style: "thin", color: { argb: "FFCBD5E1" } },
            right: { style: "thin", color: { argb: "FFCBD5E1" } },
        };
    });

    // ─── 5. ISI DATA TABEL ───
    let currentRow = 10;
    residuList.forEach((item, index) => {
        const row = sheet.getRow(currentRow);
        row.height = 20;

        const isEven = index % 2 === 0;
        const rowBg = isEven ? "FFFFFFFF" : "FFF8FAFC";

        row.getCell(1).value = index + 1;
        row.getCell(2).value = item.tanggal_formatted || formatTglDdMmYyyy(item.tanggal);
        row.getCell(3).value = item.nama_limbah || "-";
        row.getCell(4).value = parseFloat(item.jumlah || 0);
        row.getCell(5).value = item.satuan || "Kg";
        row.getCell(6).value = item.jenis_limbah || "-";
        row.getCell(7).value = item.sumber_limbah || "-";
        row.getCell(8).value = item.metode_penanganan || "-";
        row.getCell(9).value = item.status_berbahaya || "-";
        row.getCell(10).value = item.keterangan || "-";

        for (let c = 1; c <= maxCols; c++) {
            const cell = row.getCell(c);
            cell.font = { name: "Arial", size: 8.5, color: { argb: "FF1E293B" } };
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: rowBg } };
            cell.border = {
                top: { style: "thin", color: { argb: "FFE2E8F0" } },
                bottom: { style: "thin", color: { argb: "FFE2E8F0" } },
                left: { style: "thin", color: { argb: "FFE2E8F0" } },
                right: { style: "thin", color: { argb: "FFE2E8F0" } },
            };

            if (c === 1 || c === 2 || c === 5) {
                cell.alignment = { horizontal: "center", vertical: "middle" };
            } else if (c === 4) {
                cell.alignment = { horizontal: "right", vertical: "middle" };
                cell.numFmt = "#,##0.00";
            } else if (c === 6 || c === 7 || c === 8 || c === 9) {
                cell.alignment = { horizontal: "center", vertical: "middle" };
            } else {
                cell.alignment = { horizontal: "left", vertical: "middle" };
            }

            if (c === 9 && item.status_berbahaya === "Berbahaya") {
                cell.font = { name: "Arial", size: 8.5, bold: true, color: { argb: "FFE11D48" } };
            }
        }

        currentRow++;
    });

    // ─── 6. BARIS TOTAL / REKAPITULASI ───
    const totalRow = sheet.getRow(currentRow);
    totalRow.height = 24;
    sheet.mergeCells(`A${currentRow}:C${currentRow}`);
    totalRow.getCell(1).value = `TOTAL AKUMULASI (${residuList.length} Catatan)`;
    totalRow.getCell(1).alignment = { horizontal: "center", vertical: "middle" };

    const totalKg = summary.total_berat_kg || 0;
    const totalL = summary.total_volume_l || 0;
    totalRow.getCell(4).value = `${totalKg} Kg / ${totalL} L`;
    totalRow.getCell(4).alignment = { horizontal: "right", vertical: "middle" };
    sheet.mergeCells(`D${currentRow}:E${currentRow}`);

    sheet.mergeCells(`F${currentRow}:J${currentRow}`);
    totalRow.getCell(6).value = `Organik: ${summary.total_organik_kg || 0} Kg | Anorganik: ${summary.total_anorganik_kg || 0} Kg | Berbahaya: ${summary.total_berbahaya || 0} item`;
    totalRow.getCell(6).alignment = { horizontal: "left", vertical: "middle" };

    for (let c = 1; c <= maxCols; c++) {
        const cell = totalRow.getCell(c);
        cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF065F46" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFECFDF5" } };
        cell.border = {
            top: { style: "medium", color: { argb: "FF0F172A" } },
            bottom: { style: "double", color: { argb: "FF0F172A" } },
            left: { style: "thin", color: { argb: "FFCBD5E1" } },
            right: { style: "thin", color: { argb: "FFCBD5E1" } },
        };
    }

    // ─── 7. BLOK PENGESAHAN: 4 TTD SEJAJAR 1 BARIS, KATA "MENGETAHUI," LEBIH TINGGI ───
    const kopConfig = getActiveKopConfig(unitSppg);
    const kotaStr = kopConfig?.kabupaten || "Buleleng";
    const signDateStr = formatTanggalIndo(new Date());
    const korlapNama = pejabatTtd?.koordinator_lapangan || "Didik Marhadi";
    const giziNama = pejabatTtd?.pengawas_gizi || "Ika Septiana";
    const keuanganNama = pejabatTtd?.pengawas_keuangan || "I Gusti Ayu Made Padmi Swari";
    const kepalaNama = pejabatTtd?.kepala_sppg || "I Gede Gelgel Abdiutama, S.Kom.";

    // Row Pengesahan 1: Tanggal di atas Kepala SPPG
    currentRow += 2;
    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = `${kotaStr}, ${signDateStr}`;
    sheet.getCell(`H${currentRow}`).font = { name: "Arial", size: 9, italic: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    // Row Pengesahan 2: Kata "Mengetahui," LEBIH TINGGI daripada baris jabatan lainnya
    currentRow++;
    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = "Mengetahui,";
    sheet.getCell(`H${currentRow}`).font = { name: "Arial", size: 9.5, bold: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    // Row Pengesahan 3: 4 Jabatan TTD SEJAJAR 1 BARIS
    currentRow++;
    sheet.getRow(currentRow).height = 22;

    sheet.mergeCells(`A${currentRow}:B${currentRow}`);
    sheet.getCell(`A${currentRow}`).value = "Koordinator Lapangan";
    sheet.getCell(`A${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`A${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`C${currentRow}:E${currentRow}`);
    sheet.getCell(`C${currentRow}`).value = "Pengawas Gizi (PLOG)";
    sheet.getCell(`C${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`C${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`F${currentRow}:G${currentRow}`);
    sheet.getCell(`F${currentRow}`).value = "Pengawas Keuangan (PLOK)";
    sheet.getCell(`F${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`F${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = "Kepala SPPG";
    sheet.getCell(`H${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    // Ruang TTD fisik (3 baris kosong)
    currentRow++;
    sheet.getRow(currentRow).height = 18;
    currentRow++;
    sheet.getRow(currentRow).height = 18;
    currentRow++;
    sheet.getRow(currentRow).height = 18;

    // Row Pengesahan 7: 4 Nama Pejabat SEJAJAR 1 BARIS
    currentRow++;
    sheet.getRow(currentRow).height = 20;

    sheet.mergeCells(`A${currentRow}:B${currentRow}`);
    sheet.getCell(`A${currentRow}`).value = `( ${korlapNama} )`;
    sheet.getCell(`A${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`A${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`C${currentRow}:E${currentRow}`);
    sheet.getCell(`C${currentRow}`).value = `( ${giziNama} )`;
    sheet.getCell(`C${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`C${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`F${currentRow}:G${currentRow}`);
    sheet.getCell(`F${currentRow}`).value = `( ${keuanganNama} )`;
    sheet.getCell(`F${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`F${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = `( ${kepalaNama} )`;
    sheet.getCell(`H${currentRow}`).font = { name: "Arial", size: 9, bold: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    // Row Pengesahan 8: Sub-keterangan Jabatan SEJAJAR 1 BARIS
    currentRow++;
    sheet.getRow(currentRow).height = 16;

    sheet.mergeCells(`A${currentRow}:B${currentRow}`);
    sheet.getCell(`A${currentRow}`).value = "Koordinator Lapangan SPPG";
    sheet.getCell(`A${currentRow}`).font = { name: "Arial", size: 8.5, italic: true, color: { argb: "FF64748B" } };
    sheet.getCell(`A${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`C${currentRow}:E${currentRow}`);
    sheet.getCell(`C${currentRow}`).value = "Pengawas Standar Gizi & Mutu";
    sheet.getCell(`C${currentRow}`).font = { name: "Arial", size: 8.5, italic: true, color: { argb: "FF64748B" } };
    sheet.getCell(`C${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`F${currentRow}:G${currentRow}`);
    sheet.getCell(`F${currentRow}`).value = "Pengawas Administrasi & Keuangan";
    sheet.getCell(`F${currentRow}`).font = { name: "Arial", size: 8.5, italic: true, color: { argb: "FF64748B" } };
    sheet.getCell(`F${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = "Penanggung Jawab Operasional";
    sheet.getCell(`H${currentRow}`).font = { name: "Arial", size: 8.5, italic: true, color: { argb: "FF64748B" } };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: "center", vertical: "middle" };

    const cleanStart = startDate ? startDate.replace(/-/g, "") : "Semua";
    const cleanEnd = endDate ? endDate.replace(/-/g, "") : "Semua";
    const filename = `Laporan_Residu_Limbah_SPPG_${cleanStart}_sd_${cleanEnd}.xlsx`;

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
