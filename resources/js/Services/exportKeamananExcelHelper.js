import ExcelJS from "exceljs";
import { applyKopSurat } from "@/Services/exportPetugasHelper";
import {
    LOGO_BGN_RAW_BASE64,
    LOGO_YAYASAN_RAW_BASE64,
} from "@/Services/logoBase64Helper";

const MONTHS_INDO = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const DAYS_INDO = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
];

export function formatTanggalIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr + (dateStr.length <= 10 ? 'T00:00:00' : ''));
        if (isNaN(d.getTime())) return String(dateStr);
        const dayName = DAYS_INDO[d.getDay()];
        const day = d.getDate();
        const month = MONTHS_INDO[d.getMonth()];
        const year = d.getFullYear();
        return `${dayName}, ${day} ${month} ${year}`;
    } catch {
        return String(dateStr);
    }
}

const borderThin = {
    top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
};

/**
 * Export Laporan Mutasi Keamanan SPPG ke Format Excel (.xlsx)
 * Menggunakan Kop Surat Standar SPPG (Simetris Logo BGN & Logo Yayasan)
 */
export async function exportKeamananExcel({
    unitSppg = null,
    selectedTanggal = '',
    details24Jam = [],
    serahTerimas = [],
    izins = [],
    stats = {},
    petugasKeamanan = [],
    pejabatSppg = {},
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = "SIPEGE SPPG";
    workbook.created = new Date();

    let logoBgnId = null;
    let logoYayasanId = null;

    try {
        if (LOGO_BGN_RAW_BASE64) {
            logoBgnId = workbook.addImage({ base64: LOGO_BGN_RAW_BASE64, extension: 'png' });
        }
        if (LOGO_YAYASAN_RAW_BASE64) {
            logoYayasanId = workbook.addImage({ base64: LOGO_YAYASAN_RAW_BASE64, extension: 'png' });
        }
    } catch {
        // Fallback jika logo gagal decode
    }

    const tglFormatted = formatTanggalIndo(selectedTanggal);
    const petugasNamaList = (petugasKeamanan || []).map(p => p.nama).join(' & ') || 'Petugas Keamanan';

    // =========================================================================
    // SHEET 1: JURNAL PATROLI 24 JAM
    // =========================================================================
    const sheet1 = workbook.addWorksheet("Jurnal Patroli 24 Jam", {
        views: [{ showGridLines: true }],
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxCols1 = 9;
    sheet1.columns = [
        { width: 6 },   // 1: No
        { width: 22 },  // 2: Interval Jam
        { width: 15 },  // 3: Waktu Cek
        { width: 14 },  // 4: Status
        { width: 26 },  // 5: Petugas Jaga
        { width: 12 },  // 6: Cuaca
        { width: 26 },  // 7: Tempat / Posisi
        { width: 24 },  // 8: Koordinat GPS
        { width: 55 },  // 9: Uraian / Temuan
    ];

    // Terapkan Kop Surat Standar Resmi SIPEGE (Logo BGN Kiri, Logo Yayasan Kanan)
    applyKopSurat(sheet1, maxCols1, logoBgnId, logoYayasanId, unitSppg);

    // Spacing setelah kop
    sheet1.getRow(5).height = 10;

    // Title Block (Row 6 & 7)
    sheet1.getRow(6).height = 22;
    sheet1.mergeCells('A6:I6');
    sheet1.getCell('A6').value = "LAPORAN MUTASI & JURNAL PATROLI KEAMANAN SPPG (24 JAM)";
    sheet1.getCell('A6').font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    sheet1.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.getRow(7).height = 18;
    sheet1.mergeCells('A7:I7');
    sheet1.getCell('A7').value = `Hari / Tanggal: ${tglFormatted}  |  Siklus: 24 Jam Penuh (WITA)  |  Petugas Jaga: ${petugasNamaList}`;
    sheet1.getCell('A7').font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF334155' } };
    sheet1.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.getRow(8).height = 8;

    // Ringkasan KPI Bar (Row 9)
    const totalReported = details24Jam.filter(d => d.is_reported).length;
    const totalAman = details24Jam.filter(d => d.is_reported && d.kondisi === 'Aman').length;
    const totalInsiden = details24Jam.filter(d => d.is_reported && d.kondisi === 'Tidak Aman').length;
    const pct = Math.round((totalReported / 24) * 100);

    sheet1.getRow(9).height = 20;
    sheet1.mergeCells('A9:C9');
    sheet1.getCell('A9').value = `Total Terlapor: ${totalReported} / 24 Jam (${pct}%)`;
    sheet1.getCell('A9').font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0F172A' } };
    sheet1.getCell('A9').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
    sheet1.getCell('A9').alignment = { horizontal: 'center', vertical: 'middle' };
    sheet1.getCell('A9').border = borderThin;

    sheet1.mergeCells('D9:E9');
    sheet1.getCell('D9').value = `Kondisi Aman: ${totalAman} Jam`;
    sheet1.getCell('D9').font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF166534' } };
    sheet1.getCell('D9').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDCFCE7' } };
    sheet1.getCell('D9').alignment = { horizontal: 'center', vertical: 'middle' };
    sheet1.getCell('D9').border = borderThin;

    sheet1.mergeCells('F9:G9');
    sheet1.getCell('F9').value = totalInsiden > 0 ? `Perhatian / Insiden: ${totalInsiden} Jam` : `Insiden: 0 (Nihil)`;
    sheet1.getCell('F9').font = { name: 'Arial', size: 9, bold: true, color: { argb: totalInsiden > 0 ? 'FF991B1B' : 'FF1E293B' } };
    sheet1.getCell('F9').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: totalInsiden > 0 ? 'FFFEE2E2' : 'FFF8FAFC' } };
    sheet1.getCell('F9').alignment = { horizontal: 'center', vertical: 'middle' };
    sheet1.getCell('F9').border = borderThin;

    sheet1.mergeCells('H9:I9');
    sheet1.getCell('H9').value = `Serah Terima: ${serahTerimas.length} | Izin Keluar: ${izins.length}`;
    sheet1.getCell('H9').font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF4338CA' } };
    sheet1.getCell('H9').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0E7FF' } };
    sheet1.getCell('H9').alignment = { horizontal: 'center', vertical: 'middle' };
    sheet1.getCell('H9').border = borderThin;

    sheet1.getRow(10).height = 8;

    // Header Tabel Jurnal (Row 11)
    const headerRow1 = sheet1.getRow(11);
    headerRow1.height = 26;
    const headers1 = [
        "No",
        "Interval Jam (WITA)",
        "Waktu Cek",
        "Status",
        "Petugas Jaga",
        "Cuaca",
        "Tempat / Posisi",
        "Koordinat GPS",
        "Uraian Kejadian / Rincian Pemeriksaan Lingkungan SPPG",
    ];

    headers1.forEach((h, i) => {
        const cell = headerRow1.getCell(i + 1);
        cell.value = h;
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F172A' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = borderThin;
    });

    // Data 24 Jam (Row 12+)
    let rowIdx = 12;
    details24Jam.forEach((item, idx) => {
        const row = sheet1.getRow(rowIdx);
        row.height = 22;

        const isRep = item.is_reported;
        const petNama = item.petugas_nama || item.petugas?.nama || (isRep ? 'Petugas Keamanan' : '-');
        const waktuCek = item.waktu_cek ? `${item.waktu_cek} WITA` : '-';
        const kondisi = isRep ? (item.kondisi || 'Aman') : 'Belum Lapor';
        const cuaca = item.data_pengecekan?.cuaca || '-';
        const tempat = item.data_pengecekan?.tempat_posisi || item.lokasi_nama || (isRep ? 'Pos Penjagaan SPPG' : '-');
        const gps = (item.latitude && item.longitude) ? `${item.latitude}, ${item.longitude}` : (isRep ? 'Terekam' : '-');
        const uraian = item.uraian_kejadian || (isRep ? 'Situasi aman terkendali, area steril dan kondusif.' : 'Menunggu giliran waktu pelaporan.');

        row.getCell(1).value = idx + 1;
        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

        row.getCell(2).value = item.jam;
        row.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(2).font = { bold: true };

        row.getCell(3).value = waktuCek;
        row.getCell(3).alignment = { horizontal: 'center', vertical: 'middle' };

        row.getCell(4).value = kondisi;
        row.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
        if (kondisi === 'Aman') {
            row.getCell(4).font = { bold: true, color: { argb: 'FF166534' } };
            row.getCell(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDCFCE7' } };
        } else if (kondisi === 'Tidak Aman') {
            row.getCell(4).font = { bold: true, color: { argb: 'FF991B1B' } };
            row.getCell(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
        } else {
            row.getCell(4).font = { italic: true, color: { argb: 'FF94A3B8' } };
            row.getCell(4).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
        }

        row.getCell(5).value = petNama;
        row.getCell(5).alignment = { horizontal: 'left', vertical: 'middle' };

        row.getCell(6).value = cuaca;
        row.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };

        row.getCell(7).value = tempat;
        row.getCell(7).alignment = { horizontal: 'left', vertical: 'middle' };

        row.getCell(8).value = gps;
        row.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(8).font = { name: 'Courier New', size: 8.5 };

        row.getCell(9).value = uraian;
        row.getCell(9).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

        for (let c = 1; c <= maxCols1; c++) {
            row.getCell(c).border = borderThin;
        }

        rowIdx++;
    });

    // Pengesahan Tanda Tangan di Bawah Tabel
    rowIdx += 2;
    sheet1.getRow(rowIdx).height = 18;
    sheet1.mergeCells(`A${rowIdx}:C${rowIdx}`);
    sheet1.getCell(`A${rowIdx}`).value = "Mengetahui,";
    sheet1.getCell(`A${rowIdx}`).font = { name: 'Arial', size: 9.5 };
    sheet1.getCell(`A${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells(`G${rowIdx}:I${rowIdx}`);
    sheet1.getCell(`G${rowIdx}`).value = `Tegallinggah, ${tglFormatted}`;
    sheet1.getCell(`G${rowIdx}`).font = { name: 'Arial', size: 9.5 };
    sheet1.getCell(`G${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    rowIdx++;
    sheet1.getRow(rowIdx).height = 16;
    sheet1.mergeCells(`A${rowIdx}:C${rowIdx}`);
    sheet1.getCell(`A${rowIdx}`).value = "Koordinator Lapangan SPPG";
    sheet1.getCell(`A${rowIdx}`).font = { name: 'Arial', size: 9, bold: true };
    sheet1.getCell(`A${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells(`G${rowIdx}:I${rowIdx}`);
    sheet1.getCell(`G${rowIdx}`).value = "Kepala SPPG";
    sheet1.getCell(`G${rowIdx}`).font = { name: 'Arial', size: 9, bold: true };
    sheet1.getCell(`G${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    rowIdx += 4; // Space tanda tangan
    sheet1.getRow(rowIdx).height = 18;
    sheet1.mergeCells(`A${rowIdx}:C${rowIdx}`);
    sheet1.getCell(`A${rowIdx}`).value = `( ${pejabatSppg?.korlap || 'Koordinator Lapangan'} )`;
    sheet1.getCell(`A${rowIdx}`).font = { name: 'Arial', size: 9.5, bold: true };
    sheet1.getCell(`A${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells(`G${rowIdx}:I${rowIdx}`);
    sheet1.getCell(`G${rowIdx}`).value = `( ${pejabatSppg?.kepala_sppg || unitSppg?.nama_kepala || 'Kepala SPPG'} )`;
    sheet1.getCell(`G${rowIdx}`).font = { name: 'Arial', size: 9.5, bold: true };
    sheet1.getCell(`G${rowIdx}`).alignment = { horizontal: 'center', vertical: 'middle' };

    // =========================================================================
    // SHEET 2: LAPORAN SERAH TERIMA JAGA
    // =========================================================================
    const sheet2 = workbook.addWorksheet("Laporan Serah Terima", {
        views: [{ showGridLines: true }],
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1 }
    });

    const maxCols2 = 7;
    sheet2.columns = [
        { width: 6 },   // 1: No
        { width: 18 },  // 2: Waktu
        { width: 20 },  // 3: Jenis
        { width: 26 },  // 4: Petugas
        { width: 26 },  // 5: Posisi
        { width: 45 },  // 6: Catatan
        { width: 24 },  // 7: GPS
    ];

    applyKopSurat(sheet2, maxCols2, logoBgnId, logoYayasanId, unitSppg);

    sheet2.getRow(5).height = 10;

    sheet2.getRow(6).height = 22;
    sheet2.mergeCells('A6:G6');
    sheet2.getCell('A6').value = "LOG & LAPORAN SERAH TERIMA JAGA KEAMANAN SPPG";
    sheet2.getCell('A6').font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    sheet2.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet2.getRow(7).height = 18;
    sheet2.mergeCells('A7:G7');
    sheet2.getCell('A7').value = `Hari / Tanggal: ${tglFormatted}  |  Total Serah Terima: ${serahTerimas.length} Laporan`;
    sheet2.getCell('A7').font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF4338CA' } };
    sheet2.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet2.getRow(8).height = 8;

    const headerRow2 = sheet2.getRow(9);
    headerRow2.height = 26;
    const headers2 = [
        "No",
        "Waktu (WITA)",
        "Status Serah Terima",
        "Nama Petugas",
        "Posisi / Tempat",
        "Catatan & Inventarisasi",
        "Koordinat GPS",
    ];

    headers2.forEach((h, i) => {
        const cell = headerRow2.getCell(i + 1);
        cell.value = h;
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4338CA' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = borderThin;
    });

    let rowIdx2 = 10;
    if (serahTerimas.length === 0) {
        const row = sheet2.getRow(rowIdx2);
        row.height = 24;
        sheet2.mergeCells(`A${rowIdx2}:G${rowIdx2}`);
        row.getCell(1).value = "Belum ada catatan serah terima dinas jaga pada tanggal ini.";
        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(1).font = { italic: true, color: { argb: 'FF64748B' } };
        row.getCell(1).border = borderThin;
    } else {
        serahTerimas.forEach((st, idx) => {
            const row = sheet2.getRow(rowIdx2);
            row.height = 24;

            row.getCell(1).value = idx + 1;
            row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

            row.getCell(2).value = st.waktu;
            row.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
            row.getCell(2).font = { bold: true };

            row.getCell(3).value = st.jenis;
            row.getCell(3).alignment = { horizontal: 'center', vertical: 'middle' };
            row.getCell(3).font = { bold: true, color: { argb: st.jenis === 'Masuk Jaga' ? 'FF166534' : 'FF1E293B' } };
            row.getCell(3).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: st.jenis === 'Masuk Jaga' ? 'FFDCFCE7' : 'FFF1F5F9' } };

            row.getCell(4).value = st.petugas_nama || st.petugas?.nama || '-';
            row.getCell(4).alignment = { horizontal: 'left', vertical: 'middle' };

            row.getCell(5).value = st.lokasi_nama || 'Pos Penjagaan SPPG';
            row.getCell(5).alignment = { horizontal: 'left', vertical: 'middle' };

            row.getCell(6).value = st.catatan || '-';
            row.getCell(6).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

            const gps = (st.latitude && st.longitude) ? `${st.latitude}, ${st.longitude}` : '-';
            row.getCell(7).value = gps;
            row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
            row.getCell(7).font = { name: 'Courier New', size: 8.5 };

            for (let c = 1; c <= maxCols2; c++) {
                row.getCell(c).border = borderThin;
            }

            rowIdx2++;
        });
    }

    // =========================================================================
    // SHEET 3: LAPORAN KELUAR JAGA / DINAS LUAR
    // =========================================================================
    const sheet3 = workbook.addWorksheet("Laporan Keluar Jaga", {
        views: [{ showGridLines: true }],
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1 }
    });

    const maxCols3 = 8;
    sheet3.columns = [
        { width: 6 },   // 1: No
        { width: 26 },  // 2: Petugas
        { width: 24 },  // 3: Tujuan
        { width: 36 },  // 4: Keperluan
        { width: 16 },  // 5: Keluar
        { width: 16 },  // 6: Kembali
        { width: 18 },  // 7: Durasi
        { width: 18 },  // 8: Status
    ];

    applyKopSurat(sheet3, maxCols3, logoBgnId, logoYayasanId, unitSppg);

    sheet3.getRow(5).height = 10;

    sheet3.getRow(6).height = 22;
    sheet3.mergeCells('A6:H6');
    sheet3.getCell('A6').value = "JURNAL MUTASI IZIN KELUAR PENJAGAAN & DINAS LUAR SPPG";
    sheet3.getCell('A6').font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    sheet3.getCell('A6').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet3.getRow(7).height = 18;
    sheet3.mergeCells('A7:H7');
    sheet3.getCell('A7').value = `Hari / Tanggal: ${tglFormatted}  |  Total Izin Keluar: ${izins.length} Catatan`;
    sheet3.getCell('A7').font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FFD97706' } };
    sheet3.getCell('A7').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet3.getRow(8).height = 8;

    const headerRow3 = sheet3.getRow(9);
    headerRow3.height = 26;
    const headers3 = [
        "No",
        "Nama Petugas",
        "Tujuan Lokasi",
        "Keperluan Dinas Luar / Izin",
        "Waktu Keluar",
        "Waktu Kembali",
        "Durasi Keluar",
        "Status Keberadaan",
    ];

    headers3.forEach((h, i) => {
        const cell = headerRow3.getCell(i + 1);
        cell.value = h;
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD97706' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = borderThin;
    });

    let rowIdx3 = 10;
    if (izins.length === 0) {
        const row = sheet3.getRow(rowIdx3);
        row.height = 24;
        sheet3.mergeCells(`A${rowIdx3}:H${rowIdx3}`);
        row.getCell(1).value = "Belum ada catatan izin keluar dinas jaga pada tanggal ini.";
        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(1).font = { italic: true, color: { argb: 'FF64748B' } };
        row.getCell(1).border = borderThin;
    } else {
        izins.forEach((iz, idx) => {
            const row = sheet3.getRow(rowIdx3);
            row.height = 24;

            row.getCell(1).value = idx + 1;
            row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

            row.getCell(2).value = iz.petugas_nama || iz.petugas?.nama || '-';
            row.getCell(2).alignment = { horizontal: 'left', vertical: 'middle' };

            row.getCell(3).value = iz.lokasi_tujuan || '-';
            row.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };

            row.getCell(4).value = iz.keperluan || '-';
            row.getCell(4).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

            row.getCell(5).value = iz.waktu_keluar || '-';
            row.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };

            row.getCell(6).value = iz.waktu_kembali || '-';
            row.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };

            row.getCell(7).value = iz.durasi_keluar_menit ? `${iz.durasi_keluar_menit} Menit` : (iz.status === 'Kembali' ? 'Selesai' : 'Sedang Berlangsung');
            row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };

            row.getCell(8).value = iz.status || '-';
            row.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
            row.getCell(8).font = { bold: true, color: { argb: iz.status === 'Sedang Keluar' ? 'FFB45309' : 'FF166534' } };
            row.getCell(8).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: iz.status === 'Sedang Keluar' ? 'FFFEF3C7' : 'FFDCFCE7' } };

            for (let c = 1; c <= maxCols3; c++) {
                row.getCell(c).border = borderThin;
            }

            rowIdx3++;
        });
    }

    // =========================================================================
    // UNDUH FILE EXCEL KE BROWSER
    // =========================================================================
    const filename = `Laporan_Mutasi_Keamanan_SPPG_${selectedTanggal || 'Harian'}.xlsx`;
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
