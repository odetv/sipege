/**
 * Helper & Master Definisi Tabel Bantu Rincian Pengeluaran Operasional SPPG
 * Sesuai template Excel rincian operasional harian, mingguan, periode (10 hari), dan bulanan.
 */

export const DEFAULT_TABEL_BANTU_OPERASIONAL = [
    { no: 1, nama: "Gaji Pengawas Gizi", harga_pokok: 5000000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 17, is_auto_supported: true },
    { no: 2, nama: "Gaji Pengawas Keuangan", harga_pokok: 5000000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 17, is_auto_supported: true },
    { no: 3, nama: "Gaji Relawan", harga_pokok: 2270000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 1, is_auto_supported: true },
    { no: 4, nama: "Insentif PIC Satuan Pendidikan", harga_pokok: 350000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 2, is_auto_supported: true },
    { no: 5, nama: "Insentif PIC Satuan Posyandu", harga_pokok: 128000, qty_hari: 0, qty_minggu: 2, qty_periode: 0, qty_bulan: 0, pos_ref: 3, is_auto_supported: true },
    { no: 6, nama: "BBM", harga_pokok: 300000, qty_hari: 0, qty_minggu: 2, qty_periode: 0, qty_bulan: 0, pos_ref: 8 },
    { no: 7, nama: "Listrik", harga_pokok: 2001750, qty_hari: 0, qty_minggu: 0, qty_periode: 1, qty_bulan: 0, pos_ref: 5 },
    { no: 8, nama: "Air", harga_pokok: 700000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 7 },
    { no: 9, nama: "Wifi", harga_pokok: 259740, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 9 },
    { no: 10, nama: "Iuran Sampah", harga_pokok: 750000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 10 },
    { no: 11, nama: "Iuran BPJS", harga_pokok: 722399, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 4, is_auto_supported: true },
    { no: 12, nama: "Pulpen", harga_pokok: 10000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 13, nama: "Map Berkas", harga_pokok: 35000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 14, nama: "Kertas HVS 1 Rim", harga_pokok: 43000, qty_hari: 0, qty_minggu: 6, qty_periode: 0, qty_bulan: 0, pos_ref: 12 },
    { no: 15, nama: "Isolasi Fresh Segel", harga_pokok: 900000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 16, nama: "Isolasi Bening", harga_pokok: 15000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 17, nama: "Tinta Printer", harga_pokok: 100000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 4, pos_ref: 12 },
    { no: 18, nama: "Air Aqua Galon", harga_pokok: 20000, qty_hari: 8, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 7 },
    { no: 19, nama: "Gas Elpiji 12kg", harga_pokok: 245000, qty_hari: 3, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 6 },
    { no: 20, nama: "Tali Rafia Hitam", harga_pokok: 100000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 21, nama: "Tali Rafia Hijau", harga_pokok: 100000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 22, nama: "Tali Rafia Merah", harga_pokok: 100000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 12 },
    { no: 23, nama: "APD Masker Earloop", harga_pokok: 20000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 14 },
    { no: 24, nama: "APD Masker Hijab", harga_pokok: 35000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 14 },
    { no: 25, nama: "APD Slop Tangan Nitrile", harga_pokok: 60000, qty_hari: 2, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 14 },
    { no: 26, nama: "APD Hair Cap", harga_pokok: 40000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 14 },
    { no: 27, nama: "APD Selop Tangan Plastik Food Grade", harga_pokok: 9000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 14 },
    { no: 28, nama: "Trash Bag Size Sedang", harga_pokok: 41000, qty_hari: 0, qty_minggu: 0, qty_periode: 1, qty_bulan: 0, pos_ref: 13 },
    { no: 29, nama: "Trash Bag Size Besar", harga_pokok: 41000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 30, nama: "Keresek Merah", harga_pokok: 30000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 31, nama: "Plastik Es", harga_pokok: 14000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 13 },
    { no: 32, nama: "Cairan Pembersih Lantai", harga_pokok: 13000, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 33, nama: "Cairan Pembersih Tangan", harga_pokok: 23000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 34, nama: "Cairan Pembersih Kamar Mandi", harga_pokok: 30000, qty_hari: 0, qty_minggu: 0, qty_periode: 1, qty_bulan: 0, pos_ref: 13 },
    { no: 35, nama: "Cairan Pembersih Kaca", harga_pokok: 8000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 13 },
    { no: 36, nama: "Cairan Pembersih Meja Stainless", harga_pokok: 17000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 37, nama: "Sabun Pembersih Peralatan Food Grade", harga_pokok: 9000, qty_hari: 8, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 38, nama: "Sabun Cuci Mobil", harga_pokok: 20000, qty_hari: 0, qty_minggu: 0, qty_periode: 1, qty_bulan: 0, pos_ref: 13 },
    { no: 39, nama: "Sabun Cuci Lap", harga_pokok: 20000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 40, nama: "Tissu Facial", harga_pokok: 32000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 41, nama: "Tissu Makan", harga_pokok: 23000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 42, nama: "Tissu Gulung", harga_pokok: 2400, qty_hari: 1, qty_minggu: 0, qty_periode: 0, qty_bulan: 0, pos_ref: 13 },
    { no: 43, nama: "Wrap Makanan", harga_pokok: 150000, qty_hari: 0, qty_minggu: 0, qty_periode: 0, qty_bulan: 1, pos_ref: 13 },
    { no: 44, nama: "Uang Perjalanan Survei Pasar", harga_pokok: 50000, qty_hari: 0, qty_minggu: 2, qty_periode: 0, qty_bulan: 0, pos_ref: 15 },
    { no: 45, nama: "Lainnya", harga_pokok: 100000, qty_hari: 0, qty_minggu: 1, qty_periode: 0, qty_bulan: 0, pos_ref: 16 },
];

export function getAutoValueForBantuItem(no, auto = {}) {
    if (no === 1) return 5000000; // Gaji Pengawas Gizi
    if (no === 2) return 5000000; // Gaji Pengawas Keuangan
    if (no === 3) return Number(auto.relawan_gaji_harian) || 2270000; // Gaji Relawan (harian)
    if (no === 4) return Number(auto.satuan_pendidikan_harian) || 350000; // Insentif PJ Satuan Pendidikan (harian)
    if (no === 5) return Number(auto.insentif_3b_harian) || 128000; // Insentif PJ Posyandu / 3B
    if (no === 11) {
        return Number(auto.bpjs_tk_total) || (Number(auto.petugas_aktif_count || 37) * 16800) || 722399; // BPJS TK
    }
    return null;
}

export function getDefaultTabelBantuOperasional(auto = {}) {
    const items = JSON.parse(JSON.stringify(DEFAULT_TABEL_BANTU_OPERASIONAL));

    // 1. Gaji Pengawas Gizi (Rp 5.000.000)
    items[0].harga_pokok = 5000000;
    items[0].is_auto_supported = true;
    items[0].rumus_dasar = "Honorarium Bulanan Pengawas Gizi (Rp 5.000.000)";

    // 2. Gaji Pengawas Keuangan (Rp 5.000.000)
    items[1].harga_pokok = 5000000;
    items[1].is_auto_supported = true;
    items[1].rumus_dasar = "Honorarium Bulanan Pengawas Keuangan (Rp 5.000.000)";

    // 3. Gaji Relawan
    const relawanHarian = Number(auto.relawan_gaji_harian) || 2270000;
    items[2].harga_pokok = relawanHarian;
    items[2].is_auto_supported = true;
    items[2].rumus_dasar = `${auto.relawan_count || 34} Relawan aktif SPPG (${relawanHarian.toLocaleString('id-ID')}/hari)`;

    // 4. Insentif PIC Satuan Pendidikan
    const sekolahHarian = Number(auto.satuan_pendidikan_harian) || 350000;
    items[3].harga_pokok = sekolahHarian;
    items[3].is_auto_supported = true;
    items[3].rumus_dasar = `${auto.satuan_pendidikan_count || 14} Satuan Pendidikan (${sekolahHarian.toLocaleString('id-ID')}/hari)`;

    // 5. Insentif PIC Satuan Posyandu (3B)
    const insentif3b = Number(auto.insentif_3b_harian) || 128000;
    items[4].harga_pokok = insentif3b;
    items[4].is_auto_supported = true;
    items[4].rumus_dasar = `Sasaran 3B (${auto.total_pm_3b || 326} PM)`;

    // 11. Iuran BPJS TK (Bulanan)
    const bpjsTk = Number(auto.bpjs_tk_total) || (Number(auto.petugas_aktif_count || 37) * 16800) || 722399;
    items[10].harga_pokok = bpjsTk;
    items[10].is_auto_supported = true;
    items[10].rumus_dasar = `${auto.petugas_aktif_count || 37} Petugas SPPG aktif × Iuran BPJS TK`;

    return items;
}

export function calculateTabelBantuItem(item, hariEfektif = 20) {
    const hp = Number(item.harga_pokok) || 0;
    const qHari = Number(item.qty_hari) || 0;
    const qMinggu = Number(item.qty_minggu) || 0;
    const qPeriode = Number(item.qty_periode) || 0;
    const qBulan = Number(item.qty_bulan) || 0;

    const nomHari = hp * qHari;
    const nomMinggu = hp * qMinggu;
    const nomPeriode = hp * qPeriode;
    const nomBulan = hp * qBulan;

    const rerataHarian = (nomHari / 1) + (nomMinggu / 5) + (nomPeriode / 10) + (nomBulan / 20);
    const totalPengeluaran = nomHari + nomMinggu + nomPeriode + nomBulan;
    const estimasiBulanan = Math.round(rerataHarian * (Number(hariEfektif) || 20));

    return {
        ...item,
        hp,
        qHari,
        qMinggu,
        qPeriode,
        qBulan,
        nomHari,
        nomMinggu,
        nomPeriode,
        nomBulan,
        rerataHarian,
        totalPengeluaran,
        estimasiBulanan,
    };
}

