import ExcelJS from "exceljs";
import jsPDF from "jspdf";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { 
    LOGO_BGN_BASE64,
    LOGO_YAYASAN_BASE64,
    LOGO_BGN_RAW_BASE64, 
    LOGO_YAYASAN_RAW_BASE64 
} from "@/Services/logoBase64Helper";
import {
    formatTanggalIndo,
    formatTanggalWaktuIndo,
    formatDateToSheetName,
    formatRibuan,
    getPenandatanganSppg,
    applyLembarPengesahan,
} from "@/Services/exportPetugasHelper";

export { formatTanggalIndo, formatTanggalWaktuIndo };

export function colToLetter(n) {
    let s = '';
    while (n > 0) {
        let m = (n - 1) % 26;
        s = String.fromCharCode(65 + m) + s;
        n = Math.floor((n - m) / 26);
    }
    return s;
}

export function formatRupiah(val) {
    return 'Rp ' + (Number(val) || 0).toLocaleString('id-ID');
}

/**
 * Hitung tarif insentif harian resmi BGN untuk penanggung jawab / kader PM
 */
export function hitungTarifInsentifHarian(kategori, totalPm) {
    const isPosyandu = String(kategori || '').toLowerCase().includes('posyandu');
    const pm = Number(totalPm) || 0;
    if (isPosyandu) {
        return pm * 1000;
    }
    if (pm < 100) return 20000;
    if (pm <= 500) return 30000;
    if (pm <= 750) return 50000;
    if (pm <= 1000) return 60000;
    if (pm <= 2000) return 100000;
    return 200000;
}

/**
 * Konversi angka rupiah ke teks terbilang Bahasa Indonesia
 */
export function angkaKeTerbilangRupiah(nilai) {
    const angka = Math.floor(Math.abs(Number(nilai) || 0));
    if (angka === 0) return "Nol Rupiah";
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    function toWords(num) {
        if (num < 12) return satuan[num];
        if (num < 20) return toWords(num - 10) + " Belas";
        if (num < 100) return toWords(Math.floor(num / 10)) + " Puluh " + toWords(num % 10);
        if (num < 200) return "Seratus " + toWords(num - 100);
        if (num < 1000) return toWords(Math.floor(num / 100)) + " Ratus " + toWords(num % 100);
        if (num < 2000) return "Seribu " + toWords(num - 1000);
        if (num < 1000000) return toWords(Math.floor(num / 1000)) + " Ribu " + toWords(num % 1000);
        if (num < 1000000000) return toWords(Math.floor(num / 1000000)) + " Juta " + toWords(num % 1000000);
        if (num < 1000000000000) return toWords(Math.floor(num / 1000000000)) + " Miliar " + toWords(num % 1000000000);
        return toWords(Math.floor(num / 1000000000000)) + " Triliun " + toWords(num % 1000000000000);
    }
    const res = toWords(angka).replace(/\s+/g, ' ').trim();
    return res + " Rupiah";
}

export function terbilangPorsi(n) {
    n = Math.floor(Math.abs(Number(n) || 0));
    if (n === 0) return 'Nol Porsi';
    const satuan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    function toWords(num) {
        if (num < 12) return satuan[num];
        if (num < 20) return toWords(num - 10) + ' Belas';
        if (num < 100) return toWords(Math.floor(num / 10)) + ' Puluh ' + toWords(num % 10);
        if (num < 200) return 'Seratus ' + toWords(num - 100);
        if (num < 1000) return toWords(Math.floor(num / 100)) + ' Ratus ' + toWords(num % 100);
        if (num < 2000) return 'Seribu ' + toWords(num - 1000);
        if (num < 1000000) return toWords(Math.floor(num / 1000)) + ' Ribu ' + toWords(num % 1000);
        if (num < 1000000000) return toWords(Math.floor(num / 1000000)) + ' Juta ' + toWords(num % 1000000);
        if (num < 1000000000000) return toWords(Math.floor(num / 1000000000)) + ' Miliar ' + toWords(num % 1000000000);
        return toWords(Math.floor(num / 1000000000000)) + ' Triliun ' + toWords(num % 1000000000000);
    }
    const res = toWords(n).replace(/\s+/g, ' ').trim();
    return res + ' Porsi';
}

/**
 * Ekstrak data porsi harian riil dari Work Order (WO) untuk kelompok tertentu pada tanggal tertentu
 */
export function getDailyPorsiItem(item, dateStr, matrixDetailMap = null, woByDateMap = null) {
    const kId = item.id;
    const det = matrixDetailMap?.[kId]?.[dateStr] || item.history_details?.[dateStr];

    if (det && det.has_wo) {
        const pk = det.porsi_kecil !== undefined && det.porsi_kecil !== null ? Number(det.porsi_kecil) : Number(item.porsi_kecil_harian || 0);
        const pb = det.porsi_besar !== undefined && det.porsi_besar !== null ? Number(det.porsi_besar) : Number(item.porsi_besar_harian || 0);
        const total = det.total_penerima !== undefined && det.total_penerima !== null ? Number(det.total_penerima) : (pk + pb);
        const totalPm = total;
        const tarifHarian = hitungTarifInsentifHarian(item.kategori, totalPm);
        return {
            pk,
            pb,
            totalPorsi: total,
            totalPm,
            tarifHarian,
            hasWo: true,
            nomorWo: det.nomor_wo || null,
            namaMenu: det.nama_menu || null,
            status: det.status || '',
            isMenerima: det.is_menerima !== false,
        };
    }

    // Cek fallback woByDateMap jika belum terpetakan di matrixDetailMap
    const woInfo = woByDateMap?.[dateStr];
    if (woInfo && Array.isArray(woInfo.kelompoks)) {
        const wok = woInfo.kelompoks.find(k => Number(k.kelompok_id) === Number(kId) || k.nama_kelompok === item.nama_kelompok);
        if (wok) {
            const pk = Number(wok.porsi_kecil || 0);
            const pb = Number(wok.porsi_besar || 0);
            const total = Number(wok.total_penerima) || (pk + pb);
            const totalPm = total;
            const tarifHarian = hitungTarifInsentifHarian(item.kategori, totalPm);
            return {
                pk,
                pb,
                totalPorsi: total,
                totalPm,
                tarifHarian,
                hasWo: true,
                nomorWo: woInfo.nomor_wo || null,
                namaMenu: woInfo.nama_menu || null,
                status: '',
                isMenerima: wok.is_menerima !== false,
            };
        }
    }

    // Fallback ke master data KPM jika tidak ada data WO
    const pk = Number(item.porsi_kecil_harian || 0);
    const pb = Number(item.porsi_besar_harian || 0);
    const total = Number(item.total_porsi_harian) || (pk + pb);
    const totalPm = Number(item.total_penerima) || total;
    const tarifHarian = hitungTarifInsentifHarian(item.kategori, totalPm);
    return {
        pk,
        pb,
        totalPorsi: total,
        totalPm,
        tarifHarian,
        hasWo: false,
        nomorWo: null,
        namaMenu: null,
        status: '',
        isMenerima: true,
    };
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
                tl: { col: 0.1, row: 0.2 },
                ext: { width: 55, height: 55 }
            });
        } catch (e) {
            console.warn('Gagal menempelkan logo BGN:', e);
        }
    }
    if (logoYayasanId !== null) {
        try {
            sheet.addImage(logoYayasanId, {
                tl: { col: maxCols - 1.1, row: 0.2 },
                ext: { width: 55, height: 55 }
            });
        } catch (e) {
            console.warn('Gagal menempelkan logo Yayasan:', e);
        }
    }
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. UNDUH LAPORAN REKAPITULASI MATRIKS DISTRIBUSI PM EXCEL (.XLSX)
 * Sheet 1: Matriks Distribusi Harian (Akumulasi riil porsi WO & Hak Insentif)
 * Sheet 2: Daftar Alokasi & Narahubung PM
 * ─────────────────────────────────────────────────────────────────────────────
 */
export async function downloadRekapDistribusiExcel({
    distribusiList = [],
    matrixDistribusi = {},
    matrixDetailMap = {},
    woByDateMap = {},
    dateColumns = [],
    stats = {},
    startDate = null,
    endDate = null,
    mode = 'bulanan',
    unitSppg = null,
    periode = null,
    petugas = [],
    user = null,
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

    const periodeNama = periode?.nama || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional');
    const periodeText = actualStartStr === actualEndStr
        ? `Tanggal Distribusi: ${formatTanggalIndo(actualStartStr)} | ${periodeNama}`
        : `Periode Distribusi: ${formatTanggalIndo(actualStartStr)} s/d ${formatTanggalIndo(actualEndStr)} | ${periodeNama}`;

    // =========================================================================
    // SHEET 1: Matriks Distribusi Harian (Interaktif & Lengkap Status T/L)
    // =========================================================================
    if (validDates.length > 0) {
        const sheetMatriks = workbook.addWorksheet('Matriks Distribusi Harian', {
            pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
        });

        // 8 Kolom Info + N Kolom Tanggal + 3 Kolom Rekap (Hari Kirim, Total Porsi Akumulasi WO, Total Insentif)
        const totalMatriksCols = 8 + validDates.length + 3;
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

        // Kolom Akhir: Hari Kirim, Akumulasi Porsi Riil WO & Total Insentif (Rp)
        const colHariKirimIdx = 9 + validDates.length;
        const colAkumulasiIdx = colHariKirimIdx + 1;
        const colInsentifIdx = colAkumulasiIdx + 1;

        const cellHariKirim = headerRow.getCell(colHariKirimIdx);
        cellHariKirim.value = 'HARI\nKIRIM';
        cellHariKirim.font = { name: 'Arial', size: 8.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cellHariKirim.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F172A' } };
        cellHariKirim.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cellHariKirim.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };

        const cellAkumulasi = headerRow.getCell(colAkumulasiIdx);
        cellAkumulasi.value = 'TOTAL PORSI\nAKUMULASI (WO)';
        cellAkumulasi.font = { name: 'Arial', size: 8.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cellAkumulasi.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064E3B' } };
        cellAkumulasi.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cellAkumulasi.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };

        const cellInsentif = headerRow.getCell(colInsentifIdx);
        cellInsentif.value = 'TOTAL HAK\nINSENTIF (RP)';
        cellInsentif.font = { name: 'Arial', size: 8.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cellInsentif.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F766E' } };
        cellInsentif.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cellInsentif.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };

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
            let sumPorsiAkumulasiRiil = 0;
            let sumInsentifAkumulasiRiil = 0;

            validDates.forEach((col, idx) => {
                const cIdx = 9 + idx;
                const status = rowMap[col.dateStr];
                const cell = row.getCell(cIdx);
                const daily = getDailyPorsiItem(item, col.dateStr, matrixDetailMap, woByDateMap);

                if (status === 'T') {
                    countTerkirim++;
                    sumPorsiAkumulasiRiil += daily.totalPorsi;
                    sumInsentifAkumulasiRiil += hitungTarifInsentifHarian(item.kategori, daily.totalPm);

                    cell.value = 'T';
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } }; // emerald-100
                    cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                    
                    // Note detail porsi riil sesuai Work Order pada tanggal ini
                    if (daily.hasWo) {
                        const tarifVal = daily.tarifHarian !== undefined ? daily.tarifHarian : hitungTarifInsentifHarian(item.kategori, daily.totalPm);
                        cell.note = `WO: ${daily.nomorWo || '-'} | Menu: ${daily.namaMenu || '-'}\nPorsi: ${daily.totalPorsi} (PK: ${daily.pk}, PB: ${daily.pb})\nInsentif: ${formatRupiah(tarifVal)}`;
                    }
                } else if (status === 'P') {
                    cell.value = 'P';
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDBEAFE' } }; // blue-100
                    cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1E40AF' } };
                } else if (status === 'L') {
                    cell.value = 'L';
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
                    cell.font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
                } else if (status && String(status).trim() !== '') {
                    cell.value = String(status);
                    cell.font = { name: 'Arial', size: 8.5 };
                } else {
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

            // Hari kirim riil berdasarkan status 'T'
            const actualDays = countTerkirim;
            row.getCell(colHariKirimIdx).value = actualDays;
            
            // Total Akumulasi Porsi riil dari Work Order (tidak mengalikan porsi statis)
            row.getCell(colAkumulasiIdx).value = sumPorsiAkumulasiRiil > 0 ? sumPorsiAkumulasiRiil : (actualDays * (Number(item.total_porsi_harian) || 0));
            row.getCell(colInsentifIdx).value = sumInsentifAkumulasiRiil;

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

            const cellIn = row.getCell(colInsentifIdx);
            cellIn.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0F766E' } };
            cellIn.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDFA' } };
            cellIn.alignment = { horizontal: 'right', vertical: 'middle' };
            cellIn.numFmt = '#,##0';
            cellIn.border = {
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
        const letterIn = colToLetter(colInsentifIdx);
        totRowM.getCell(colHariKirimIdx).value = '-';
        totRowM.getCell(colAkumulasiIdx).value = { formula: `SUM(${letterAk}10:${letterAk}${curRowM - 1})` };
        totRowM.getCell(colInsentifIdx).value = { formula: `SUM(${letterIn}10:${letterIn}${curRowM - 1})` };

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
            if ([5, 6, 7, 8, colAkumulasiIdx, colInsentifIdx].includes(c)) {
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
        colWidths.push({ width: 18 }); // Total Akumulasi (WO)
        colWidths.push({ width: 18 }); // Total Insentif (Rp)
        sheetMatriks.columns = colWidths;

        // Lembar Pengesahan Resmi Matriks Distribusi
        applyLembarPengesahan(sheetMatriks, {
            maxCols: totalMatriksCols,
            leftStartCol: 2,
            leftEndCol: 5,
            rightColsWidth: 4,
            unitSppg,
            petugas,
            user,
            tanggal: actualEndStr || actualStartStr || new Date(),
        });
    }

    // =========================================================================
    // SHEET 2: Ringkasan Alokasi & Narahubung PM (Format Tabel Resmi)
    // =========================================================================
    const sheetRingkasan = workbook.addWorksheet('Daftar Alokasi PM', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxColsRingkasan = 12;
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
        'TOTAL AKUMULASI (WO)',
        'HAK INSENTIF (RP)'
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
        row.getCell(10).value = Number(item.total_porsi_harian) || 0;

        const rowMapR = matrixDistribusi[item.id] || {};
        let countTerkirimR = 0;
        let sumAkumulasiR = 0;
        let sumInsentifR = 0;

        validDates.forEach((col) => {
            if (rowMapR[col.dateStr] === 'T') {
                countTerkirimR++;
                const daily = getDailyPorsiItem(item, col.dateStr, matrixDetailMap, woByDateMap);
                sumAkumulasiR += daily.totalPorsi;
                sumInsentifR += hitungTarifInsentifHarian(item.kategori, daily.totalPm);
            }
        });

        const actualDaysR = countTerkirimR > 0 ? countTerkirimR : (Number(item.total_hari_kirim) || 0);
        row.getCell(11).value = sumAkumulasiR > 0 ? sumAkumulasiR : (actualDaysR * (Number(item.total_porsi_harian) || 0));
        row.getCell(12).value = sumInsentifR;

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
            } else if ([7, 8, 9, 10, 11, 12].includes(c)) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '#,##0';
            }
        }
        currentRowR++;
    });

    // Baris Total Ringkasan
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
    totalRowR.getCell(12).value = { formula: `SUM(L10:L${currentRowR - 1})` };

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
        { width: 18 }, // Akumulasi Porsi
        { width: 18 }, // Total Insentif
    ];

    // Lembar Pengesahan Resmi Daftar Alokasi PM
    applyLembarPengesahan(sheetRingkasan, {
        maxCols: maxColsRingkasan,
        leftStartCol: 2,
        leftEndCol: 5,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: actualEndStr || actualStartStr || new Date(),
    });

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

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * 2. BUKU TANDA TERIMA DISTRIBUSI PM HARIAN EXCEL (.XLSX)
 * Format cetak buku tanda terima pengiriman porsi MBG per sheet DD-MM-YYYY
 * Lengkap dengan porsi riil sesuai Work Order (WO), hak insentif harian,
 * dan kolom tanda tangan fisik penerima pihak sekolah/posyandu
 * ─────────────────────────────────────────────────────────────────────────────
 */
export async function downloadBukuTandaTerimaDistribusiHarianExcel({
    distribusiList = [],
    matrixDistribusi = {},
    matrixDetailMap = {},
    woByDateMap = {},
    dateList = [],
    dateColumns = [],
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    petugas = [],
    user = null,
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
    let targetDates = (Array.isArray(dateColumns) && dateColumns.length > 0)
        ? [...dateColumns]
        : (Array.isArray(dateList) && dateList.length > 0 ? [...dateList] : []);

    const actualStartStr = startDate || (targetDates[0]?.dateStr);
    const actualEndStr = endDate || (targetDates[targetDates.length - 1]?.dateStr) || actualStartStr;

    // Pastikan targetDates mencakup seluruh rentang startDate s/d endDate yang difilter
    if (
        actualStartStr && actualEndStr &&
        (targetDates.length === 0 ||
         (startDate && targetDates[0]?.dateStr !== startDate) ||
         (endDate && targetDates[targetDates.length - 1]?.dateStr !== endDate))
    ) {
        targetDates = [];
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
                targetDates.push({
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
            console.warn('Gagal generate targetDates:', e);
        }
    }

    const dayNameMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const periodeNama = periode?.nama || periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');

    targetDates.forEach(dt => {
        const sheetName = formatDateToSheetName(dt.dateStr);
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

        const maxCols = 11;
        sheet.columns = [
            { width: 5 },   // 1: No
            { width: 32 },  // 2: Nama Kelompok / Sekolah
            { width: 14 },  // 3: Kategori
            { width: 18 },  // 4: NPSN / Kode Identitas
            { width: 18 },  // 5: Desa / Kelurahan
            { width: 14 },  // 6: Sasaran PM (WO)
            { width: 12 },  // 7: Porsi Kecil (PK)
            { width: 12 },  // 8: Porsi Besar (PB)
            { width: 14 },  // 9: Total Porsi (WO)
            { width: 16 },  // 10: Hak Insentif (Rp)
            { width: 28 },  // 11: Tanda Tangan Penerima Sekolah
        ];

        applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

        sheet.addRow([]);
        sheet.getRow(5).height = 10;

        const rJudul = sheet.addRow(['DAFTAR TANDA TERIMA PENGIRIMAN & PENERIMAAN MAKANAN BERGIZI GRATIS (MBG)']);
        sheet.mergeCells(`A${rJudul.number}:K${rJudul.number}`);
        rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
        rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        rJudul.height = 22;

        let dayName = '';
        try {
            const curD = new Date(dt.dateStr + 'T00:00:00');
            dayName = dayNameMap[curD.getDay()] || '';
        } catch {}

        const woInfoForDate = woByDateMap?.[dt.dateStr] || null;
        const woText = woInfoForDate ? ` | No. WO: ${woInfoForDate.nomor_wo || '-'} (Menu: ${woInfoForDate.nama_menu || '-'})` : '';

        const rSub = sheet.addRow([
            `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | Hari & Tanggal: ${dayName ? dayName + ', ' : ''}${formatTanggalIndo(dt.dateStr)} | ${periodeNama}${woText}`
        ]);
        sheet.mergeCells(`A${rSub.number}:K${rSub.number}`);
        rSub.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
        rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        rSub.height = 18;

        sheet.addRow([]);
        sheet.getRow(8).height = 8;

        const headValues = [
            'NO',
            'NAMA KELOMPOK / SASARAN',
            'KATEGORI',
            'NPSN / KODE',
            'DESA / KELURAHAN',
            'SASARAN (PM)',
            'PK',
            'PB',
            'TOTAL PORSI (WO)',
            'HAK INSENTIF (RP)',
            'TANDA TANGAN PENERIMA',
        ];
        const rHead = sheet.addRow(headValues);
        rHead.height = 26;
        rHead.eachCell({ includeEmpty: true }, cell => {
            cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0F766E' } }; // teal-700
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'medium' }, right: { style: 'thin' } };
        });

        const dataStartRow = 10;
        let curRow = dataStartRow;

        items.forEach((item, idx) => {
            const isTerkirim = matrixDistribusi[item.id]?.[dt.dateStr] === 'T';
            // Porsi harian riil diambil dari Work Order untuk tanggal ini
            const daily = getDailyPorsiItem(item, dt.dateStr, matrixDetailMap, woByDateMap);
            const pk = daily.pk;
            const pb = daily.pb;
            const totalPorsi = daily.totalPorsi;
            const totalPm = daily.totalPm;
            const tarifHarian = hitungTarifInsentifHarian(item.kategori, totalPm);

            const ttdNum = idx + 1;
            const ttdText = (ttdNum % 2 === 1) ? `${ttdNum}. ..........................` : `            ${ttdNum}. ..........................`;

            const row = sheet.addRow([
                idx + 1,
                item.nama_kelompok || '-',
                item.kategori || '-',
                `${item.tipe_identitas || ''} ${item.kode_identitas || '-'}`.trim(),
                item.desa_kelurahan || '-',
                totalPm,
                pk,
                pb,
                totalPorsi,
                tarifHarian,
                ttdText,
            ]);
            row.height = 22;

            const isEven = idx % 2 === 1;
            const rowBg = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

            row.eachCell({ includeEmpty: true }, (cell, cIdx) => {
                cell.font = { name: 'Arial', size: 9 };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                };
                cell.alignment = { vertical: 'middle' };

                if (cIdx === 1) {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (cIdx === 2) {
                    cell.font = { name: 'Arial', size: 9, bold: true };
                } else if ([3, 4, 5].includes(cIdx)) {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if ([6, 7, 8, 9, 10].includes(cIdx)) {
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '#,##0';
                    if (cIdx === 9) {
                        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                    }
                    if (cIdx === 10) {
                        cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0F766E' } };
                    }
                } else if (cIdx === 11) {
                    cell.font = { name: 'Arial', size: 8.5, italic: true, color: { argb: 'FF475569' } };
                    cell.alignment = { horizontal: 'left', vertical: 'middle' };
                }
            });
            curRow++;
        });

        // Baris Total Harian
        const totRow = sheet.addRow([
            'TOTAL KESELURUHAN', '', '', '', '',
            { formula: `SUM(F${dataStartRow}:F${curRow - 1})` },
            { formula: `SUM(G${dataStartRow}:G${curRow - 1})` },
            { formula: `SUM(H${dataStartRow}:H${curRow - 1})` },
            { formula: `SUM(I${dataStartRow}:I${curRow - 1})` },
            { formula: `SUM(J${dataStartRow}:J${curRow - 1})` },
            '',
        ]);
        sheet.mergeCells(`A${totRow.number}:E${totRow.number}`);
        totRow.height = 24;
        totRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };

        for (let c = 1; c <= maxCols; c++) {
            const cell = totRow.getCell(c);
            cell.font = { name: 'Arial', size: 9.5, bold: true };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
            cell.border = {
                top: { style: 'medium', color: { argb: 'FF0F172A' } },
                bottom: { style: 'double', color: { argb: 'FF0F172A' } },
                left: { style: 'thin' },
                right: { style: 'thin' },
            };
            if ([6, 7, 8, 9, 10].includes(c)) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '#,##0';
                if (c === 9) {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF047857' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
                }
                if (c === 10) {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F766E' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDFA' } };
                }
            }
        }

        // Lembar Pengesahan
        applyLembarPengesahan(sheet, {
            maxCols,
            leftStartCol: 2,
            leftEndCol: 5,
            rightColsWidth: 4,
            unitSppg,
            petugas,
            user,
            tanggal: dt.dateStr,
        });
    });

    const fileDateLabel = targetDates.length === 1 ? targetDates[0].dateStr : `${targetDates[0]?.dateStr}_sd_${targetDates[targetDates.length - 1]?.dateStr}`;
    const filename = `Buku_Tanda_Terima_Distribusi_Harian_${fileDateLabel}.xlsx`;
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

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * 3. BUKTI TANDA TERIMA DISTRIBUSI & HAK INSENTIF PM PDF (1 LEMBAR PER SASARAN PM)
 * Format cetak resmi surat tanda terima penyaluran MBG memuat:
 * - Kop resmi BGN & Yayasan proporsional (tanpa garis balok)
 * - Identitas Lembaga & Unit Kerja
 * - TABEL DAFTAR HARI PENERIMAAN MBG (Tanggal, WO/Menu, PK, PB, Total Porsi, Insentif)
 * - TOTAL AKUMULASI PORSI & TOTAL HAK INSENTIF DITERIMA (RP) LENGKAP TERBILANG
 * - Kolom tanda tangan 3 pihak resmi (Penerima, Petugas Distribusi, Kepala SPPG)
 * - Tanggal cetak dan waktu (WITA)
 * ─────────────────────────────────────────────────────────────────────────────
 */
export async function downloadBuktiDistribusiPdf({
    distribusiList = [],
    matrixDistribusi = {},
    matrixDetailMap = {},
    woByDateMap = {},
    dateColumns = [],
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    petugas = [],
    user = null,
}) {
    if (!distribusiList || distribusiList.length === 0) {
        throw new Error('Tidak ada data penerima manfaat untuk dicetak bukti distribusi.');
    }

    const kopConfig = getActiveKopConfig(unitSppg);
    const penandatangan = getPenandatanganSppg(petugas, user);

    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    const items = distribusiList;
    const dateList = dateColumns || [];

    const startStr = startDate || dateList[0]?.dateStr || '';
    const endStr = endDate || dateList[dateList.length - 1]?.dateStr || startStr;
    const rangeText = startStr === endStr
        ? formatTanggalIndo(startStr)
        : `${formatTanggalIndo(startStr)} s.d. ${formatTanggalIndo(endStr)}`;

    const periodeNama = periode?.nama || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Berjalan');

    items.forEach((p, idx) => {
        if (idx > 0) {
            doc.addPage();
        }

        // Kumpulkan daftar hari penerimaan MBG berstatus 'T'
        const receivingDays = [];
        dateList.forEach(dt => {
            const st = matrixDistribusi[p.id]?.[dt.dateStr];
            if (st === 'T') {
                const daily = getDailyPorsiItem(p, dt.dateStr, matrixDetailMap, woByDateMap);
                const tarifHarian = hitungTarifInsentifHarian(p.kategori, daily.totalPm);
                receivingDays.push({
                    dateStr: dt.dateStr,
                    dayName: dt.dayName || '',
                    nomorWo: daily.nomorWo || '-',
                    namaMenu: daily.namaMenu || '-',
                    pk: daily.pk,
                    pb: daily.pb,
                    totalPorsi: daily.totalPorsi,
                    totalPm: daily.totalPm,
                    tarifHarian,
                });
            }
        });

        // Fallback jika belum ada status 'T' (misal baru dibuat atau preview 1 hari):
        if (receivingDays.length === 0) {
            dateList.forEach(dt => {
                const daily = getDailyPorsiItem(p, dt.dateStr, matrixDetailMap, woByDateMap);
                const tarifHarian = hitungTarifInsentifHarian(p.kategori, daily.totalPm);
                receivingDays.push({
                    dateStr: dt.dateStr,
                    dayName: dt.dayName || '',
                    nomorWo: daily.nomorWo || '-',
                    namaMenu: daily.namaMenu || '-',
                    pk: daily.pk,
                    pb: daily.pb,
                    totalPorsi: daily.totalPorsi,
                    totalPm: daily.totalPm,
                    tarifHarian,
                });
            });
        }

        let totalHariKirim = receivingDays.length;
        let totalPk = 0;
        let totalPb = 0;
        let totalPorsiAkumulasi = 0;
        let totalNominalAkumulasi = 0;

        receivingDays.forEach(d => {
            totalPk += d.pk;
            totalPb += d.pb;
            totalPorsiAkumulasi += d.totalPorsi;
            totalNominalAkumulasi += d.tarifHarian;
        });

        // 1. Box Container Kartu Slip (1 Halaman Penuh A4: 210 x 297mm)
        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.4);
        doc.roundedRect(12, 12, 186, 273, 2.5, 2.5, 'FD');

        // 2. Kop Surat SPPG
        try {
            if (LOGO_BGN_BASE64) {
                doc.addImage(LOGO_BGN_BASE64, 'PNG', 15, 15.5, 19, 19);
            }
        } catch (e) {}

        try {
            if (LOGO_YAYASAN_BASE64) {
                doc.addImage(LOGO_YAYASAN_BASE64, 'PNG', 178, 17, 16, 16);
            }
        } catch (e) {}

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10.5);
        doc.setTextColor(15, 23, 42);
        doc.text(kopConfig?.nama_instansi_1 || 'SPPG BULELENG SUKASADA TEGALLINGGAH', 105, 21, { align: 'center' });

        doc.setFontSize(9);
        doc.setTextColor(30, 41, 59);
        doc.text(kopConfig?.nama_instansi_2 || 'YAYASAN PESANTREN MIFTAHUL ULUM', 105, 25.5, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(71, 85, 105);
        doc.text(kopConfig?.alamat_lengkap || 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali', 105, 29.5, { align: 'center' });

        doc.setFont('helvetica', 'italic');
        doc.setTextColor(100, 116, 139);
        doc.text(`E-mail: ${kopConfig?.email || 'sppgsukasadategallinggah@gmail.com'}`, 105, 33.5, { align: 'center' });

        // Garis Pembatas Kop
        doc.setDrawColor(15, 23, 42);
        doc.setLineWidth(0.4);
        doc.line(16, 36.5, 194, 36.5);
        doc.setLineWidth(0.15);
        doc.line(16, 37.3, 194, 37.3);

        // 3. Title Banner
        doc.setFillColor(15, 118, 110); // teal-700
        doc.roundedRect(16, 39.5, 178, 10.0, 1.5, 1.5, 'F');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(255, 255, 255);
        doc.text('BUKTI PENERIMAAN DISTRIBUSI & HAK INSENTIF OPERASIONAL MBG', 105, 44.0, { align: 'center' });

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(6.8);
        doc.setTextColor(204, 251, 241);
        doc.text('Surat Bukti Penyaluran Makanan Bergizi Gratis (MBG) & Rekapitulasi Hak Penanggung Jawab Satuan PM', 105, 47.5, { align: 'center' });

        // 4. Metadata Box (8 Field - Rata Tengah Atas-Bawah Presisi)
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.3);
        doc.roundedRect(16, 51.5, 178, 23.0, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);

        // Row 1 (baseline 56.2 - centered)
        doc.setTextColor(100, 116, 139);
        doc.text('Nama Satuan / PM', 20, 56.2);
        doc.text(':', 51, 56.2);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(p.nama_kelompok || '-', 54, 56.2);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Periode Kerja', 110, 56.2);
        doc.text(':', 136, 56.2);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(periodeNama, 139, 56.2);

        // Row 2 (baseline 61.2)
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Identitas / NPSN', 20, 61.2);
        doc.text(':', 51, 61.2);
        doc.setTextColor(30, 41, 59);
        doc.text(`${p.tipe_identitas || 'NPSN'} ${p.kode_identitas || '-'} (${p.kategori || 'Sekolah'})`, 54, 61.2);

        doc.setTextColor(100, 116, 139);
        doc.text('Rentang Tanggal', 110, 61.2);
        doc.text(':', 136, 61.2);
        doc.setTextColor(30, 41, 59);
        doc.text(rangeText, 139, 61.2);

        // Row 3 (baseline 66.2)
        doc.setTextColor(100, 116, 139);
        doc.text('Penanggung Jawab (PIC)', 20, 66.2);
        doc.text(':', 51, 66.2);
        doc.setTextColor(30, 41, 59);
        doc.text(`${p.nama_pic || '-'} (${p.telepon_pic || '-'})`, 54, 66.2);

        doc.setTextColor(100, 116, 139);
        doc.text('Desa / Wilayah', 110, 66.2);
        doc.text(':', 136, 66.2);
        doc.setTextColor(30, 41, 59);
        doc.text(p.desa_kelurahan || 'Kec. Sukasada', 139, 66.2);

        // Row 4 (baseline 71.2)
        doc.setTextColor(100, 116, 139);
        doc.text('Unit Kerja', 20, 71.2);
        doc.text(':', 51, 71.2);
        doc.setTextColor(51, 65, 85);
        doc.text(unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah', 54, 71.2);

        doc.setTextColor(100, 116, 139);
        doc.text('Status Distribusi', 110, 71.2);
        doc.text(':', 136, 71.2);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 118, 110);
        doc.text(`Aktif Terlayani (${totalHariKirim} Hari Kirim)`, 139, 71.2);

        // 5. TABEL DAFTAR HARI PENERIMAAN MBG & RINCIAN PORSI + INSENTIF
        const startTableY = 76.5;
        const tableW = 178.0;
        const colStartX = 16.0;

        // Table Header (Rata Tengah Vertikal)
        const headerH = 6.5;
        doc.setFillColor(15, 23, 42); // slate-900
        doc.rect(colStartX, startTableY, tableW, headerH, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(255, 255, 255);

        const headerTextY = startTableY + 4.2;
        doc.text('NO', 19, headerTextY, { align: 'center' });
        doc.text('HARI & TANGGAL', 25, headerTextY);
        doc.text('NAMA MENU', 62, headerTextY);
        doc.text('PK', 111, headerTextY, { align: 'right' });
        doc.text('PB', 125, headerTextY, { align: 'right' });
        doc.text('TOTAL PORSI', 145, headerTextY, { align: 'right' });
        doc.text('TARIF/HARI', 167, headerTextY, { align: 'right' });
        doc.text('HAK DITERIMA', 191, headerTextY, { align: 'right' });

        // Baris-baris Data Harian (Rata Tengah Vertikal)
        const rowH = receivingDays.length > 12 ? 4.2 : (receivingDays.length > 8 ? 4.8 : 5.4);
        let curTableY = startTableY + headerH;

        receivingDays.forEach((d, dIdx) => {
            const isEven = dIdx % 2 === 1;
            doc.setFillColor(isEven ? 248 : 255, isEven ? 250 : 255, isEven ? 252 : 255);
            doc.rect(colStartX, curTableY, tableW, rowH, 'F');
            doc.setDrawColor(226, 232, 240);
            doc.setLineWidth(0.2);
            doc.line(colStartX, curTableY + rowH, colStartX + tableW, curTableY + rowH);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.8);
            doc.setTextColor(51, 65, 85);

            // Posisi Y teks rata tengah vertikal presisi
            const textY = curTableY + (rowH / 2) + 1.1;

            // No
            doc.text(String(dIdx + 1), 19, textY, { align: 'center' });

            // Hari & Tanggal
            const dayTgl = `${d.dayName ? d.dayName + ', ' : ''}${formatTanggalIndo(d.dateStr)}`;
            doc.text(dayTgl, 25, textY);

            // Nama Menu (Hanya nama menu, tanpa nomor WO dan tanpa sub menu)
            let menuStr = (d.namaMenu && d.namaMenu !== '-') ? d.namaMenu.trim() : '-';
            if (menuStr.length > 36) {
                menuStr = menuStr.substring(0, 34) + '...';
            }
            doc.text(menuStr, 62, textY);

            // PK & PB
            doc.text(formatRibuan(d.pk), 111, textY, { align: 'right' });
            doc.text(formatRibuan(d.pb), 125, textY, { align: 'right' });

            // Total Porsi
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(15, 23, 42);
            doc.text(formatRibuan(d.totalPorsi), 145, textY, { align: 'right' });

            // Tarif / Hari
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(71, 85, 105);
            doc.text(formatRupiah(d.tarifHarian), 167, textY, { align: 'right' });

            // Hak Diterima
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(15, 118, 110);
            doc.text(formatRupiah(d.tarifHarian), 191, textY, { align: 'right' });

            curTableY += rowH;
        });

        // Baris Total Tabel (Rata Tengah Vertikal)
        const totalRowH = 6.2;
        doc.setFillColor(241, 245, 249); // slate-100
        doc.rect(colStartX, curTableY, tableW, totalRowH, 'F');
        doc.setDrawColor(15, 23, 42);
        doc.setLineWidth(0.3);
        doc.line(colStartX, curTableY, colStartX + tableW, curTableY);
        doc.line(colStartX, curTableY + totalRowH, colStartX + tableW, curTableY + totalRowH);

        const totTextY = curTableY + (totalRowH / 2) + 1.1;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.2);
        doc.setTextColor(15, 23, 42);
        doc.text(`TOTAL KESELURUHAN (${totalHariKirim} HARI OPERASIONAL)`, 25, totTextY);

        doc.text(formatRibuan(totalPk), 111, totTextY, { align: 'right' });
        doc.text(formatRibuan(totalPb), 125, totTextY, { align: 'right' });

        doc.setTextColor(17, 94, 89);
        doc.text(`${formatRibuan(totalPorsiAkumulasi)} Porsi`, 145, totTextY, { align: 'right' });

        doc.setTextColor(100, 116, 139);
        doc.text('-', 167, totTextY, { align: 'right' });

        doc.setTextColor(15, 118, 110);
        doc.setFontSize(7.5);
        doc.text(formatRupiah(totalNominalAkumulasi), 191, totTextY, { align: 'right' });

        curTableY += totalRowH;

        // 6. Callout Box Highlight: Total Hak Insentif PIC (Hanya Nominal Rupiah)
        const calloutY = curTableY + 3.0;
        const calloutH = 19.5;
        doc.setFillColor(204, 251, 241); // teal-100
        doc.setDrawColor(15, 118, 110);
        doc.setLineWidth(0.5);
        doc.roundedRect(colStartX, calloutY, tableW, calloutH, 2.0, 2.0, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.8);
        doc.setTextColor(17, 94, 89);
        doc.text('TOTAL HAK INSENTIF PIC', 105, calloutY + 5.0, { align: 'center' });

        doc.setFontSize(13.5);
        doc.setTextColor(19, 78, 74);
        doc.text(formatRupiah(totalNominalAkumulasi), 105, calloutY + 11.2, { align: 'center' });

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(7.2);
        doc.setTextColor(51, 65, 85);
        const terbilangStr = `Terbilang: "${angkaKeTerbilangRupiah(totalNominalAkumulasi)}"`;
        const splitTerbilang = doc.splitTextToSize(terbilangStr, 168);
        doc.text(splitTerbilang, 105, calloutY + 16.0, { align: 'center' });

        // 7. Catatan Resmi Box (Rata Tengah Vertikal Presisi)
        const noteY = calloutY + calloutH + 2.5;
        const noteH = 13.0;
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.25);
        doc.roundedRect(colStartX, noteY, tableW, noteH, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.8);
        doc.setTextColor(71, 85, 105);
        doc.text('CATATAN PENCAIRAN & PENYALURAN RESMI SPPG:', 20, noteY + 3.8);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.2);
        doc.setTextColor(100, 116, 139);
        doc.text('1. Besaran nominal hak insentif penanggung jawab/kader dihitung harian berdasarkan porsi riil sesuai Keputusan BGN RI.', 20, noteY + 7.4);
        doc.text('2. Rincian porsi harian mengikuti Work Order (WO) operasional SPPG dan telah diverifikasi oleh petugas distribusi.', 20, noteY + 10.7);

        // 8. Tanda Tangan Pengesahan (3 Pihak: Penerima Satuan PM, Petugas Distribusi, Mengetahui Kepala SPPG)
        // Penempatan proporsional di bagian bawah kartu dokumen
        const ttdTopY = Math.max(noteY + noteH + 4.5, 212.0);

        const kepalaPetugas = (petugas || []).find(pt => {
            const j = (pt.jabatan || '').toLowerCase();
            return j === 'kepala sppg' || j.includes('kepala sppg');
        });
        const namaKepala = penandatangan?.kepala?.nama || kepalaPetugas?.nama || '';
        const ttdKepala = namaKepala ? `( ${namaKepala} )` : '( .................................................... )';

        const namaDistribusi = penandatangan?.distribusi?.nama || 'Petugas Distribusi SPPG';
        const ttdDistribusi = namaDistribusi ? `( ${namaDistribusi} )` : '( .................................................... )';

        const namaPicPenerima = p.nama_pic || p.nama_kepala || 'PIC Satuan PM';
        const ttdPenerima = `( ${namaPicPenerima} )`;

        const now = new Date();
        const tglCetak = formatTanggalIndo(now);
        const tglWaktuCetak = formatTanggalWaktuIndo(now);
        const kotaStr = kopConfig?.kabupaten || 'Buleleng';

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 65, 85);

        // Tanggal diletakkan di atas Mengetahui
        doc.text(`${kotaStr}, ${tglCetak}`, 165, ttdTopY + 2.0, { align: 'center' });
        doc.text('Mengetahui,', 105, ttdTopY + 6.5, { align: 'center' });

        // Header 3 Kolom
        doc.text('Penerima / PIC Satuan PM,', 45, ttdTopY + 12.0, { align: 'center' });
        doc.text('Petugas Distribusi SPPG,', 105, ttdTopY + 12.0, { align: 'center' });
        doc.text('Kepala SPPG,', 165, ttdTopY + 12.0, { align: 'center' });

        // Nama Penandatangan di Bawah (Ruang TTD ~22mm)
        const nameY = ttdTopY + 34.0;
        doc.setFont('helvetica', 'bold');
        doc.text(ttdPenerima, 45, nameY, { align: 'center' });
        doc.text(ttdDistribusi, 105, nameY, { align: 'center' });
        doc.text(ttdKepala, 165, nameY, { align: 'center' });

        // 9. Footer Resmi
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(148, 163, 184);
        doc.text(
            `Sistem Informasi SPPG (SIPEGE) - Slip dicetak secara elektronik pada ${tglWaktuCetak} • Halaman ${idx + 1} dari ${items.length}`,
            105,
            281,
            { align: 'center' }
        );
    });

    const fileDateLabel = startStr === endStr ? startStr : `${startStr}_sd_${endStr}`;
    doc.save(`Bukti_Tanda_Terima_Distribusi_PM_${fileDateLabel}.pdf`);
}
