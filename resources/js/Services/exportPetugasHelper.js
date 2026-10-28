import ExcelJS from "exceljs";
import jsPDF from "jspdf";
import { getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { 
    LOGO_BGN_BASE64,
    LOGO_YAYASAN_BASE64,
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

export function formatTanggalWaktuIndo(dateObj) {
    if (!dateObj) return '-';
    try {
        const d = new Date(dateObj);
        if (isNaN(d.getTime())) return String(dateObj);
        const day = d.getDate();
        const month = MONTHS_INDO[d.getMonth()];
        const year = d.getFullYear();
        const jam = String(d.getHours()).padStart(2, '0');
        const menit = String(d.getMinutes()).padStart(2, '0');
        const detik = String(d.getSeconds()).padStart(2, '0');
        return `${day} ${month} ${year} pukul ${jam}:${menit}:${detik} WITA`;
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
 * Helper untuk mendapatkan data nama & jabatan resmi penandatangan SPPG
 * Pihak 1 (Kiri): Kepala SPPG
 * Pihak 2 (Kanan): Pengawas Keuangan atau Gizi
 * Nama diambil dari data petugas / user; bila belum ada diisi titik-titik ( .................................................... )
 */
export function getPenandatanganSppg(petugas = [], user = null) {
    // 1. Kepala SPPG
    let kepala = (petugas || []).find(p => {
        const j = (p.jabatan || '').toLowerCase();
        return j === 'kepala sppg' || j.includes('kepala sppg');
    });

    let namaKepala = '';
    if (kepala && kepala.nama) {
        namaKepala = kepala.nama;
    }
    // Jika ada user login dengan nama lengkap (berisi gelar dsb) yang sesuai atau sebagai fallback
    if (user) {
        const userFullName = user.nama_lengkap || user.nama || user.name || '';
        if (userFullName) {
            if (!namaKepala) {
                namaKepala = userFullName;
            } else if (userFullName.toLowerCase().includes(namaKepala.toLowerCase()) || namaKepala.toLowerCase().includes((user.nama || '').toLowerCase())) {
                namaKepala = userFullName;
            }
        }
    }

    const ttdKepala = namaKepala ? `( ${namaKepala} )` : '( .................................................... )';
    const jabatanKepala = 'Kepala SPPG';
    const subKepala = 'Penanggung Jawab Operasional SPPG';

    // 2. Pengawas Keuangan atau Gizi
    const pengawasKeu = (petugas || []).find(p => {
        const j = (p.jabatan || '').toLowerCase();
        return j === 'pengawas keuangan' || j.includes('pengawas keuangan');
    });

    const pengawasGizi = (petugas || []).find(p => {
        const j = (p.jabatan || '').toLowerCase();
        return j === 'pengawas gizi' || j.includes('pengawas gizi');
    });

    let jabatanPengawas = 'Pengawas Keuangan / Gizi';
    let subPengawas = 'Pengawas Operasional SPPG';
    let namaPengawas = '';

    if (pengawasKeu && pengawasKeu.nama) {
        jabatanPengawas = 'Pengawas Keuangan';
        subPengawas = 'Pengawas Administrasi & Keuangan';
        namaPengawas = pengawasKeu.nama;
    } else if (pengawasGizi && pengawasGizi.nama) {
        jabatanPengawas = 'Pengawas Gizi';
        subPengawas = 'Pengawas Standar Gizi & Mutu';
        namaPengawas = pengawasGizi.nama;
    }

    const ttdPengawas = namaPengawas ? `( ${namaPengawas} )` : '( .................................................... )';

    return {
        kepala: {
            jabatan: jabatanKepala,
            sub: subKepala,
            nama: namaKepala,
            ttdText: ttdKepala,
        },
        pengawas: {
            jabatan: jabatanPengawas,
            sub: subPengawas,
            nama: namaPengawas,
            ttdText: ttdPengawas,
        }
    };
}

/**
 * Helper untuk menerapkan blok pengesahan resmi (Tanda Tangan) di akhir lembar Excel
 * Format simetris di sisi kiri (Kepala SPPG) dan sisi kanan (Pengawas Keuangan / Gizi)
 */
export function applyLembarPengesahan(sheet, {
    maxCols,
    leftStartCol = 2,
    leftEndCol = 5,
    rightColsWidth = 4,
    unitSppg = null,
    petugas = [],
    user = null,
    tanggal = null,
}) {
    sheet.addRow([]);
    sheet.addRow([]);

    const tglCetak = formatTanggalIndo(tanggal || new Date());
    const rightStartCol = Math.max(leftEndCol + 2, maxCols - rightColsWidth + 1);
    const rightEndCol = maxCols;

    const letterLeftStart = colToLetter(leftStartCol);
    const letterLeftEnd = colToLetter(leftEndCol);
    const letterRightStart = colToLetter(rightStartCol);
    const letterRightEnd = colToLetter(rightEndCol);

    const signers = getPenandatanganSppg(petugas, user);
    const kota = unitSppg?.kabupaten || 'Buleleng';

    // Baris 1: Tanggal Cetak Dokumen (Sisi Kanan)
    const ttdDateRowVals = Array(maxCols).fill('');
    ttdDateRowVals[rightStartCol - 1] = `${kota}, ${tglCetak}`;
    const rTtdTgl = sheet.addRow(ttdDateRowVals);
    sheet.mergeCells(`${letterRightStart}${rTtdTgl.number}:${letterRightEnd}${rTtdTgl.number}`);
    rTtdTgl.getCell(rightStartCol).font = { name: 'Arial', size: 9.5 };
    rTtdTgl.getCell(rightStartCol).alignment = { horizontal: 'center', vertical: 'middle' };

    // Baris 2: Header Frasa Pengesahan (Kiri & Kanan)
    const ttdHeadRowVals = Array(maxCols).fill('');
    ttdHeadRowVals[leftStartCol - 1] = 'Mengetahui & Menyetujui,';
    ttdHeadRowVals[rightStartCol - 1] = 'Dibuat & Diverifikasi Oleh,';
    const rTtdHead = sheet.addRow(ttdHeadRowVals);
    sheet.mergeCells(`${letterLeftStart}${rTtdHead.number}:${letterLeftEnd}${rTtdHead.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdHead.number}:${letterRightEnd}${rTtdHead.number}`);
    rTtdHead.getCell(leftStartCol).font = { name: 'Arial', size: 9, italic: true };
    rTtdHead.getCell(rightStartCol).font = { name: 'Arial', size: 9, italic: true };
    rTtdHead.getCell(leftStartCol).alignment = { horizontal: 'center', vertical: 'middle' };
    rTtdHead.getCell(rightStartCol).alignment = { horizontal: 'center', vertical: 'middle' };

    // Baris 3: Jabatan Resmi (Kiri: Kepala SPPG, Kanan: Pengawas Keuangan / Gizi)
    const ttdJabRowVals = Array(maxCols).fill('');
    ttdJabRowVals[leftStartCol - 1] = signers.kepala.jabatan;
    ttdJabRowVals[rightStartCol - 1] = signers.pengawas.jabatan;
    const rTtdJab = sheet.addRow(ttdJabRowVals);
    sheet.mergeCells(`${letterLeftStart}${rTtdJab.number}:${letterLeftEnd}${rTtdJab.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdJab.number}:${letterRightEnd}${rTtdJab.number}`);
    rTtdJab.getCell(leftStartCol).font = { name: 'Arial', size: 10, bold: true };
    rTtdJab.getCell(rightStartCol).font = { name: 'Arial', size: 10, bold: true };
    rTtdJab.getCell(leftStartCol).alignment = { horizontal: 'center', vertical: 'middle' };
    rTtdJab.getCell(rightStartCol).alignment = { horizontal: 'center', vertical: 'middle' };

    // Baris Ruang Tanda Tangan Fisik (3 baris tinggi 18pt)
    const s1 = sheet.addRow([]); s1.height = 18;
    const s2 = sheet.addRow([]); s2.height = 18;
    const s3 = sheet.addRow([]); s3.height = 18;

    // Baris 4: Nama Terang Penandatangan
    const ttdNamaRowVals = Array(maxCols).fill('');
    ttdNamaRowVals[leftStartCol - 1] = signers.kepala.ttdText;
    ttdNamaRowVals[rightStartCol - 1] = signers.pengawas.ttdText;
    const rTtdNama = sheet.addRow(ttdNamaRowVals);
    sheet.mergeCells(`${letterLeftStart}${rTtdNama.number}:${letterLeftEnd}${rTtdNama.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdNama.number}:${letterRightEnd}${rTtdNama.number}`);
    rTtdNama.getCell(leftStartCol).font = { name: 'Arial', size: 9.5, bold: true, underline: !!signers.kepala.nama };
    rTtdNama.getCell(rightStartCol).font = { name: 'Arial', size: 9.5, bold: true, underline: !!signers.pengawas.nama };
    rTtdNama.getCell(leftStartCol).alignment = { horizontal: 'center', vertical: 'middle' };
    rTtdNama.getCell(rightStartCol).alignment = { horizontal: 'center', vertical: 'middle' };

    // Baris 5: Sub-keterangan Tugas & Fungsi
    const ttdKetRowVals = Array(maxCols).fill('');
    ttdKetRowVals[leftStartCol - 1] = signers.kepala.sub;
    ttdKetRowVals[rightStartCol - 1] = signers.pengawas.sub;
    const rTtdKet = sheet.addRow(ttdKetRowVals);
    sheet.mergeCells(`${letterLeftStart}${rTtdKet.number}:${letterLeftEnd}${rTtdKet.number}`);
    sheet.mergeCells(`${letterRightStart}${rTtdKet.number}:${letterRightEnd}${rTtdKet.number}`);
    rTtdKet.getCell(leftStartCol).font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
    rTtdKet.getCell(rightStartCol).font = { name: 'Arial', size: 8.5, color: { argb: 'FF64748B' } };
    rTtdKet.getCell(leftStartCol).alignment = { horizontal: 'center', vertical: 'middle' };
    rTtdKet.getCell(rightStartCol).alignment = { horizontal: 'center', vertical: 'middle' };
}

/**
 * Konversi tanggal YYYY-MM-DD ke nama sheet DD-MM-YYYY
 */
export function formatDateToSheetName(dateStr) {
    if (!dateStr) return '01-01-2026';
    try {
        const parts = String(dateStr).split('-');
        if (parts.length === 3) {
            const y = parts[0];
            const m = parts[1].padStart(2, '0');
            const d = parts[2].padStart(2, '0');
            return `${d}-${m}-${y}`;
        }
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    } catch {
        return String(dateStr).substring(0, 10);
    }
}

/**
 * Builder Sheet Harian Tunggal (1 Sheet per Tanggal bernama DD-MM-YYYY)
 * Berisi:
 * 1. Kop Dokumen Resmi
 * 2. Tabel tanda tangan personil per orang
 * 3. Total nominal pembayaran harian (Gaji BGN, Bonus Mitra, Total Diterima)
 * 4. Keterangan rekapitulasi kehadiran (berapa hadir, izin, sakit, alpa, libur)
 * 5. Tanda tangan pengesahan resmi (Kepala SPPG & Pengawas Keuangan/Gizi)
 */
export function buildSingleDayTandaTerimaSheet(workbook, {
    dateStr,
    dayName = '',
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    user = null,
}) {
    const sheetName = formatDateToSheetName(dateStr);
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

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FF065F46' } },
        bottom: { style: 'double', color: { argb: 'FF065F46' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxCols = 9;
    sheet.columns = [
        { width: 6 },   // 1: No
        { width: 18 },  // 2: NIK
        { width: 28 },  // 3: Nama Petugas
        { width: 22 },  // 4: Jabatan / Divisi
        { width: 18 },  // 5: Status Presensi
        { width: 16 },  // 6: Gaji Pokok BGN
        { width: 16 },  // 7: Iuran BPJS TK
        { width: 18 },  // 8: Total Diterima
        { width: 26 },  // 9: Tanda Tangan
    ];

    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['DAFTAR TANDA TERIMA PEMBAYARAN HONORARIUM HARIAN']);
    sheet.mergeCells(`A${rJudul.number}:I${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');

    const dayNameMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    let computedDayName = dayName;
    if (!computedDayName && dateStr) {
        try {
            const curD = new Date(dateStr + 'T00:00:00');
            computedDayName = dayNameMap[curD.getDay()] || '';
        } catch {}
    }

    const rSub = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | Hari & Tanggal: ${computedDayName ? computedDayName + ', ' : ''}${formatTanggalIndo(dateStr)} | ${periodeNama}`
    ]);
    sheet.mergeCells(`A${rSub.number}:I${rSub.number}`);
    rSub.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub.height = 18;

    sheet.addRow([]);
    sheet.getRow(8).height = 8;

    const headValues = [
        'No',
        'NIK',
        'Nama Petugas',
        'Jabatan / Divisi',
        'Status Presensi',
        'Gaji Pokok BGN',
        'Iuran BPJS TK',
        'Total Diterima',
        'Tanda Tangan',
    ];
    const rHead = sheet.addRow(headValues);
    rHead.height = 26;
    rHead.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF065F46' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = headerBorder;
    });

    let sumGajiBgn = 0;
    let sumBpjsTk = 0;
    let sumTotalDiterima = 0;

    let countHadirPenuh = 0;
    let countHadirSetengah = 0;
    let countIzin = 0;
    let countSakit = 0;
    let countAlpa = 0;
    let countLibur = 0;
    let countBelumDiisi = 0;

    petugas.forEach((p, idx) => {
        const personAttendance = presensiMap[p.id] || {};
        const st = personAttendance[dateStr] || '';
        const snap = presensiDetailsMap?.[p.id]?.[dateStr];

        const nama = snap?.nama || p.nama || '-';
        const nik = snap?.nik || p.nik || '-';
        const jabatan = snap?.jabatan || p.jabatan || '-';

        const defaultGaji = Number(p.gaji_harian_bgn) || 0;
        const gajiHarian = (snap && snap.gaji_harian_bgn !== undefined && snap.gaji_harian_bgn !== null)
            ? Number(snap.gaji_harian_bgn)
            : defaultGaji;
        const bpjsTk = Number(p.iuran_bpjs_tk) || (snap?.iuran_bpjs_tk !== undefined ? Number(snap.iuran_bpjs_tk) : 16800);

        let mandays = 0;
        let statusLabel = 'Belum Diisi (-)';
        if (st === 'H') {
            mandays = 1.0;
            statusLabel = 'Hadir Penuh (H)';
            countHadirPenuh++;
        } else if (st === 'H2' || st === '½') {
            mandays = 0.5;
            statusLabel = 'Hadir ½ Hari (½)';
            countHadirSetengah++;
        } else if (st === 'L') {
            mandays = 0;
            statusLabel = 'Libur (L)';
            countLibur++;
        } else if (st === 'I') {
            mandays = 0;
            statusLabel = 'Izin (I)';
            countIzin++;
        } else if (st === 'S') {
            mandays = 0;
            statusLabel = 'Sakit (S)';
            countSakit++;
        } else if (st === 'TK') {
            mandays = 0;
            statusLabel = 'Tanpa Keterangan (TK)';
            countAlpa++;
        } else {
            countBelumDiisi++;
        }

        const hakGaji = mandays * gajiHarian;
        const totalHak = hakGaji;

        sumGajiBgn += hakGaji;
        sumBpjsTk += bpjsTk;
        sumTotalDiterima += totalHak;

        const personIdx = idx + 1;
        const isOdd = personIdx % 2 === 1;
        let ttdPattern = '-';
        if (mandays > 0) {
            ttdPattern = isOdd
                ? `${personIdx}. ................................`
                : `           ${personIdx}. ................................`;
        } else if (st === 'L') {
            ttdPattern = '- Libur Operasional -';
        } else if (st === 'I') {
            ttdPattern = '- Izin -';
        } else if (st === 'S') {
            ttdPattern = '- Sakit -';
        } else if (st === 'TK') {
            ttdPattern = '- Alpa (TK) -';
        }

        const row = sheet.addRow([
            personIdx,
            nik,
            nama,
            jabatan,
            statusLabel,
            hakGaji,
            bpjsTk,
            totalHak,
            ttdPattern,
        ]);
        row.height = 28;

        const isEvenRow = idx % 2 === 1;
        const rowBg = isEvenRow ? 'FFF8FAFC' : 'FFFFFFFF';

        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 2) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === 3) {
                cell.font = { name: 'Arial', size: 9.5, bold: true };
            }
            if (colNumber === 5) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                if (st === 'H') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                else if (st === 'H2' || st === '½') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0D9488' } };
                else if (st === 'L') cell.font = { name: 'Arial', size: 9, color: { argb: 'FF64748B' } };
                else if (st === 'I') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFB45309' } };
                else if (st === 'S') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1D4ED8' } };
                else if (st === 'TK') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFE11D48' } };
            }
            if (colNumber === 6 || colNumber === 7 || colNumber === 8) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === 8) {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
                }
            }
            if (colNumber === 9) {
                cell.font = { name: 'Arial', size: 9, bold: mandays > 0 };
                if (mandays > 0) {
                    cell.alignment = {
                        horizontal: isOdd ? 'left' : 'center',
                        vertical: 'bottom',
                        indent: isOdd ? 1 : 0
                    };
                } else {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                    cell.font = { name: 'Arial', size: 8.5, color: { argb: 'FF94A3B8' }, italic: true };
                }
            }
        });
    });

    // 1. Baris Grand Total Nominal Pembayaran
    const rTot = sheet.addRow([
        'TOTAL PEMBAYARAN',
        '',
        '',
        '',
        `Total Penerimaan (${petugas.length} Personil)`,
        sumGajiBgn,
        sumBpjsTk,
        sumTotalDiterima,
        '',
    ]);
    sheet.mergeCells(`A${rTot.number}:D${rTot.number}`);
    rTot.height = 24;
    rTot.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
        cell.border = totalBorder;
        cell.alignment = { vertical: 'middle' };

        if (colNumber === 1 || colNumber === 5) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        }
        if (colNumber === 6 || colNumber === 7 || colNumber === 8) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            if (colNumber === 8) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            }
        }
    });

    // 2. Baris Keterangan Rekapitulasi Presensi Hari Ini (Berapa Hadir, Ijin, Sakit, dst)
    sheet.addRow([]); // Baris kosong pemisah

    const totalMandays = countHadirPenuh + (countHadirSetengah * 0.5);
    const totalPersonil = petugas.length;

    const rRingkasanHead = sheet.addRow([
        '',
        `REKAPITULASI KEHADIRAN & OPERASIONAL HARIAN (${formatTanggalIndo(dateStr).toUpperCase()})`,
        '', '', '', '', '', '', ''
    ]);
    sheet.mergeCells(`B${rRingkasanHead.number}:H${rRingkasanHead.number}`);
    rRingkasanHead.height = 22;
    rRingkasanHead.getCell(2).font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
    rRingkasanHead.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
    rRingkasanHead.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
    rRingkasanHead.getCell(2).border = {
        top: { style: 'thin', color: { argb: 'FF059669' } },
        bottom: { style: 'thin', color: { argb: 'FF059669' } },
        left: { style: 'thin', color: { argb: 'FF059669' } },
        right: { style: 'thin', color: { argb: 'FF059669' } },
    };

    const rStat1 = sheet.addRow([
        '',
        `Hadir Penuh (H): ${countHadirPenuh} Orang`,
        '',
        `Hadir ½ Hari (½): ${countHadirSetengah} Orang`,
        '',
        `Total Mandays: ${totalMandays} Mandays`,
        '',
        `Total Bayar: Rp ${new Intl.NumberFormat('id-ID').format(sumTotalDiterima)}`,
        ''
    ]);
    sheet.mergeCells(`B${rStat1.number}:C${rStat1.number}`);
    sheet.mergeCells(`D${rStat1.number}:E${rStat1.number}`);
    sheet.mergeCells(`F${rStat1.number}:G${rStat1.number}`);
    sheet.mergeCells(`H${rStat1.number}:I${rStat1.number}`);
    rStat1.height = 20;

    const rStat2 = sheet.addRow([
        '',
        `Izin (I): ${countIzin} Orang`,
        '',
        `Sakit (S): ${countSakit} Orang`,
        '',
        `Tanpa Keterangan (TK): ${countAlpa} Orang`,
        '',
        `Libur (L): ${countLibur} Orang | Total: ${totalPersonil} Personil`,
        ''
    ]);
    sheet.mergeCells(`B${rStat2.number}:C${rStat2.number}`);
    sheet.mergeCells(`D${rStat2.number}:E${rStat2.number}`);
    sheet.mergeCells(`F${rStat2.number}:G${rStat2.number}`);
    sheet.mergeCells(`H${rStat2.number}:I${rStat2.number}`);
    rStat2.height = 20;

    [rStat1, rStat2].forEach(r => {
        r.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            if (colNumber >= 2 && colNumber <= 9) {
                cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1E293B' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                cell.border = borderThin;
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
        });
    });

    // 3. Baru isi Tanda Tangan Paling Bawah (Kepala SPPG & Pengawas Keuangan/Gizi)
    applyLembarPengesahan(sheet, {
        maxCols: 9,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: dateStr,
    });

    return sheet;
}

/**
 * Helper pembuat seluruh sheet tanda terima harian (1 sheet per tanggal DD-MM-YYYY)
 */
export function buildTandaTerimaHarianSheet(workbook, {
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    modeLabel = 'Bulanan 28 Hari',
    user = null,
}) {
    const finalDateList = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 1);

    finalDateList.forEach(dt => {
        buildSingleDayTandaTerimaSheet(workbook, {
            dateStr: dt.dateStr,
            dayName: dt.dayName,
            petugas,
            presensiMap,
            presensiDetailsMap,
            unitSppg,
            periode,
            logoBgnId,
            logoYayasanId,
            user,
        });
    });
}

/**
 * Export File Excel Baru Khusus Buku Tanda Terima Honor Harian Petugas SPPG
 * Format per tanggal dipisah per sheet dengan nama sheet DD-MM-YYYY
 * Setiap lembar sheet berisi tabel tanda tangan personil, total nominal, rincian kehadiran (hadir, izin, sakit, dll), dan lembar pengesahan TTD.
 */
export async function downloadBukuTandaTerimaHarianExcel({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    kehadiranMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    user = null,
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

    const finalDateList = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 1);

    // Buat 1 sheet per tanggal dengan format nama DD-MM-YYYY
    finalDateList.forEach(dt => {
        buildSingleDayTandaTerimaSheet(workbook, {
            dateStr: dt.dateStr,
            dayName: dt.dayName,
            petugas,
            presensiMap: finalPresensiMap,
            presensiDetailsMap,
            unitSppg,
            periode,
            logoBgnId,
            logoYayasanId,
            user,
        });
    });

    const isSingleDay = finalDateList.length === 1;
    const startStr = finalDateList[0]?.dateStr || 'tanggal';
    const endStr = finalDateList[finalDateList.length - 1]?.dateStr || startStr;
    const sheetNameStart = formatDateToSheetName(startStr);
    const sheetNameEnd = formatDateToSheetName(endStr);

    const filename = isSingleDay
        ? `Tanda_Terima_Honor_Harian_${sheetNameStart}.xlsx`
        : `Buku_Tanda_Terima_Honor_Harian_${sheetNameStart}_sd_${sheetNameEnd}.xlsx`;

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
 * Pembuat 1 sheet tanda terima bonus harian (Nama sheet format: DD-MM-YYYY)
 */
export function buildSingleDayTandaTerimaBonusSheet(workbook, {
    dateStr,
    dayName = '',
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    user = null,
}) {
    const sheetName = formatDateToSheetName(dateStr);
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
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'thin', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFB45309' } },
        right: { style: 'thin', color: { argb: 'FFB45309' } },
    };

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'double', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxCols = 9;
    sheet.columns = [
        { width: 6 },   // 1: No
        { width: 18 },  // 2: NIK
        { width: 28 },  // 3: Nama Petugas
        { width: 22 },  // 4: Jabatan / Divisi
        { width: 18 },  // 5: Status Presensi
        { width: 16 },  // 6: Tarif Bonus Harian
        { width: 18 },  // 7: Hak Bonus Diterima
        { width: 18 },  // 8: No Rekening BNI
        { width: 26 },  // 9: Tanda Tangan
    ];

    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['DAFTAR TANDA TERIMA PEMBAYARAN BONUS HARIAN PETUGAS SPPG']);
    sheet.mergeCells(`A${rJudul.number}:I${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');

    const dayNameMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    let computedDayName = dayName;
    if (!computedDayName && dateStr) {
        try {
            const curD = new Date(dateStr + 'T00:00:00');
            computedDayName = dayNameMap[curD.getDay()] || '';
        } catch {}
    }

    const rSub = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | Hari & Tanggal: ${computedDayName ? computedDayName + ', ' : ''}${formatTanggalIndo(dateStr)} | ${periodeNama}`
    ]);
    sheet.mergeCells(`A${rSub.number}:I${rSub.number}`);
    rSub.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub.height = 18;

    sheet.addRow([]);
    sheet.getRow(8).height = 8;

    const headValues = [
        'No',
        'NIK',
        'Nama Petugas',
        'Jabatan / Divisi',
        'Status Presensi',
        'Tarif Bonus\nHarian',
        'Hak Bonus\nDiterima',
        'No Rekening BNI',
        'Tanda Tangan',
    ];
    const rHead = sheet.addRow(headValues);
    rHead.height = 26;
    rHead.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF92400E' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = headerBorder;
    });

    let sumTarifBonus = 0;
    let sumHakBonus = 0;

    let countHadirPenuh = 0;
    let countHadirSetengah = 0;
    let countIzin = 0;
    let countSakit = 0;
    let countAlpa = 0;
    let countLibur = 0;
    let countBelumDiisi = 0;

    petugas.forEach((p, idx) => {
        const personAttendance = presensiMap[p.id] || {};
        const st = personAttendance[dateStr] || '';
        const snap = presensiDetailsMap?.[p.id]?.[dateStr];

        const nama = snap?.nama || p.nama || '-';
        const nik = snap?.nik || p.nik || '-';
        const jabatan = snap?.jabatan || p.jabatan || '-';

        const defaultBonus = Number(p.bonus_harian_mitra) || 0;
        const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
            ? Number(snap.bonus_harian_mitra)
            : defaultBonus;
        const rekening = p.nomor_rekening_bni || p.no_rekening || p.rekening_bni || '-';

        let mandays = 0;
        let statusLabel = 'Belum Diisi (-)';
        if (st === 'H') {
            mandays = 1.0;
            statusLabel = 'Hadir Penuh (H)';
            countHadirPenuh++;
        } else if (st === 'H2' || st === '½') {
            mandays = 0.5;
            statusLabel = 'Hadir ½ Hari (½)';
            countHadirSetengah++;
        } else if (st === 'L') {
            mandays = 0;
            statusLabel = 'Libur (L)';
            countLibur++;
        } else if (st === 'I') {
            mandays = 0;
            statusLabel = 'Izin (I)';
            countIzin++;
        } else if (st === 'S') {
            mandays = 0;
            statusLabel = 'Sakit (S)';
            countSakit++;
        } else if (st === 'TK') {
            mandays = 0;
            statusLabel = 'Tanpa Keterangan (TK)';
            countAlpa++;
        } else {
            countBelumDiisi++;
        }

        const hakBonus = mandays * dailyBonus;

        sumTarifBonus += dailyBonus;
        sumHakBonus += hakBonus;

        const personIdx = idx + 1;
        const isOdd = personIdx % 2 === 1;
        let ttdPattern = '-';
        if (mandays > 0) {
            ttdPattern = isOdd
                ? `${personIdx}. ................................`
                : `           ${personIdx}. ................................`;
        } else if (st === 'L') {
            ttdPattern = '- Libur Operasional -';
        } else if (st === 'I') {
            ttdPattern = '- Izin -';
        } else if (st === 'S') {
            ttdPattern = '- Sakit -';
        } else if (st === 'TK') {
            ttdPattern = '- Alpa (TK) -';
        }

        const row = sheet.addRow([
            personIdx,
            nik,
            nama,
            jabatan,
            statusLabel,
            dailyBonus,
            hakBonus,
            rekening,
            ttdPattern,
        ]);
        row.height = 28;

        const isEvenRow = idx % 2 === 1;
        const rowBg = isEvenRow ? 'FFFDFBF7' : 'FFFFFFFF';

        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 2 || colNumber === 8) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === 3) {
                cell.font = { name: 'Arial', size: 9.5, bold: true };
            }
            if (colNumber === 5) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                if (st === 'H') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                else if (st === 'H2' || st === '½') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0D9488' } };
                else if (st === 'L') cell.font = { name: 'Arial', size: 9, color: { argb: 'FF64748B' } };
                else if (st === 'I') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFB45309' } };
                else if (st === 'S') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1D4ED8' } };
                else if (st === 'TK') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFE11D48' } };
            }
            if (colNumber === 6 || colNumber === 7) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === 7) {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF92400E' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
                }
            }
            if (colNumber === 9) {
                cell.font = { name: 'Arial', size: 9, bold: mandays > 0 };
                if (mandays > 0) {
                    cell.alignment = {
                        horizontal: isOdd ? 'left' : 'center',
                        vertical: 'bottom',
                        indent: isOdd ? 1 : 0
                    };
                } else {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                    cell.font = { name: 'Arial', size: 8.5, color: { argb: 'FF94A3B8' }, italic: true };
                }
            }
        });
    });

    // 1. Baris Grand Total Nominal Pembayaran Bonus
    const rTot = sheet.addRow([
        'TOTAL PEMBAYARAN BONUS',
        '',
        '',
        '',
        `Total Penerimaan (${petugas.length} Personil)`,
        sumTarifBonus,
        sumHakBonus,
        '',
        '',
    ]);
    sheet.mergeCells(`A${rTot.number}:D${rTot.number}`);
    rTot.height = 24;
    rTot.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
        cell.border = totalBorder;
        cell.alignment = { vertical: 'middle' };

        if (colNumber === 1 || colNumber === 5) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        }
        if (colNumber === 6 || colNumber === 7) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            if (colNumber === 7) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF92400E' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDE68A' } };
            }
        }
    });

    // 2. Baris Keterangan Rekapitulasi Presensi Hari Ini
    sheet.addRow([]);

    const totalMandays = countHadirPenuh + (countHadirSetengah * 0.5);
    const totalPersonil = petugas.length;

    const rRingkasanHead = sheet.addRow([
        '',
        `REKAPITULASI PRESENSI & BONUS HARIAN (${formatTanggalIndo(dateStr).toUpperCase()})`,
        '', '', '', '', '', '', ''
    ]);
    sheet.mergeCells(`B${rRingkasanHead.number}:H${rRingkasanHead.number}`);
    rRingkasanHead.height = 22;
    rRingkasanHead.getCell(2).font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF92400E' } };
    rRingkasanHead.getCell(2).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDE68A' } };
    rRingkasanHead.getCell(2).alignment = { horizontal: 'center', vertical: 'middle' };
    rRingkasanHead.getCell(2).border = {
        top: { style: 'thin', color: { argb: 'FFD97706' } },
        bottom: { style: 'thin', color: { argb: 'FFD97706' } },
        left: { style: 'thin', color: { argb: 'FFD97706' } },
        right: { style: 'thin', color: { argb: 'FFD97706' } },
    };

    const rStat1 = sheet.addRow([
        '',
        `Hadir Penuh (H): ${countHadirPenuh} Orang`,
        '',
        `Hadir ½ Hari (½): ${countHadirSetengah} Orang`,
        '',
        `Total Mandays: ${totalMandays} Mandays`,
        '',
        `Total Pembayaran Bonus: Rp ${new Intl.NumberFormat('id-ID').format(sumHakBonus)}`,
        ''
    ]);
    sheet.mergeCells(`B${rStat1.number}:C${rStat1.number}`);
    sheet.mergeCells(`D${rStat1.number}:E${rStat1.number}`);
    sheet.mergeCells(`F${rStat1.number}:G${rStat1.number}`);
    sheet.mergeCells(`H${rStat1.number}:I${rStat1.number}`);
    rStat1.height = 20;

    const rStat2 = sheet.addRow([
        '',
        `Izin (I): ${countIzin} Orang`,
        '',
        `Sakit (S): ${countSakit} Orang`,
        '',
        `Tanpa Keterangan (TK): ${countAlpa} Orang`,
        '',
        `Libur (L): ${countLibur} Orang | Total: ${totalPersonil} Personil`,
        ''
    ]);
    sheet.mergeCells(`B${rStat2.number}:C${rStat2.number}`);
    sheet.mergeCells(`D${rStat2.number}:E${rStat2.number}`);
    sheet.mergeCells(`F${rStat2.number}:G${rStat2.number}`);
    sheet.mergeCells(`H${rStat2.number}:I${rStat2.number}`);
    rStat2.height = 20;

    [rStat1, rStat2].forEach(r => {
        r.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            if (colNumber >= 2 && colNumber <= 9) {
                cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1E293B' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDFBF7' } };
                cell.border = borderThin;
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
        });
    });

    // 3. Tanda Tangan Paling Bawah (Kepala SPPG & Pengawas Keuangan/Gizi)
    applyLembarPengesahan(sheet, {
        maxCols: 9,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: dateStr,
    });

    return sheet;
}

/**
 * Export File Excel Baru Khusus Buku Tanda Terima Bonus Harian Petugas SPPG
 * Format per tanggal dipisah per sheet dengan nama sheet DD-MM-YYYY
 */
export async function downloadBukuTandaTerimaBonusHarianExcel({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    kehadiranMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    user = null,
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

    const finalDateList = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 1);

    // Buat 1 sheet per tanggal dengan format nama DD-MM-YYYY
    finalDateList.forEach(dt => {
        buildSingleDayTandaTerimaBonusSheet(workbook, {
            dateStr: dt.dateStr,
            dayName: dt.dayName,
            petugas,
            presensiMap: finalPresensiMap,
            presensiDetailsMap,
            unitSppg,
            periode,
            logoBgnId,
            logoYayasanId,
            user,
        });
    });

    const isSingleDay = finalDateList.length === 1;
    const startStr = finalDateList[0]?.dateStr || 'tanggal';
    const endStr = finalDateList[finalDateList.length - 1]?.dateStr || startStr;
    const sheetNameStart = formatDateToSheetName(startStr);
    const sheetNameEnd = formatDateToSheetName(endStr);

    const filename = isSingleDay
        ? `Tanda_Terima_Bonus_Harian_${sheetNameStart}.xlsx`
        : `Buku_Tanda_Terima_Bonus_Harian_${sheetNameStart}_sd_${sheetNameEnd}.xlsx`;

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
    presensiDetailsMap = {},
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    user = null,
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
    const colBGNIdx = colDayEnd + 8;
    const colTotBGNIdx = colDayEnd + 9;

    const maxCols = colTotBGNIdx;

    const letterDayStart = colToLetter(colDayStart);
    const letterDayEnd = colToLetter(colDayEnd);
    const letterS = colToLetter(colSIdx);
    const letterI = colToLetter(colIIdx);
    const letterTK = colToLetter(colTKIdx);
    const letterL = colToLetter(colLIdx);
    const letterH2 = colToLetter(colH2Idx);
    const letterHadir = colToLetter(colHadirIdx);
    const letterBPJS = colToLetter(colBPJSIdx);
    const letterBGN = colToLetter(colBGNIdx);
    const letterTotBGN = colToLetter(colTotBGNIdx);

    const isHariIni = actualDays === 1 || (startDate && endDate && startDate === endDate);

    // Konfigurasi Lebar Kolom
    const colDefs = [
        { key: 'no', width: 5 },          // A: No
        { key: 'nama', width: 27 },       // B: Nama Petugas
        { key: 'jabatan', width: 23 },    // C: Jabatan / Divisi
    ];

    const dayColWidth = actualDays === 1 ? 16 : (actualDays > 20 ? 4.2 : 5.0);
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
    colDefs.push({ key: 'bgn', width: 16 });
    colDefs.push({ key: 'tot_bgn', width: 22 });

    sheet.columns = colDefs;

    // 1. Terapkan Kop Surat Resmi di Baris 1-4
    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    // 2. Judul Dokumen
    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['REKAPITULASI PRESENSI & ESTIMASI HONORARIUM PETUGAS SPPG']);
    sheet.mergeCells(`A${rJudul.number}:${letterTotBGN}${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rangeText = isHariIni
        ? `Tanggal: ${formatTanggalIndo(startDate || finalDateList[0]?.dateStr)} (Rekap Harian)`
        : ((startDate && endDate)
            ? `Rentang Tanggal: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)} (${actualDays} Hari Kerja)`
            : `Standar Siklus: ${modeLabel} Kerja`);

    const rSubJudul = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet.mergeCells(`A${rSubJudul.number}:${letterTotBGN}${rSubJudul.number}`);
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
        actualDays === 1 ? `Tanggal` : `Tanggal (${modeLabel})`,
    ];
    for (let d = 2; d <= actualDays; d++) head1Values.push('');

    head1Values.push('Presensi & Kehadiran', '', '', '', '', '');
    head1Values.push('Iuran BPJS TK');
    head1Values.push('Gaji Harian\nBGN');
    head1Values.push('Total Gaji BGN');

    const rHead1 = sheet.addRow(head1Values);
    rHead1.height = 26;

    // Baris 10: Sub-Header
    const head2Values = ['', '', ''];
    finalDateList.forEach((dt, idx) => {
        let label = dt.dayNum ? String(dt.dayNum) : String(idx + 1);
        if (actualDays === 1 && dt.dateStr) {
            try {
                const curD = new Date(dt.dateStr + 'T00:00:00');
                label = `${curD.getDate()} ${MONTHS_INDO[curD.getMonth()] || ''}`;
            } catch {
                label = String(dt.dayNum || '1');
            }
        }
        head2Values.push(label);
    });
    head2Values.push('S', 'I', 'TK', 'L', '½', 'Hadir');
    head2Values.push('', '', '');

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
    sheet.mergeCells(`${letterBGN}${h1}:${letterBGN}${h2}`); // Gaji Harian BGN
    sheet.mergeCells(`${letterTotBGN}${h1}:${letterTotBGN}${h2}`); // Total Gaji BGN

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
        let effTotalBgn = 0;
        const defaultGaji = Number(p.gaji_harian_bgn) || 0;

        const dayMarks = finalDateList.map(dt => {
            const st = personAttendance[dt.dateStr] || personAttendance[dt.label] || '';
            if (!st || st === '-') return '-';

            const snap = presensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyGaji = (snap && snap.gaji_harian_bgn !== undefined && snap.gaji_harian_bgn !== null) ? Number(snap.gaji_harian_bgn) : defaultGaji;

            if (st === 'H') {
                effHadirPenuh++;
                effTotalBgn += dailyGaji * 1.0;
            } else if (st === 'H2' || st === '½') {
                effHadirSetengah++;
                effTotalBgn += dailyGaji * 0.5;
            } else if (st === 'L') effLibur++;
            else if (st === 'I') effIzin++;
            else if (st === 'S') effSakit++;
            else if (st === 'TK') effAlpa++;

            // Tampilkan simbol yang rapi di sel
            return st === 'H2' ? '½' : st;
        });

        const effMandays = effHadirPenuh + (effHadirSetengah * 0.5);
        const gajiHarianBgn = defaultGaji;
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
            // Gaji BGN
            gajiHarianBgn,
            // Total BGN -> Hadir Mandays * Gaji BGN
            { formula: `${letterHadir}${rowNum}*${letterBGN}${rowNum}`, result: effTotalBgn > 0 ? effTotalBgn : (effMandays * gajiHarianBgn) },
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
            } else if (colNumber >= colBPJSIdx && colNumber <= colTotBGNIdx) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === colTotBGNIdx) {
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
    totalRowValues.push({ formula: `SUM(${letterTotBGN}${dataStartRow}:${letterTotBGN}${lastDataRow})` });

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
        } else if (colNumber >= colBPJSIdx && colNumber <= colTotBGNIdx) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            if (colNumber === colBPJSIdx || colNumber === colTotBGNIdx) {
                cell.numFmt = '"Rp "#,##0';
            } else {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === colTotBGNIdx) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            }
        }
    });

    // 6. Lembar Tanda Tangan Resmi Pengesahan
    applyLembarPengesahan(sheet, {
        maxCols,
        leftStartCol: 2,
        leftEndCol: Math.min(5, colDayEnd),
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: endDate || startDate || new Date(),
    });

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
    user = null,
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

    const maxColsS2 = 9;
    sheet2.columns = [
        { width: 6 },   // A: No
        { width: 28 },  // B: Divisi / Jabatan
        { width: 14 },  // C: Jumlah Personil
        { width: 14 },  // D: Total Hadir (Mandays)
        { width: 12 },  // E: Total Izin
        { width: 12 },  // F: Total Sakit
        { width: 12 },  // G: Total TK (Tanpa Keterangan)
        { width: 20 },  // H: Total Iuran BPJS TK
        { width: 24 },  // I: Total Gaji BGN
    ];

    applyKopSurat(sheet2, maxColsS2, logoBgnId, logoYayasanId, unitSppg);

    sheet2.addRow([]);
    sheet2.getRow(5).height = 10;

    const rJ2 = sheet2.addRow(['RINGKASAN REKAPITULASI PRESENSI & KOMPENSASI PER DIVISI']);
    sheet2.mergeCells(`A${rJ2.number}:I${rJ2.number}`);
    rJ2.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJ2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJ2.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const isSingleDay = (startDate && endDate && startDate === endDate) || (dateList && dateList.length === 1);
    const rangeText = isSingleDay
        ? `Tanggal: ${formatTanggalIndo(startDate || (dateList && dateList[0]?.dateStr))} (Rekap Harian)`
        : ((startDate && endDate)
            ? `Rentang: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)}`
            : `Standar: ${modeLabel} Kerja`);

    const rSub2 = sheet2.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet2.mergeCells(`A${rSub2.number}:I${rSub2.number}`);
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
        'Total Iuran BPJS TK',
        'Total Gaji BGN'
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
                bpjsTk: 0,
                gajiBgn: 0,
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
        const bpjs = Number(p.iuran_bpjs_tk) || 16800;

        divisiMap[jab].mandays += mDays;
        divisiMap[jab].izin += iz;
        divisiMap[jab].sakit += s;
        divisiMap[jab].alpa += a;
        divisiMap[jab].bpjsTk += bpjs;
        divisiMap[jab].gajiBgn += (mDays * gBgn);
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
            d.bpjsTk,
            d.gajiBgn
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
            if (colNumber >= 8 && colNumber <= 9) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                if (colNumber === 9) {
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
        if (colNumber >= 8 && colNumber <= 9) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            if (colNumber === 9) {
                cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            }
        }
    });

    applyLembarPengesahan(sheet2, {
        maxCols: maxColsS2,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: endDate || startDate || new Date(),
    });

    return sheet2;
}

/**
 * Builder Sheet Rincian Detail Harian Per Personil (Log Detail Presensi Harian Lengkap)
 */
function buildDetailHarianSheet(workbook, {
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    modeLabel = 'Bulanan 28 Hari',
    user = null,
}) {
    const sheet3 = workbook.addWorksheet('Rincian Detail Harian', {
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

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FF065F46' } },
        bottom: { style: 'double', color: { argb: 'FF065F46' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxColsS3 = 12;
    sheet3.columns = [
        { width: 6 },   // A: No
        { width: 14 },  // B: Tanggal
        { width: 10 },  // C: Hari
        { width: 18 },  // D: NIK
        { width: 28 },  // E: Nama Petugas
        { width: 22 },  // F: Jabatan / Divisi
        { width: 22 },  // G: Status Kehadiran
        { width: 11 },  // H: Mandays
        { width: 16 },  // I: Gaji Harian BGN
        { width: 16 },  // J: Iuran BPJS TK
        { width: 20 },  // K: Hak Gaji Diterima
        { width: 13 },  // L: Status Petugas
    ];

    applyKopSurat(sheet3, maxColsS3, logoBgnId, logoYayasanId, unitSppg);

    sheet3.addRow([]);
    sheet3.getRow(5).height = 10;

    const rJ3 = sheet3.addRow(['RINCIAN DETAIL LOG PRESENSI & HAK HONORARIUM PETUGAS SPPG']);
    sheet3.mergeCells(`A${rJ3.number}:L${rJ3.number}`);
    rJ3.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJ3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJ3.height = 22;

    const finalDateList = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 28);
    const actualDays = finalDateList.length;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const isSingleDay = actualDays === 1 || (startDate && endDate && startDate === endDate);
    const rangeText = isSingleDay
        ? `Tanggal: ${formatTanggalIndo(startDate || finalDateList[0]?.dateStr)} (Rekap Harian)`
        : ((startDate && endDate)
            ? `Rentang: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)} (${actualDays} Hari Kerja)`
            : `Standar: ${modeLabel} Kerja`);

    const rSub3 = sheet3.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet3.mergeCells(`A${rSub3.number}:L${rSub3.number}`);
    rSub3.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub3.height = 18;

    sheet3.addRow([]);
    sheet3.getRow(8).height = 8;

    // Header Tabel Baris 9
    const headValuesS3 = [
        'No',
        'Tanggal',
        'Hari',
        'NIK',
        'Nama Petugas',
        'Jabatan / Divisi',
        'Status Presensi',
        'Mandays',
        'Gaji Harian BGN',
        'Iuran BPJS TK',
        'Hak Gaji Diterima',
        'Status',
    ];
    const rHeadS3 = sheet3.addRow(headValuesS3);
    rHeadS3.height = 25;
    rHeadS3.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF065F46' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = headerBorder;
    });

    const dayNameMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const startDataRow = 10;
    let counter = 1;

    // Iterasi per tanggal lalu per petugas (kronologis log harian lengkap)
    finalDateList.forEach(dt => {
        const dateStr = dt.dateStr;
        let dayName = dt.dayName || '';
        if (!dayName && dateStr) {
            try {
                const curD = new Date(dateStr + 'T00:00:00');
                dayName = dayNameMap[curD.getDay()] || '';
            } catch {
                dayName = '';
            }
        } else if (dayName.length <= 3 && dateStr) {
            try {
                const curD = new Date(dateStr + 'T00:00:00');
                dayName = dayNameMap[curD.getDay()] || dayName;
            } catch {}
        }

        petugas.forEach(p => {
            const personAttendance = presensiMap[p.id] || {};
            const st = personAttendance[dateStr] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dateStr];

            const nama = snap?.nama || p.nama || '-';
            const nik = snap?.nik || p.nik || '-';
            const jabatan = snap?.jabatan || p.jabatan || '-';
            const statusPetugas = snap?.status_petugas || p.status || 'Aktif';

            const defaultGaji = Number(p.gaji_harian_bgn) || 0;
            const gajiHarian = (snap && snap.gaji_harian_bgn !== undefined && snap.gaji_harian_bgn !== null)
                ? Number(snap.gaji_harian_bgn)
                : defaultGaji;
            const bpjsTk = Number(p.iuran_bpjs_tk) || (snap?.iuran_bpjs_tk !== undefined ? Number(snap.iuran_bpjs_tk) : 16800);

            let mandays = 0;
            let statusLabel = 'Belum Diisi (-)';
            if (st === 'H') {
                mandays = 1.0;
                statusLabel = 'Hadir Penuh (H)';
            } else if (st === 'H2' || st === '½') {
                mandays = 0.5;
                statusLabel = 'Hadir ½ Hari (½)';
            } else if (st === 'L') {
                mandays = 0;
                statusLabel = 'Libur (L)';
            } else if (st === 'I') {
                mandays = 0;
                statusLabel = 'Izin (I)';
            } else if (st === 'S') {
                mandays = 0;
                statusLabel = 'Sakit (S)';
            } else if (st === 'TK') {
                mandays = 0;
                statusLabel = 'Tanpa Keterangan (TK)';
            }

            const hakGaji = mandays * gajiHarian;
            const totalHak = hakGaji;

            const rData = sheet3.addRow([
                counter,
                dateStr,
                dayName,
                nik,
                nama,
                jabatan,
                statusLabel,
                mandays,
                gajiHarian,
                bpjsTk,
                totalHak,
                statusPetugas,
            ]);
            rData.height = 19;

            const isEven = counter % 2 === 0;
            const rowBg = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

            rData.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                cell.font = { name: 'Arial', size: 9 };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
                cell.border = borderThin;
                cell.alignment = { vertical: 'middle' };

                if ([1, 2, 3, 4, 7, 8, 12].includes(colNumber)) {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                }
                if (colNumber === 5) {
                    cell.font = { name: 'Arial', size: 9, bold: true };
                }
                if (colNumber >= 9 && colNumber <= 11) {
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '"Rp "#,##0';
                    if (colNumber === 11) {
                        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
                    }
                }
                if (colNumber === 7) {
                    if (st === 'H') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                    else if (st === 'H2' || st === '½') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0D9488' } };
                    else if (st === 'L') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF64748B' } };
                    else if (st === 'I') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFB45309' } };
                    else if (st === 'S') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1D4ED8' } };
                    else if (st === 'TK') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFE11D48' } };
                }
            });

            counter++;
        });
    });

    const lastDataRowS3 = startDataRow + counter - 2;

    // Total Row Sheet 3
    if (lastDataRowS3 >= startDataRow) {
        const rTotS3 = sheet3.addRow([
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            `${counter - 1} Catatan Log`,
            { formula: `SUM(H${startDataRow}:H${lastDataRowS3})` },
            '-',
            { formula: `SUM(J${startDataRow}:J${lastDataRowS3})` },
            { formula: `SUM(K${startDataRow}:K${lastDataRowS3})` },
            '',
        ]);
        sheet3.mergeCells(`A${rTotS3.number}:F${rTotS3.number}`);
        rTotS3.height = 24;

        rTotS3.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9.5, bold: true };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
            cell.border = totalBorder;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (colNumber === 7 || colNumber === 8) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if ([9, 10, 11].includes(colNumber)) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                if ([10, 11].includes(colNumber)) {
                    cell.numFmt = '"Rp "#,##0';
                } else {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                }
                if (colNumber === 11) {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF065F46' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
                }
            }
        });
    }

    // Auto-filter di baris 9 agar pengguna Excel bisa filter per nama, tanggal, jabatan, atau presensi
    sheet3.autoFilter = `A9:L9`;

    applyLembarPengesahan(sheet3, {
        maxCols: maxColsS3,
        leftStartCol: 2,
        leftEndCol: 5,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: endDate || startDate || new Date(),
    });

    return sheet3;
}

/**
 * Download File Excel Rekap Presensi & Gaji Petugas SPPG
 */
export async function downloadRekapAbsenGajiExcel({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    kehadiranMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    modeHariKerja = 28,
    user = null,
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
    const isSingleDay = Number(modeHariKerja) === 1 || (startDate && endDate && startDate === endDate) || (dateList && dateList.length === 1);
    const modeLabel = isSingleDay
        ? `Hari Ini (${formatTanggalIndo(startDate || (dateList && dateList[0]?.dateStr))})`
        : (isBulanan ? 'Bulanan 28 Hari' : (Number(modeHariKerja) === 14 ? 'Periodik 14 Hari' : `${modeHariKerja} Hari`));

    // =========================================================
    // SHEET 1: Rekap Presensi & Gaji (Primary Matriks)
    // =========================================================
    buildRekapSheet(workbook, {
        sheetName: 'Rekap Presensi & Gaji',
        daysCount: isSingleDay ? 1 : (Number(modeHariKerja) || 28),
        dateList,
        startDate,
        endDate,
        modeLabel,
        petugas,
        presensiMap: finalPresensiMap,
        presensiDetailsMap,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
        user,
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
        user,
    });

    // =========================================================
    // SHEET 3: Rincian Detail Harian (Granular Log Per Hari)
    // =========================================================
    buildDetailHarianSheet(workbook, {
        petugas,
        presensiMap: finalPresensiMap,
        presensiDetailsMap,
        dateList,
        startDate,
        endDate,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
        modeLabel,
        user,
    });

    // Export & Download
    const actualStartStr = startDate || (dateList && dateList[0]?.dateStr) || 'tanggal';
    const actualEndStr = endDate || (dateList && dateList[dateList.length - 1]?.dateStr) || actualStartStr;
    const cycleSuffix = isSingleDay
        ? `Hari_Ini_${actualStartStr}`
        : (isBulanan ? 'Bulanan_28_Hari' : (Number(modeHariKerja) === 14 ? 'Periodik_14_Hari' : `Rentang_${actualStartStr}_sd_${actualEndStr}`));
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

/**
 * Sheet 2: Ringkasan per Divisi untuk Laporan Bonus
 */
export function buildRingkasanDivisiBonusSheet(workbook, {
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = null,
    startDate = null,
    daysCount = 28,
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    modeLabel = 'Bulanan 28 Hari',
    user = null,
}) {
    const finalDates = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, daysCount);

    const sheet2 = workbook.addWorksheet('Ringkasan per Divisi', {
        pageSetup: { orientation: 'portrait', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const headerBorder = {
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'thin', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFB45309' } },
        right: { style: 'thin', color: { argb: 'FFB45309' } },
    };

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'double', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxColsS2 = 8;
    sheet2.columns = [
        { width: 6 },   // 1: No
        { width: 26 },  // 2: Divisi / Jabatan
        { width: 16 },  // 3: Jumlah Personil
        { width: 16 },  // 4: Total Mandays
        { width: 10 },  // 5: Izin
        { width: 10 },  // 6: Sakit
        { width: 10 },  // 7: TK
        { width: 22 },  // 8: Total Hak Bonus
    ];

    applyKopSurat(sheet2, maxColsS2, logoBgnId, logoYayasanId, unitSppg);

    sheet2.addRow([]);
    sheet2.getRow(5).height = 10;

    const rJudulS2 = sheet2.addRow(['RINGKASAN REKAPITULASI BONUS PER DIVISI / JABATAN']);
    sheet2.mergeCells(`A${rJudulS2.number}:H${rJudulS2.number}`);
    rJudulS2.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudulS2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudulS2.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rSubS2 = sheet2.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | Standar: ${modeLabel}`
    ]);
    sheet2.mergeCells(`A${rSubS2.number}:H${rSubS2.number}`);
    rSubS2.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSubS2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSubS2.height = 18;

    sheet2.addRow([]);
    sheet2.getRow(8).height = 8;

    const rHeadS2 = sheet2.addRow([
        'No',
        'Divisi / Jabatan',
        'Jumlah Personil',
        'Total Mandays',
        'Izin (I)',
        'Sakit (S)',
        'Tanpa Ket (TK)',
        'Total Hak Bonus Mitra',
    ]);
    rHeadS2.height = 24;
    rHeadS2.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF92400E' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = headerBorder;
    });

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
                bonusMitra: 0,
            };
        }
        divisiMap[jab].count += 1;
        const pAtt = presensiMap[p.id] || {};

        let mDays = 0, iz = 0, s = 0, a = 0, bTot = 0;
        finalDates.forEach(dt => {
            const st = pAtt[dt.dateStr] || pAtt[dt.label] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
                ? Number(snap.bonus_harian_mitra)
                : (Number(p.bonus_harian_mitra) || 0);

            if (!st || st === '-') return;
            if (st === 'H') {
                mDays += 1;
                bTot += dailyBonus * 1.0;
            } else if (st === 'H2' || st === '½') {
                mDays += 0.5;
                bTot += dailyBonus * 0.5;
            } else if (st === 'I') iz++;
            else if (st === 'S') s++;
            else if (st === 'TK') a++;
        });

        divisiMap[jab].mandays += mDays;
        divisiMap[jab].izin += iz;
        divisiMap[jab].sakit += s;
        divisiMap[jab].alpa += a;
        divisiMap[jab].bonusMitra += bTot;
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
            d.bonusMitra,
        ]);
        r.height = 20;

        const isEven = idx % 2 === 1;
        const rowBg = isEven ? 'FFFDFBF7' : 'FFFFFFFF';

        r.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if ([1, 3, 4, 5, 6, 7].includes(colNumber)) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
            if (colNumber === 2) {
                cell.font = { name: 'Arial', size: 9, bold: true };
            }
            if (colNumber === 8) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.numFmt = '"Rp "#,##0';
                cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF92400E' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
            }
        });
    });

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
    ]);
    rTotS2.height = 24;
    rTotS2.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
        cell.border = totalBorder;
        cell.alignment = { vertical: 'middle' };

        if ([1, 3, 4, 5, 6, 7].includes(colNumber)) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        }
        if (colNumber === 8) {
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
            cell.numFmt = '"Rp "#,##0';
            cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF92400E' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDE68A' } };
        }
    });

    applyLembarPengesahan(sheet2, {
        maxCols: maxColsS2,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: startDate || new Date(),
    });

    return sheet2;
}

/**
 * Sheet 3: Rincian Detail Harian untuk Laporan Bonus
 */
export function buildDetailHarianBonusSheet(workbook, {
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = null,
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    logoBgnId = null,
    logoYayasanId = null,
    modeLabel = 'Bulanan 28 Hari',
    user = null,
}) {
    const finalDates = (dateList && dateList.length > 0)
        ? dateList
        : generateDefaultDateList(startDate, 28);

    const sheet3 = workbook.addWorksheet('Rincian Detail Harian', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const headerBorder = {
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'thin', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFB45309' } },
        right: { style: 'thin', color: { argb: 'FFB45309' } },
    };

    const totalBorder = {
        top: { style: 'thin', color: { argb: 'FFB45309' } },
        bottom: { style: 'double', color: { argb: 'FFB45309' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const maxColsS3 = 12;
    sheet3.columns = [
        { width: 6 },   // 1: No
        { width: 14 },  // 2: Tanggal
        { width: 10 },  // 3: Hari
        { width: 18 },  // 4: NIK
        { width: 28 },  // 5: Nama Petugas
        { width: 22 },  // 6: Jabatan / Divisi
        { width: 18 },  // 7: Status Presensi
        { width: 10 },  // 8: Mandays
        { width: 18 },  // 9: Tarif Bonus Harian
        { width: 18 },  // 10: Hak Bonus Diterima
        { width: 18 },  // 11: No Rekening BNI
        { width: 14 },  // 12: Status Petugas
    ];

    applyKopSurat(sheet3, maxColsS3, logoBgnId, logoYayasanId, unitSppg);

    sheet3.addRow([]);
    sheet3.getRow(5).height = 10;

    const rJudulS3 = sheet3.addRow(['LOG RINCIAN DETAIL HARIAN BONUS PETUGAS SPPG']);
    sheet3.mergeCells(`A${rJudulS3.number}:L${rJudulS3.number}`);
    rJudulS3.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudulS3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudulS3.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rangeText = (startDate && endDate)
        ? `Rentang Tanggal: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)} (${finalDates.length} Hari Kerja)`
        : `Standar: ${modeLabel}`;

    const rSubS3 = sheet3.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet3.mergeCells(`A${rSubS3.number}:L${rSubS3.number}`);
    rSubS3.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSubS3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSubS3.height = 18;

    sheet3.addRow([]);
    sheet3.getRow(8).height = 8;

    const rHeadS3 = sheet3.addRow([
        'No',
        'Tanggal',
        'Hari',
        'NIK',
        'Nama Petugas',
        'Jabatan / Divisi',
        'Status Presensi',
        'Mandays',
        'Bonus Harian',
        'Hak Bonus Diterima',
        'No Rekening BNI',
        'Status',
    ]);
    rHeadS3.height = 24;
    rHeadS3.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF92400E' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = headerBorder;
    });

    const dayNameMap = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    let counter = 1;
    const startDataRow = 10;

    finalDates.forEach(dt => {
        const dateStr = dt.dateStr;
        let dayName = dt.dayName;
        if (!dayName && dateStr) {
            try {
                const curD = new Date(dateStr + 'T00:00:00');
                dayName = dayNameMap[curD.getDay()] || '';
            } catch {}
        }

        petugas.forEach(p => {
            const personAttendance = presensiMap[p.id] || {};
            const st = personAttendance[dateStr] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dateStr];

            const nama = snap?.nama || p.nama || '-';
            const nik = snap?.nik || p.nik || '-';
            const jabatan = snap?.jabatan || p.jabatan || '-';
            const statusPetugas = snap?.status_petugas || p.status || 'Aktif';

            const defaultBonus = Number(p.bonus_harian_mitra) || 0;
            const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
                ? Number(snap.bonus_harian_mitra)
                : defaultBonus;
            const rekening = p.nomor_rekening_bni || p.no_rekening || p.rekening_bni || '-';

            let mandays = 0;
            let statusLabel = 'Belum Diisi (-)';
            if (st === 'H') {
                mandays = 1.0;
                statusLabel = 'Hadir Penuh (H)';
            } else if (st === 'H2' || st === '½') {
                mandays = 0.5;
                statusLabel = 'Hadir ½ Hari (½)';
            } else if (st === 'L') {
                mandays = 0;
                statusLabel = 'Libur (L)';
            } else if (st === 'I') {
                mandays = 0;
                statusLabel = 'Izin (I)';
            } else if (st === 'S') {
                mandays = 0;
                statusLabel = 'Sakit (S)';
            } else if (st === 'TK') {
                mandays = 0;
                statusLabel = 'Tanpa Keterangan (TK)';
            }

            const hakBonus = mandays * dailyBonus;

            const rData = sheet3.addRow([
                counter,
                dateStr,
                dayName,
                nik,
                nama,
                jabatan,
                statusLabel,
                mandays,
                dailyBonus,
                hakBonus,
                rekening,
                statusPetugas,
            ]);
            rData.height = 19;

            const isEven = counter % 2 === 0;
            const rowBg = isEven ? 'FFFDFBF7' : 'FFFFFFFF';

            rData.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                cell.font = { name: 'Arial', size: 9 };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
                cell.border = borderThin;
                cell.alignment = { vertical: 'middle' };

                if ([1, 2, 3, 4, 7, 8, 11, 12].includes(colNumber)) {
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                }
                if (colNumber === 5) {
                    cell.font = { name: 'Arial', size: 9, bold: true };
                }
                if (colNumber === 9 || colNumber === 10) {
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '"Rp "#,##0';
                    if (colNumber === 10) {
                        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF92400E' } };
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
                    }
                }
                if (colNumber === 7) {
                    if (st === 'H') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF065F46' } };
                    else if (st === 'H2' || st === '½') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF0D9488' } };
                    else if (st === 'L') cell.font = { name: 'Arial', size: 9, color: { argb: 'FF64748B' } };
                    else if (st === 'I') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFB45309' } };
                    else if (st === 'S') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FF1D4ED8' } };
                    else if (st === 'TK') cell.font = { name: 'Arial', size: 9, bold: true, color: { argb: 'FFE11D48' } };
                }
            });

            counter++;
        });
    });

    const lastDataRowS3 = startDataRow + counter - 2;

    if (lastDataRowS3 >= startDataRow) {
        const rTotS3 = sheet3.addRow([
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            `${counter - 1} Catatan Log`,
            { formula: `SUM(H${startDataRow}:H${lastDataRowS3})` },
            '-',
            { formula: `SUM(J${startDataRow}:J${lastDataRowS3})` },
            '',
            '',
        ]);
        sheet3.mergeCells(`A${rTotS3.number}:F${rTotS3.number}`);
        rTotS3.height = 24;

        rTotS3.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9.5, bold: true };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
            cell.border = totalBorder;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 7 || colNumber === 8) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (colNumber === 9 || colNumber === 10) {
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                if (colNumber === 10) {
                    cell.numFmt = '"Rp "#,##0';
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF92400E' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFDE68A' } };
                }
            }
        });
    }

    sheet3.autoFilter = `A9:L9`;

    applyLembarPengesahan(sheet3, {
        maxCols: maxColsS3,
        leftStartCol: 2,
        leftEndCol: 5,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: endDate || startDate || new Date(),
    });

    return sheet3;
}

/**
 * Export File Excel untuk Rekapitulasi Hak Bonus Harian Petugas SPPG
 */
export async function downloadRekapBonusExcel({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = [],
    startDate = null,
    endDate = null,
    unitSppg = null,
    periode = null,
    modeLabel = 'Bulanan (28 Hari Kerja)',
    user = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE - SPPG System';
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
    } catch (e) {
        console.warn('Gagal memuat logo:', e);
    }

    const sheet = workbook.addWorksheet('Rekap Bonus', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxCols = 10;
    sheet.columns = [
        { width: 6 },   // 1: No
        { width: 28 },  // 2: Nama Petugas
        { width: 22 },  // 3: Jabatan / Divisi
        { width: 18 },  // 4: Bonus Harian (Rp)
        { width: 12 },  // 5: Hadir Penuh (H)
        { width: 12 },  // 6: Hadir ½ Hari
        { width: 16 },  // 7: Total Mandays
        { width: 22 },  // 8: Total Hak Bonus (Rp)
        { width: 20 },  // 9: Rekening BNI
        { width: 16 },  // 10: Status
    ];

    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['REKAPITULASI HAK BONUS HARIAN PETUGAS SPPG']);
    sheet.mergeCells(`A${rJudul.number}:J${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const periodeNama = periode?.label || (periode?.nomor_periode ? `Periode ${periode.nomor_periode}` : 'Periode Operasional Berjalan');
    const rangeText = (startDate && endDate)
        ? `Rentang: ${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)}`
        : `Standar: ${modeLabel}`;

    const rSub = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | ${periodeNama} | ${rangeText}`
    ]);
    sheet.mergeCells(`A${rSub.number}:J${rSub.number}`);
    rSub.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub.height = 18;

    sheet.addRow([]);
    sheet.getRow(8).height = 8;

    const rHeader = sheet.addRow([
        'No',
        'Nama Petugas',
        'Divisi / Jabatan',
        'Bonus Harian (Rp)',
        'Hadir (H)',
        '½ Hari',
        'Total Mandays',
        'Total Hak Bonus (Rp)',
        'Nomor Rekening',
        'Status'
    ]);
    rHeader.height = 24;

    const headerBorder = {
        top: { style: 'medium', color: { argb: 'FF047857' } },
        bottom: { style: 'medium', color: { argb: 'FF047857' } },
        left: { style: 'thin', color: { argb: 'FF065F46' } },
        right: { style: 'thin', color: { argb: 'FF065F46' } },
    };

    rHeader.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF065F46' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = headerBorder;
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
    };

    let startDataRow = 10;
    let totalPenerima = 0;
    let sumMandays = 0;
    let sumBonus = 0;

    petugas.forEach((p, idx) => {
        const rowNum = startDataRow + idx;
        const isAktif = p.status === 'Aktif';
        const pAtt = presensiMap[p.id] || {};

        let effHadirPenuh = 0;
        let effHadirSetengah = 0;
        let effTotalBonus = 0;
        const rateBonus = parseInt(p.bonus_harian_mitra) || 0;

        dateList.forEach(dt => {
            const st = pAtt[dt.dateStr] || pAtt[dt.label] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
                ? Number(snap.bonus_harian_mitra)
                : rateBonus;

            if (st === 'H') {
                effHadirPenuh++;
                effTotalBonus += dailyBonus * 1.0;
            } else if (st === 'H2' || st === '½') {
                effHadirSetengah++;
                effTotalBonus += dailyBonus * 0.5;
            }
        });

        const effMandays = effHadirPenuh + (effHadirSetengah * 0.5);
        const totalBonus = effTotalBonus > 0 ? effTotalBonus : (effMandays * rateBonus);

        if (isAktif || effMandays > 0) {
            totalPenerima++;
            sumMandays += effMandays;
            sumBonus += totalBonus;
        }

        const row = sheet.addRow([
            idx + 1,
            p.nama || '-',
            p.jabatan || '-',
            rateBonus,
            effHadirPenuh,
            effHadirSetengah,
            effMandays,
            totalBonus,
            p.nomor_rekening ? `${p.jenis_bank || 'BNI'} - ${p.nomor_rekening}` : '-',
            p.status || 'Aktif',
        ]);
        row.height = 20;

        const isEven = idx % 2 === 1;
        const rowBg = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 10) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (colNumber === 2) {
                cell.font = { name: 'Arial', size: 9, bold: true };
            } else if (colNumber === 4 || colNumber === 8) {
                cell.numFmt = '#,##0';
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
            } else if (colNumber >= 5 && colNumber <= 7) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                if (colNumber === 7) {
                    cell.font = { name: 'Arial', size: 9, bold: true };
                }
            }
        });
    });

    // Baris Grand Total
    const rTot = sheet.addRow([
        '',
        `TOTAL (${petugas.length} Petugas)`,
        '',
        '',
        '',
        '',
        sumMandays,
        sumBonus,
        '',
        ''
    ]);
    rTot.height = 22;
    rTot.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF065F46' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
        cell.border = {
            top: { style: 'medium', color: { argb: 'FF059669' } },
            bottom: { style: 'medium', color: { argb: 'FF059669' } },
            left: { style: 'thin', color: { argb: 'FF6EE7B7' } },
            right: { style: 'thin', color: { argb: 'FF6EE7B7' } },
        };
        cell.alignment = { vertical: 'middle' };
        if (colNumber === 7) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        } else if (colNumber === 8) {
            cell.numFmt = '#,##0';
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
        }
    });

    applyLembarPengesahan(sheet, {
        maxCols: maxCols,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: endDate || startDate || new Date(),
    });

    // =========================================================
    // SHEET 2: Ringkasan per Divisi (Executive Summary Bonus)
    // =========================================================
    buildRingkasanDivisiBonusSheet(workbook, {
        petugas,
        presensiMap,
        presensiDetailsMap,
        dateList,
        startDate,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
        modeLabel,
        user,
    });

    // =========================================================
    // SHEET 3: Rincian Detail Harian (Granular Log Bonus Per Hari)
    // =========================================================
    buildDetailHarianBonusSheet(workbook, {
        petugas,
        presensiMap,
        presensiDetailsMap,
        dateList,
        startDate,
        endDate,
        unitSppg,
        periode,
        logoBgnId,
        logoYayasanId,
        modeLabel,
        user,
    });

    const actualStartStr = startDate || (dateList && dateList[0]?.dateStr) || 'tanggal';
    const actualEndStr = endDate || (dateList && dateList[dateList.length - 1]?.dateStr) || actualStartStr;
    const filename = `Rekap_Bonus_Petugas_SPPG_${actualStartStr}_sd_${actualEndStr}.xlsx`;

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
 * Export File Excel untuk Rekapitulasi Iuran BPJS Ketenagakerjaan Petugas SPPG
 */
export async function downloadRekapBpjsTkExcel({
    petugas = [],
    unitSppg = null,
    user = null,
}) {
    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE - SPPG System';
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
    } catch (e) {
        console.warn('Gagal memuat logo:', e);
    }

    const sheet = workbook.addWorksheet('Rekap BPJS TK', {
        pageSetup: { orientation: 'landscape', paperSize: 9, fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
    });

    const maxCols = 10;
    sheet.columns = [
        { width: 6 },   // 1: No
        { width: 28 },  // 2: Nama Petugas
        { width: 22 },  // 3: NIK (KTP)
        { width: 8 },   // 4: L/P
        { width: 16 },  // 5: Tgl Lahir
        { width: 8 },   // 6: Usia
        { width: 24 },  // 7: Divisi / Jabatan
        { width: 14 },  // 8: Status
        { width: 20 },  // 9: Iuran BPJS TK (Rp)
        { width: 22 },  // 10: Program Jaminan
    ];

    applyKopSurat(sheet, maxCols, logoBgnId, logoYayasanId, unitSppg);

    sheet.addRow([]);
    sheet.getRow(5).height = 10;

    const rJudul = sheet.addRow(['REKAPITULASI IURAN KEPESERTAAN BPJS KETENAGAKERJAAN PETUGAS SPPG']);
    sheet.mergeCells(`A${rJudul.number}:J${rJudul.number}`);
    rJudul.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF0F172A' } };
    rJudul.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rJudul.height = 22;

    const rSub = sheet.addRow([
        `Unit: ${unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah'} | Program: JKK (Jaminan Kecelakaan Kerja) & JKM (Jaminan Kematian)`
    ]);
    sheet.mergeCells(`A${rSub.number}:J${rSub.number}`);
    rSub.getCell(1).font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF475569' } };
    rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rSub.height = 18;

    sheet.addRow([]);
    sheet.getRow(8).height = 8;

    const rHeader = sheet.addRow([
        'No',
        'Nama Petugas',
        'NIK (KTP)',
        'L/P',
        'Tanggal Lahir',
        'Usia',
        'Divisi / Jabatan',
        'Status',
        'Iuran Premi / Bulan',
        'Cakupan Program'
    ]);
    rHeader.height = 24;

    const headerBorder = {
        top: { style: 'medium', color: { argb: 'FF0369A1' } },
        bottom: { style: 'medium', color: { argb: 'FF0369A1' } },
        left: { style: 'thin', color: { argb: 'FF075985' } },
        right: { style: 'thin', color: { argb: 'FF075985' } },
    };

    rHeader.eachCell({ includeEmpty: true }, cell => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0284C7' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = headerBorder;
    });

    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } },
    };

    let startDataRow = 10;
    let sumIuran = 0;

    petugas.forEach((p, idx) => {
        const iuran = parseInt(p.iuran_bpjs_tk) || 0;
        if (p.status === 'Aktif') {
            sumIuran += iuran;
        }

        const row = sheet.addRow([
            idx + 1,
            p.nama || '-',
            p.nik ? `'${p.nik}` : '-',
            p.jenis_kelamin || '-',
            p.tanggal_lahir ? formatTanggalIndo(p.tanggal_lahir) : '-',
            p.umur ? `${p.umur} th` : '-',
            p.jabatan || '-',
            p.status || 'Aktif',
            iuran,
            iuran > 0 ? 'JKK & JKM (Aktif)' : 'Belum Didaftarkan',
        ]);
        row.height = 20;

        const isEven = idx % 2 === 1;
        const rowBg = isEven ? 'FFF0F9FF' : 'FFFFFFFF';

        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
            cell.font = { name: 'Arial', size: 9 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rowBg } };
            cell.border = borderThin;
            cell.alignment = { vertical: 'middle' };

            if (colNumber === 1 || colNumber === 4 || colNumber === 6 || colNumber === 8) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else if (colNumber === 2) {
                cell.font = { name: 'Arial', size: 9, bold: true };
            } else if (colNumber === 9) {
                cell.numFmt = '#,##0';
                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                cell.font = { name: 'Arial', size: 9, bold: true };
            } else if (colNumber === 10) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            }
        });
    });

    // Baris Total Iuran
    const rTot = sheet.addRow([
        '',
        `TOTAL IURAN PREMI (${petugas.length} Petugas)`,
        '',
        '',
        '',
        '',
        '',
        '',
        sumIuran,
        'Per Bulan'
    ]);
    rTot.height = 22;
    rTot.eachCell({ includeEmpty: true }, (cell, colNumber) => {
        cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0369A1' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0F2FE' } };
        cell.border = {
            top: { style: 'medium', color: { argb: 'FF0284C7' } },
            bottom: { style: 'medium', color: { argb: 'FF0284C7' } },
            left: { style: 'thin', color: { argb: 'FFBAE6FD' } },
            right: { style: 'thin', color: { argb: 'FFBAE6FD' } },
        };
        cell.alignment = { vertical: 'middle' };
        if (colNumber === 9) {
            cell.numFmt = '#,##0';
            cell.alignment = { horizontal: 'right', vertical: 'middle' };
        } else if (colNumber === 10) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        }
    });

    applyLembarPengesahan(sheet, {
        maxCols: 10,
        leftStartCol: 2,
        leftEndCol: 4,
        rightColsWidth: 4,
        unitSppg,
        petugas,
        user,
        tanggal: new Date(),
    });

    const nowStr = new Date().toISOString().substring(0, 10);
    const filename = `Rekap_BPJS_Ketenagakerjaan_Petugas_SPPG_${nowStr}.xlsx`;

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
 * Helper format rupiah & ribuan
 */
export function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}

export function formatRibuan(num) {
    if (num === null || num === undefined || isNaN(num) || num === '') return '0';
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Helper konversi angka ke terbilang bahasa Indonesia
 */
export function terbilang(n) {
    n = Math.floor(Math.abs(Number(n) || 0));
    if (n === 0) return 'Nol Rupiah';
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
    return res + ' Rupiah';
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * EXPORT SLIP GAJI PETUGAS SPPG (.PDF)
 * Menghasilkan dokumen PDF berisi slip per personil (1 slip per lembar A4 portrait)
 * ─────────────────────────────────────────────────────────────────────────────
 */
export async function downloadSlipGajiPdf({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = [],
    startDate = '',
    endDate = '',
    unitSppg = null,
    periode = null,
    user = null,
}) {
    if (!petugas || petugas.length === 0) {
        throw new Error('Tidak ada data petugas untuk dicetak slip gaji.');
    }

    const kopConfig = getActiveKopConfig(unitSppg);
    const penandatangan = getPenandatanganSppg(petugas, user);

    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    const activeList = petugas.filter(p => p.status === 'Aktif');
    const targetList = activeList.length > 0 ? activeList : petugas;

    targetList.forEach((p, idx) => {
        if (idx > 0) {
            doc.addPage();
        }

        // Hitung statistik kehadiran personil
        const personAtt = presensiMap[p.id] || {};
        let hadir = 0;
        let setengahHari = 0;
        let libur = 0;
        let izin = 0;
        let sakit = 0;
        let tk = 0;
        let totalGaji = 0;

        const defaultRate = Number(p.gaji_harian_bgn) || 0;

        dateList.forEach(dt => {
            const st = personAtt[dt.dateStr] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyGaji = (snap && snap.gaji_harian_bgn !== undefined && snap.gaji_harian_bgn !== null)
                ? Number(snap.gaji_harian_bgn)
                : defaultRate;

            if (st === 'H') {
                hadir++;
                totalGaji += dailyGaji * 1.0;
            } else if (st === 'H2' || st === '½') {
                setengahHari++;
                totalGaji += dailyGaji * 0.5;
            } else if (st === 'L') {
                libur++;
            } else if (st === 'I') {
                izin++;
            } else if (st === 'S') {
                sakit++;
            } else if (st === 'TK') {
                tk++;
            }
        });

        const mandays = hadir + (setengahHari * 0.5);
        const bpjsTk = Number(p.iuran_bpjs_tk) || 16800;
        const totalDiterima = Math.round(totalGaji);

        // 1. Box Container Kartu Slip (1 Halaman Penuh A4: 210 x 297mm)
        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(203, 213, 225); // slate-300
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
        doc.setFillColor(6, 95, 70); // emerald-800
        doc.rect(16, 40, 178, 8.5, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor(255, 255, 255);
        doc.text('SLIP PEMBAYARAN HONORARIUM / GAJI PETUGAS SPPG', 105, 45.5, { align: 'center' });

        // 4. Identitas & Periode Metadata Box
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.25);
        doc.roundedRect(16, 51, 178, 28, 2, 2, 'FD');

        const periodeNama = periode?.nama || (dateList.length <= 14 ? 'Periodik (14 Hari Kerja)' : 'Bulanan (28 Hari Kerja)');
        const rangeText = `${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)}`;
        const rekStr = p.nomor_rekening ? `${p.nama_bank || 'BNI'} ${p.nomor_rekening}` : '- (Pembayaran Tunai)';

        // Left Col
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(100, 116, 139);
        doc.text('Nama Petugas', 20, 57);
        doc.text(':', 48, 57);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(p.nama || '-', 51, 57);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('NIK', 20, 63.5);
        doc.text(':', 48, 63.5);
        doc.setTextColor(30, 41, 59);
        doc.text(p.nik || '-', 51, 63.5);

        doc.setTextColor(100, 116, 139);
        doc.text('Jabatan / Divisi', 20, 70);
        doc.text(':', 48, 70);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(4, 120, 87);
        doc.text(p.jabatan || '-', 51, 70);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Unit Kerja', 20, 75.5);
        doc.text(':', 48, 75.5);
        doc.setTextColor(51, 65, 85);
        doc.text(unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah', 51, 75.5);

        // Right Col
        doc.setTextColor(100, 116, 139);
        doc.text('Periode Kerja', 110, 57);
        doc.text(':', 136, 57);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(periodeNama, 139, 57);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Rentang Tanggal', 110, 63.5);
        doc.text(':', 136, 63.5);
        doc.setTextColor(30, 41, 59);
        doc.text(rangeText, 139, 63.5);

        doc.setTextColor(100, 116, 139);
        doc.text('Rekening Bank', 110, 70);
        doc.text(':', 136, 70);
        doc.setTextColor(30, 41, 59);
        doc.text(rekStr, 139, 70);

        doc.setTextColor(100, 116, 139);
        doc.text('Status Kerja', 110, 75.5);
        doc.text(':', 136, 75.5);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(4, 120, 87);
        doc.text(p.status || 'Aktif', 139, 75.5);

        // 5. Rekap Presensi Box (Vertikal Rata & Rapi)
        doc.setFillColor(241, 245, 249);
        doc.setDrawColor(203, 213, 225);
        doc.roundedRect(16, 81.5, 178, 18, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(51, 65, 85);
        doc.text('REKAPITULASI KEHADIRAN (LOG MANDAYS KERJA):', 20, 86.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text(`Hadir Penuh (H): ${hadir} Hari    |    Hadir ½ Hari (H2): ${setengahHari} Hari    |    Libur (L): ${libur} Hari`, 20, 91.5);
        doc.text(`Izin (I): ${izin} Hari    |    Sakit (S): ${sakit} Hari    |    Tanpa Keterangan (TK): ${tk} Hari`, 20, 96.0);

        // Right Badge Total Mandays (Vertikal Ditengah Sempurna)
        doc.setFillColor(209, 250, 229);
        doc.setDrawColor(5, 150, 105);
        doc.roundedRect(138, 85, 52, 11, 2, 2, 'FD');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(6, 95, 70);
        doc.text(`Total: ${mandays} Mandays`, 164, 90.5, { align: 'center', baseline: 'middle' });

        // 6. Rincian Tabel Perhitungan Gaji & Potongan
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.3);
        doc.roundedRect(16, 103, 178, 36, 2, 2, 'FD');

        // Table Header
        doc.setFillColor(226, 232, 240);
        doc.rect(16, 103, 178, 8, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(30, 41, 59);
        doc.text('NO', 20, 108.5);
        doc.text('KOMPONEN HONORARIUM / HAK KERJA', 32, 108.5);
        doc.text('VOLUME', 115, 108.5, { align: 'center' });
        doc.text('TARIF HARIAN', 145, 108.5, { align: 'center' });
        doc.text('JUMLAH (RP)', 188, 108.5, { align: 'right' });

        // Row 1: Honor Mandays
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text('1.', 20, 116.5);
        doc.text('Honorarium Mandays Kerja Petugas SPPG (BGN)', 32, 116.5);
        doc.text(`${mandays} Mandays`, 115, 116.5, { align: 'center' });
        doc.text(formatRupiah(defaultRate), 145, 116.5, { align: 'center' });
        doc.setFont('helvetica', 'bold');
        doc.text(formatRupiah(totalGaji), 188, 116.5, { align: 'right' });

        // Row 2: BPJS Ketenagakerjaan
        doc.setDrawColor(226, 232, 240);
        doc.line(16, 121.5, 194, 121.5);
        doc.setFont('helvetica', 'normal');
        doc.text('2.', 20, 128);
        doc.text('Jaminan Kecelakaan Kerja & Kematian (BPJS TK)', 32, 128);
        doc.text('1 Bulan', 115, 128, { align: 'center' });
        doc.text(formatRupiah(bpjsTk), 145, 128, { align: 'center' });
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(4, 120, 87);
        doc.text('*Tercatat Terlindungi', 188, 128, { align: 'right' });

        // Mini info line
        doc.line(16, 132.5, 194, 132.5);
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(6.8);
        doc.setTextColor(100, 116, 139);
        doc.text('* Iuran perlindungan jaminan kecelakaan kerja & jaminan kematian resmi didaftarkan pada BPJS Ketenagakerjaan.', 20, 136.5);

        // 7. Callout Box Total Gaji Bersih Diterima (Rata & Proporsional)
        doc.setFillColor(236, 253, 245);
        doc.setDrawColor(5, 150, 105);
        doc.setLineWidth(0.6);
        doc.roundedRect(16, 143, 178, 37, 2.5, 2.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(6, 95, 70);
        doc.text('TOTAL HONORARIUM BERSIH DITERIMA', 105, 151.5, { align: 'center' });

        doc.setFontSize(19);
        doc.setTextColor(6, 78, 59);
        doc.text(formatRupiah(totalDiterima), 105, 163, { align: 'center' });

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 65, 85);
        const terbilangStr = `"${terbilang(totalDiterima)}"`;
        const splitTerbilang = doc.splitTextToSize(terbilangStr, 160);
        doc.text(splitTerbilang, 105, 173, { align: 'center' });

        // 8. Catatan Resmi (Rata Atas & Bawah Seimbang)
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.25);
        doc.roundedRect(16, 183.5, 178, 16.5, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(71, 85, 105);
        doc.text('CATATAN PENGELOLA SPPG:', 20, 188.0);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(100, 116, 139);
        doc.text('1. Dokumen slip honorarium ini diterbitkan secara sah oleh Sistem Informasi Pengelolaan SPPG (SIPEGE).', 20, 193.0);
        doc.text('2. Apabila terdapat kekeliruan perhitungan mandays kehadiran atau nominal pembayaran, harap segera berkoordinasi dengan Pengawas Keuangan.', 20, 197.0);

        // 9. Tanda Tangan Pengesahan (3 Pihak: Yang Menerima, Pengawas Keuangan, Kepala SPPG - Tanpa NIK, Cukup Nama)
        const kepalaPetugas = (petugas || []).find(pt => {
            const j = (pt.jabatan || '').toLowerCase();
            return j === 'kepala sppg' || j.includes('kepala sppg');
        });
        const pengawasKeuPetugas = (petugas || []).find(pt => {
            const j = (pt.jabatan || '').toLowerCase();
            return j === 'pengawas keuangan' || j.includes('pengawas keuangan');
        });

        const namaKepala = penandatangan?.kepala?.nama || kepalaPetugas?.nama || '';
        const ttdKepala = namaKepala ? `( ${namaKepala} )` : '( .................................................... )';

        const namaPengawas = penandatangan?.pengawas?.nama || pengawasKeuPetugas?.nama || '';
        const ttdPengawasKeu = namaPengawas ? `( ${namaPengawas} )` : '( .................................................... )';

        const now = new Date();
        const tglCetak = formatTanggalIndo(now);
        const tglWaktuCetak = formatTanggalWaktuIndo(now);
        const kotaStr = kopConfig?.kabupaten || 'Buleleng';

        // Baris Atas Jabatan / Header TTD (Tanggal diatas Mengetahui)
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 65, 85);

        // Baris 1 (Kanan): Tempat & Tanggal Dokumen (Diletakkan diatas Mengetahui)
        doc.text(`${kotaStr}, ${tglCetak}`, 165, 209, { align: 'center' });

        // Baris 2 (Tengah): Mengetahui (Dibawah tanggal, diatas Pengawas Keuangan)
        doc.text('Mengetahui,', 105, 215, { align: 'center' });

        // Baris 3: Tiga Pihak Bertandatangan (Sejajar Horisontal)
        doc.text('Yang Menerima,', 45, 221, { align: 'center' });
        doc.text('Pengawas Keuangan,', 105, 221, { align: 'center' });
        doc.text('Kepala SPPG,', 165, 221, { align: 'center' });

        // Baris Nama Terang / TTD (Ruang tanda tangan ideal ~26mm, Hanya Nama tanpa NIK)
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(15, 23, 42);

        doc.text(String(`( ${p.nama || '................................'} )`), 45, 247, { align: 'center' });
        doc.text(String(ttdPengawasKeu), 105, 247, { align: 'center' });
        doc.text(String(ttdKepala), 165, 247, { align: 'center' });

        // 10. Footer Micro-line
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(6);
        doc.setTextColor(148, 163, 184);
        doc.text(`Sistem Informasi SPPG (SIPEGE) - Slip dicetak secara elektronik pada ${tglWaktuCetak} • Halaman ${idx + 1} dari ${targetList.length}`, 105, 276, { align: 'center' });
    });

    const nowStr = new Date().toISOString().substring(0, 10);
    const filename = `Slip_Gaji_Petugas_SPPG_${startDate}_sd_${endDate}_${nowStr}.pdf`;
    doc.save(filename);
}

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * EXPORT SLIP BONUS MITRA PETUGAS SPPG (.PDF)
 * Menghasilkan dokumen PDF berisi slip bonus per personil (1 slip per lembar A4 portrait)
 * ─────────────────────────────────────────────────────────────────────────────
 */
export async function downloadSlipBonusPdf({
    petugas = [],
    presensiMap = {},
    presensiDetailsMap = {},
    dateList = [],
    startDate = '',
    endDate = '',
    unitSppg = null,
    periode = null,
    user = null,
}) {
    if (!petugas || petugas.length === 0) {
        throw new Error('Tidak ada data petugas untuk dicetak slip bonus.');
    }

    const kopConfig = getActiveKopConfig(unitSppg);
    const penandatangan = getPenandatanganSppg(petugas, user);

    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    const activeList = petugas.filter(p => p.status === 'Aktif');
    const targetList = activeList.length > 0 ? activeList : petugas;

    targetList.forEach((p, idx) => {
        if (idx > 0) {
            doc.addPage();
        }

        // Hitung statistik kehadiran personil
        const personAtt = presensiMap[p.id] || {};
        let hadir = 0;
        let setengahHari = 0;
        let libur = 0;
        let izin = 0;
        let sakit = 0;
        let tk = 0;
        let totalBonus = 0;

        const defaultBonus = Number(p.bonus_harian_mitra) || 0;

        dateList.forEach(dt => {
            const st = personAtt[dt.dateStr] || '';
            const snap = presensiDetailsMap?.[p.id]?.[dt.dateStr];
            const dailyBonus = (snap && snap.bonus_harian_mitra !== undefined && snap.bonus_harian_mitra !== null)
                ? Number(snap.bonus_harian_mitra)
                : defaultBonus;

            if (st === 'H') {
                hadir++;
                totalBonus += dailyBonus * 1.0;
            } else if (st === 'H2' || st === '½') {
                setengahHari++;
                totalBonus += dailyBonus * 0.5;
            } else if (st === 'L') {
                libur++;
            } else if (st === 'I') {
                izin++;
            } else if (st === 'S') {
                sakit++;
            } else if (st === 'TK') {
                tk++;
            }
        });

        const mandays = hadir + (setengahHari * 0.5);
        const totalDiterima = Math.round(totalBonus);

        // 1. Box Container Kartu Slip (1 Halaman Penuh A4: 210 x 297mm)
        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(203, 213, 225); // slate-300
        doc.setLineWidth(0.4);
        doc.roundedRect(12, 12, 186, 273, 2.5, 2.5, 'FD');

        // 2. Kop Surat SPPG
        try {
            if (LOGO_BGN_BASE64) {
                // Perbesar proporsi agar setara dan seimbang visual dengan logo yayasan di kanan
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
        doc.setFillColor(180, 83, 9); // amber-700
        doc.rect(16, 40, 178, 8.5, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor(255, 255, 255);
        doc.text('SLIP PEMBAYARAN BONUS HARIAN MITRA PETUGAS SPPG', 105, 45.5, { align: 'center' });

        // 4. Identitas & Periode Metadata Box
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.25);
        doc.roundedRect(16, 51, 178, 28, 2, 2, 'FD');

        const periodeNama = periode?.nama || (dateList.length <= 14 ? 'Periodik (14 Hari Kerja)' : 'Bulanan (28 Hari Kerja)');
        const rangeText = `${formatTanggalIndo(startDate)} s.d. ${formatTanggalIndo(endDate)}`;
        const rekStr = p.nomor_rekening ? `${p.nama_bank || 'BNI'} ${p.nomor_rekening}` : '- (Pembayaran Tunai)';

        // Left Col
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(100, 116, 139);
        doc.text('Nama Petugas Mitra', 20, 57);
        doc.text(':', 50, 57);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(p.nama || '-', 53, 57);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('NIK', 20, 63.5);
        doc.text(':', 50, 63.5);
        doc.setTextColor(30, 41, 59);
        doc.text(p.nik || '-', 53, 63.5);

        doc.setTextColor(100, 116, 139);
        doc.text('Tugas / Divisi', 20, 70);
        doc.text(':', 50, 70);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(180, 83, 9);
        doc.text(p.jabatan || '-', 53, 70);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Unit Kerja', 20, 75.5);
        doc.text(':', 50, 75.5);
        doc.setTextColor(51, 65, 85);
        doc.text(unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah', 53, 75.5);

        // Right Col
        doc.setTextColor(100, 116, 139);
        doc.text('Periode Kerja', 110, 57);
        doc.text(':', 136, 57);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(15, 23, 42);
        doc.text(periodeNama, 139, 57);

        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text('Rentang Tanggal', 110, 63.5);
        doc.text(':', 136, 63.5);
        doc.setTextColor(30, 41, 59);
        doc.text(rangeText, 139, 63.5);

        doc.setTextColor(100, 116, 139);
        doc.text('Rekening Bank', 110, 70);
        doc.text(':', 136, 70);
        doc.setTextColor(30, 41, 59);
        doc.text(rekStr, 139, 70);

        doc.setTextColor(100, 116, 139);
        doc.text('Status Kemitraan', 110, 75.5);
        doc.text(':', 136, 75.5);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(180, 83, 9);
        doc.text(p.status || 'Aktif', 139, 75.5);

        // 5. Rekap Presensi Box (Vertically Centered Text & Badge)
        doc.setFillColor(241, 245, 249);
        doc.setDrawColor(203, 213, 225);
        doc.roundedRect(16, 81.5, 178, 18, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 65, 85);
        doc.text('REKAPITULASI KEHADIRAN (LOG MANDAYS KERJA):', 20, 87.0);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text(`Hadir Penuh (H): ${hadir} Hari   |   Hadir ½ Hari (H2): ${setengahHari} Hari   |   Libur (L): ${libur} Hari`, 20, 92.5);
        doc.text(`Izin (I): ${izin} Hari   |   Sakit (S): ${sakit} Hari   |   Tanpa Keterangan (TK): ${tk} Hari`, 20, 96.5);

        doc.setFillColor(254, 243, 199);
        doc.setDrawColor(217, 119, 6);
        doc.roundedRect(138, 85, 52, 11, 2, 2, 'FD');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(146, 64, 14);
        doc.text(`Total: ${mandays} Mandays`, 164, 90.5, { align: 'center', baseline: 'middle' });

        // 6. Rincian Tabel Perhitungan Bonus Harian
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(203, 213, 225);
        doc.setLineWidth(0.3);
        doc.roundedRect(16, 103, 178, 36, 2, 2, 'FD');

        // Table Header
        doc.setFillColor(226, 232, 240);
        doc.rect(16, 103, 178, 8, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(30, 41, 59);
        doc.text('NO', 20, 108.5);
        doc.text('KOMPONEN BONUS KERJA MITRA', 32, 108.5);
        doc.text('VOLUME', 115, 108.5, { align: 'center' });
        doc.text('TARIF BONUS HARIAN', 145, 108.5, { align: 'center' });
        doc.text('JUMLAH (RP)', 188, 108.5, { align: 'right' });

        // Row 1: Bonus Mandays
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(15, 23, 42);
        doc.text('1.', 20, 116.5);
        doc.text('Hak Bonus Harian Kinerja Mitra Petugas SPPG', 32, 116.5);
        doc.text(`${mandays} Mandays`, 115, 116.5, { align: 'center' });
        doc.text(formatRupiah(defaultBonus), 145, 116.5, { align: 'center' });
        doc.setFont('helvetica', 'bold');
        doc.text(formatRupiah(totalBonus), 188, 116.5, { align: 'right' });

        // Info line
        doc.setDrawColor(226, 232, 240);
        doc.line(16, 122.5, 194, 122.5);
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(6.8);
        doc.setTextColor(100, 116, 139);
        doc.text('* Bonus dihitung otomatis berdasarkan kehadiran log presensi kerja mandays dan ditransfer via rekening bank terdaftar.', 20, 129);
        doc.text('* Pembayaran bonus disalurkan sesuai jadwal ketentuan SPPG Buleleng.', 20, 133.5);

        // 7. Callout Box Total Bonus Diterima (Rata & Proporsional)
        doc.setFillColor(254, 243, 199);
        doc.setDrawColor(217, 119, 6);
        doc.setLineWidth(0.6);
        doc.roundedRect(16, 143, 178, 37, 2.5, 2.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(146, 64, 14);
        doc.text('TOTAL HAK BONUS MITRA DITERIMA', 105, 151.5, { align: 'center' });

        doc.setFontSize(20);
        doc.setTextColor(120, 53, 15);
        doc.text(formatRupiah(totalDiterima), 105, 163, { align: 'center' });

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(8);
        doc.setTextColor(51, 65, 85);
        const terbilangStr = `"${terbilang(totalDiterima)}"`;
        const splitTerbilang = doc.splitTextToSize(terbilangStr, 160);
        doc.text(splitTerbilang, 105, 173, { align: 'center' });

        // 8. Catatan Resmi (Rata Atas & Bawah Seimbang)
        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.25);
        doc.roundedRect(16, 183.5, 178, 16.5, 1.5, 1.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(71, 85, 105);
        doc.text('CATATAN PENGELOLA SPPG:', 20, 188.0);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(100, 116, 139);
        doc.text('1. Dokumen slip bonus kemitraan ini diterbitkan secara sah oleh Sistem Informasi Pengelolaan SPPG (SIPEGE).', 20, 193.0);
        doc.text('2. Apabila terdapat selisih perhitungan kehadiran mandays atau nominal bonus, harap segera berkoordinasi dengan Pengawas Keuangan.', 20, 197.0);

        // 9. Tanda Tangan Pengesahan (3 Pihak: Yang Menerima, Pengawas Keuangan, Kepala SPPG - Tanpa NIK, Cukup Nama)
        const kepalaPetugas = (petugas || []).find(pt => {
            const j = (pt.jabatan || '').toLowerCase();
            return j === 'kepala sppg' || j.includes('kepala sppg');
        });
        const pengawasKeuPetugas = (petugas || []).find(pt => {
            const j = (pt.jabatan || '').toLowerCase();
            return j === 'pengawas keuangan' || j.includes('pengawas keuangan');
        });

        const namaKepala = penandatangan?.kepala?.nama || kepalaPetugas?.nama || '';
        const ttdKepala = namaKepala ? `( ${namaKepala} )` : '( .................................................... )';

        const namaPengawas = penandatangan?.pengawas?.nama || pengawasKeuPetugas?.nama || '';
        const ttdPengawasKeu = namaPengawas ? `( ${namaPengawas} )` : '( .................................................... )';

        const now = new Date();
        const tglCetak = formatTanggalIndo(now);
        const tglWaktuCetak = formatTanggalWaktuIndo(now);
        const kotaStr = kopConfig?.kabupaten || 'Buleleng';

        // Baris Atas Jabatan / Header TTD (Tanggal diatas Mengetahui)
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(51, 65, 85);

        // Baris 1 (Kanan): Tempat & Tanggal Dokumen (Diletakkan diatas Mengetahui)
        doc.text(`${kotaStr}, ${tglCetak}`, 165, 209, { align: 'center' });

        // Baris 2 (Tengah): Mengetahui (Dibawah tanggal, diatas Pengawas Keuangan)
        doc.text('Mengetahui,', 105, 215, { align: 'center' });

        // Baris 3: Tiga Pihak Bertandatangan (Sejajar Horisontal)
        doc.text('Yang Menerima,', 45, 221, { align: 'center' });
        doc.text('Pengawas Keuangan,', 105, 221, { align: 'center' });
        doc.text('Kepala SPPG,', 165, 221, { align: 'center' });

        // Baris Nama Terang / TTD (Ruang tanda tangan ideal ~26mm, Hanya Nama tanpa NIK)
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(15, 23, 42);

        doc.text(String(`( ${p.nama || '................................'} )`), 45, 247, { align: 'center' });
        doc.text(String(ttdPengawasKeu), 105, 247, { align: 'center' });
        doc.text(String(ttdKepala), 165, 247, { align: 'center' });

        // 10. Footer Micro-line
        doc.setFont('helvetica', 'italic');
        doc.setFontSize(6);
        doc.setTextColor(148, 163, 184);
        doc.text(`Sistem Informasi SPPG (SIPEGE) - Slip dicetak secara elektronik pada ${tglWaktuCetak} • Halaman ${idx + 1} dari ${targetList.length}`, 105, 276, { align: 'center' });
    });

    const nowStr = new Date().toISOString().substring(0, 10);
    const filename = `Slip_Bonus_Mitra_SPPG_${startDate}_sd_${endDate}_${nowStr}.pdf`;
    doc.save(filename);
}
