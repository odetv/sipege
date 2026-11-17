import ExcelJS from "exceljs";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { 
    LOGO_BGN_RAW_BASE64, 
    LOGO_YAYASAN_RAW_BASE64 
} from "@/Services/logoBase64Helper";
import { DEFAULT_TABEL_BANTU_OPERASIONAL } from "@/Services/operasionalBantuHelper";

const MONTH_NAMES = [
    "",
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

const BORDER_THIN = {
    top: { style: 'thin', color: { argb: 'FFD1D5DB' } },
    left: { style: 'thin', color: { argb: 'FFD1D5DB' } },
    bottom: { style: 'thin', color: { argb: 'FFD1D5DB' } },
    right: { style: 'thin', color: { argb: 'FFD1D5DB' } },
};

const BORDER_HEADER = {
    top: { style: 'medium', color: { argb: 'FF1E293B' } },
    left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    bottom: { style: 'medium', color: { argb: 'FF1E293B' } },
    right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
};

const BORDER_TOTAL = {
    top: { style: 'thin', color: { argb: 'FF000000' } },
    left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    bottom: { style: 'double', color: { argb: 'FF000000' } },
    right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
};

/**
 * Helper Kop Surat Resmi SPPG di Excel
 */
function applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg = null, kopDokumen = null) {
    const kopConfig = getActiveKopConfig(kopDokumen || unitSppg);
    const lastColLetter = getColumnLetter(maxCols);

    sheet.getRow(1).height = 22;
    sheet.getRow(2).height = 18;
    sheet.getRow(3).height = 15;
    sheet.getRow(4).height = 15;

    sheet.mergeCells(`A1:${lastColLetter}1`);
    sheet.getCell('A1').value = kopConfig?.nama_instansi_1 || 'SATUAN PELAYANAN PEMENUHAN GIZI (SPPG)';
    sheet.getCell('A1').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A2:${lastColLetter}2`);
    sheet.getCell('A2').value = kopConfig?.nama_instansi_2 || (unitSppg?.nama ? `SPPG ${unitSppg.nama.toUpperCase()}` : 'PROGRAM MAKAN BERGIZI GRATIS');
    sheet.getCell('A2').font = { name: 'Arial', size: 11, bold: true };
    sheet.getCell('A2').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A3:${lastColLetter}3`);
    sheet.getCell('A3').value = kopConfig?.alamat_lengkap || 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';
    sheet.getCell('A3').font = { name: 'Arial', size: 8.5 };
    sheet.getCell('A3').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.mergeCells(`A4:${lastColLetter}4`);
    const kontakText = [
        kopConfig?.telepon ? `Telp: ${kopConfig.telepon}` : '',
        kopConfig?.email ? `E-mail: ${kopConfig.email}` : ''
    ].filter(Boolean).join(' • ') || 'E-mail: sppgsukasadategallinggah@gmail.com';
    sheet.getCell('A4').value = kontakText;
    sheet.getCell('A4').font = { name: 'Arial', size: 8.5, italic: true };
    sheet.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = { bottom: { style: 'double', color: { argb: 'FF000000' } } };
    }

    if (logoBgnId !== null) {
        try {
            sheet.addImage(logoBgnId, {
                tl: { col: 0.1, row: 0.15 },
                ext: { width: 52, height: 52 }
            });
        } catch (e) {
            console.warn('Gagal menempelkan logo BGN:', e);
        }
    }
    if (logoYayasanId !== null) {
        try {
            sheet.addImage(logoYayasanId, {
                tl: { col: maxCols - 1.1, row: 0.15 },
                ext: { width: 52, height: 52 }
            });
        } catch (e) {
            console.warn('Gagal menempelkan logo Yayasan:', e);
        }
    }
}

/**
 * Download RAB Bulanan dalam format Excel (.xlsx) dengan Kop SPPG resmi dan rumus perhitungan Excel hidup.
 */
export async function downloadRabBulananExcel({
    rabData,
    month = 1,
    year = 2026,
    unitSppg = null,
    pejabatSppg = {},
    rekeningUtama = null,
    kopDokumen = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE - BGN SPPG';
    workbook.created = new Date();

    const monthName = MONTH_NAMES[month] || `Bulan ${month}`;
    const sheet = workbook.addWorksheet(`RAB ${monthName}`);
    sheet.views = [{ showGridLines: true }];

    // Siapkan Logo KOP
    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        if (LOGO_BGN_RAW_BASE64) {
            logoBgnId = workbook.addImage({ base64: LOGO_BGN_RAW_BASE64, extension: 'png' });
        }
        if (LOGO_YAYASAN_RAW_BASE64) {
            logoYayasanId = workbook.addImage({ base64: LOGO_YAYASAN_RAW_BASE64, extension: 'png' });
        }
    } catch (e) {
        console.warn('Gagal load logo ke excel:', e);
    }

    // 1. KOP SURAT RESMI (Baris 1 - 4)
    applyKopSurat(sheet, 9, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

    // 2. JUDUL RAB BULANAN & PERIODE (Baris 6 & 7)
    sheet.getRow(5).height = 10; // Spacer

    sheet.getRow(6).height = 20;
    sheet.mergeCells('A6:I6');
    sheet.getCell('A6').value = 'RENCANA ANGGARAN BIAYA (RAB) BULANAN';
    sheet.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.getRow(7).height = 16;
    sheet.mergeCells('A7:I7');
    sheet.getCell('A7').value = `Bulan: ${monthName} ${year}`;
    sheet.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
    sheet.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.getRow(8).height = 10; // Spacer

    // 3. HEADER TABEL UTAMA (Baris 9 & 10)
    const headerRow1 = [
        "No",
        "Kebutuhan",
        "Jumlah paket MBG* (Paket)",
        "Harga Satuan (Rp)",
        "Biaya per hari (Rp)",
        "Biaya per 2 mingguan (Rp)",
        "Jumlah hari pelayanan efektif dalam 1 bulan",
        "Biaya per bulan (Rp)",
        "Keterangan"
    ];

    const subHeaderRow = [
        "1",
        "2",
        "3",
        "4",
        "(5)=(3)*(4)",
        "(6)=(5)*12",
        "7",
        "(8)=(7)*(5)",
        "9"
    ];

    sheet.getRow(9).values = headerRow1;
    sheet.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    sheet.getRow(9).height = 32;

    sheet.getRow(10).values = subHeaderRow;
    sheet.getRow(10).font = { name: 'Arial', size: 8, italic: true, bold: true };
    sheet.getRow(10).alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(10).height = 18;

    for (let c = 1; c <= 9; c++) {
        sheet.getRow(9).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF0F766E' } // Teal BGN
        };
        sheet.getRow(9).getCell(c).border = BORDER_HEADER;

        sheet.getRow(10).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF0FDFA' } // Light Teal
        };
        sheet.getRow(10).getCell(c).border = BORDER_THIN;
    }

    // 4. BARIS DATA RAB DENGAN RUMUS EXCEL HIDUP (Mulai Baris 11)
    const items = rabData?.rincian_porsi || [];
    const hariEfektif = rabData?.hari_efektif || 20;

    let startRow = 11;
    items.forEach((item, idx) => {
        const rowNum = startRow + idx;
        const row = sheet.getRow(rowNum);

        const jml = Number(item.jumlah_paket) || 0;
        const hrg = Number(item.harga_satuan) || 0;
        const perHari = jml * hrg;
        const per2Minggu = perHari * 12;
        const perBulan = perHari * hariEfektif;

        row.values = [
            idx + 1,
            item.nama || '',
            jml,
            hrg,
            perHari,
            per2Minggu,
            hariEfektif,
            perBulan,
            item.keterangan || ''
        ];

        // Rumus Perhitungan Excel: (5)=(3)*(4), (6)=(5)*12, (8)=(7)*(5)
        row.getCell(5).value = { formula: `C${rowNum}*D${rowNum}`, result: perHari };
        row.getCell(6).value = { formula: `E${rowNum}*12`, result: per2Minggu };
        row.getCell(8).value = { formula: `G${rowNum}*E${rowNum}`, result: perBulan };

        row.font = { name: 'Arial', size: 9 };
        row.alignment = { vertical: 'middle', wrapText: true };
        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(3).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(4).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(9).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

        // Format Angka
        row.getCell(3).numFmt = '#,##0';
        row.getCell(4).numFmt = '#,##0';
        row.getCell(5).numFmt = '#,##0';
        row.getCell(6).numFmt = '#,##0';
        row.getCell(7).numFmt = '0';
        row.getCell(8).numFmt = '#,##0';

        for (let c = 1; c <= 9; c++) {
            row.getCell(c).border = BORDER_THIN;
        }

        // Zebra striping
        if (idx % 2 === 1) {
            for (let c = 1; c <= 9; c++) {
                row.getCell(c).fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFF8FAFC' }
                };
            }
        }
    });

    // 5. BARIS TOTAL DENGAN RUMUS SUM EXCEL
    const totalRowNum = startRow + items.length;
    const totalRow = sheet.getRow(totalRowNum);

    const firstDataRow = startRow;
    const lastDataRow = totalRowNum - 1;

    const totPerHari = items.reduce((s, it) => s + ((Number(it.jumlah_paket) || 0) * (Number(it.harga_satuan) || 0)), 0);
    const totPer2Minggu = totPerHari * 12;
    const totPerBulan = totPerHari * hariEfektif;

    totalRow.getCell(2).value = 'Total';
    totalRow.getCell(5).value = { formula: `SUM(E${firstDataRow}:E${lastDataRow})`, result: totPerHari };
    totalRow.getCell(6).value = { formula: `SUM(F${firstDataRow}:F${lastDataRow})`, result: totPer2Minggu };
    totalRow.getCell(8).value = { formula: `SUM(H${firstDataRow}:H${lastDataRow})`, result: totPerBulan };

    totalRow.font = { name: 'Arial', size: 9, bold: true };
    totalRow.alignment = { vertical: 'middle' };
    totalRow.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
    totalRow.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };

    totalRow.getCell(5).numFmt = '#,##0';
    totalRow.getCell(6).numFmt = '#,##0';
    totalRow.getCell(8).numFmt = '#,##0';

    for (let c = 1; c <= 9; c++) {
        totalRow.getCell(c).border = BORDER_TOTAL;
        totalRow.getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF3C7' } // Light Amber
        };
    }

    // 6. CATATAN KAKI
    let curRow = totalRowNum + 2;
    sheet.getCell(`A${curRow}`).value = 'Catatan :';
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    curRow++;
    sheet.getCell(`A${curRow}`).value = `*Jumlah hari pelayanan efektif = ${hariEfektif} hari (5 Hari KBM X 4 Minggu)`;
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 8, italic: true };

    curRow++;
    sheet.getCell(`A${curRow}`).value = 'Hari pelayanan efektif belum memperhitungkan hari libur nasional dan kalender akademik.';
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 8, italic: true };

    // 7. INFORMASI YAYASAN & REKENING
    curRow += 2;
    const namaYayasanVal = rabData?.nama_yayasan || kopDokumen?.nama_instansi_1 || unitSppg?.nama || '-';
    const noRekVal = rabData?.nomor_rekening_yayasan || rekeningUtama?.nomor_rekening || '-';
    const npwpVal = rabData?.npwp || unitSppg?.extra_attributes?.npwp || '-';

    sheet.getCell(`B${curRow}`).value = 'Nama Yayasan';
    sheet.getCell(`C${curRow}`).value = `: ${namaYayasanVal}`;
    sheet.getCell(`B${curRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`C${curRow}`).font = { name: 'Arial', size: 9 };

    curRow++;
    sheet.getCell(`B${curRow}`).value = 'Nomor Rekening Yayasan';
    sheet.getCell(`C${curRow}`).value = `: ${noRekVal}`;
    sheet.getCell(`B${curRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`C${curRow}`).font = { name: 'Arial', size: 9 };

    curRow++;
    sheet.getCell(`B${curRow}`).value = 'NPWP';
    sheet.getCell(`C${curRow}`).value = `: ${npwpVal}`;
    sheet.getCell(`B${curRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheet.getCell(`C${curRow}`).font = { name: 'Arial', size: 9 };

    // 8. TANDA TANGAN PEJABAT
    curRow += 3;
    const kotaSppg = unitSppg?.kabupaten || unitSppg?.kecamatan || 'Buleleng';
    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = `${kotaSppg}, 01 ${monthName} ${year}`;
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9 };

    curRow += 1;
    sheet.mergeCells(`A${curRow}:C${curRow}`);
    sheet.getCell(`A${curRow}`).value = 'Kepala SPPG,';
    sheet.getCell(`A${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    sheet.mergeCells(`D${curRow}:F${curRow}`);
    sheet.getCell(`D${curRow}`).value = 'Pengawas Keuangan,';
    sheet.getCell(`D${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`D${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = 'Pengawas Gizi,';
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    curRow += 4;
    const kepalaNama = rabData?.kepala_sppg_nama || pejabatSppg?.kepala_sppg || 'Kepala SPPG';
    const keuNama = rabData?.pengawas_keuangan_nama || pejabatSppg?.pengawas_keuangan || 'Pengawas Keuangan';
    const giziNama = rabData?.pengawas_gizi_nama || pejabatSppg?.pengawas_gizi || 'Pengawas Gizi';

    sheet.mergeCells(`A${curRow}:C${curRow}`);
    sheet.getCell(`A${curRow}`).value = `( ${kepalaNama} )`;
    sheet.getCell(`A${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    sheet.mergeCells(`D${curRow}:F${curRow}`);
    sheet.getCell(`D${curRow}`).value = `( ${keuNama} )`;
    sheet.getCell(`D${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`D${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = `( ${giziNama} )`;
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    // Column Widths
    sheet.columns = [
        { width: 6 },   // A: No
        { width: 44 },  // B: Kebutuhan
        { width: 16 },  // C: Jumlah Paket
        { width: 16 },  // D: Harga Satuan
        { width: 18 },  // E: Biaya per hari
        { width: 22 },  // F: Biaya per 2 mingguan
        { width: 14 },  // G: Hari Efektif
        { width: 20 },  // H: Biaya per bulan
        { width: 34 },  // I: Keterangan
    ];

    // SHEET 2: Rincian Operasional (17 Item) jika tersedia
    if (rabData?.rincian_operasional && rabData.rincian_operasional.length > 0) {
        const sheetOps = workbook.addWorksheet('Rincian Operasional');
        sheetOps.views = [{ showGridLines: true }];

        applyKopSurat(sheetOps, 6, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

        sheetOps.getRow(5).height = 10;
        sheetOps.getRow(6).height = 20;
        sheetOps.mergeCells('A6:F6');
        sheetOps.getCell('A6').value = 'RINCIAN ESTIMASI BIAYA OPERASIONAL (17 POS)';
        sheetOps.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
        sheetOps.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

        sheetOps.getRow(7).height = 16;
        sheetOps.mergeCells('A7:F7');
        sheetOps.getCell('A7').value = `Bulan: ${monthName} ${year}`;
        sheetOps.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
        sheetOps.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

        sheetOps.getRow(8).height = 10;

        sheetOps.getRow(9).values = [
            'No',
            'Komponen Operasional',
            'Dasar Perhitungan Otomatis',
            'Satuan',
            'Estimasi Biaya per Bulan (Rp)',
            'Keterangan / Rincian Riil'
        ];
        sheetOps.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
        sheetOps.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        sheetOps.getRow(9).height = 28;

        for (let c = 1; c <= 6; c++) {
            sheetOps.getRow(9).getCell(c).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FF1E293B' }
            };
            sheetOps.getRow(9).getCell(c).border = BORDER_HEADER;
        }

        rabData.rincian_operasional.forEach((op, idx) => {
            const rNum = 10 + idx;
            const r = sheetOps.getRow(rNum);
            r.values = [
                idx + 1,
                op.nama || '',
                op.rumus_dasar || op.deskripsi_rumus || '-',
                op.satuan || 'Bulan',
                Number(op.biaya) || 0,
                op.keterangan || ''
            ];
            r.font = { name: 'Arial', size: 9 };
            r.alignment = { vertical: 'middle', wrapText: true };
            r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
            r.getCell(5).numFmt = '#,##0';

            for (let c = 1; c <= 6; c++) {
                r.getCell(c).border = BORDER_THIN;
            }

            if (idx % 2 === 1) {
                for (let c = 1; c <= 6; c++) {
                    r.getCell(c).fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: 'FFF8FAFC' }
                    };
                }
            }
        });

        const totalOpsRow = 10 + rabData.rincian_operasional.length;
        const totalOpsVal = rabData.rincian_operasional.reduce((acc, it) => acc + (Number(it.biaya) || 0), 0);
        sheetOps.mergeCells(`B${totalOpsRow}:D${totalOpsRow}`);
        sheetOps.getRow(totalOpsRow).getCell(2).value = 'Total Estimasi 17 Pos Operasional';
        sheetOps.getRow(totalOpsRow).getCell(5).value = {
            formula: `SUM(E10:E${totalOpsRow - 1})`,
            result: totalOpsVal
        };
        sheetOps.getRow(totalOpsRow).font = { name: 'Arial', size: 9, bold: true };
        sheetOps.getRow(totalOpsRow).getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
        sheetOps.getRow(totalOpsRow).getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
        sheetOps.getRow(totalOpsRow).getCell(5).numFmt = '#,##0';

        for (let c = 1; c <= 6; c++) {
            sheetOps.getRow(totalOpsRow).getCell(c).border = BORDER_TOTAL;
            sheetOps.getRow(totalOpsRow).getCell(c).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFFEF3C7' }
            };
        }

        sheetOps.columns = [
            { width: 6 },   // No
            { width: 44 },  // Komponen
            { width: 38 },  // Rumus
            { width: 14 },  // Satuan
            { width: 24 },  // Biaya
            { width: 40 },  // Keterangan
        ];
    }

    // SHEET 3: TABEL BANTU OPERASIONAL (Persis format spreadsheet user)
    const bantuItems = (rabData?.rincian_operasional_bantu && rabData.rincian_operasional_bantu.length > 0)
        ? rabData.rincian_operasional_bantu
        : DEFAULT_TABEL_BANTU_OPERASIONAL;

    const sheetBantu = workbook.addWorksheet('Tabel Bantu Operasional');
    sheetBantu.views = [{ showGridLines: true }];

    applyKopSurat(sheetBantu, 13, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

    sheetBantu.getRow(5).height = 10;
    sheetBantu.getRow(6).height = 20;
    sheetBantu.mergeCells('A6:M6');
    sheetBantu.getCell('A6').value = 'TABEL BANTU RINCIAN PENGELUARAN OPERASIONAL';
    sheetBantu.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheetBantu.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetBantu.getRow(7).height = 16;
    sheetBantu.mergeCells('A7:M7');
    sheetBantu.getCell('A7').value = `Bulan: ${monthName} ${year} (${hariEfektif} Hari Pelayanan Efektif)`;
    sheetBantu.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
    sheetBantu.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetBantu.getRow(8).height = 10;

    // Headers Row 9 & 10
    sheetBantu.getRow(9).values = [
        'No',
        'Nama Penggunaan Barang',
        'Harga Pokok (Rp)',
        'Jumlah Penggunaan Per', '', '', '',
        'Nominal Pengeluaran Per (Rp)', '', '', '',
        'Rerata Pengeluaran Harian (Rp)',
        'Total Pengeluaran (Rp)'
    ];
    sheetBantu.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    sheetBantu.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    sheetBantu.getRow(9).height = 26;

    sheetBantu.getRow(10).values = [
        '', '', '',
        'Hari (1 Hari)', 'Minggu (5 Hari)', 'Periode (10 Hari)', 'Bulan (20 Hari)',
        'Hari (1 Hari)', 'Minggu (5 Hari)', 'Periode (10 Hari)', 'Bulan (20 Hari)',
        '', ''
    ];
    sheetBantu.getRow(10).font = { name: 'Arial', size: 8, bold: true, color: { argb: 'FFFFFFFF' } };
    sheetBantu.getRow(10).alignment = { horizontal: 'center', vertical: 'middle' };
    sheetBantu.getRow(10).height = 22;

    sheetBantu.mergeCells('A9:A10');
    sheetBantu.mergeCells('B9:B10');
    sheetBantu.mergeCells('C9:C10');
    sheetBantu.mergeCells('D9:G9');
    sheetBantu.mergeCells('H9:K9');
    sheetBantu.mergeCells('L9:L10');
    sheetBantu.mergeCells('M9:M10');

    for (let c = 1; c <= 13; c++) {
        sheetBantu.getRow(9).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF1E293B' }
        };
        sheetBantu.getRow(9).getCell(c).border = BORDER_HEADER;
        sheetBantu.getRow(10).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF334155' }
        };
        sheetBantu.getRow(10).getCell(c).border = BORDER_HEADER;
    }

    const startBantuRow = 11;
    bantuItems.forEach((it, idx) => {
        const rNum = startBantuRow + idx;
        const row = sheetBantu.getRow(rNum);

        const hp = Number(it.harga_pokok) || 0;
        const qH = Number(it.qty_hari) || 0;
        const qM = Number(it.qty_minggu) || 0;
        const qP = Number(it.qty_periode) || 0;
        const qB = Number(it.qty_bulan) || 0;

        const nomH = hp * qH;
        const nomM = hp * qM;
        const nomP = hp * qP;
        const nomB = hp * qB;
        const rerata = (nomH / 1) + (nomM / 5) + (nomP / 10) + (nomB / 20);
        const total = nomH + nomM + nomP + nomB;

        row.values = [
            idx + 1,
            it.nama || '',
            hp,
            qH || null,
            qM || null,
            qP || null,
            qB || null,
            nomH,
            nomM,
            nomP,
            nomB,
            rerata,
            total
        ];

        // Rumus Excel Persis Spreadsheet
        row.getCell(8).value = { formula: `C${rNum}*D${rNum}`, result: nomH };
        row.getCell(9).value = { formula: `C${rNum}*E${rNum}`, result: nomM };
        row.getCell(10).value = { formula: `C${rNum}*F${rNum}`, result: nomP };
        row.getCell(11).value = { formula: `C${rNum}*G${rNum}`, result: nomB };
        row.getCell(12).value = {
            formula: `(H${rNum}/1)+(I${rNum}/5)+(J${rNum}/10)+(K${rNum}/20)`,
            result: rerata
        };
        row.getCell(13).value = {
            formula: `H${rNum}+I${rNum}+J${rNum}+K${rNum}`,
            result: total
        };

        row.font = { name: 'Arial', size: 9 };
        row.alignment = { vertical: 'middle' };
        row.getCell(1).alignment = { horizontal: 'center' };
        row.getCell(2).alignment = { horizontal: 'left' };
        row.getCell(3).alignment = { horizontal: 'right' };
        row.getCell(3).numFmt = '#,##0';

        for (let c = 4; c <= 7; c++) {
            row.getCell(c).alignment = { horizontal: 'center' };
            row.getCell(c).numFmt = '0.00';
        }
        for (let c = 8; c <= 13; c++) {
            row.getCell(c).alignment = { horizontal: 'right' };
            row.getCell(c).numFmt = '#,##0';
        }

        for (let c = 1; c <= 13; c++) {
            row.getCell(c).border = BORDER_THIN;
        }

        if (idx % 2 === 1) {
            for (let c = 1; c <= 13; c++) {
                row.getCell(c).fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFF8FAFC' }
                };
            }
        }
    });

    const totBantuRow = startBantuRow + bantuItems.length;
    const totBantuRowObj = sheetBantu.getRow(totBantuRow);
    const lastBantuDataRow = totBantuRow - 1;

    totBantuRowObj.getCell(1).value = '';
    totBantuRowObj.getCell(2).value = 'Total Pengeluaran';
    totBantuRowObj.getCell(3).value = { formula: `SUM(C${startBantuRow}:C${lastBantuDataRow})` };
    totBantuRowObj.getCell(8).value = { formula: `SUM(H${startBantuRow}:H${lastBantuDataRow})` };
    totBantuRowObj.getCell(9).value = { formula: `SUM(I${startBantuRow}:I${lastBantuDataRow})` };
    totBantuRowObj.getCell(10).value = { formula: `SUM(J${startBantuRow}:J${lastBantuDataRow})` };
    totBantuRowObj.getCell(11).value = { formula: `SUM(K${startBantuRow}:K${lastBantuDataRow})` };
    totBantuRowObj.getCell(12).value = { formula: `SUM(L${startBantuRow}:L${lastBantuDataRow})` };
    totBantuRowObj.getCell(13).value = { formula: `SUM(M${startBantuRow}:M${lastBantuDataRow})` };

    totBantuRowObj.font = { name: 'Arial', size: 9, bold: true };
    totBantuRowObj.alignment = { vertical: 'middle' };
    totBantuRowObj.getCell(2).alignment = { horizontal: 'center' };
    totBantuRowObj.getCell(3).numFmt = '#,##0';
    for (let c = 8; c <= 13; c++) {
        totBantuRowObj.getCell(c).alignment = { horizontal: 'right' };
        totBantuRowObj.getCell(c).numFmt = '#,##0';
    }

    for (let c = 1; c <= 13; c++) {
        totBantuRowObj.getCell(c).border = BORDER_TOTAL;
        totBantuRowObj.getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF3C7' }
        };
    }

    // Evaluasi Pagu BGN vs Realisasi
    const opItem = rabData?.rincian_porsi?.[4] || {};
    const jmlPM = Number(opItem?.jumlah_paket) || 0;
    const paguBulan = jmlPM * 3000 * hariEfektif;

    let evalRow = totBantuRow + 2;
    sheetBantu.mergeCells(`B${evalRow}:E${evalRow}`);
    sheetBantu.getCell(`B${evalRow}`).value = `Pagu Operasional BGN (${jmlPM} PM × Rp 3.000 × ${hariEfektif} Hari) :`;
    sheetBantu.getCell(`B${evalRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheetBantu.getCell(`F${evalRow}`).value = paguBulan;
    sheetBantu.getCell(`F${evalRow}`).numFmt = '#,##0';
    sheetBantu.getCell(`F${evalRow}`).font = { name: 'Arial', size: 9, bold: true };

    evalRow++;
    sheetBantu.mergeCells(`B${evalRow}:E${evalRow}`);
    sheetBantu.getCell(`B${evalRow}`).value = `Estimasi Riil Bulanan (Rerata Harian × ${hariEfektif} Hari) :`;
    sheetBantu.getCell(`B${evalRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheetBantu.getCell(`F${evalRow}`).value = { formula: `L${totBantuRow}*${hariEfektif}` };
    sheetBantu.getCell(`F${evalRow}`).numFmt = '#,##0';
    sheetBantu.getCell(`F${evalRow}`).font = { name: 'Arial', size: 9, bold: true };

    evalRow++;
    sheetBantu.mergeCells(`B${evalRow}:E${evalRow}`);
    sheetBantu.getCell(`B${evalRow}`).value = `Selisih Anggaran Operasional (Pagu - Riil) :`;
    sheetBantu.getCell(`B${evalRow}`).font = { name: 'Arial', size: 9, bold: true };
    sheetBantu.getCell(`F${evalRow}`).value = { formula: `F${evalRow - 2}-F${evalRow - 1}` };
    sheetBantu.getCell(`F${evalRow}`).numFmt = '#,##0';
    sheetBantu.getCell(`F${evalRow}`).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0F766E' } };

    sheetBantu.columns = [
        { width: 6 },   // No
        { width: 38 },  // Nama Barang
        { width: 16 },  // Harga Pokok
        { width: 14 },  // Qty Hari
        { width: 14 },  // Qty Minggu
        { width: 15 },  // Qty Periode
        { width: 15 },  // Qty Bulan
        { width: 16 },  // Nom Hari
        { width: 16 },  // Nom Minggu
        { width: 16 },  // Nom Periode
        { width: 16 },  // Nom Bulan
        { width: 22 },  // Rerata Harian
        { width: 22 },  // Total Pengeluaran
    ];

    // SHEET 4: JADWAL & PENETAPAN PM HARIAN PER TANGGAL
    if (rabData?.jadwal_pm_harian && rabData.jadwal_pm_harian.length > 0) {
        const sheetPm = workbook.addWorksheet('Jadwal PM Harian');
        sheetPm.views = [{ showGridLines: true }];

        applyKopSurat(sheetPm, 8, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

        sheetPm.getRow(5).height = 10;
        sheetPm.getRow(6).height = 20;
        sheetPm.mergeCells('A6:H6');
        sheetPm.getCell('A6').value = 'JADWAL & PENETAPAN SASARAN PENERIMA MANFAAT (PM) HARIAN';
        sheetPm.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
        sheetPm.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

        sheetPm.getRow(7).height = 16;
        sheetPm.mergeCells('A7:H7');
        sheetPm.getCell('A7').value = `Bulan: ${monthName} ${year} (Rekapitulasi Pelayanan Efektif per Tanggal)`;
        sheetPm.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
        sheetPm.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

        sheetPm.getRow(8).height = 10;

        // Header Table
        sheetPm.getRow(9).values = [
            'No',
            'Tanggal',
            'Hari',
            'Status Layanan',
            'Sasaran Porsi Kecil (Balita, TK/SD 1-3)',
            'Sasaran Porsi Besar (SD 4-6, SMP, SMA, 3B)',
            'Total Sasaran PM Hari Ini',
            'Keterangan / Catatan Pelayanan'
        ];
        sheetPm.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
        sheetPm.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        sheetPm.getRow(9).height = 28;

        for (let c = 1; c <= 8; c++) {
            sheetPm.getRow(9).getCell(c).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FF0F766E' }
            };
            sheetPm.getRow(9).getCell(c).border = BORDER_HEADER;
        }

        const startPmRow = 10;
        rabData.jadwal_pm_harian.forEach((it, idx) => {
            const rNum = startPmRow + idx;
            const r = sheetPm.getRow(rNum);
            const pk = Number(it.porsi_kecil) || 0;
            const pb = Number(it.porsi_besar) || 0;
            const tot = pk + pb;
            const statusText = it.is_aktif ? 'Melayani' : 'Libur';

            r.values = [
                idx + 1,
                it.tanggal || '',
                it.nama_hari || '',
                statusText,
                pk,
                pb,
                tot,
                it.keterangan || ''
            ];

            // Formula Total Hari Ini
            r.getCell(7).value = { formula: `E${rNum}+F${rNum}`, result: tot };

            r.font = { name: 'Arial', size: 9 };
            r.alignment = { vertical: 'middle' };
            r.getCell(1).alignment = { horizontal: 'center' };
            r.getCell(2).alignment = { horizontal: 'center' };
            r.getCell(3).alignment = { horizontal: 'center' };
            r.getCell(4).alignment = { horizontal: 'center' };
            r.getCell(5).alignment = { horizontal: 'right' };
            r.getCell(6).alignment = { horizontal: 'right' };
            r.getCell(7).alignment = { horizontal: 'right' };
            r.getCell(8).alignment = { horizontal: 'left' };

            r.getCell(5).numFmt = '#,##0';
            r.getCell(6).numFmt = '#,##0';
            r.getCell(7).numFmt = '#,##0';

            for (let c = 1; c <= 8; c++) {
                r.getCell(c).border = BORDER_THIN;
            }

            if (!it.is_aktif) {
                for (let c = 1; c <= 8; c++) {
                    r.getCell(c).fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: 'FFF1F5F9' }
                    };
                    r.getCell(c).font = { name: 'Arial', size: 9, color: { argb: 'FF94A3B8' } };
                }
            } else if (idx % 2 === 1) {
                for (let c = 1; c <= 8; c++) {
                    r.getCell(c).fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: 'FFF8FAFC' }
                    };
                }
            }
        });

        const totPmRow = startPmRow + rabData.jadwal_pm_harian.length;
        const totPmRowObj = sheetPm.getRow(totPmRow);
        const lastPmDataRow = totPmRow - 1;

        totPmRowObj.getCell(1).value = '';
        totPmRowObj.getCell(2).value = 'Total Akumulasi';
        totPmRowObj.getCell(4).value = { formula: `COUNTIF(D${startPmRow}:D${lastPmDataRow}, "Melayani")`, result: hariEfektif };
        totPmRowObj.getCell(5).value = { formula: `SUM(E${startPmRow}:E${lastPmDataRow})` };
        totPmRowObj.getCell(6).value = { formula: `SUM(F${startPmRow}:F${lastPmDataRow})` };
        totPmRowObj.getCell(7).value = { formula: `SUM(G${startPmRow}:G${lastPmDataRow})` };
        totPmRowObj.getCell(8).value = '*Total paket porsi yang disajikan dalam 1 bulan';

        totPmRowObj.font = { name: 'Arial', size: 9, bold: true };
        totPmRowObj.alignment = { vertical: 'middle' };
        totPmRowObj.getCell(2).alignment = { horizontal: 'center' };
        totPmRowObj.getCell(4).alignment = { horizontal: 'center' };
        totPmRowObj.getCell(5).alignment = { horizontal: 'right' };
        totPmRowObj.getCell(6).alignment = { horizontal: 'right' };
        totPmRowObj.getCell(7).alignment = { horizontal: 'right' };
        totPmRowObj.getCell(5).numFmt = '#,##0';
        totPmRowObj.getCell(6).numFmt = '#,##0';
        totPmRowObj.getCell(7).numFmt = '#,##0';

        for (let c = 1; c <= 8; c++) {
            totPmRowObj.getCell(c).border = BORDER_TOTAL;
            totPmRowObj.getCell(c).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFFEF3C7' }
            };
        }

        // Baris Rerata Harian
        const avgRow = totPmRow + 1;
        const avgRowObj = sheetPm.getRow(avgRow);
        avgRowObj.getCell(2).value = 'Rerata per Hari Aktif';
        avgRowObj.getCell(4).value = 'PM / Hari';
        avgRowObj.getCell(5).value = { formula: `ROUND(E${totPmRow}/MAX(D${totPmRow}, 1), 0)` };
        avgRowObj.getCell(6).value = { formula: `ROUND(F${totPmRow}/MAX(D${totPmRow}, 1), 0)` };
        avgRowObj.getCell(7).value = { formula: `ROUND(G${totPmRow}/MAX(D${totPmRow}, 1), 0)` };
        avgRowObj.getCell(8).value = '*Digunakan sebagai Kuota Paket MBG pada Lampiran III RAB';

        avgRowObj.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0F766E' } };
        avgRowObj.alignment = { vertical: 'middle' };
        avgRowObj.getCell(2).alignment = { horizontal: 'center' };
        avgRowObj.getCell(4).alignment = { horizontal: 'center' };
        avgRowObj.getCell(5).alignment = { horizontal: 'right' };
        avgRowObj.getCell(6).alignment = { horizontal: 'right' };
        avgRowObj.getCell(7).alignment = { horizontal: 'right' };
        avgRowObj.getCell(5).numFmt = '#,##0';
        avgRowObj.getCell(6).numFmt = '#,##0';
        avgRowObj.getCell(7).numFmt = '#,##0';

        for (let c = 1; c <= 8; c++) {
            avgRowObj.getCell(c).border = BORDER_THIN;
            avgRowObj.getCell(c).fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFF0FDFA' }
            };
        }

        sheetPm.columns = [
            { width: 6 },   // No
            { width: 14 },  // Tanggal
            { width: 12 },  // Hari
            { width: 16 },  // Status Layanan
            { width: 22 },  // Porsi Kecil
            { width: 22 },  // Porsi Besar
            { width: 22 },  // Total PM
            { width: 34 },  // Keterangan
        ];
    }

    // Trigger Browser Download
    const filename = `RAB_Bulanan_${monthName}_${year}_${unitSppg?.nama?.replace(/[^a-zA-Z0-9]/g, '_') || 'SPPG'}.xlsx`;
    await triggerDownload(workbook, filename);
}

/**
 * Download RAB Tahunan dalam format Excel (.xlsx) dengan Kop SPPG resmi dan rumus perhitungan Excel hidup.
 */
export async function downloadRabTahunanExcel({
    rabList = [],
    year = 2026,
    unitSppg = null,
    pejabatSppg = {},
    rekeningUtama = null,
    kopDokumen = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE - BGN SPPG';
    workbook.created = new Date();

    // Siapkan Logo KOP
    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        if (LOGO_BGN_RAW_BASE64) {
            logoBgnId = workbook.addImage({ base64: LOGO_BGN_RAW_BASE64, extension: 'png' });
        }
        if (LOGO_YAYASAN_RAW_BASE64) {
            logoYayasanId = workbook.addImage({ base64: LOGO_YAYASAN_RAW_BASE64, extension: 'png' });
        }
    } catch (e) {
        console.warn('Gagal load logo ke excel:', e);
    }

    // 1. SHEET UTAMA: RAB TAHUNAN
    const sheet = workbook.addWorksheet(`RAB Tahunan ${year}`);
    sheet.views = [{ showGridLines: true }];

    // 1. KOP RESMI SPPG (Baris 1 - 4)
    applyKopSurat(sheet, 9, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

    // 2. JUDUL RAB TAHUNAN & PERIODE (Baris 6 & 7)
    sheet.getRow(5).height = 10;

    sheet.getRow(6).height = 20;
    sheet.mergeCells('A6:I6');
    sheet.getCell('A6').value = 'RENCANA ANGGARAN BIAYA (RAB) TAHUNAN';
    sheet.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheet.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.getRow(7).height = 16;
    sheet.mergeCells('A7:I7');
    sheet.getCell('A7').value = `Tahun Anggaran: ${year}`;
    sheet.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
    sheet.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet.getRow(8).height = 10;

    const savedCount = Object.keys(rabMap).length;

    // Header Bab VI (Baris 9 & 10)
    const headerRow1 = [
        "No",
        "Kebutuhan",
        "Jumlah paket MBG* (Paket)",
        "Harga Satuan (Rp)",
        "Biaya per hari (Rp)",
        "Biaya per 2 mingguan (Rp)",
        "Biaya per bulan (Rp)",
        `Jumlah Biaya Akumulasi (${savedCount} Bln Dibuat s/d 31 Des ${year})`,
        "Keterangan"
    ];

    const subHeaderRow = [
        "1",
        "2",
        "3",
        "4",
        "(5)=(3)*(4)",
        "(6)=(5)*12",
        "7",
        `(8)=Akumulasi Bulan Dibuat`,
        "9"
    ];

    sheet.getRow(9).values = headerRow1;
    sheet.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    sheet.getRow(9).height = 32;

    sheet.getRow(10).values = subHeaderRow;
    sheet.getRow(10).font = { name: 'Arial', size: 8, italic: true, bold: true };
    sheet.getRow(10).alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(10).height = 18;

    for (let c = 1; c <= 9; c++) {
        sheet.getRow(9).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF0F766E' }
        };
        sheet.getRow(9).getCell(c).border = BORDER_HEADER;

        sheet.getRow(10).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFF0FDFA' }
        };
        sheet.getRow(10).getCell(c).border = BORDER_THIN;
    }

    const rabMap = {};
    rabList.forEach(r => { rabMap[r.bulan] = r; });

    const firstActiveRab = rabList.find(r => r.rincian_porsi && r.rincian_porsi.length > 0) || {
        rincian_porsi: [
            { nama: "Bahan makanan anak balita, siswa TK/PAUD/RA dan SD/MI/SD SLB Kelas 1-3", jumlah_paket: 0, harga_satuan: 8000, keterangan: "Maksimal 8.000 dengan dasar perhitungan sesuai biaya riil" },
            { nama: "Bahan Makanan uji organoleptik untuk kelompok anak balita, siswa TK/PAUD/RA dan SD/MI/SD SLB Kelas 1-3", jumlah_paket: 1, harga_satuan: 8000, keterangan: "1 paket per pengantaran untuk kelompok 3B" },
            { nama: "Bahan makanan kelompok siswa SD/MI/ SD SLB Kelas 4-6, SMP/MTS/SMP SLB, SMA/MA/SMK/SMA SLB, Pendidik, Tenaga Kependidikan, ibu hamil dan ibu menyusui", jumlah_paket: 0, harga_satuan: 10000, keterangan: "Maksimal 10.000 dengan dasar perhitungan sesuai biaya riil" },
            { nama: "Bahan Makanan uji organoleptik untuk kelompok SD/MI/ SD SLB Kelas 4-6, SMP/MTS/SMP SLB, SMA/MA/SMK/SMA SLB, Pendidik, Tenaga Kependidikan, ibu hamil dan ibu menyusui", jumlah_paket: 1, harga_satuan: 10000, keterangan: "1 paket per pengantaran untuk kelompok 3B" },
            { nama: "Operasional (listrik, gas, air, insentif relawan, insentif kader/TPK, insentif satuan pendidikan, asuransi kecelakaan kerja relawan termasuk Jurutama Masak, Biaya Bahan Bakar Minyak Kendaraan Operasional dan Genset, internet, satpam, rapat koordinasi satu kali sebulan dll)", jumlah_paket: 0, harga_satuan: 3000, keterangan: "Maksimal 3.000 dengan dasar perhitungan sesuai biaya riil" }
        ],
        hari_efektif: 20
    };

    const startRow = 11;
    firstActiveRab.rincian_porsi.forEach((item, idx) => {
        const rowNum = startRow + idx;
        const row = sheet.getRow(rowNum);

        let totalTahunanItem = 0;
        for (let m = 1; m <= 12; m++) {
            const mRab = rabMap[m];
            if (mRab && mRab.rincian_porsi && mRab.rincian_porsi[idx]) {
                const pItem = mRab.rincian_porsi[idx];
                const hEff = mRab.hari_efektif || 20;
                const mCost = (Number(pItem.jumlah_paket) || 0) * (Number(pItem.harga_satuan) || 0) * hEff;
                totalTahunanItem += mCost;
            }
        }

        const jml = Number(item.jumlah_paket) || 0;
        const hrg = Number(item.harga_satuan) || 0;
        const perHari = jml * hrg;
        const per2Minggu = perHari * 12;
        const hEff = firstActiveRab.hari_efektif || 20;
        const defBulanCost = perHari * hEff;
        const costTahunan = totalTahunanItem;

        row.values = [
            idx + 1,
            item.nama || '',
            jml,
            hrg,
            perHari,
            per2Minggu,
            defBulanCost,
            costTahunan,
            item.keterangan || ''
        ];

        // Rumus Excel: (5)=(3)*(4), (6)=(5)*12, (7)=Biaya per bulan, (8)=Total dari Sheet Rekap 12 Bulan
        const mRowNum = 10 + idx;
        const matrixSheetName = `'Rekap 12 Bulan ${year}'`;
        row.getCell(5).value = { formula: `C${rowNum}*D${rowNum}`, result: perHari };
        row.getCell(6).value = { formula: `E${rowNum}*12`, result: per2Minggu };
        row.getCell(7).value = { formula: `E${rowNum}*${hEff}`, result: defBulanCost };
        row.getCell(8).value = { formula: `${matrixSheetName}!P${mRowNum}`, result: costTahunan };

        row.font = { name: 'Arial', size: 9 };
        row.alignment = { vertical: 'middle', wrapText: true };
        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(3).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(4).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(9).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

        row.getCell(3).numFmt = '#,##0';
        row.getCell(4).numFmt = '#,##0';
        row.getCell(5).numFmt = '#,##0';
        row.getCell(6).numFmt = '#,##0';
        row.getCell(7).numFmt = '#,##0';
        row.getCell(8).numFmt = '#,##0';

        for (let c = 1; c <= 9; c++) {
            row.getCell(c).border = BORDER_THIN;
        }

        if (idx % 2 === 1) {
            for (let c = 1; c <= 9; c++) {
                row.getCell(c).fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFF8FAFC' }
                };
            }
        }
    });

    const totalRowNum = startRow + firstActiveRab.rincian_porsi.length;
    const totalRow = sheet.getRow(totalRowNum);
    const lastDataRow = totalRowNum - 1;

    const totHrgHarian = firstActiveRab.rincian_porsi.reduce((s, it) => s + ((Number(it.jumlah_paket) || 0) * (Number(it.harga_satuan) || 0)), 0);
    const hEff = firstActiveRab.hari_efektif || 20;
    const tot2Mingguan = totHrgHarian * 12;
    const totBulanan = totHrgHarian * hEff;
    const totTahunan = firstActiveRab.rincian_porsi.reduce((sum, _, idx) => {
        let t = 0;
        for (let m = 1; m <= 12; m++) {
            const mRab = rabMap[m];
            if (mRab && mRab.rincian_porsi && mRab.rincian_porsi[idx]) {
                const p = mRab.rincian_porsi[idx];
                const h = mRab.hari_efektif || 20;
                t += (Number(p.jumlah_paket) || 0) * (Number(p.harga_satuan) || 0) * h;
            }
        }
        return sum + t;
    }, 0);

    totalRow.getCell(2).value = 'Total';
    totalRow.getCell(5).value = { formula: `SUM(E${startRow}:E${lastDataRow})`, result: totHrgHarian };
    totalRow.getCell(6).value = { formula: `SUM(F${startRow}:F${lastDataRow})`, result: tot2Mingguan };
    totalRow.getCell(7).value = { formula: `SUM(G${startRow}:G${lastDataRow})`, result: totBulanan };
    totalRow.getCell(8).value = { formula: `SUM(H${startRow}:H${lastDataRow})`, result: totTahunan };

    totalRow.font = { name: 'Arial', size: 9, bold: true };
    totalRow.alignment = { vertical: 'middle' };
    totalRow.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
    totalRow.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };

    totalRow.getCell(5).numFmt = '#,##0';
    totalRow.getCell(6).numFmt = '#,##0';
    totalRow.getCell(7).numFmt = '#,##0';
    totalRow.getCell(8).numFmt = '#,##0';

    for (let c = 1; c <= 9; c++) {
        totalRow.getCell(c).border = BORDER_TOTAL;
        totalRow.getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF3C7' }
        };
    }

    // Tanda Tangan
    let curRow = totalRowNum + 3;
    const kotaSppg = unitSppg?.kabupaten || 'Buleleng';
    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = `${kotaSppg}, 31 Desember ${year}`;
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9 };

    curRow += 1;
    sheet.mergeCells(`A${curRow}:C${curRow}`);
    sheet.getCell(`A${curRow}`).value = 'Kepala SPPG,';
    sheet.getCell(`A${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    sheet.mergeCells(`D${curRow}:F${curRow}`);
    sheet.getCell(`D${curRow}`).value = 'Pengawas Keuangan,';
    sheet.getCell(`D${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`D${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = 'Pengawas Gizi,';
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9, bold: true };

    curRow += 4;
    const kepalaNama = pejabatSppg?.kepala_sppg || 'Kepala SPPG';
    const keuNama = pejabatSppg?.pengawas_keuangan || 'Pengawas Keuangan';
    const giziNama = pejabatSppg?.pengawas_gizi || 'Pengawas Gizi';

    sheet.mergeCells(`A${curRow}:C${curRow}`);
    sheet.getCell(`A${curRow}`).value = `( ${kepalaNama} )`;
    sheet.getCell(`A${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`A${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    sheet.mergeCells(`D${curRow}:F${curRow}`);
    sheet.getCell(`D${curRow}`).value = `( ${keuNama} )`;
    sheet.getCell(`D${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`D${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    sheet.mergeCells(`G${curRow}:I${curRow}`);
    sheet.getCell(`G${curRow}`).value = `( ${giziNama} )`;
    sheet.getCell(`G${curRow}`).alignment = { horizontal: 'center' };
    sheet.getCell(`G${curRow}`).font = { name: 'Arial', size: 9, bold: true, underline: true };

    sheet.columns = [
        { width: 6 },
        { width: 44 },
        { width: 16 },
        { width: 16 },
        { width: 18 },
        { width: 22 },
        { width: 20 },
        { width: 26 },
        { width: 34 },
    ];

    // 2. SHEET KEDUA: REKAPITULASI MATRIKS 12 BULAN (Jan - Des)
    const sheetMatrix = workbook.addWorksheet(`Rekap 12 Bulan ${year}`);
    sheetMatrix.views = [{ showGridLines: true }];

    applyKopSurat(sheetMatrix, 16, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

    sheetMatrix.getRow(5).height = 10;
    sheetMatrix.getRow(6).height = 20;
    sheetMatrix.mergeCells('A6:P6');
    sheetMatrix.getCell('A6').value = `REKAPITULASI RENCANA ANGGARAN BIAYA 12 BULAN TAHUN ${year}`;
    sheetMatrix.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheetMatrix.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetMatrix.getRow(7).height = 16;
    sheetMatrix.mergeCells('A7:P7');
    sheetMatrix.getCell('A7').value = `Tahun Anggaran: ${year}`;
    sheetMatrix.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
    sheetMatrix.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetMatrix.getRow(8).height = 10;

    const matrixHeader = ['No', 'Komponen Anggaran', 'Satuan'];
    for (let m = 1; m <= 12; m++) {
        matrixHeader.push(MONTH_NAMES[m].substring(0, 3));
    }
    matrixHeader.push('Total 1 Tahun');

    sheetMatrix.getRow(9).values = matrixHeader;
    sheetMatrix.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    sheetMatrix.getRow(9).alignment = { horizontal: 'center', vertical: 'middle' };
    sheetMatrix.getRow(9).height = 26;

    for (let c = 1; c <= 16; c++) {
        sheetMatrix.getRow(9).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF0F766E' }
        };
        sheetMatrix.getRow(9).getCell(c).border = BORDER_HEADER;
    }

    firstActiveRab.rincian_porsi.forEach((item, idx) => {
        const mRowNum = 10 + idx;
        const mRow = sheetMatrix.getRow(mRowNum);
        const rowVals = [idx + 1, item.nama, 'Rp'];

        let itemTotal = 0;
        for (let m = 1; m <= 12; m++) {
            const mRab = rabMap[m];
            let val = 0;
            if (mRab && mRab.rincian_porsi && mRab.rincian_porsi[idx]) {
                const p = mRab.rincian_porsi[idx];
                const h = mRab.hari_efektif || 20;
                val = (Number(p.jumlah_paket) || 0) * (Number(p.harga_satuan) || 0) * h;
            } else {
                // Bulan yang belum dibuat bernilai 0 (tidak dihitung dalam akumulasi)
                val = 0;
            }
            rowVals.push(val);
            itemTotal += val;
        }
        rowVals.push(itemTotal);

        mRow.values = rowVals;
        // Rumus Excel Total 1 Tahun untuk item ini (Jumlah Jan s/d Des)
        mRow.getCell(16).value = {
            formula: `SUM(D${mRowNum}:O${mRowNum})`,
            result: itemTotal
        };

        mRow.font = { name: 'Arial', size: 9 };
        mRow.alignment = { vertical: 'middle' };
        mRow.getCell(1).alignment = { horizontal: 'center' };
        mRow.getCell(3).alignment = { horizontal: 'center' };

        for (let c = 4; c <= 16; c++) {
            mRow.getCell(c).alignment = { horizontal: 'right' };
            mRow.getCell(c).numFmt = '#,##0';
        }

        for (let c = 1; c <= 16; c++) {
            mRow.getCell(c).border = BORDER_THIN;
        }
    });

    // Baris Total Matrix
    const totMatRow = 10 + firstActiveRab.rincian_porsi.length;
    const totRowObj = sheetMatrix.getRow(totMatRow);
    const totVals = ['', 'TOTAL KESELURUHAN', 'Rp'];

    for (let c = 4; c <= 16; c++) {
        const colLetter = getColumnLetter(c);
        let colSum = 0;
        for (let r = 10; r < totMatRow; r++) {
            const cellVal = sheetMatrix.getRow(r).getCell(c).value;
            const num = typeof cellVal === 'object' && cellVal?.result !== undefined ? cellVal.result : (Number(cellVal) || 0);
            colSum += num;
        }
        totVals.push({
            formula: `SUM(${colLetter}10:${colLetter}${totMatRow - 1})`,
            result: colSum
        });
    }

    totRowObj.values = totVals;
    totRowObj.font = { name: 'Arial', size: 9, bold: true };
    totRowObj.alignment = { vertical: 'middle' };
    totRowObj.getCell(2).alignment = { horizontal: 'center' };
    totRowObj.getCell(3).alignment = { horizontal: 'center' };

    for (let c = 4; c <= 16; c++) {
        totRowObj.getCell(c).alignment = { horizontal: 'right' };
        totRowObj.getCell(c).numFmt = '#,##0';
    }

    for (let c = 1; c <= 16; c++) {
        totRowObj.getCell(c).border = BORDER_TOTAL;
        totRowObj.getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF3C7' }
        };
    }

    // Catatan penjelas di bawah tabel matrix
    const noteRow = totMatRow + 2;
    sheetMatrix.mergeCells(`B${noteRow}:P${noteRow}`);
    sheetMatrix.getCell(`B${noteRow}`).value = `* Catatan: Kolom bulan dengan nilai Rp 0 menandakan bulan tersebut Belum Dibuat dan tidak dihitung ke dalam Total Akumulasi.`;
    sheetMatrix.getCell(`B${noteRow}`).font = { name: 'Arial', size: 8, italic: true, color: { argb: 'FF64748B' } };

    sheetMatrix.columns = [
        { width: 6 },
        { width: 45 },
        { width: 8 },
        { width: 14 }, { width: 14 }, { width: 14 }, { width: 14 },
        { width: 14 }, { width: 14 }, { width: 14 }, { width: 14 },
        { width: 14 }, { width: 14 }, { width: 14 }, { width: 14 },
        { width: 18 }
    ];

    // 3. SHEET KETIGA: TABEL BANTU OPERASIONAL RINCI (45 Item & Rumus Live Excel)
    const activeRabWithBantu = rabList.find(r => r.rincian_operasional_bantu && r.rincian_operasional_bantu.length > 0);
    const bantuTahunanItems = activeRabWithBantu?.rincian_operasional_bantu || DEFAULT_TABEL_BANTU_OPERASIONAL;

    const sheetBantuTahunan = workbook.addWorksheet(`Tabel Bantu Operasional`);
    sheetBantuTahunan.views = [{ showGridLines: true }];

    applyKopSurat(sheetBantuTahunan, 13, logoBgnId, logoYayasanId, unitSppg, kopDokumen);

    sheetBantuTahunan.getRow(5).height = 10;
    sheetBantuTahunan.getRow(6).height = 20;
    sheetBantuTahunan.mergeCells('A6:M6');
    sheetBantuTahunan.getCell('A6').value = 'TABEL BANTU RINCIAN PENGELUARAN OPERASIONAL';
    sheetBantuTahunan.getCell('A6').font = { name: 'Arial', size: 12, bold: true };
    sheetBantuTahunan.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetBantuTahunan.getRow(7).height = 16;
    sheetBantuTahunan.mergeCells('A7:M7');
    sheetBantuTahunan.getCell('A7').value = `Standar Rincian Operasional Tahun Anggaran: ${year}`;
    sheetBantuTahunan.getCell('A7').font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
    sheetBantuTahunan.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheetBantuTahunan.getRow(8).height = 10;

    // Headers Row 9 & 10
    sheetBantuTahunan.getRow(9).values = [
        'No',
        'Nama Penggunaan Barang',
        'Harga Pokok (Rp)',
        'Jumlah Penggunaan Per', '', '', '',
        'Nominal Pengeluaran Per (Rp)', '', '', '',
        'Rerata Pengeluaran Harian (Rp)',
        'Total Pengeluaran (Rp)'
    ];
    sheetBantuTahunan.getRow(9).font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    sheetBantuTahunan.getRow(9).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    sheetBantuTahunan.getRow(9).height = 26;

    sheetBantuTahunan.getRow(10).values = [
        '', '', '',
        'Hari (1 Hari)', 'Minggu (5 Hari)', 'Periode (10 Hari)', 'Bulan (20 Hari)',
        'Hari (1 Hari)', 'Minggu (5 Hari)', 'Periode (10 Hari)', 'Bulan (20 Hari)',
        '', ''
    ];
    sheetBantuTahunan.getRow(10).font = { name: 'Arial', size: 8, bold: true, color: { argb: 'FFFFFFFF' } };
    sheetBantuTahunan.getRow(10).alignment = { horizontal: 'center', vertical: 'middle' };
    sheetBantuTahunan.getRow(10).height = 22;

    sheetBantuTahunan.mergeCells('A9:A10');
    sheetBantuTahunan.mergeCells('B9:B10');
    sheetBantuTahunan.mergeCells('C9:C10');
    sheetBantuTahunan.mergeCells('D9:G9');
    sheetBantuTahunan.mergeCells('H9:K9');
    sheetBantuTahunan.mergeCells('L9:L10');
    sheetBantuTahunan.mergeCells('M9:M10');

    for (let c = 1; c <= 13; c++) {
        sheetBantuTahunan.getRow(9).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF1E293B' }
        };
        sheetBantuTahunan.getRow(9).getCell(c).border = BORDER_HEADER;
        sheetBantuTahunan.getRow(10).getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF334155' }
        };
        sheetBantuTahunan.getRow(10).getCell(c).border = BORDER_HEADER;
    }

    const startBantuRow = 11;
    bantuTahunanItems.forEach((it, idx) => {
        const rNum = startBantuRow + idx;
        const row = sheetBantuTahunan.getRow(rNum);

        const hp = Number(it.harga_pokok) || 0;
        const qH = Number(it.qty_hari) || 0;
        const qM = Number(it.qty_minggu) || 0;
        const qP = Number(it.qty_periode) || 0;
        const qB = Number(it.qty_bulan) || 0;

        const nomH = hp * qH;
        const nomM = hp * qM;
        const nomP = hp * qP;
        const nomB = hp * qB;
        const rerata = (nomH / 1) + (nomM / 5) + (nomP / 10) + (nomB / 20);
        const total = nomH + nomM + nomP + nomB;

        row.values = [
            idx + 1,
            it.nama || '',
            hp,
            qH || null,
            qM || null,
            qP || null,
            qB || null,
            nomH,
            nomM,
            nomP,
            nomB,
            rerata,
            total
        ];

        // Rumus Live Excel Persis Spreadsheet User
        row.getCell(8).value = { formula: `C${rNum}*D${rNum}`, result: nomH };
        row.getCell(9).value = { formula: `C${rNum}*E${rNum}`, result: nomM };
        row.getCell(10).value = { formula: `C${rNum}*F${rNum}`, result: nomP };
        row.getCell(11).value = { formula: `C${rNum}*G${rNum}`, result: nomB };
        row.getCell(12).value = {
            formula: `(H${rNum}/1)+(I${rNum}/5)+(J${rNum}/10)+(K${rNum}/20)`,
            result: rerata
        };
        row.getCell(13).value = {
            formula: `H${rNum}+I${rNum}+J${rNum}+K${rNum}`,
            result: total
        };

        row.font = { name: 'Arial', size: 9 };
        row.alignment = { vertical: 'middle' };
        row.getCell(1).alignment = { horizontal: 'center' };
        row.getCell(2).alignment = { horizontal: 'left' };
        row.getCell(3).alignment = { horizontal: 'right' };
        row.getCell(3).numFmt = '#,##0';

        for (let c = 4; c <= 7; c++) {
            row.getCell(c).alignment = { horizontal: 'center' };
            row.getCell(c).numFmt = '0.00';
        }
        for (let c = 8; c <= 13; c++) {
            row.getCell(c).alignment = { horizontal: 'right' };
            row.getCell(c).numFmt = '#,##0';
        }

        for (let c = 1; c <= 13; c++) {
            row.getCell(c).border = BORDER_THIN;
        }

        if (idx % 2 === 1) {
            for (let c = 1; c <= 13; c++) {
                row.getCell(c).fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFF8FAFC' }
                };
            }
        }
    });

    const totBantuRow = startBantuRow + bantuTahunanItems.length;
    const totBantuRowObj = sheetBantuTahunan.getRow(totBantuRow);
    const lastBantuDataRow = totBantuRow - 1;

    totBantuRowObj.getCell(1).value = '';
    totBantuRowObj.getCell(2).value = 'Total Pengeluaran';
    totBantuRowObj.getCell(3).value = { formula: `SUM(C${startBantuRow}:C${lastBantuDataRow})` };
    totBantuRowObj.getCell(8).value = { formula: `SUM(H${startBantuRow}:H${lastBantuDataRow})` };
    totBantuRowObj.getCell(9).value = { formula: `SUM(I${startBantuRow}:I${lastBantuDataRow})` };
    totBantuRowObj.getCell(10).value = { formula: `SUM(J${startBantuRow}:J${lastBantuDataRow})` };
    totBantuRowObj.getCell(11).value = { formula: `SUM(K${startBantuRow}:K${lastBantuDataRow})` };
    totBantuRowObj.getCell(12).value = { formula: `SUM(L${startBantuRow}:L${lastBantuDataRow})` };
    totBantuRowObj.getCell(13).value = { formula: `SUM(M${startBantuRow}:M${lastBantuDataRow})` };

    totBantuRowObj.font = { name: 'Arial', size: 9, bold: true };
    totBantuRowObj.alignment = { vertical: 'middle' };
    totBantuRowObj.getCell(2).alignment = { horizontal: 'center' };
    totBantuRowObj.getCell(3).numFmt = '#,##0';
    for (let c = 8; c <= 13; c++) {
        totBantuRowObj.getCell(c).alignment = { horizontal: 'right' };
        totBantuRowObj.getCell(c).numFmt = '#,##0';
    }

    for (let c = 1; c <= 13; c++) {
        totBantuRowObj.getCell(c).border = BORDER_TOTAL;
        totBantuRowObj.getCell(c).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFFEF3C7' }
        };
    }

    sheetBantuTahunan.columns = [
        { width: 6 },   // A: No
        { width: 36 },  // B: Nama Penggunaan Barang
        { width: 18 },  // C: Harga Pokok
        { width: 14 },  // D: Qty Hari
        { width: 15 },  // E: Qty Minggu
        { width: 16 },  // F: Qty Periode
        { width: 15 },  // G: Qty Bulan
        { width: 18 },  // H: Nom Hari
        { width: 18 },  // I: Nom Minggu
        { width: 18 },  // J: Nom Periode
        { width: 18 },  // K: Nom Bulan
        { width: 22 },  // L: Rerata Harian
        { width: 22 },  // M: Total Pengeluaran
    ];

    const filename = `RAB_Tahunan_${year}_${unitSppg?.nama?.replace(/[^a-zA-Z0-9]/g, '_') || 'SPPG'}.xlsx`;
    await triggerDownload(workbook, filename);
}

function getColumnLetter(colIndex) {
    let temp = null;
    let letter = '';
    while (colIndex > 0) {
        temp = (colIndex - 1) % 26;
        letter = String.fromCharCode(temp + 65) + letter;
        colIndex = (colIndex - temp - 1) / 26;
    }
    return letter;
}

async function triggerDownload(workbook, filename) {
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
