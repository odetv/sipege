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
        const d = new Date(String(dateObj).includes('T') ? dateObj : String(dateObj) + 'T00:00:00');
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

/**
 * Helper Kop Surat Resmi SPPG di Excel
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
    sheet.getCell('A4').value = `E-mail: ${kopConfig?.email || 'sppgsukasadategallinggah@gmail.com'}`;
    sheet.getCell('A4').font = { name: 'Arial', size: 9, italic: true };
    sheet.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    if (logoBgnId !== null) {
        try {
            sheet.addImage(logoBgnId, {
                tl: { col: 0.1, row: 0.1 },
                ext: { width: 55, height: 55 },
            });
        } catch (e) {
            console.warn('Gagal menempelkan Logo BGN:', e);
        }
    }
    if (logoYayasanId !== null) {
        try {
            sheet.addImage(logoYayasanId, {
                tl: { col: Math.max(0.1, maxCols - 1.2), row: 0.1 },
                ext: { width: 55, height: 55 },
            });
        } catch (e) {
            console.warn('Gagal menempelkan Logo Yayasan:', e);
        }
    }
}

/**
 * Unduh Laporan Rekapitulasi Distribusi Penerima Manfaat (PM) Excel
 */
export async function downloadRekapDistribusiExcel({
    distribusiList = [],
    matrixDistribusi = {},
    dateColumns = [],
    stats = {},
    startDate = null,
    endDate = null,
    mode = 'bulanan',
    unitSppg = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE SPPG Buleleng';
    workbook.created = new Date();

    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        if (LOGO_BGN_RAW_BASE64) {
            logoBgnId = workbook.addImage({
                base64: LOGO_BGN_RAW_BASE64,
                extension: 'png',
            });
        }
        if (LOGO_YAYASAN_RAW_BASE64) {
            logoYayasanId = workbook.addImage({
                base64: LOGO_YAYASAN_RAW_BASE64,
                extension: 'png',
            });
        }
    } catch (e) {
        console.warn('Gagal memuat logo ke workbook Excel:', e);
    }

    const items = Array.isArray(distribusiList) ? distribusiList : [];
    const actualStartStr = startDate || (dateColumns[0]?.dateStr) || 'tanggal_mulai';
    const actualEndStr = endDate || (dateColumns[dateColumns.length - 1]?.dateStr) || actualStartStr;

    // Pastikan validDates sinkron dengan actualStartStr & actualEndStr
    let validDates = Array.isArray(dateColumns) && dateColumns.length > 0 ? [...dateColumns] : [];
    if (
        validDates.length === 0 ||
        (startDate && validDates[0]?.dateStr !== startDate) ||
        (endDate && validDates[validDates.length - 1]?.dateStr !== endDate)
    ) {
        validDates = [];
        try {
            let cur = new Date(actualStartStr + 'T00:00:00');
            const end = new Date(actualEndStr + 'T00:00:00');
            const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            let count = 0;
            while (cur <= end && count < 60) {
                const y = cur.getFullYear();
                const m = String(cur.getMonth() + 1).padStart(2, '0');
                const d = String(cur.getDate()).padStart(2, '0');
                const dateStr = `${y}-${m}-${d}`;
                const dayIdx = cur.getDay();
                validDates.push({
                    dateStr,
                    dayNum: d,
                    monthNum: m,
                    dayName: dayNames[dayIdx],
                    isWeekend: dayIdx === 0 || dayIdx === 6,
                    isSunday: dayIdx === 0,
                    index: count + 1,
                    label: `${d}/${m}`,
                });
                cur.setDate(cur.getDate() + 1);
                count++;
            }
        } catch (e) {
            console.warn('Gagal generate validDates:', e);
        }
    }

    const periodeText = actualStartStr === actualEndStr
        ? `Tanggal Distribusi: ${formatTanggalIndo(actualStartStr)}`
        : `Periode Distribusi: ${formatTanggalIndo(actualStartStr)} s/d ${formatTanggalIndo(actualEndStr)}`;

    // =========================================================================
    // SHEET 1: Matriks Distribusi Harian (Interaktif & Lengkap Status T/L)
    // =========================================================================
    if (validDates.length > 0) {
        const sheetMatriks = workbook.addWorksheet('Matriks Distribusi Harian', {
            pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
        });

        // 8 Kolom Info + N Kolom Tanggal + 2 Kolom Rekap (Hari Kirim & Akumulasi)
        const totalMatriksCols = 8 + validDates.length + 2;
        const lastColLetterMatriks = colToLetter(totalMatriksCols);

        // Kop Surat
        applyKopSurat(sheetMatriks, totalMatriksCols, logoBgnId, logoYayasanId, unitSppg);

        // Judul Dokumen
        sheetMatriks.getRow(6).height = 22;
        sheetMatriks.mergeCells(`A6:${lastColLetterMatriks}6`);
        sheetMatriks.getCell('A6').value = 'REKAPITULASI MATRIKS DISTRIBUSI PENERIMA MANFAAT (PM)';
        sheetMatriks.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
        sheetMatriks.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

        sheetMatriks.getRow(7).height = 18;
        sheetMatriks.mergeCells(`A7:${lastColLetterMatriks}7`);
        sheetMatriks.getCell('A7').value = `${periodeText} (${validDates.length} Hari Kerja)`;
        sheetMatriks.getCell('A7').font = { name: 'Arial', size: 10, italic: true };
        sheetMatriks.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

        // Header Row 9: Info Dasar
        const headerRow = sheetMatriks.getRow(9);
        headerRow.height = 30;

        const baseHeaders = [
            'NO',
            'NAMA KELOMPOK / SASARAN',
            'KATEGORI',
            'DESA / KELURAHAN',
            'SASARAN (PM)',
            'PK',
            'PB',
            'PORSI/HARI'
        ];

        baseHeaders.forEach((h, idx) => {
            const cell = headerRow.getCell(idx + 1);
            cell.value = h;
            cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F172A' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };
        });

        // Kolom Tanggal Header
        validDates.forEach((col, idx) => {
            const cIdx = 9 + idx;
            const cell = headerRow.getCell(cIdx);
            cell.value = `${col.dayName || ''}\n${col.dayNum || idx + 1}`;
            cell.font = { name: 'Arial', size: 8, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { 
                type: 'pattern', 
                pattern: 'solid', 
                fgColor: { argb: col.isSunday ? 'FF991B1B' : (col.isToday ? 'FF065F46' : 'FF1E293B') } 
            };
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };
        });

        // Kolom Akhir: Hari Kirim & Akumulasi
        const colHariKirimIdx = 9 + validDates.length;
        const colAkumulasiIdx = colHariKirimIdx + 1;

        const cellHariKirim = headerRow.getCell(colHariKirimIdx);
        cellHariKirim.value = 'HARI\nKIRIM';
        cellHariKirim.font = { name: 'Arial', size: 8.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cellHariKirim.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F172A' } };
        cellHariKirim.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cellHariKirim.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };

        const cellAkumulasi = headerRow.getCell(colAkumulasiIdx);
        cellAkumulasi.value = 'TOTAL\nAKUMULASI';
        cellAkumulasi.font = { name: 'Arial', size: 8.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cellAkumulasi.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064E3B' } };
        cellAkumulasi.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cellAkumulasi.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };

        // Data Rows Matriks
        let curRowM = 10;
        items.forEach((item, index) => {
            const row = sheetMatriks.getRow(curRowM);
            row.height = 20;

            row.getCell(1).value = index + 1;
            row.getCell(2).value = item.nama_kelompok;
            row.getCell(3).value = item.kategori;
            row.getCell(4).value = item.desa_kelurahan || '-';
            row.getCell(5).value = Number(item.total_penerima) || 0;
            row.getCell(6).value = Number(item.porsi_kecil_harian) || 0;
            row.getCell(7).value = Number(item.porsi_besar_harian) || 0;
            row.getCell(8).value = Number(item.total_porsi_harian) || 0;

            const rowMap = matrixDistribusi[item.id] || {};
            let countTerkirim = 0;

            validDates.forEach((col, idx) => {
                const cIdx = 9 + idx;
                const status = rowMap[col.dateStr];
                const cell = row.getCell(cIdx);

                if (status === 'T') {
                    cell.value = 'T';
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
                    cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF047857' } };
                    countTerkirim++;
                } else if (status === 'L') {
                    cell.value = 'L';
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
                    cell.font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
                } else if (status && String(status).trim() !== '') {
                    cell.value = String(status);
                    cell.font = { name: 'Arial', size: 8.5 };
                } else {
                    // Kosong / Belum Ditentukan:
                    // Mau Sabtu atau Minggu jika masih kosong belum ditentukan maka di Excel juga tetap kosong!
                    cell.value = '';
                    if (col.isSunday) {
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF2F2' } };
                    }
                }
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                };
            });

            // Hari kirim riil berdasarkan jumlah status 'T' (Terkirim) yang tercatat
            const actualDays = countTerkirim;

            row.getCell(colHariKirimIdx).value = actualDays;
            row.getCell(colAkumulasiIdx).value = actualDays * (Number(item.total_porsi_harian) || 0);

            // Styling Base Columns
            for (let c = 1; c <= 8; c++) {
                const cell = row.getCell(c);
                cell.font = { name: 'Arial', size: 9 };
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                };
                if ([1, 3, 4].includes(c)) {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (c >= 5 && c <= 8) {
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '#,##0';
                    if (c === 8) {
                        cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                    }
                } else {
                    cell.alignment = { vertical: 'middle' };
                }
            }

            // Styling Rekap Akhir
            const cellHk = row.getCell(colHariKirimIdx);
            cellHk.font = { name: 'Arial', size: 9, bold: true };
            cellHk.alignment = { horizontal: 'center', vertical: 'middle' };
            cellHk.numFmt = '#,##0';
            cellHk.border = {
                top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            };

            const cellAk = row.getCell(colAkumulasiIdx);
            cellAk.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF064E3B' } };
            cellAk.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
            cellAk.alignment = { horizontal: 'right', vertical: 'middle' };
            cellAk.numFmt = '#,##0';
            cellAk.border = {
                top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            };

            curRowM++;
        });

        // Total Row Matriks
        const totRowM = sheetMatriks.getRow(curRowM);
        totRowM.height = 24;
        totRowM.getCell(1).value = 'TOTAL KESELURUHAN';
        sheetMatriks.mergeCells(`A${curRowM}:D${curRowM}`);
        totRowM.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

        totRowM.getCell(5).value = { formula: `SUM(E10:E${curRowM - 1})` };
        totRowM.getCell(6).value = { formula: `SUM(F10:F${curRowM - 1})` };
        totRowM.getCell(7).value = { formula: `SUM(G10:G${curRowM - 1})` };
        totRowM.getCell(8).value = { formula: `SUM(H10:H${curRowM - 1})` };

        validDates.forEach((col, idx) => {
            const cIdx = 9 + idx;
            const letter = colToLetter(cIdx);
            totRowM.getCell(cIdx).value = { formula: `COUNTIF(${letter}10:${letter}${curRowM - 1}, "T")` };
        });

        const letterHk = colToLetter(colHariKirimIdx);
        const letterAk = colToLetter(colAkumulasiIdx);
        totRowM.getCell(colHariKirimIdx).value = '-';
        totRowM.getCell(colAkumulasiIdx).value = { formula: `SUM(${letterAk}10:${letterAk}${curRowM - 1})` };

        for (let c = 1; c <= totalMatriksCols; c++) {
            const cell = totRowM.getCell(c);
            cell.font = { name: 'Arial', size: 9, bold: true };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
            cell.border = {
                top: { style: 'medium', color: { argb: 'FF0F172A' } },
                bottom: { style: 'double', color: { argb: 'FF0F172A' } },
                left: { style: 'thin' },
                right: { style: 'thin' },
            };
            if ([5, 6, 7, 8, colAkumulasiIdx].includes(c)) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '#,##0';
            } else if (c >= 9 && c <= colHariKirimIdx) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.numFmt = '#,##0';
            }
        }

        // Set Col Widths
        const colWidths = [
            { width: 5 },  // No
            { width: 28 }, // Nama
            { width: 11 }, // Kategori
            { width: 16 }, // Desa
            { width: 13 }, // Sasaran PM
            { width: 9 },  // PK
            { width: 9 },  // PB
            { width: 12 }, // Porsi/Hari
        ];
        validDates.forEach(() => {
            colWidths.push({ width: 4.8 });
        });
        colWidths.push({ width: 10 }); // Hari Kirim
        colWidths.push({ width: 16 }); // Total Akumulasi
        sheetMatriks.columns = colWidths;
    }

    // =========================================================================
    // SHEET 2: Ringkasan Alokasi & Narahubung PM (Format Tabel Resmi)
    // =========================================================================
    const sheetRingkasan = workbook.addWorksheet('Daftar Alokasi PM', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxColsRingkasan = 11;
    const lastColLetterRingkasan = colToLetter(maxColsRingkasan);

    // Kop Surat
    applyKopSurat(sheetRingkasan, maxColsRingkasan, logoBgnId, logoYayasanId, unitSppg);

    // Judul Dokumen
    sheetRingkasan.getRow(6).height = 22;
    sheetRingkasan.mergeCells(`A6:${lastColLetterRingkasan}6`);
    sheetRingkasan.getCell('A6').value = 'DAFTAR ALOKASI DAN NARAHUBUNG PENERIMA MANFAAT (PM)';
    sheetRingkasan.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheetRingkasan.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetRingkasan.getRow(7).height = 18;
    sheetRingkasan.mergeCells(`A7:${lastColLetterRingkasan}7`);
    sheetRingkasan.getCell('A7').value = periodeText;
    sheetRingkasan.getCell('A7').font = { name: 'Arial', size: 10, italic: true };
    sheetRingkasan.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    // Header Tabel
    const headerRowR = sheetRingkasan.getRow(9);
    headerRowR.height = 28;
    const headersR = [
        'NO',
        'NAMA KELOMPOK / SASARAN',
        'KATEGORI',
        'IDENTITAS / NPSN',
        'DESA / KELURAHAN',
        'PIC / KONTAK',
        'TOTAL SASARAN (PM)',
        'PORSI KECIL (PK)',
        'PORSI BESAR (PB)',
        'PORSI HARIAN',
        'TOTAL AKUMULASI'
    ];

    headersR.forEach((h, idx) => {
        const cell = headerRowR.getCell(idx + 1);
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
    let currentRowR = 10;
    items.forEach((item, index) => {
        const row = sheetRingkasan.getRow(currentRowR);
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
        const rowMapR = matrixDistribusi[item.id] || {};
        let countTerkirimR = 0;
        validDates.forEach((col) => {
            if (rowMapR[col.dateStr] === 'T') countTerkirimR++;
        });
        const actualDaysR = countTerkirimR > 0 ? countTerkirimR : (Number(item.total_hari_kirim) || 0);
        row.getCell(11).value = actualDaysR * (Number(item.total_porsi_harian) || 0);

        for (let c = 1; c <= maxColsRingkasan; c++) {
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
        currentRowR++;
    });

    // Baris Total
    const totalRowR = sheetRingkasan.getRow(currentRowR);
    totalRowR.height = 24;
    totalRowR.getCell(1).value = 'TOTAL KESELURUHAN';
    sheetRingkasan.mergeCells(`A${currentRowR}:F${currentRowR}`);
    totalRowR.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

    totalRowR.getCell(7).value = { formula: `SUM(G10:G${currentRowR - 1})` };
    totalRowR.getCell(8).value = { formula: `SUM(H10:H${currentRowR - 1})` };
    totalRowR.getCell(9).value = { formula: `SUM(I10:I${currentRowR - 1})` };
    totalRowR.getCell(10).value = { formula: `SUM(J10:J${currentRowR - 1})` };
    totalRowR.getCell(11).value = { formula: `SUM(K10:K${currentRowR - 1})` };

    for (let c = 1; c <= maxColsRingkasan; c++) {
        const cell = totalRowR.getCell(c);
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

    sheetRingkasan.columns = [
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

    // Download File Excel
    const filename = `Rekap_Distribusi_PM_${actualStartStr}_sd_${actualEndStr}.xlsx`;
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
