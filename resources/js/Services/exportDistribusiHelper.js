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

export async function downloadRekapDistribusiExcel({
    distribusiList = [],
    stats = {},
    startDate = null,
    endDate = null,
    mode = 'hari_ini',
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

    const sheet = workbook.addWorksheet('Rekap Distribusi PM', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxCols = 11;
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
    sheet.getCell('A6').value = 'REKAPITULASI DISTRIBUSI PENERIMA MANFAAT (PM)';
    sheet.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    const periodeText = startDate === endDate
        ? `Tanggal Distribusi: ${formatTanggalIndo(startDate)}`
        : `Periode Distribusi: ${formatTanggalIndo(startDate)} s/d ${formatTanggalIndo(endDate)}`;

    sheet.getRow(7).height = 18;
    sheet.mergeCells(`A7:${lastColLetter}7`);
    sheet.getCell('A7').value = periodeText;
    sheet.getCell('A7').font = { name: 'Arial', size: 10, italic: true };
    sheet.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    // Header Tabel
    const headerRow = sheet.getRow(9);
    headerRow.height = 28;
    const headers = [
        'NO',
        'NAMA KELOMPOK / SASARAN',
        'KATEGORI',
        'IDENTITAS / NPSN',
        'DESA / KELURAHAN',
        'PIC / KONTAK',
        'TOTAL SASARAN (JIWA)',
        'PORSI KECIL (PK)',
        'PORSI BESAR (PB)',
        'PORSI HARIAN',
        'TOTAL AKUMULASI'
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
    distribusiList.forEach((item, index) => {
        const row = sheet.getRow(currentRow);
        row.height = 20;

        row.getCell(1).value = index + 1;
        row.getCell(2).value = item.nama_kelompok;
        row.getCell(3).value = item.kategori;
        row.getCell(4).value = `${item.tipe_identitas || ''} ${item.kode_identitas || '-'}`.trim();
        row.getCell(5).value = item.desa_kelurahan || '-';
        row.getCell(6).value = `${item.nama_pic || '-'} (${item.telepon_pic || '-'})`;
        row.getCell(7).value = Number(item.total_penerima) || 0;
        row.getCell(8).value = Number(item.porsi_kecil_harian) || 0;
        row.getCell(9).value = Number(item.porsi_besar_harian) || 0;
        row.getCell(10).value = Number(item.total_porsi_harian) || 0;
        row.getCell(11).value = Number(item.total_porsi_akumulasi) || 0;

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

            if ([1, 3, 4, 5].includes(c)) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if ([7, 8, 9, 10, 11].includes(c)) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '#,##0';
            }
        }
        currentRow++;
    });

    // Baris Total
    const totalRow = sheet.getRow(currentRow);
    totalRow.height = 24;
    totalRow.getCell(1).value = 'TOTAL KESELURUHAN';
    sheet.mergeCells(`A${currentRow}:F${currentRow}`);
    totalRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

    totalRow.getCell(7).value = { formula: `SUM(G10:G${currentRow - 1})` };
    totalRow.getCell(8).value = { formula: `SUM(H10:H${currentRow - 1})` };
    totalRow.getCell(9).value = { formula: `SUM(I10:I${currentRow - 1})` };
    totalRow.getCell(10).value = { formula: `SUM(J10:J${currentRow - 1})` };
    totalRow.getCell(11).value = { formula: `SUM(K10:K${currentRow - 1})` };

    for (let c = 1; c <= maxCols; c++) {
        const cell = totalRow.getCell(c);
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
        cell.border = {
            top: { style: 'medium', color: { argb: 'FF000000' } },
            bottom: { style: 'double', color: { argb: 'FF000000' } },
            left: { style: 'thin' },
            right: { style: 'thin' }
        };
        if (c >= 7) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '#,##0';
        }
    }

    // Set Column Widths
    sheet.columns = [
        { width: 6 },  // No
        { width: 32 }, // Nama
        { width: 12 }, // Kategori
        { width: 18 }, // Identitas
        { width: 18 }, // Desa
        { width: 28 }, // PIC
        { width: 14 }, // Total Sasaran
        { width: 14 }, // PK
        { width: 14 }, // PB
        { width: 14 }, // Total Harian
        { width: 16 }, // Akumulasi
    ];

    // Download
    const filename = `Rekap_Distribusi_PM_${startDate}_sd_${endDate}.xlsx`;
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
