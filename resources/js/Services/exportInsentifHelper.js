import ExcelJS from "exceljs";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { 
    LOGO_BGN_RAW_BASE64, 
    LOGO_YAYASAN_RAW_BASE64 
} from "@/Services/logoBase64Helper";

const MONTHS_INDO = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export function formatTanggalIndo(dateObj) {
    if (!dateObj) return '-';
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

export function colToLetter(n) {
    let s = '';
    while (n > 0) {
        let m = (n - 1) % 26;
        s = String.fromCharCode(65 + m) + s;
        n = Math.floor((n - m) / 26);
    }
    return s;
}

export async function downloadRekapInsentifExcel({
    insentifList = [],
    summary = {},
    startDate = null,
    endDate = null,
    unitSppg = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE SPPG Buleleng';
    workbook.created = new Date();

    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        logoBgnId = workbook.addImage({
            base64: LOGO_BGN_RAW_BASE64,
            extension: 'png',
        });
        logoYayasanId = workbook.addImage({
            base64: LOGO_YAYASAN_RAW_BASE64,
            extension: 'png',
        });
    } catch (e) {
        console.warn('Gagal memuat logo ke workbook Excel:', e);
    }

    const sheet = workbook.addWorksheet('Daftar Insentif Tunai PM', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxCols = 10;
    const kopConfig = getActiveKopConfig(unitSppg);
    const lastColLetter = colToLetter(maxCols);

    // Kop Surat
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
    sheet.getCell('A4').value = `E-mail: ${kopConfig?.email || 'sppgsukasadategallinggah@gmail.com'}`;
    sheet.getCell('A4').font = { name: 'Arial', size: 9, italic: true };
    sheet.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    if (logoBgnId !== null) {
        sheet.addImage(logoBgnId, {
            tl: { col: 0.2, row: 0.2 },
            ext: { width: 55, height: 55 }
        });
    }
    if (logoYayasanId !== null) {
        sheet.addImage(logoYayasanId, {
            tl: { col: maxCols - 1.2, row: 0.2 },
            ext: { width: 55, height: 55 }
        });
    }

    // Judul Dokumen
    sheet.getRow(6).height = 22;
    sheet.mergeCells(`A6:${lastColLetter}6`);
    sheet.getCell('A6').value = 'DAFTAR PEMBAYARAN INSENTIF TUNAI PENANGGUNG JAWAB & KADER PM';
    sheet.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    const periodeText = startDate === endDate
        ? `Tanggal Pelaksanaan: ${formatTanggalIndo(startDate)}`
        : `Periode Operasional: ${formatTanggalIndo(startDate)} s/d ${formatTanggalIndo(endDate)}`;

    sheet.getRow(7).height = 18;
    sheet.mergeCells(`A7:${lastColLetter}7`);
    sheet.getCell('A7').value = `${periodeText} • Metode Pembayaran: Tunai Langsung`;
    sheet.getCell('A7').font = { name: 'Arial', size: 10, italic: true };
    sheet.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    // Header Tabel
    const headerRow = sheet.getRow(9);
    headerRow.height = 28;
    const headers = [
        'NO',
        'NAMA SATUAN / KELOMPOK',
        'KATEGORI',
        'PENANGGUNG JAWAB (PIC)',
        'SASARAN (PM)',
        'SKEMA TARIF HARIAN',
        'HARI KERJA',
        'TOTAL DITERIMA (RP)',
        'METODE',
        'TANDA TANGAN PENERIMA'
    ];

    headers.forEach((h, idx) => {
        const cell = headerRow.getCell(idx + 1);
        cell.value = h;
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = {
            top: { style: 'thin' },
            left: { style: 'thin' },
            bottom: { style: 'medium' },
            right: { style: 'thin' }
        };
    });

    // Baris Data
    let currentRow = 10;
    insentifList.forEach((item, index) => {
        const row = sheet.getRow(currentRow);
        row.height = 28;

        row.getCell(1).value = index + 1;
        row.getCell(2).value = item.nama_kelompok;
        row.getCell(3).value = item.kategori;
        row.getCell(4).value = `${item.nama_pic || '-'} (${item.telepon_pic || '-'})`;
        row.getCell(5).value = Number(item.total_penerima) || 0;
        row.getCell(6).value = item.deskripsi_tarif || '-';
        row.getCell(7).value = Number(item.hari_operasional) || 1;
        row.getCell(8).value = Number(item.amount) || 0;
        row.getCell(9).value = 'Tunai Langsung';
        row.getCell(10).value = `${index + 1}. ......................................`;

        for (let c = 1; c <= maxCols; c++) {
            const cell = row.getCell(c);
            cell.font = { name: 'Arial', size: 9 };
            cell.border = {
                top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
            };
            cell.alignment = { vertical: 'middle' };

            if ([1, 3, 7, 9].includes(c)) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (c === 5) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '#,##0';
            } else if (c === 8) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
            } else if (c === 10) {
                cell.alignment = { horizontal: 'left', vertical: 'bottom' };
                cell.font = { name: 'Arial', size: 8, italic: true, color: { argb: 'FF64748B' } };
            }
        }
        currentRow++;
    });

    // Baris Total
    const totalRow = sheet.getRow(currentRow);
    totalRow.height = 24;
    totalRow.getCell(1).value = 'TOTAL KESELURUHAN';
    sheet.mergeCells(`A${currentRow}:D${currentRow}`);
    totalRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

    totalRow.getCell(5).value = { formula: `SUM(E10:E${currentRow - 1})` };
    totalRow.getCell(8).value = { formula: `SUM(H10:H${currentRow - 1})` };

    for (let c = 1; c <= maxCols; c++) {
        const cell = totalRow.getCell(c);
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
        cell.border = {
            top: { style: 'medium', color: { argb: 'FF000000' } },
            bottom: { style: 'double', color: { argb: 'FF000000' } },
            left: { style: 'thin' },
            right: { style: 'thin' }
        };
        if (c === 5) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '#,##0';
        } else if (c === 8) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
        }
    }

    // Tanda Tangan SPPG di Bawah
    currentRow += 2;
    const signDateStr = formatTanggalIndo(new Date());
    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = `Buleleng, ${signDateStr}`;
    sheet.getCell(`H${currentRow}`).font = { name: 'Arial', size: 9 };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: 'center', vertical: 'middle' };

    currentRow++;
    sheet.mergeCells(`B${currentRow}:D${currentRow}`);
    sheet.getCell(`B${currentRow}`).value = 'Mengetahui,\nKepala Unit SPPG';
    sheet.getCell(`B${currentRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`B${currentRow}`).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };

    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = 'Dibuat Oleh,\nBendahara / Kasir SPPG';
    sheet.getCell(`H${currentRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };

    currentRow += 4;
    sheet.mergeCells(`B${currentRow}:D${currentRow}`);
    sheet.getCell(`B${currentRow}`).value = `( ${unitSppg?.nama_kepala || 'Kepala SPPG'} )`;
    sheet.getCell(`B${currentRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`B${currentRow}`).alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`H${currentRow}:J${currentRow}`);
    sheet.getCell(`H${currentRow}`).value = `( ${unitSppg?.nama_bendahara || 'Bendahara SPPG'} )`;
    sheet.getCell(`H${currentRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`H${currentRow}`).alignment = { horizontal: 'center', vertical: 'middle' };

    // Column widths
    sheet.columns = [
        { width: 6 },  // No
        { width: 32 }, // Nama Satuan
        { width: 12 }, // Kategori
        { width: 28 }, // PIC
        { width: 14 }, // Sasaran PM
        { width: 24 }, // Skema Tarif
        { width: 12 }, // Hari Kerja
        { width: 18 }, // Total Diterima
        { width: 16 }, // Metode
        { width: 26 }, // Tanda Tangan
    ];

    // Download
    const filename = `Daftar_Insentif_Tunai_PM_${startDate}_sd_${endDate}.xlsx`;
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
