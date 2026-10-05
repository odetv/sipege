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

/**
 * Konversi angka kolom 1-based (1, 2, 3...) ke huruf Excel ('A', 'B', 'AA', dll)
 */
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
 * Helper untuk menerapkan Kop Surat Resmi SPPG di Excel
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

    // Garis pemisah bawah kop (hitam medium)
    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    // Offset logo simetris
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
 * Generate daftar tanggal default jika tidak diberikan
 */
function generateDefaultDateList(startDateStr, daysCount) {
    const list = [];
    const base = startDateStr ? new Date(startDateStr + 'T00:00:00') : new Date();
    for (let i = 0; i < daysCount; i++) {
        const cur = new Date(base);
        cur.setDate(base.getDate() + i);
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, '0');
        const d = String(cur.getDate()).padStart(2, '0');
        list.push({
            dateStr: `${y}-${m}-${d}`,
            dayNum: d,
            label: String(i + 1),
        });
    }
    return list;
}

/**
 * Builder Sheet Rekap Presensi & Gaji
 * Mendukung status: H (Hadir Penuh), H2 (Hadir 1/2 Hari), L (Libur), I (Izin), S (Sakit), TK (Tanpa Keterangan)
 */
function buildRekapSheet(workbook, {
    sheetName = 'Rekap Presensi & Gaji',
    daysCount = 28,
    dateList = null,
    startDate = null,
    endDate = null,
    modeLabel = 'Bulanan 28 Hari',
    petugas = [],
    presensiMap = {},
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
}) {
    const sheet = workbook.addWorksheet(sheetName, {
        views: [{ showGridLines: true }],
        pageSetup: {
            orientation: 'landscape',
            paperSize: 9, // A4
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
        },
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const headerBorder = {
        top: { style: 'thin', color: { argb: 'FF064E3B' } },
        bottom: { style: 'thin', color: { argb: 'FF064E3B' } },
        left: { style: 'thin', color: { argb: 'FF064E3B' } },
        right: { style: 'thin', color: { argb: 'FF064E3B' } },
    };

    const finalDateList = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, daysCount);

    const actualDays = finalDateList.length;

    // Posisi Kolom
    // Col 1..3: No, Nama, Jabatan
    // Col 4..(3 + actualDays): Tanggal 1 s.d. actualDays
    // Col Kehadiran: S, I, TK, L (Libur), H2 (Hadir 1/2), Hadir (Total Mandays)
    const colDayStart = 4;
    const colDayEnd = 3 + actualDays;
    const colSIdx = colDayEnd + 1;
    const colIIdx = colDayEnd + 2;
    const colTKIdx = colDayEnd + 3;
    const colLIdx = colDayEnd + 4;
    const colH2Idx = colDayEnd + 5;
    const colHadirIdx = colDayEnd + 6; // Total Mandays
    const colBPJSIdx = colDayEnd + 7;
    const colMitraIdx = colDayEnd + 8;
    const colBGNIdx = colDayEnd + 9;
    const colTotBGNIdx = colDayEnd + 10;
    const colTotSemuaIdx = colDayEnd + 11;

    const maxCols = colTotSemuaIdx;

    const letterDayStart = colToLetter(colDayStart);
    const letterDayEnd = colToLetter(colDayEnd);
    const letterS = colToLetter(colSIdx);
    const letterI = colToLetter(colIIdx);
    const letterTK = colToLetter(colTKIdx);
    const letterL = colToLetter(colLIdx);
    const letterH2 = colToLetter(colH2Idx);
    const letterHadir = colToLetter(colHadirIdx);
    const letterBPJS = colToLetter(colBPJSIdx);
    const letterMitra = colToLetter(colMitraIdx);
    const letterBGN = colToLetter(colBGNIdx);
    const letterTotBGN = colToLetter(colTotBGNIdx);
    const letterTotSemua = colToLetter(colTotSemuaIdx);

    // Konfigurasi Lebar Kolom
    const colDefs = [
        { key: 'no', width: 5 },          // A: No
        { key: 'nama', width: 27 },       // B: Nama Petugas
        { key: 'jabatan', width: 23 },    // C: Jabatan / Divisi
    ];

    const dayColWidth = actualDays > 20 ? 4.2 : 5.0;
    for (let d = 1; d <= actualDays; d++) {
        colDefs.push({ key: `d${d}`, width: dayColWidth });
    }

    colDefs.push({ key: 's', width: 5.5 });
    colDefs.push({ key: 'i', width: 5.5 });
    colDefs.push({ key: 'tk', width: 6.0 }); // TK (Tanpa Keterangan)
    colDefs.push({ key: 'l', width: 5.5 });  // L (Libur)
    colDefs.push({ key: 'h2', width: 6.0 }); // ½ (Hadir 1/2 Hari)
    colDefs.push({ key: 'hadir', width: 8.0 }); // Total Hadir Mandays
    colDefs.push({ key: 'bpjs', width: 17 });
    colDefs.push({ key: 'mitra', width: 16 });
    colDefs.push({ key: 'bgn', width: 16 });
    colDefs.push({ key: 'tot_bgn', width: 22 });
    colDefs.push({ key: 'tot_semua', width: 24 });

    sheet.columns = colDefs;

    // 1. Terapkan Kop Surat Resmi di Baris 1-4
    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    // 2. Judul Dokumen
    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['REKAPITULASI PRESENSI & ESTIMASI HONORARIUM PETUGAS SPPG']);
    sheet.mergeCells(`A${rJudul.number}:${letterTotSemua}${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rangeText = (startDate && endDate)
        ? `Rentang Tanggal: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)} (${actualDays} Hari Kerja)`
        : `Standar Siklus: ${modeLabel} Kerja`;

    const rSubJudul = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet.mergeCells(`A${rSubJudul.number}:${letterTotSemua}${rSubJudul.number}`);
    rSubJudul.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSubJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSubJudul.height = 18;

    sheet.addRow([]);
    sheet.getRow(8).height = 8;

    // 3. Header Tabel (2 Baris: Baris 9 & 10)
    const head1Values = [
        'No',
        'Nama Petugas',
        'Jabatan / Divisi',
        `Tanggal (${modeLabel})`,
    ];
    for (let d = 2; d <= actualDays; d++) head1Values.push('');

    head1Values.push('Presensi & Kehadiran', '', '', '', '', '');
    head1Values.push('Iuran BPJS TK\n(Tidak Terhitung)');
    head1Values.push('Bonus Harian\nMitra');
    head1Values.push('Gaji Harian\nBGN');
    head1Values.push('Total Gaji BGN\n(tanpa menghitung harian mitra)');
    head1Values.push('Total Pendapatan Keseluruhan\n(mitra dan bgn)');

    const rHead1 = sheet.addRow(head1Values);
    rHead1.height = 26;

    // Baris 10: Sub-Header
    const head2Values = ['', '', ''];
    finalDateList.forEach((dt, idx) => {
        head2Values.push(dt.dayNum ? String(dt.dayNum) : String(idx + 1));
    });
    head2Values.push('S', 'I', 'TK', 'L', '½', 'Hadir');
    head2Values.push('', '', '', '', '');

    const rHead2 = sheet.addRow(head2Values);
    rHead2.height = 22;

    const h1 = rHead1.number;
    const h2 = rHead2.number;

    // Merge Cells Header
    sheet.mergeCells(`A${h1}:A${h2}`); // No
    sheet.mergeCells(`B${h1}:B${h2}`); // Nama Petugas
    sheet.mergeCells(`C${h1}:C${h2}`); // Jabatan/Divisi
    sheet.mergeCells(`${letterDayStart}${h1}:${letterDayEnd}${h1}`); // Tanggal
    sheet.mergeCells(`${letterS}${h1}:${letterHadir}${h1}`); // Presensi
    sheet.mergeCells(`${letterBPJS}${h1}:${letterBPJS}${h2}`); // Iuran BPJS TK
    sheet.mergeCells(`${letterMitra}${h1}:${letterMitra}${h2}`); // Bonus Harian Mitra
    sheet.mergeCells(`${letterBGN}${h1}:${letterBGN}${h2}`); // Gaji Harian BGN
    sheet.mergeCells(`${letterTotBGN}${h1}:${letterTotBGN}${h2}`); // Total Gaji BGN
    sheet.mergeCells(`${letterTotSemua}${h1}:${letterTotSemua}${h2}`); // Total Pendapatan Keseluruhan

    [rHead1, rHead2].forEach(r => {
        r.eachCell({ includeEmpty: true }, cell => {
            cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF065F46' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = headerBorder;
        });
    });

    for (let c = colDayStart; c <= colHadirIdx; c++) {
        rHead2.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF047857' } };
    }

    // 4. Baris Data Petugas
    const dataStartRow = 11;
    petugas.forEach((p, idx) => {
        const rowNum = dataStartRow + idx;
        const isAktif = p.status === 'Aktif';
        const personAttendance = presensiMap[p.id] || {};

        let effHadirPenuh = 0;
        let effHadirSetengah = 0;
        let effIzin = 0;
        let effSakit = 0;
        let effAlpa = 0;
        let effLibur = 0;

        const dayMarks = finalDateList.map(dt => {
            if (!isAktif) return '-';
            const st = personAttendance[dt.dateStr] || personAttendance[dt.label] || '';
            if (!st || st === '-') return '-';

            if (st === 'H') effHadirPenuh++;
            else if (st === 'H2' || st === '½') effHadirSetengah++;
            else if (st === 'L') effLibur++;
            else if (st === 'I') effIzin++;
            else if (st === 'S') effSakit++;
            else if (st === 'TK') effAlpa++;

            // Tampilkan simbol yang rapi di sel
            return st === 'H2' ? '½' : st;
        });

        const effMandays = effHadirPenuh + (effHadirSetengah * 0.5);
        const gajiHarianBgn = Number(p.gaji_harian_bgn) || 0;
        const bonusHarianMitra = Number(p.bonus_harian_mitra) || 0;
        const bpjsTk = Number(p.iuran_bpjs_tk) || 16800;

        const rowValues = [
            idx + 1,
            p.nama || '-',
            p.jabatan || '-',
            ...dayMarks,
            // S
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"S")`, result: effSakit },
            // I
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"I")`, result: effIzin },
            // TK (Tanpa Keterangan)
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"TK")`, result: effAlpa },
            // L (Libur)
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"L")`, result: effLibur },
            // ½ (Hadir Setengah Hari)
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"½")`, result: effHadirSetengah },
            // Total Hadir (Mandays: Hadir Penuh + 0.5 * Setengah Hari)
            { formula: `COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"H")+(0.5*COUNTIF(${letterDayStart}${rowNum}:${letterDayEnd}${rowNum},"½"))`, result: effMandays },
            // BPJS TK
            bpjsTk,
            // Bonus Mitra
            bonusHarianMitra,
            // Gaji BGN
            gajiHarianBgn,
            // Total BGN -> Hadir Mandays * Gaji BGN
            { formula: `${letterHadir}${rowNum}*${letterBGN}${rowNum}`, result: effMandays * gajiHarianBgn },
            // Total Semua -> Hadir Mandays * (Gaji BGN + Bonus Mitra)
            { formula: `${letterHadir}${rowNum}*(${letterBGN}${rowNum}+${letterMitra}${rowNum})`, result: effMandays * (gajiHarianBgn + bonusHarianMitra) },
        ];

        const row = sheet.addRow(rowValues);
        row.height = 20;

        const isEven = idx % 2 === 1;
        const rowBg = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (colNumber === 2) {
                cell.font = { name: 'Arial', size: 9, bold: true };
            } else if (colNumber >= colDayStart && colNumber <= colDayEnd) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                const val = cell.value;
                if (val === 'H') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                else if (val === '½' || val === 'H2') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0D9488' } }; // Teal
                else if (val === 'L') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF64748B' } }; // Slate
                else if (val === 'I') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFB45309' } };
                else if (val === 'S') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1D4ED8' } };
                else if (val === 'TK') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFBE123C' } };
                else cell.font = { name: 'Arial', size: 9, color: { argb: 'FF94A3B8' } };
            } else if (colNumber >= colSIdx && colNumber <= colHadirIdx) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                if (colNumber === colHadirIdx) {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                }
            } else if (colNumber >= colBPJSIdx && colNumber <= colTotSemuaIdx) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === colTotBGNIdx) {
                    cell.font = { name: 'Arial', size: 9, bold: true };
                } else if (colNumber === colTotSemuaIdx) {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
                }
            }
        });
    });

    const lastDataRow = dataStartRow + petugas.length - 1;
    const totRow = lastDataRow + 1;

    // 5. Baris Total Rekapitulasi
    const totalRowValues = [
        `TOTAL REKAPITULASI (${petugas.length} PERSONIL)`, '', ''
    ];

    for (let c = colDayStart; c <= colDayEnd; c++) {
        const colLet = colToLetter(c);
        // Total hadir penuh di tanggal tersebut
        totalRowValues.push({ formula: `COUNTIF(${colLet}${dataStartRow}:${colLet}${lastDataRow},"H")` });
    }

    // Totals S, I, TK, L, ½, Hadir
    totalRowValues.push({ formula: `SUM(${letterS}${dataStartRow}:${letterS}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterI}${dataStartRow}:${letterI}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterTK}${dataStartRow}:${letterTK}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterL}${dataStartRow}:${letterL}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterH2}${dataStartRow}:${letterH2}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterHadir}${dataStartRow}:${letterHadir}${lastDataRow})` });

    totalRowValues.push({ formula: `SUM(${letterBPJS}${dataStartRow}:${letterBPJS}${lastDataRow})` });
    totalRowValues.push('-');
    totalRowValues.push('-');
    totalRowValues.push({ formula: `SUM(${letterTotBGN}${dataStartRow}:${letterTotBGN}${lastDataRow})` });
    totalRowValues.push({ formula: `SUM(${letterTotSemua}${dataStartRow}:${letterTotSemua}${lastDataRow})` });

    const rTot = sheet.addRow(totalRowValues);
    sheet.mergeCells(`A${totRow}:C${totRow}`);
    rTot.height = 24;

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FF065F46' } },
        bottom: { style: 'double', color: { argb: 'FF065F46' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    rTot.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
        cell.border = totalBorder;
        cell.alignment = { vertical: 'middle' };

        if (colNumber === 1) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
        } else if (colNumber >= colDayStart && colNumber <= colHadirIdx) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        } else if (colNumber >= colBPJSIdx && colNumber <= colTotSemuaIdx) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            if (colNumber === colBPJSIdx || colNumber === colTotBGNIdx || colNumber === colTotSemuaIdx) {
                cell.numFmt = '"Rp "#,##0';
            } else {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === colTotSemuaIdx) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            }
        }
    });

    // 6. Lembar Tanda Tangan Resmi Pengesahan
    sheet.addRow([]);
    sheet.addRow([]);

    const tglCetak = formatTanggalIndo(new Date());
    const rightTtdStart = Math.max(colDayEnd + 1, maxCols - 4);
    const rightTtdEnd = maxCols;
    const letterRightStart = colToLetter(rightTtdStart);
    const letterRightEnd = colToLetter(rightTtdEnd);

    const ttdDateRowVals = Array(maxCols).fill('');
    ttdDateRowVals[rightTtdStart - 1] = `Buleleng, ${tglCetak}`;
    const rTtdTgl = sheet.addRow(ttdDateRowVals);
    sheet.mergeCells(`${letterRightStart}${rTtdTgl.number}:${letterRightEnd}${rTtdTgl.number}`);
    rTtdTgl.getCell(rightTtdStart).font = { name: 'Arial', size: 9.5 };
    rTtdTgl.getCell(rightTtdStart).alignment = { horizontal: 'center', vertical: 'middle' };

    const ttdHeadRowVals = Array(maxCols).fill('');
    ttdHeadRowVals[1] = 'Mengetahui & Menyetujui,';
    ttdHeadRowVals[rightTtdStart - 1] = 'Dibuat & Diverifikasi Oleh,';
    const rTtdHead = sheet.addRow(ttdHeadRowVals);
    sheet.mergeCells(`B${rTtdHead.number}:E${rTtdHead.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdHead.number}:${letterRightEnd}${rTtdHead.number}`);
    rTtdHead.getCell(2).font = { name: 'Arial', size: 9, italic: true };
    rTtdHead.getCell(rightTtdStart).font = { name: 'Arial', size: 9, italic: true };
    rTtdHead.getCell(2).alignment = { horizontal: 'center' };
    rTtdHead.getCell(rightTtdStart).alignment = { horizontal: 'center' };

    const ttdJabRowVals = Array(maxCols).fill('');
    ttdJabRowVals[1] = 'Kepala SPPG';
    ttdJabRowVals[rightTtdStart - 1] = 'Koordinator Keuangan / Admin SPPG';
    const rTtdJab = sheet.addRow(ttdJabRowVals);
    sheet.mergeCells(`B${rTtdJab.number}:E${rTtdJab.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdJab.number}:${letterRightEnd}${rTtdJab.number}`);
    rTtdJab.getCell(2).font = { name: 'Arial', size: 10, bold: true };
    rTtdJab.getCell(rightTtdStart).font = { name: 'Arial', size: 10, bold: true };
    rTtdJab.getCell(2).alignment = { horizontal: 'center' };
    rTtdJab.getCell(rightTtdStart).alignment = { horizontal: 'center' };

    const s1 = sheet.addRow([]); s1.height = 18;
    const s2 = sheet.addRow([]); s2.height = 18;
    const s3 = sheet.addRow([]); s3.height = 18;

    const ttdNamaRowVals = Array(maxCols).fill('');
    ttdNamaRowVals[1] = '( Gede Wisnu Saputra, S.Tr.Gz )';
    ttdNamaRowVals[rightTtdStart - 1] = '( .................................................... )';
    const rTtdNama = sheet.addRow(ttdNamaRowVals);
    sheet.mergeCells(`B${rTtdNama.number}:E${rTtdNama.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdNama.number}:${letterRightEnd}${rTtdNama.number}`);
    rTtdNama.getCell(2).font = { name: 'Arial', size: 9.5, bold: true, underline: true };
    rTtdNama.getCell(rightTtdStart).font = { name: 'Arial', size: 9.5, bold: true };
    rTtdNama.getCell(2).alignment = { horizontal: 'center' };
    rTtdNama.getCell(rightTtdStart).alignment = { horizontal: 'center' };

    const ttdKetRowVals = Array(maxCols).fill('');
    ttdKetRowVals[1] = 'Penanggung Jawab Operasional SPPG';
    ttdKetRowVals[rightTtdStart - 1] = 'Staf Administrasi & Keuangan';
    const rTtdKet = sheet.addRow(ttdKetRowVals);
    sheet.mergeCells(`B${rTtdKet.number}:E${rTtdKet.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdKet.number}:${letterRightEnd}${rTtdKet.number}`);
    rTtdKet.getCell(2).font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
    rTtdKet.getCell(rightTtdStart).font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
    rTtdKet.getCell(2).alignment = { horizontal: 'center' };
    rTtdKet.getCell(rightTtdStart).alignment = { horizontal: 'center' };

    return sheet;
}

/**
 * Builder Sheet Ringkasan per Divisi (Executive Summary)
 */
function buildRingkasanDivisiSheet(workbook, {
    petugas = [],
    presensiMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    modeLabel = 'Bulanan 28 Hari',
}) {
    const sheet2 = workbook.addWorksheet('Ringkasan per Divisi', {
        views: [{ showGridLines: true }],
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const headerBorder = {
        top: { style: 'thin', color: { argb: 'FF064E3B' } },
        bottom: { style: 'thin', color: { argb: 'FF064E3B' } },
        left: { style: 'thin', color: { argb: 'FF064E3B' } },
        right: { style: 'thin', color: { argb: 'FF064E3B' } },
    };

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FF065F46' } },
        bottom: { style: 'double', color: { argb: 'FF065F46' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxColsS2 = 10;
    sheet2.columns = [
        { width: 6 },   // A: No
        { width: 28 },  // B: Divisi / Jabatan
        { width: 14 },  // C: Jumlah Personil
        { width: 14 },  // D: Total Hadir (Mandays)
        { width: 12 },  // E: Total Izin
        { width: 12 },  // F: Total Sakit
        { width: 12 },  // G: Total TK (Tanpa Keterangan)
        { width: 20 },  // H: Total Gaji BGN
        { width: 20 },  // I: Total Bonus Mitra
        { width: 24 },  // J: Total Pendapatan
    ];

    applyKopSurat(sheet2, maxColsS2, logoBgnId, logoYayasanId, unitSppg);

    sheet2.addRow([]);
    sheet2.getRow(5).height = 10;

    const rJ2 = sheet2.addRow(['RINGKASAN REKAPITULASI PRESENSI & KOMPENSASI PER DIVISI']);
    sheet2.mergeCells(`A${rJ2.number}:J${rJ2.number}`);
    rJ2.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJ2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJ2.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rangeText = (startDate && endDate)
        ? `Rentang: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)}`
        : `Standar: ${modeLabel} Kerja`;

    const rSub2 = sheet2.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet2.mergeCells(`A${rSub2.number}:J${rSub2.number}`);
    rSub2.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub2.height = 18;

    sheet2.addRow([]);
    sheet2.getRow(8).height = 8;

    const rHeadS2 = sheet2.addRow([
        'No',
        'Divisi / Jabatan',
        'Jumlah Personil',
        'Total Mandays',
        'Izin (I)',
        'Sakit (S)',
        'Tanpa Ket. (TK)',
        'Total Gaji BGN',
        'Total Bonus Mitra',
        'Total Pendapatan'
    ]);
    rHeadS2.height = 24;
    rHeadS2.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF065F46' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = headerBorder;
    });

    const finalDates = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 28);

    // Grouping per Divisi
    const divisiMap = {};
    petugas.forEach(p => {
        const jab = p.jabatan || 'Lainnya';
        if (!divisiMap[jab]) {
            divisiMap[jab] = {
                jabatan: jab,
                count: 0,
                mandays: 0,
                izin: 0,
                sakit: 0,
                alpa: 0,
                gajiBgn: 0,
                bonusMitra: 0,
                total: 0,
            };
        }
        divisiMap[jab].count += 1;
        const isAktif = p.status === 'Aktif';
        const pAtt = presensiMap[p.id] || {};

        let mDays = 0, iz = 0, s = 0, a = 0;
        if (isAktif) {
            finalDates.forEach(dt => {
                const st = pAtt[dt.dateStr] || pAtt[dt.label] || '';
                if (!st || st === '-') return;
                if (st === 'H') mDays += 1;
                else if (st === 'H2' || st === '½') mDays += 0.5;
                else if (st === 'I') iz++;
                else if (st === 'S') s++;
                else if (st === 'TK') a++;
            });
        }

        const gBgn = Number(p.gaji_harian_bgn) || 0;
        const bMitra = Number(p.bonus_harian_mitra) || 0;

        divisiMap[jab].mandays += mDays;
        divisiMap[jab].izin += iz;
        divisiMap[jab].sakit += s;
        divisiMap[jab].alpa += a;
        divisiMap[jab].gajiBgn += (mDays * gBgn);
        divisiMap[jab].bonusMitra += (mDays * bMitra);
        divisiMap[jab].total += (mDays * (gBgn + bMitra));
    });

    const divisiList = Object.values(divisiMap);
    const startRowS2 = 10;
    divisiList.forEach((d, idx) => {
        const r = sheet2.addRow([
            idx + 1,
            d.jabatan,
            `${d.count} Orang`,
            `${d.mandays} Mandays`,
            d.izin,
            d.sakit,
            d.alpa,
            d.gajiBgn,
            d.bonusMitra,
            d.total
        ]);
        r.height = 20;

        const isEven = idx % 2 === 1;
        const rowBg = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

        r.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 3 || colNumber === 4 || colNumber === 5 || colNumber === 6 || colNumber === 7) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === 2) {
                cell.font = { name: 'Arial', size: 9, bold: true };
            }
            if (colNumber >= 8 && colNumber <= 10) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === 10) {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
                }
            }
        });
    });

    // Total Row Sheet 2
    const lastRowS2 = startRowS2 + divisiList.length;
    const rTotS2 = sheet2.addRow([
        'TOTAL',
        'SELURUH DIVISI',
        `${petugas.length} Orang`,
        { formula: `SUM(D10:D${lastRowS2 - 1})` },
        { formula: `SUM(E10:E${lastRowS2 - 1})` },
        { formula: `SUM(F10:F${lastRowS2 - 1})` },
        { formula: `SUM(G10:G${lastRowS2 - 1})` },
        { formula: `SUM(H10:H${lastRowS2 - 1})` },
        { formula: `SUM(I10:I${lastRowS2 - 1})` },
        { formula: `SUM(J10:J${lastRowS2 - 1})` },
    ]);
    rTotS2.height = 24;
    rTotS2.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
        cell.border = totalBorder;
        cell.alignment = { vertical: 'middle' };

        if (colNumber === 1 || colNumber === 3 || colNumber === 4 || colNumber === 5 || colNumber === 6 || colNumber === 7) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        }
        if (colNumber >= 8 && colNumber <= 10) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            if (colNumber === 10) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            }
        }
    });

    return sheet2;
}

/**
 * Download File Excel Rekap Presensi & Gaji Petugas SPPG
 */
export async function downloadRekapAbsenGajiExcel({
    petugas = [],
    presensiMap = {},
    kehadiranMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    modeHariKerja = 28,
}) {
    const finalPresensiMap = (presensiMap && Object.keys(presensiMap).length > 0) ? presensiMap : (kehadiranMap || {});
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

    const isBulanan = Number(modeHariKerja) === 28;
    const modeLabel = isBulanan ? 'Bulanan 28 Hari' : (Number(modeHariKerja) === 14 ? 'Periodik 14 Hari' : `${modeHariKerja} Hari`);

    // =========================================================
    // SHEET 1: Rekap Presensi & Gaji (Primary)
    // =========================================================
    buildRekapSheet(workbook, {
        sheetName: 'Rekap Presensi & Gaji',
        daysCount: Number(modeHariKerja) || 28,
        dateList,
        startDate,
        endDate,
        modeLabel,
        petugas,
        presensiMap: finalPresensiMap,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
    });

    // =========================================================
    // SHEET 2: Ringkasan per Divisi (Executive Summary)
    // =========================================================
    buildRingkasanDivisiSheet(workbook, {
        petugas,
        presensiMap: finalPresensiMap,
        dateList,
        startDate,
        endDate,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
        modeLabel,
    });

    // Export & Download
    const cycleSuffix = isBulanan ? 'Bulanan_28_Hari' : (Number(modeHariKerja) === 14 ? 'Periodik_14_Hari' : `Rentang_${startDate || 'custom'}`);
    const filename = `Rekap_Presensi_dan_Gaji_Petugas_SPPG_${cycleSuffix}.xlsx`;
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
