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

    let itemsRows = items.map((it, idx) => `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${it.nama || it.nama_bahan || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.tipe_porsi || 'Semua'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.gram_bersih_pk || 0} g</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.gram_bersih_pb || 0} g</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.bdd || 100}%</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${Number(it.gross_weight_kg || it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${formatRupiahNum(it.harga_master || it.harga || 0)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${formatRupiahNum(it.subtotal || ((it.gross_weight_kg || it.gross_kg || 0) * (it.harga_master || 0)))}</td>
        </tr>
    `).join('');

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
                th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #94a3b8; padding: 8px; }
                .title { font-size: 16pt; font-weight: bold; text-align: center; color: #0f172a; margin-bottom: 4px; }
                .subtitle { font-size: 12pt; text-align: center; color: #475569; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="title">LEMBAR PERENCANAAN PRODUKSI & WORK ORDER (WO) MENU MBG</div>
            <div class="subtitle">Sistem Pengelolaan Pelayanan Gizi (SIPEGE)</div>
            
            <table style="margin-bottom: 20px; width: 600px;">
                <tr>
                    <td style="font-weight: bold; width: 180px;">Nomor Work Order</td>
                    <td>: ${wo.id || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Menu</td>
                    <td>: ${wo.nama || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal Distribusi</td>
                    <td>: ${formatTanggalIndoFull(wo.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Sasaran PM</td>
                    <td>: ${Number(wo.total_porsi || 0).toLocaleString('id-ID')} Porsi (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status Menu</td>
                    <td>: ${wo.status_wo || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">AKG Nutrisi PK</td>
                    <td>: ${wo.energi_pk || 0} kkal / ${wo.protein_pk || 0}g Protein</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">AKG Nutrisi PB</td>
                    <td>: ${wo.energi_pb || 0} kkal / ${wo.protein_pb || 0}g Protein</td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; color: #1e293b;">A. DAFTAR FORMULASI BAHAN BAKU & KEBUTUHAN BELANJA</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Bahan Pangan (TKPI)</th>
                        <th>Kategori</th>
                        <th>Peruntukan</th>
                        <th>Gram Bersih PK</th>
                        <th>Gram Bersih PB</th>
                        <th>BDD (%)</th>
                        <th>Kebutuhan Kotor (Kg)</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal Estimasi</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="10" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
            </table>

            ${kelRows ? `
            <h3 style="margin-top: 25px; color: #1e293b;">B. DAFTAR KELOMPOK SASARAN PENERIMA MANFAAT</h3>
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

    let itemsRows = items.map((it, idx) => `
        <tr>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${it.nama || it.nama_bahan || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 6px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${it.tipe_porsi || 'Semua'}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${it.gram_bersih_pk || 0}g</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${it.gram_bersih_pb || 0}g</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${Number(it.gross_weight_kg || it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px;">${formatRupiahNum(it.harga_master || it.harga || 0)}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${formatRupiahNum(it.subtotal || ((it.gross_weight_kg || it.gross_kg || 0) * (it.harga_master || 0)))}</td>
        </tr>
    `).join('');

    const template = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset="utf-8">
            <title>Work Order ${wo.id}</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.4; color: #000; }
                .kop-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
                .kop-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0; }
                .kop-sub { font-size: 11pt; font-weight: bold; margin: 3px 0; }
                .kop-desc { font-size: 9pt; margin: 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
                table.data th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px; font-size: 10pt; text-align: center; }
                table.data td { font-size: 10pt; }
                .info-table td { padding: 3px 0; font-size: 10.5pt; }
                .ttd-box { margin-top: 35px; width: 100%; }
            </style>
        </head>
        <body>
            <div class="kop-header">
                <p class="kop-title">PROGRAM MAKAN BERGIZI GRATIS (MBG)</p>
                <p class="kop-sub">SATUAN PELAYANAN PROGRAM GIZI (SPPG)</p>
                <p class="kop-desc">LEMBAR PERENCANAAN WORK ORDER & FORMULASI GIZI MENU HARIAN</p>
            </div>

            <table class="info-table" style="width: 100%;">
                <tr>
                    <td style="width: 180px; font-weight: bold;">Nomor Work Order</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>${wo.id}</strong></td>
                    <td style="width: 160px; font-weight: bold;">Tanggal Distribusi</td>
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
                    <td style="font-weight: bold;">Biaya / Porsi</td>
                    <td>:</td>
                    <td>PK: ${formatRupiahNum(wo.cost_pk)} | PB: ${formatRupiahNum(wo.cost_pb)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">AKG Gizi PK</td>
                    <td>:</td>
                    <td>${wo.energi_pk || 0} kkal, ${wo.protein_pk || 0}g Prot</td>
                    <td style="font-weight: bold;">AKG Gizi PB</td>
                    <td>:</td>
                    <td>${wo.energi_pb || 0} kkal, ${wo.protein_pb || 0}g Prot</td>
                </tr>
            </table>

            <h4 style="margin-top: 20px; margin-bottom: 5px;">A. Rincian Formulasi Resep & Kebutuhan Belanja Bahan Baku</h4>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Porsi</th>
                        <th>PK (g)</th>
                        <th>PB (g)</th>
                        <th>Kg Kotor</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal Estimasi</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="9" style="text-align: center; padding: 8px;">Tidak ada bahan</td></tr>'}
                </tbody>
            </table>

            <table class="ttd-box" style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Direncanakan Oleh:<br><strong>Ahli Gizi SPPG</strong></p>
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

export function exportWorkOrderPdf(wo) {
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak/mengunduh PDF.');
        return;
    }

    const items = wo.items || [];
    let itemsRows = items.map((it, idx) => `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: 600;">${it.nama || it.nama_bahan || '-'}</td>
            <td>${it.kategori || '-'}</td>
            <td style="text-align: center;">${it.tipe_porsi || 'Semua'}</td>
            <td style="text-align: center;">${it.gram_bersih_pk || 0}g</td>
            <td style="text-align: center;">${it.gram_bersih_pb || 0}g</td>
            <td style="text-align: right; font-weight: bold;">${Number(it.gross_weight_kg || it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right;">${formatRupiahNum(it.harga_master || it.harga || 0)}</td>
            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(it.subtotal || ((it.gross_weight_kg || it.gross_kg || 0) * (it.harga_master || 0)))}</td>
        </tr>
    `).join('');

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Work Order - ${wo.id}</title>
            <style>
                @page { size: A4 portrait; margin: 15mm; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; }
                .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
                .kop h2 { margin: 0; font-size: 14pt; color: #0f172a; text-transform: uppercase; }
                .kop p { margin: 2px 0 0; font-size: 9pt; color: #475569; }
                .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 16px; font-size: 9.5pt; }
                .grid-info div span:first-child { font-weight: bold; color: #64748b; display: inline-block; width: 140px; }
                table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 10px; }
                th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 8px; font-weight: bold; text-align: left; }
                td { border: 1px solid #e2e8f0; padding: 6px 8px; }
                .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt; background: #e0f2fe; color: #0369a1; }
                .ttd { margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid; }
                .ttd-box { text-align: center; width: 220px; }
                .ttd-line { margin-top: 50px; border-bottom: 1px solid #000; }
            </style>
        </head>
        <body>
            <div class="kop">
                <h2>SATUAN PELAYANAN PROGRAM GIZI (SPPG)</h2>
                <p>LEMBAR WORK ORDER (WO) PERENCANAAN & FORMULASI GIZI MAKAN BERGIZI GRATIS (MBG)</p>
            </div>

            <div class="grid-info">
                <div>
                    <div><span>Nomor Work Order:</span> <strong style="font-family: monospace;">${wo.id}</strong></div>
                    <div><span>Nama Menu:</span> <strong>${wo.nama}</strong></div>
                    <div><span>Tanggal Distribusi:</span> ${formatTanggalIndoFull(wo.tanggal)}</div>
                </div>
                <div>
                    <div><span>Sasaran PM:</span> <strong>${Number(wo.total_porsi || 0).toLocaleString('id-ID')} Porsi</strong> (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)</div>
                    <div><span>Nutrisi PK / PB:</span> ${wo.energi_pk || 0} kkal / ${wo.energi_pb || 0} kkal</div>
                    <div><span>Status Menu:</span> <span class="badge">${wo.status_wo}</span></div>
                </div>
            </div>

            <h4 style="margin: 12px 0 6px; font-size: 10pt;">Daftar Formulasi Resep & Kebutuhan Belanja Bahan Baku</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: center;">No</th>
                        <th>Bahan Pangan</th>
                        <th>Kategori</th>
                        <th style="text-align: center;">Porsi</th>
                        <th style="text-align: center;">PK (g)</th>
                        <th style="text-align: center;">PB (g)</th>
                        <th style="text-align: right;">Kg Kotor</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="9" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
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

            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>
    `;

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
    let itemsRows = items.map((it, idx) => {
        const subtotal = it.subtotal_aktual || (it.gross_kg * (it.harga_aktual || it.harga_master || 0));
        totalNominal += Number(subtotal) || 0;
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${it.tipe || 'PK/PB'}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${Number(it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${formatRupiahNum(it.harga_aktual || it.harga_master || 0)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

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
                    <td>: ${po.vendor || 'Rekanan Pangan SPPG'}</td>
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

            <h3 style="margin-top: 20px; color: #1e293b;">RINCIAN BAHAN BAKU & NILAI PEMBELIAN</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Peruntukan</th>
                        <th>Kuantitas Kotor (Kg)</th>
                        <th>Harga Satuan Aktual</th>
                        <th>Subtotal Pembelian</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="7" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td colspan="6" style="text-align: right; border: 1px solid #cbd5e1; padding: 8px;">TOTAL BELANJA PO:</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; font-weight: bold; color: #047857;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
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
    const filename = `${po.id || 'PO-MBG'}_${(po.menu || 'Belanja').replace(/[^a-zA-Z0-9]/g, '_')}.doc`;
    const items = po.items || [];

    let totalNominal = 0;
    let itemsRows = items.map((it, idx) => {
        const subtotal = it.subtotal_aktual || (it.gross_kg * (it.harga_aktual || it.harga_master || 0));
        totalNominal += Number(subtotal) || 0;
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 6px;">${it.kategori || '-'}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 6px;">${it.tipe || 'PK/PB'}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${Number(it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px;">${formatRupiahNum(it.harga_aktual || it.harga_master || 0)}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

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
                table.data th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px; font-size: 10pt; text-align: center; }
                table.data td { font-size: 10pt; }
                .info-table td { padding: 3px 0; font-size: 10.5pt; }
                .ttd-box { margin-top: 35px; width: 100%; }
            </style>
        </head>
        <body>
            <div class="kop-header">
                <p class="kop-title">PROGRAM MAKAN BERGIZI GRATIS (MBG)</p>
                <p class="kop-sub">SATUAN PELAYANAN PROGRAM GIZI (SPPG)</p>
                <p class="kop-desc">SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO)</p>
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
                    <td style="font-weight: bold;">Vendor / Rekanan</td>
                    <td>:</td>
                    <td><strong>${po.vendor || 'Rekanan Pangan SPPG'}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Menu MBG</td>
                    <td>:</td>
                    <td>${po.menu}</td>
                    <td style="font-weight: bold;">Sasaran Porsi</td>
                    <td>:</td>
                    <td>${Number(po.total_porsi || 0).toLocaleString('id-ID')} PM (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status Pembayaran</td>
                    <td>:</td>
                    <td><strong>${po.status_bayar || 'Belum Bayar'}</strong></td>
                    <td style="font-weight: bold;">Total Nilai PO</td>
                    <td>:</td>
                    <td><strong style="color: #047857; font-size: 11pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></td>
                </tr>
            </table>

            <h4 style="margin-top: 20px; margin-bottom: 5px;">Rincian Item Pembelian Bahan Baku</h4>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Porsi</th>
                        <th>Kg Kotor</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal Pembelian</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="7" style="text-align: center; padding: 8px;">Tidak ada bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f1f5f9;">
                        <td colspan="6" style="text-align: right; border: 1px solid #94a3b8; padding: 6px;">TOTAL PO:</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 6px;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="ttd-box" style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Penyedia / Vendor:<br><strong>Rekanan Bahan Pangan</strong></p>
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

export function exportPoPdf(po) {
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak/mengunduh PDF.');
        return;
    }

    const items = po.items || [];
    let totalNominal = 0;
    let itemsRows = items.map((it, idx) => {
        const subtotal = it.subtotal_aktual || (it.gross_kg * (it.harga_aktual || it.harga_master || 0));
        totalNominal += Number(subtotal) || 0;
        return `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: 600;">${it.nama || '-'}</td>
            <td>${it.kategori || '-'}</td>
            <td style="text-align: center;">${it.tipe || 'PK/PB'}</td>
            <td style="text-align: right; font-weight: bold;">${Number(it.gross_kg || 0).toFixed(2)} kg</td>
            <td style="text-align: right;">${formatRupiahNum(it.harga_aktual || it.harga_master || 0)}</td>
            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Order - ${po.id}</title>
            <style>
                @page { size: A4 portrait; margin: 15mm; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; }
                .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
                .kop h2 { margin: 0; font-size: 14pt; color: #0f172a; text-transform: uppercase; }
                .kop p { margin: 2px 0 0; font-size: 9pt; color: #475569; }
                .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 16px; font-size: 9.5pt; }
                .grid-info div span:first-child { font-weight: bold; color: #64748b; display: inline-block; width: 140px; }
                table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 10px; }
                th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 8px; font-weight: bold; text-align: left; }
                td { border: 1px solid #e2e8f0; padding: 6px 8px; }
                .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt; background: #dcfce7; color: #15803d; }
                .ttd { margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid; }
                .ttd-box { text-align: center; width: 220px; }
                .ttd-line { margin-top: 50px; border-bottom: 1px solid #000; }
            </style>
        </head>
        <body>
            <div class="kop">
                <h2>SATUAN PELAYANAN PROGRAM GIZI (SPPG)</h2>
                <p>SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO) RESMI MBG</p>
            </div>

            <div class="grid-info">
                <div>
                    <div><span>Nomor PO:</span> <strong style="font-family: monospace;">${po.id}</strong></div>
                    <div><span>Ref Work Order:</span> ${po.wo_id}</div>
                    <div><span>Nama Menu:</span> <strong>${po.menu}</strong></div>
                    <div><span>Tanggal Distribusi:</span> ${formatTanggalIndoFull(po.tanggal)}</div>
                </div>
                <div>
                    <div><span>Vendor / Rekanan:</span> <strong>${po.vendor || 'Rekanan Pangan SPPG'}</strong></div>
                    <div><span>Sasaran PM:</span> ${Number(po.total_porsi || 0).toLocaleString('id-ID')} Porsi (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</div>
                    <div><span>Status Bayar:</span> <span class="badge">${po.status_bayar || 'Belum Bayar'}</span></div>
                    <div><span>Total Pembelian:</span> <strong style="color: #047857; font-size: 11pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></div>
                </div>
            </div>

            <h4 style="margin: 12px 0 6px; font-size: 10pt;">Daftar Item Belanja Bahan Baku PO</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: center;">No</th>
                        <th>Bahan Pangan</th>
                        <th>Kategori</th>
                        <th style="text-align: center;">Peruntukan</th>
                        <th style="text-align: right;">Kuantitas (Kg)</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="7" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td colspan="6" style="text-align: right;">TOTAL ESTIMASI PEMBELIAN PO:</td>
                        <td style="text-align: right; color: #047857; font-size: 10pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="ttd">
                <div class="ttd-box">
                    <p>Vendor Rekanan Pangan:<br><br></p>
                    <div class="ttd-line"></div>
                </div>
                <div class="ttd-box">
                    <p>Bagian Keuangan SPPG:<br><br></p>
                    <div class="ttd-line"></div>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                }
            </script>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
