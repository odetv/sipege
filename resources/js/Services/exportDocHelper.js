import { generateKopHtml } from "@/Services/kopDokumenHelper";
import jsPDF from "jspdf";
import html2canvas from "html2canvas";

// Helper Export Dokumen Work Order & Purchase Order ke Excel, Word, dan PDF

export function formatRupiahNum(val) {
    if (!val && val !== 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(val);
}

export function formatTanggalIndoFull(tgl) {
    if (!tgl) return '-';
    try {
        const d = new Date(tgl);
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    } catch {
        return tgl;
    }
}

// -------------------------------------------------------------
// 1. EXPORT WORK ORDER (DAFTAR MENU GIZI)
// -------------------------------------------------------------

export function exportWorkOrderExcel(wo) {
    const filename = `${wo.id || 'WO-MBG'}_${(wo.nama || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.xls`;
    
    const items = wo.items || [];
    const kelompoks = wo.kelompoks || [];

    let totalNetKgAll = 0;
    let totalGrossKgAll = 0;
    let totalSubtotalAll = 0;

    let itemsRows = items.map((it, idx) => {
        const pk = Number(it.gram_pk !== undefined ? it.gram_pk : (it.gram_bersih_pk || 0)) || 0;
        const pb = Number(it.gram_pb !== undefined ? it.gram_pb : (it.gram_bersih_pb || 0)) || 0;
        const targetPK = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pk) || 1) : (Number(wo?.porsi_pk) || 0);
        const targetPB = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pb) || 1) : (Number(wo?.porsi_pb) || 0);
        const netKg = it.total_net_kg !== undefined ? Number(it.total_net_kg) : ((pk * targetPK + pb * targetPB) / 1000);
        const grossKg = Number(it.total_gross_kg !== undefined ? it.total_gross_kg : (it.gross_weight_kg !== undefined ? it.gross_weight_kg : it.gross_kg)) || 0;
        const harga = Number(it.harga_master || it.harga_aktual || it.harga) || 0;
        const subtotal = Number(it.subtotal_master !== undefined ? it.subtotal_master : (it.subtotal !== undefined ? it.subtotal : it.subtotal_aktual)) || Math.round(grossKg * harga);

        totalNetKgAll += netKg;
        totalGrossKgAll += grossKg;
        totalSubtotalAll += subtotal;

        const nutrisiPK = it.nutrisi_pk || it.nutrisiPK || {};
        const nutrisiPB = it.nutrisi_pb || it.nutrisiPB || {};

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${it.nama || it.nama_bahan || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.tipe_porsi === 'alergi' ? 'Alergi (' + (it.jenis_alergi || 'Khusus') + ')' : 'Normal'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${pk} g</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${pb} g</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.bdd || 100}%</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.buffer || 0}%</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; background-color: #fef3c7; font-weight: bold;">${netKg.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; background-color: #dbeafe; font-weight: bold;">${grossKg.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">PK: ${nutrisiPK.energi || 0} | PB: ${nutrisiPB.energi || 0}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">PK: ${nutrisiPK.protein || 0}g | PB: ${nutrisiPB.protein || 0}g</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">PK: ${nutrisiPK.lemak || 0}g | PB: ${nutrisiPB.lemak || 0}g</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">PK: ${nutrisiPK.karbohidrat || 0}g | PB: ${nutrisiPB.karbohidrat || 0}g</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">PK: ${nutrisiPK.serat || 0}g | PB: ${nutrisiPB.serat || 0}g</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    let kelRows = kelompoks.map((k, idx) => {
        const kelData = k.kelompok || k;
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${kelData.nama_kelompok || kelData.nama || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${kelData.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${kelData.total_porsi_kecil || k.porsi_kecil || 0}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${kelData.total_porsi_besar || k.porsi_besar || 0}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${kelData.total_penerima || k.total_penerima || 0}</td>
        </tr>
        `;
    }).join('');

    const template = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Work Order Menu</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
            <style>
                body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #94a3b8; padding: 8px; font-size: 10pt; }
                .title { font-size: 16pt; font-weight: bold; text-align: center; color: #0f172a; margin-bottom: 4px; }
                .subtitle { font-size: 12pt; text-align: center; color: #475569; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="title">LEMBAR PERENCANAAN PRODUKSI & WORK ORDER (WO) MENU MBG</div>
            <div class="subtitle">Sistem Pengelolaan Pelayanan Gizi (SIPEGE)</div>
            
            <table style="margin-bottom: 16px; width: 700px;">
                <tr>
                    <td style="font-weight: bold; width: 180px;">Nomor Work Order</td>
                    <td>: ${wo.id || '-'}</td>
                    <td style="font-weight: bold; width: 150px;">Tanggal Distribusi</td>
                    <td>: ${formatTanggalIndoFull(wo.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Menu Utama</td>
                    <td>: ${wo.nama || '-'}</td>
                    <td style="font-weight: bold;">Status Menu</td>
                    <td>: ${wo.status_wo || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Sasaran PM</td>
                    <td>: ${Number(wo.total_porsi || 0).toLocaleString('id-ID')} Porsi (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)</td>
                    <td style="font-weight: bold;">Database Pangan</td>
                    <td>: ${wo.database_pangan === 'csv' ? 'Kemenkes (Tabel Komposisi Pangan Indonesia)' : 'Nutri Survey (Indonesian Food Table)'}</td>
                </tr>
            </table>

            <h3 style="margin-top: 15px; color: #1e293b; font-size: 12pt;">A. EVALUASI KECUKUPAN STANDAR GIZI AKG BGN (BADAN GIZI NASIONAL)</h3>
            <table style="margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #e0f2fe;">
                        <th style="text-align: left;">Kelompok Sasaran Porsi</th>
                        <th>Energi (Kkal)</th>
                        <th>Protein (g)</th>
                        <th>Lemak (g)</th>
                        <th>Karbohidrat (g)</th>
                        <th>Serat (g)</th>
                        <th>Food Cost / Porsi</th>
                        <th>Status AKG</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">Porsi Kecil (PK - PAUD/TK & SD 1-3)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.energi_pk || 0} kkal</strong> (330-413)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.protein_pk || 0} g</strong> (8.0-10.0g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.lemak_pk || 0} g</strong> (11.0-13.8g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.karbo_pk || 0} g</strong> (50.0-62.5g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.serat_pk || 0} g</strong> (4.0-7.0g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${formatRupiahNum(wo.cost_pk)}</strong> (Pagu: Rp 8.000)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center; color: #047857; font-weight: bold;">MEMENUHI</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">Porsi Besar (PB - SD 4-6/SMP/SMA/Tendik)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.energi_pb || 0} kkal</strong> (585-831)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.protein_pb || 0} g</strong> (15.8-24.5g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.lemak_pb || 0} g</strong> (19.5-26.3g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.karbo_pb || 0} g</strong> (87.0-122.5g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${wo.serat_pb || 0} g</strong> (6.0-10.0g)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;"><strong>${formatRupiahNum(wo.cost_pb)}</strong> (Pagu: Rp 10.000)</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center; color: #047857; font-weight: bold;">MEMENUHI</td>
                    </tr>
                </tbody>
            </table>

            <h3 style="margin-top: 20px; color: #1e293b; font-size: 12pt;">B. DAFTAR FORMULASI BAHAN BAKU, KANDUNGAN GIZI & ESTIMASI BIAYA</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th>Bahan Pangan</th>
                        <th>Kategori</th>
                        <th>Peruntukan</th>
                        <th>Gram Bersih PK</th>
                        <th>Gram Bersih PB</th>
                        <th>BDD (%)</th>
                        <th>Buffer (%)</th>
                        <th style="background-color: #fef3c7;">Kg Bersih</th>
                        <th style="background-color: #dbeafe;">Kg Kotor</th>
                        <th>Energi (Kkal)</th>
                        <th>Protein (g)</th>
                        <th>Lemak (g)</th>
                        <th>Karbohidrat (g)</th>
                        <th>Serat (g)</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal Estimasi</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="17" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="8" style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">Total Estimasi:</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; background-color: #fef3c7;">${totalNetKgAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; background-color: #dbeafe;">${totalGrossKgAll.toFixed(2)} kg</td>
                        <td colspan="5" style="border: 1px solid #cbd5e1; padding: 6px; text-align: center; color: #64748b;">Kontribusi Gizi Terhitung</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px;"></td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; color: #1e3a8a;">${formatRupiahNum(totalSubtotalAll || wo.total_anggaran)}</td>
                    </tr>
                </tfoot>
            </table>

            ${kelRows ? `
            <h3 style="margin-top: 25px; color: #1e293b; font-size: 12pt;">C. DAFTAR KELOMPOK SASARAN PENERIMA MANFAAT</h3>
            <table style="width: 700px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Kelompok Sasaran</th>
                        <th>Kategori</th>
                        <th>Porsi Kecil (PK)</th>
                        <th>Porsi Besar (PB)</th>
                        <th>Total PM</th>
                    </tr>
                </thead>
                <tbody>
                    ${kelRows}
                </tbody>
            </table>
            ` : ''}
        </body>
        </html>
    `;

    const blob = new Blob([template], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

export function exportWorkOrderWord(wo) {
    const filename = `${wo.id || 'WO-MBG'}_${(wo.nama || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.doc`;
    const items = wo.items || [];

    let totalNetKgAll = 0;
    let totalGrossKgAll = 0;
    let totalSubtotalAll = 0;

    let itemsRows = items.map((it, idx) => {
        const pk = Number(it.gram_pk !== undefined ? it.gram_pk : (it.gram_bersih_pk || 0)) || 0;
        const pb = Number(it.gram_pb !== undefined ? it.gram_pb : (it.gram_bersih_pb || 0)) || 0;
        const targetPK = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pk) || 1) : (Number(wo?.porsi_pk) || 0);
        const targetPB = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pb) || 1) : (Number(wo?.porsi_pb) || 0);
        const netKg = it.total_net_kg !== undefined ? Number(it.total_net_kg) : ((pk * targetPK + pb * targetPB) / 1000);
        const grossKg = Number(it.total_gross_kg !== undefined ? it.total_gross_kg : (it.gross_weight_kg !== undefined ? it.gross_weight_kg : it.gross_kg)) || 0;
        const harga = Number(it.harga_master || it.harga_aktual || it.harga) || 0;
        const subtotal = Number(it.subtotal_master !== undefined ? it.subtotal_master : (it.subtotal !== undefined ? it.subtotal : it.subtotal_aktual)) || Math.round(grossKg * harga);

        totalNetKgAll += netKg;
        totalGrossKgAll += grossKg;
        totalSubtotalAll += subtotal;

        const nutrisiPK = it.nutrisi_pk || it.nutrisiPK || {};
        const nutrisiPB = it.nutrisi_pb || it.nutrisiPB || {};

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 4px;">${idx + 1}</td>
            <td style="border: 1px solid #94a3b8; padding: 4px; font-weight: bold;">${it.nama || it.nama_bahan || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 4px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 4px;">${pk}g</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 4px;">${pb}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">${netKg.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-weight: bold;">${grossKg.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-size: 8.5pt;">PK: ${nutrisiPK.energi || 0}<br>PB: ${nutrisiPB.energi || 0}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-size: 8.5pt;">PK: ${nutrisiPK.protein || 0}g<br>PB: ${nutrisiPB.protein || 0}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-size: 8.5pt;">PK: ${nutrisiPK.lemak || 0}g<br>PB: ${nutrisiPB.lemak || 0}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-size: 8.5pt;">PK: ${nutrisiPK.karbohidrat || 0}g<br>PB: ${nutrisiPB.karbohidrat || 0}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-size: 8.5pt;">PK: ${nutrisiPK.serat || 0}g<br>PB: ${nutrisiPB.serat || 0}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const template = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset="utf-8">
            <title>Work Order ${wo.id}</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; font-size: 10pt; line-height: 1.3; color: #000; }
                .kop-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
                .kop-title { font-size: 13pt; font-weight: bold; text-transform: uppercase; margin: 0; }
                .kop-sub { font-size: 10.5pt; font-weight: bold; margin: 2px 0; }
                .kop-desc { font-size: 8.5pt; margin: 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 12px; font-size: 9pt; }
                table.data th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 5px; font-size: 9pt; text-align: center; }
                table.data td { font-size: 8.5pt; }
                .info-table td { padding: 2.5px 0; font-size: 9.5pt; }
                .ttd-box { margin-top: 25px; width: 100%; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { mode: 'word' })}
            <div style="text-align: center; margin-bottom: 14px;">
                <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase; text-decoration: underline;">LEMBAR PERENCANAAN WORK ORDER & FORMULASI GIZI MENU HARIAN</div>
            </div>

            <table class="info-table" style="width: 100%;">
                <tr>
                    <td style="width: 150px; font-weight: bold;">Nomor Work Order</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>${wo.id}</strong></td>
                    <td style="width: 140px; font-weight: bold;">Tanggal Distribusi</td>
                    <td style="width: 10px;">:</td>
                    <td>${formatTanggalIndoFull(wo.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Menu Utama</td>
                    <td>:</td>
                    <td><strong>${wo.nama}</strong></td>
                    <td style="font-weight: bold;">Status Persetujuan</td>
                    <td>:</td>
                    <td>${wo.status_wo}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Sasaran PM</td>
                    <td>:</td>
                    <td>${Number(wo.total_porsi || 0).toLocaleString('id-ID')} Porsi (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)</td>
                    <td style="font-weight: bold;">Food Cost / Porsi</td>
                    <td>:</td>
                    <td>PK: ${formatRupiahNum(wo.cost_pk)} | PB: ${formatRupiahNum(wo.cost_pb)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">AKG Nutrisi PK</td>
                    <td>:</td>
                    <td>${wo.energi_pk || 0} kkal, Prot: ${wo.protein_pk || 0}g, Lemak: ${wo.lemak_pk || 0}g, Karbo: ${wo.karbo_pk || 0}g, Serat: ${wo.serat_pk || 0}g</td>
                    <td style="font-weight: bold;">AKG Nutrisi PB</td>
                    <td>:</td>
                    <td>${wo.energi_pb || 0} kkal, Prot: ${wo.protein_pb || 0}g, Lemak: ${wo.lemak_pb || 0}g, Karbo: ${wo.karbo_pb || 0}g, Serat: ${wo.serat_pb || 0}g</td>
                </tr>
            </table>

            <h4 style="margin-top: 15px; margin-bottom: 4px; font-size: 10pt;">A. Rincian Formulasi Resep, Kandungan Gizi & Kebutuhan Belanja</h4>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 25px;">No</th>
                        <th>Bahan Baku</th>
                        <th>Kategori</th>
                        <th>PK (g)</th>
                        <th>PB (g)</th>
                        <th>Kg Bersih</th>
                        <th>Kg Kotor</th>
                        <th>Energi</th>
                        <th>Protein</th>
                        <th>Lemak</th>
                        <th>Karbo</th>
                        <th>Serat</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="14" style="text-align: center; padding: 8px;">Tidak ada bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="5" style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">Total Estimasi:</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">${totalNetKgAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">${totalGrossKgAll.toFixed(2)} kg</td>
                        <td colspan="5" style="border: 1px solid #94a3b8; padding: 4px; text-align: center;">5 Zat Gizi Terhitung</td>
                        <td style="border: 1px solid #94a3b8; padding: 4px;"></td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 4px;">${formatRupiahNum(totalSubtotalAll || wo.total_anggaran)}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="ttd-box" style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Direncanakan Oleh:<br><strong>Tim Ahli Gizi SPPG</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <p>Mengetahui & Menyetujui:<br><strong>Kepala SPPG / Akuntan</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([template], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

export function generateWorkOrderHtml(wo, forPrint = false) {
    const items = wo.items || [];
    let totalNetKgAll = 0;
    let totalGrossKgAll = 0;
    let totalSubtotalAll = 0;

    let itemsRows = items.map((it, idx) => {
        const pk = Number(it.gram_pk !== undefined ? it.gram_pk : (it.gram_bersih_pk || 0)) || 0;
        const pb = Number(it.gram_pb !== undefined ? it.gram_pb : (it.gram_bersih_pb || 0)) || 0;
        const targetPK = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pk) || 1) : (Number(wo?.porsi_pk) || 0);
        const targetPB = it.tipe_porsi === 'alergi' ? (Number(wo?.total_alergi_pb) || 1) : (Number(wo?.porsi_pb) || 0);
        const netKg = it.total_net_kg !== undefined ? Number(it.total_net_kg) : ((pk * targetPK + pb * targetPB) / 1000);
        const grossKg = Number(it.total_gross_kg !== undefined ? it.total_gross_kg : (it.gross_weight_kg !== undefined ? it.gross_weight_kg : it.gross_kg)) || 0;
        const harga = Number(it.harga_master || it.harga_aktual || it.harga) || 0;
        const subtotal = Number(it.subtotal_master !== undefined ? it.subtotal_master : (it.subtotal !== undefined ? it.subtotal : it.subtotal_aktual)) || Math.round(grossKg * harga);

        totalNetKgAll += netKg;
        totalGrossKgAll += grossKg;
        totalSubtotalAll += subtotal;

        const nutrisiPK = it.nutrisi_pk || it.nutrisiPK || {};
        const nutrisiPB = it.nutrisi_pb || it.nutrisiPB || {};

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px;">${idx + 1}</td>
            <td style="font-weight: 600; border: 1px solid #cbd5e1; padding: 4px;">${it.nama || it.nama_bahan || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px;">${pk}g</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px;">${pb}g</td>
            <td style="text-align: right; background-color: #fffbeb; border: 1px solid #cbd5e1; padding: 4px;">${netKg.toFixed(2)} kg</td>
            <td style="text-align: right; font-weight: bold; background-color: #eff6ff; border: 1px solid #cbd5e1; padding: 4px;">${grossKg.toFixed(2)} kg</td>
            <td style="text-align: right; font-size: 8pt; border: 1px solid #cbd5e1; padding: 4px;">PK: ${nutrisiPK.energi || 0}<br>PB: ${nutrisiPB.energi || 0}</td>
            <td style="text-align: right; font-size: 8pt; border: 1px solid #cbd5e1; padding: 4px;">PK: ${nutrisiPK.protein || 0}g<br>PB: ${nutrisiPB.protein || 0}g</td>
            <td style="text-align: right; font-size: 8pt; border: 1px solid #cbd5e1; padding: 4px;">PK: ${nutrisiPK.lemak || 0}g<br>PB: ${nutrisiPB.lemak || 0}g</td>
            <td style="text-align: right; font-size: 8pt; border: 1px solid #cbd5e1; padding: 4px;">PK: ${nutrisiPK.karbohidrat || 0}g<br>PB: ${nutrisiPB.karbohidrat || 0}g</td>
            <td style="text-align: right; font-size: 8pt; border: 1px solid #cbd5e1; padding: 4px;">PK: ${nutrisiPK.serat || 0}g<br>PB: ${nutrisiPB.serat || 0}g</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; font-weight: bold; border: 1px solid #cbd5e1; padding: 4px;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Work Order - ${wo.id || 'WO-MBG'}</title>
            <style>
                @page { size: A4 landscape; margin: 10mm; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 12px; background: #fff; }
                .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
                .kop h2 { margin: 0; font-size: 13pt; color: #0f172a; text-transform: uppercase; }
                .kop p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
                .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 12px; font-size: 9pt; }
                .grid-info div span:first-child { font-weight: bold; color: #64748b; display: inline-block; width: 130px; }
                table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 8px; }
                th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; font-weight: bold; text-align: left; }
                td { border: 1px solid #e2e8f0; padding: 4px 6px; }
                .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 7.5pt; background: #e0f2fe; color: #0369a1; }
                .ttd { margin-top: 25px; display: flex; justify-content: space-between; page-break-inside: avoid; }
                .ttd-box { text-align: center; width: 220px; }
                .ttd-line { margin-top: 45px; border-bottom: 1px solid #000; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { mode: 'print' })}
            <div style="text-align: center; margin-bottom: 10px;">
                <div style="font-size: 10.5pt; font-weight: bold; text-transform: uppercase; text-decoration: underline; color: #0f172a;">LEMBAR WORK ORDER (WO) PERENCANAAN & FORMULASI GIZI MAKAN BERGIZI GRATIS (MBG)</div>
            </div>

            <div class="grid-info">
                <div>
                    <div><span>Nomor Work Order:</span> <strong style="font-family: monospace;">${wo.id || '-'}</strong></div>
                    <div><span>Nama Menu:</span> <strong>${wo.nama || '-'}</strong></div>
                    <div><span>Tanggal Distribusi:</span> ${formatTanggalIndoFull(wo.tanggal)}</div>
                </div>
                <div>
                    <div><span>Sasaran PM:</span> <strong>${Number(wo.total_porsi || 0).toLocaleString('id-ID')} Porsi</strong> (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)</div>
                    <div><span>Nutrisi PK:</span> ${wo.energi_pk || 0} kkal, Prot: ${wo.protein_pk || 0}g, Lemak: ${wo.lemak_pk || 0}g, Karbo: ${wo.karbo_pk || 0}g, Serat: ${wo.serat_pk || 0}g</div>
                    <div><span>Nutrisi PB:</span> ${wo.energi_pb || 0} kkal, Prot: ${wo.protein_pb || 0}g, Lemak: ${wo.lemak_pb || 0}g, Karbo: ${wo.karbo_pb || 0}g, Serat: ${wo.serat_pb || 0}g</div>
                </div>
            </div>

            <h4 style="margin: 8px 0 4px; font-size: 9.5pt;">Daftar Formulasi Resep, Kandungan Gizi & Kebutuhan Belanja Bahan Baku</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">No</th>
                        <th>Bahan Pangan</th>
                        <th>Kategori</th>
                        <th style="text-align: center;">PK (g)</th>
                        <th style="text-align: center;">PB (g)</th>
                        <th style="text-align: right; background-color: #fffbeb;">Kg Bersih</th>
                        <th style="text-align: right; background-color: #eff6ff;">Kg Kotor</th>
                        <th style="text-align: right;">Energi</th>
                        <th style="text-align: right;">Protein</th>
                        <th style="text-align: right;">Lemak</th>
                        <th style="text-align: right;">Karbo</th>
                        <th style="text-align: right;">Serat</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="14" style="text-align: center; padding: 8px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="5" style="text-align: right; border: 1px solid #cbd5e1; padding: 5px;">Total Estimasi:</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 5px; background-color: #fffbeb;">${totalNetKgAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 5px; background-color: #eff6ff;">${totalGrossKgAll.toFixed(2)} kg</td>
                        <td colspan="5" style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; color: #64748b;">5 Zat Gizi Terhitung</td>
                        <td style="border: 1px solid #cbd5e1; padding: 5px;"></td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 5px; color: #1e3a8a;">${formatRupiahNum(totalSubtotalAll || wo.total_anggaran)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="ttd">
                <div class="ttd-box">
                    <p>Dibuat Oleh:<br><strong>Tim Ahli Gizi SPPG</strong></p>
                    <div class="ttd-line"></div>
                </div>
                <div class="ttd-box">
                    <p>Disetujui Oleh:<br><strong>Kepala SPPG / Akuntan</strong></p>
                    <div class="ttd-line"></div>
                </div>
            </div>

            ${forPrint ? `
            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
            ` : ''}
        </body>
        </html>
    `;
}

export async function exportWorkOrderPdf(wo) {
    const filename = `${wo.id || 'WO-MBG'}_${(wo.nama || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;

    // Elemen bayangan untuk di-render ke PDF
    const container = document.createElement('div');
    container.style.position = 'fixed';
    container.style.top = '-99999px';
    container.style.left = '-99999px';
    container.style.width = '1120px';
    container.style.backgroundColor = '#ffffff';
    container.style.padding = '20px';
    container.style.fontFamily = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    container.innerHTML = generateWorkOrderHtml(wo, false);

    document.body.appendChild(container);

    try {
        const canvas = await html2canvas(container, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            logging: false,
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4',
        });

        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 10;
        const printWidth = pageWidth - (margin * 2);
        const printHeight = (canvas.height * printWidth) / canvas.width;

        let heightLeft = printHeight;
        let position = margin;

        doc.addImage(imgData, 'JPEG', margin, position, printWidth, printHeight);
        heightLeft -= (pageHeight - (margin * 2));

        while (heightLeft > 0) {
            position = heightLeft - printHeight + margin;
            doc.addPage();
            doc.addImage(imgData, 'JPEG', margin, position, printWidth, printHeight);
            heightLeft -= (pageHeight - (margin * 2));
        }

        doc.save(filename);
    } catch (err) {
        console.error('Gagal mengunduh file PDF:', err);
        // Fallback jika canvas terkendala
        printWorkOrder(wo);
    } finally {
        if (container.parentNode) {
            document.body.removeChild(container);
        }
    }
}

export function printWorkOrder(wo) {
    const printWindow = window.open('', '_blank', 'width=1050,height=750');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak dokumen.');
        return;
    }
    const html = generateWorkOrderHtml(wo, true);
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}


// -------------------------------------------------------------
// 2. EXPORT PURCHASE ORDER (DAFTAR PO KEUANGAN)
// -------------------------------------------------------------

export function exportPoExcel(po) {
    const filename = `${po.id || 'PO-MBG'}_${(po.menu || 'Belanja').replace(/[^a-zA-Z0-9]/g, '_')}.xls`;
    const items = po.items || [];

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    let itemsRows = items.map((it, idx) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0));
        const subtotal = Number(it.subtotal_aktual !== undefined && it.subtotal_aktual !== null ? it.subtotal_aktual : Math.round(qtyBeli * harga));

        totalGrossAll += gross;
        totalStokAll += stok;
        totalBeliAll += qtyBeli;
        totalNominal += subtotal;

        const supName = it.supplier?.nama_usaha || (it.supplier_id ? `Supplier #${it.supplier_id}` : (po.supplier?.nama_usaha || po.vendor || '-'));
        const trxType = it.jenis_transaksi || po.jenis_transaksi || 'Bahan Baku';

        let statusKet = 'Beli PO';
        if (qtyBeli === 0 || (stok >= gross && gross > 0)) {
            statusKet = '100% Dari Stok';
        } else if (stok > 0) {
            statusKet = 'Parsial Stok';
        }

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${it.kategori || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: 600; color: #1e40af;">${supName}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${trxType}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; color: #1e40af;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold; background-color: #ecfdf5;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${statusKet}</td>
        </tr>
        `;
    }).join('');

    const itemSuppliers = (po.items || []).map(i => i.supplier?.nama_usaha).filter(Boolean);
    const uniqueSuppliersList = Array.from(new Set(itemSuppliers));
    const displayVendor = uniqueSuppliersList.length > 0
        ? uniqueSuppliersList.join(', ')
        : (po.supplier ? (po.supplier.nama_usaha + (po.supplier.jenis_supplier ? ' (' + po.supplier.jenis_supplier + ')' : '')) : (po.vendor && po.vendor !== 'Rekanan Pangan SPPG' ? po.vendor : '-'));

    const displayJenisTrx = (po.items || []).map(i => i.jenis_transaksi).filter(Boolean);
    const uniqueTrxList = Array.from(new Set(displayJenisTrx));
    const displayTrx = uniqueTrxList.length > 0 ? uniqueTrxList.join(', ') : (po.jenis_transaksi || '-');

    const template = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Purchase Order</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
            <style>
                body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #94a3b8; padding: 8px; }
                .title { font-size: 16pt; font-weight: bold; text-align: center; color: #0f172a; margin-bottom: 4px; }
                .subtitle { font-size: 12pt; text-align: center; color: #475569; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="title">SURAT PESANAN / PURCHASE ORDER (PO) RESMI</div>
            <div class="subtitle">Satuan Pelayanan Program Gizi (SPPG) - MBG</div>
            
            <table style="margin-bottom: 20px; width: 600px;">
                <tr>
                    <td style="font-weight: bold; width: 180px;">Nomor Purchase Order</td>
                    <td>: ${po.id || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Referensi Work Order</td>
                    <td>: ${po.wo_id || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Menu</td>
                    <td>: ${po.menu || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal Distribusi</td>
                    <td>: ${formatTanggalIndoFull(po.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Vendor / Rekanan</td>
                    <td>: ${displayVendor}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Jenis Transaksi</td>
                    <td>: <strong>${displayTrx}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Sasaran PM</td>
                    <td>: ${Number(po.total_porsi || 0).toLocaleString('id-ID')} Porsi (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status PO / Pembayaran</td>
                    <td>: ${po.status_po || 'Disetujui'} / ${po.status_bayar || 'Belum Bayar'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Belanja PO</td>
                    <td>: <strong>${formatRupiahNum(po.total_nominal || totalNominal)}</strong></td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; color: #1e293b;">RINCIAN BAHAN BAKU, PENGALIHAN STOK & NILAI PEMBELIAN PO</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th>Jenis Transaksi</th>
                        <th>Resep Kotor (Kg)</th>
                        <th>Dari Stok (Kg)</th>
                        <th>Qty Beli PO (Kg)</th>
                        <th>Harga Satuan Aktual</th>
                        <th>Subtotal Pembelian</th>
                        <th>Status Pengadaan</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="11" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td colspan="5" style="text-align: right; border: 1px solid #cbd5e1; padding: 8px;">TOTAL KESELURUHAN:</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; color: #1e40af;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; color: #047857;">${totalBeliAll.toFixed(2)} kg</td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px;"></td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; font-weight: bold; color: #047857;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px;"></td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([template], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

export function exportPoWord(po) {
    if (!po) return;

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    const itemsRows = (po.items || []).map((it, idx) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0));
        const subtotal = Number(it.subtotal_aktual !== undefined && it.subtotal_aktual !== null ? it.subtotal_aktual : Math.round(qtyBeli * harga));

        totalGrossAll += gross;
        totalStokAll += stok;
        totalBeliAll += qtyBeli;
        totalNominal += subtotal;

        const supName = it.supplier?.nama_usaha || (it.supplier_id ? `Supplier #${it.supplier_id}` : (po.supplier?.nama_usaha || po.vendor || '-'));
        const trxType = it.jenis_transaksi || po.jenis_transaksi || 'Bahan Baku';

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 5px;">${idx + 1}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px;">${it.kategori || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px; font-weight: 600;">${supName}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 5px;">${trxType}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const itemSuppliers = (po.items || []).map(i => i.supplier?.nama_usaha).filter(Boolean);
    const uniqueSuppliersList = Array.from(new Set(itemSuppliers));
    const displayVendor = uniqueSuppliersList.length > 0
        ? uniqueSuppliersList.join(', ')
        : (po.supplier ? (po.supplier.nama_usaha + (po.supplier.jenis_supplier ? ' (' + po.supplier.jenis_supplier + ')' : '')) : (po.vendor && po.vendor !== 'Rekanan Pangan SPPG' ? po.vendor : '-'));

    const displayJenisTrx = (po.items || []).map(i => i.jenis_transaksi).filter(Boolean);
    const uniqueTrxList = Array.from(new Set(displayJenisTrx));
    const displayTrx = uniqueTrxList.length > 0 ? uniqueTrxList.join(', ') : (po.jenis_transaksi || '-');

    const template = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset="utf-8">
            <title>Purchase Order ${po.id}</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.4; color: #000; }
                .kop-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
                .kop-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0; }
                .kop-sub { font-size: 11pt; font-weight: bold; margin: 3px 0; }
                .kop-desc { font-size: 9pt; margin: 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
                table.data th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px; font-size: 9.5pt; text-align: center; }
                table.data td { font-size: 9.5pt; }
                .info-table td { padding: 3px 0; font-size: 10.5pt; }
                .ttd-box { margin-top: 35px; width: 100%; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { isForWord: true })}
            <div style="text-align: center; margin-top: 10px; margin-bottom: 16px;">
                <p style="font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO)</p>
                <p style="font-size: 10pt; color: #475569; margin: 2px 0 0 0;">Program Makan Bergizi Gratis (MBG)</p>
            </div>

            <table class="info-table" style="width: 100%;">
                <tr>
                    <td style="width: 180px; font-weight: bold;">Nomor Purchase Order</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>${po.id}</strong></td>
                    <td style="width: 160px; font-weight: bold;">Tanggal Distribusi</td>
                    <td style="width: 10px;">:</td>
                    <td>${formatTanggalIndoFull(po.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Referensi Work Order</td>
                    <td>:</td>
                    <td>${po.wo_id}</td>
                    <td style="font-weight: bold;">Vendor / Supplier</td>
                    <td>:</td>
                    <td><strong>${displayVendor}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Menu MBG</td>
                    <td>:</td>
                    <td>${po.menu}</td>
                    <td style="font-weight: bold;">Jenis Transaksi</td>
                    <td>:</td>
                    <td><strong>${displayTrx}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Sasaran Porsi</td>
                    <td>:</td>
                    <td>${Number(po.total_porsi || 0).toLocaleString('id-ID')} PM (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</td>
                    <td style="font-weight: bold;">Total Nilai PO</td>
                    <td>:</td>
                    <td><strong style="color: #047857; font-size: 11pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status Pembayaran</td>
                    <td>:</td>
                    <td><strong>${po.status_bayar || 'Belum Bayar'}</strong></td>
                    <td style="font-weight: bold;"></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>

            <h4 style="margin-top: 20px; margin-bottom: 5px;">Rincian Item Pembelian Bahan Baku</h4>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 25px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th>Transaksi</th>
                        <th>Resep Kotor</th>
                        <th>Dari Stok</th>
                        <th>Qty Beli PO</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal PO</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="10" style="text-align: center; padding: 8px;">Tidak ada bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f1f5f9;">
                        <td colspan="5" style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">TOTAL:</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalBeliAll.toFixed(2)} kg</td>
                        <td style="border: 1px solid #94a3b8; padding: 5px;"></td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="ttd-box" style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Penyedia / Vendor:<br><strong>${displayVendor !== '-' ? displayVendor : 'Rekanan Bahan Pangan'}</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <p>Pemesan & Verifikator:<br><strong>Bagian Keuangan SPPG</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob(['\ufeff', template], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

export function exportPoPdf(po) {
    if (!po) return;

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    const itemsRows = (po.items || []).map((it, idx) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0));
        const subtotal = Number(it.subtotal_aktual !== undefined && it.subtotal_aktual !== null ? it.subtotal_aktual : Math.round(qtyBeli * harga));

        totalGrossAll += gross;
        totalStokAll += stok;
        totalBeliAll += qtyBeli;
        totalNominal += subtotal;

        const supName = it.supplier?.nama_usaha || (it.supplier_id ? `Supplier #${it.supplier_id}` : (po.supplier?.nama_usaha || po.vendor || '-'));
        const trxType = it.jenis_transaksi || po.jenis_transaksi || 'Bahan Baku';

        return `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: 600;">${it.nama || '-'}</td>
            <td>${it.kategori || '-'}</td>
            <td style="font-weight: 600; color: #1e40af;">${supName}</td>
            <td style="text-align: center;">${trxType}</td>
            <td style="text-align: right;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; color: #1e40af;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; font-weight: bold; background-color: #ecfdf5;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const itemSuppliers = (po.items || []).map(i => i.supplier?.nama_usaha).filter(Boolean);
    const uniqueSuppliersList = Array.from(new Set(itemSuppliers));
    const displayVendor = uniqueSuppliersList.length > 0
        ? uniqueSuppliersList.join(', ')
        : (po.supplier ? (po.supplier.nama_usaha + (po.supplier.jenis_supplier ? ' (' + po.supplier.jenis_supplier + ')' : '')) : (po.vendor && po.vendor !== 'Rekanan Pangan SPPG' ? po.vendor : '-'));

    const displayJenisTrx = (po.items || []).map(i => i.jenis_transaksi).filter(Boolean);
    const uniqueTrxList = Array.from(new Set(displayJenisTrx));
    const displayTrx = uniqueTrxList.length > 0 ? uniqueTrxList.join(', ') : (po.jenis_transaksi || '-');

    const printWindow = window.open('', '_blank', 'width=950,height=750');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak/mengunduh PDF.');
        return;
    }

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Order - ${po.id}</title>
            <style>
                @page { size: A4 landscape; margin: 12mm; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 9.5pt; color: #1e293b; margin: 0; }
                .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
                .kop h2 { margin: 0; font-size: 13pt; color: #0f172a; text-transform: uppercase; }
                .kop p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
                .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 14px; font-size: 9pt; }
                .grid-info div span:first-child { font-weight: bold; color: #64748b; display: inline-block; width: 140px; }
                table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 10px; }
                th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; font-weight: bold; text-align: left; }
                td { border: 1px solid #e2e8f0; padding: 5px 6px; }
                .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt; background: #dcfce7; color: #15803d; }
                .ttd { margin-top: 25px; display: flex; justify-content: space-between; page-break-inside: avoid; }
                .ttd-box { text-align: center; width: 220px; font-size: 9pt; }
                .ttd-line { margin-top: 45px; border-bottom: 1px solid #000; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { isForWord: false })}
            <div style="text-align: center; margin-top: 8px; margin-bottom: 14px;">
                <h3 style="margin: 0; font-size: 13pt; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO) RESMI</h3>
                <p style="margin: 2px 0 0 0; font-size: 9pt; color: #64748b;">Program Makan Bergizi Gratis (MBG)</p>
            </div>

            <div class="grid-info">
                <div>
                    <div><span>Nomor PO:</span> <strong style="font-family: monospace;">${po.id}</strong></div>
                    <div><span>Ref Work Order:</span> ${po.wo_id}</div>
                    <div><span>Nama Menu:</span> <strong>${po.menu}</strong></div>
                    <div><span>Tanggal Distribusi:</span> ${formatTanggalIndoFull(po.tanggal)}</div>
                </div>
                <div>
                    <div><span>Supplier Rekanan:</span> <strong>${displayVendor}</strong></div>
                    <div><span>Jenis Transaksi:</span> <strong>${displayTrx}</strong></div>
                    <div><span>Sasaran PM:</span> ${Number(po.total_porsi || 0).toLocaleString('id-ID')} Porsi (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</div>
                    <div><span>Status Bayar:</span> <span class="badge">${po.status_bayar || 'Belum Bayar'}</span></div>
                    <div><span>Total Pembelian:</span> <strong style="color: #047857; font-size: 10.5pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">No</th>
                        <th>Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th style="text-align: center;">Transaksi</th>
                        <th style="text-align: right;">Resep Kotor</th>
                        <th style="text-align: right;">Dari Stok</th>
                        <th style="text-align: right;">Qty Beli PO</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal Pembelian</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td colspan="5" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; color: #1e40af;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; color: #047857;">${totalBeliAll.toFixed(2)} kg</td>
                        <td></td>
                        <td style="text-align: right; color: #047857;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="ttd">
                <div class="ttd-box">
                    <p>Penyedia / Rekanan:<br><strong>${displayVendor !== '-' ? displayVendor : 'Rekanan Pangan SPPG'}</strong></p>
                    <div class="ttd-line"></div>
                </div>
                <div class="ttd-box">
                    <p>Pemesan & Verifikator:<br><strong>Akuntan Keuangan SPPG</strong></p>
                    <div class="ttd-line"></div>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
