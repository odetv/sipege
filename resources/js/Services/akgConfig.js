// =======================================================================
// DATASET RUJUKAN ANGKA KECUKUPAN GIZI (AKG) SATU KALI MBG
// Mengikuti Peraturan Kementerian Kesehatan Nomor 28 Tahun 2019
// =======================================================================

export const DASAR_HUKUM_AKG = "Peraturan Kementerian Kesehatan Republik Indonesia Nomor 28 Tahun 2019 tentang Angka Kecukupan Gizi yang Dianjurkan untuk Masyarakat Indonesia";

export const RUJUKAN_AKG_MBG = [
    {
        no: 1,
        id: "tk_paud",
        kelompokSasaran: "PM TK/PAUD/TK LB",
        pendistribusian: "Pagi",
        rujukanAkgPct: "20-25%",
        energiMin: 280,
        energiMax: 350,
        proteinMin: 5.0,
        proteinMax: 6.3,
        lemakMin: 10.0,
        lemakMax: 12.5,
        karbohidratMin: 44.0,
        karbohidratMax: 55.0,
        tipePorsi: "Porsi Kecil",
        keterangan: "Pemberian sarapan / kudapan pagi bernutrisi untuk anak usia dini"
    },
    {
        no: 2,
        id: "sd_kelas_1_3",
        kelompokSasaran: "PM SD/MI/SDLB Kelas 1-3",
        pendistribusian: "Pagi",
        rujukanAkgPct: "20-25%",
        energiMin: 330,
        energiMax: 413,
        proteinMin: 8.0,
        proteinMax: 10.0,
        lemakMin: 11.0,
        lemakMax: 13.8,
        karbohidratMin: 50.0,
        karbohidratMax: 62.5,
        tipePorsi: "Porsi Kecil",
        keterangan: "Porsi Kecil (PK) standar sekolah dasar kelas awal"
    },
    {
        no: 3,
        id: "sd_kelas_4_6",
        kelompokSasaran: "PM SD/MI/SDLB Kelas 4-6",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 585,
        energiMax: 683,
        proteinMin: 15.8,
        proteinMax: 18.4,
        lemakMin: 19.5,
        lemakMax: 22.8,
        karbohidratMin: 87.0,
        karbohidratMax: 101.5,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi Besar (PB) standar sekolah dasar kelas atas"
    },
    {
        no: 4,
        id: "smp_mts",
        kelompokSasaran: "PM SMP/MTS/SMPLB",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 668,
        energiMax: 779,
        proteinMin: 20.3,
        proteinMax: 23.6,
        lemakMin: 22.5,
        lemakMax: 26.3,
        karbohidratMin: 97.5,
        karbohidratMax: 113.8,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi Besar (PB) remaja awal / tingkat menengah pertama"
    },
    {
        no: 5,
        id: "sma_smk",
        kelompokSasaran: "PM SMA/SMK/MA/SMALB",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 713,
        energiMax: 831,
        proteinMin: 21.0,
        proteinMax: 24.5,
        lemakMin: 22.5,
        lemakMax: 26.3,
        karbohidratMin: 105.0,
        karbohidratMax: 122.5,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi Besar (PB) remaja akhir / tingkat menengah atas"
    },
    {
        no: 6,
        id: "pendidik",
        kelompokSasaran: "Pendidik",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 713,
        energiMax: 831,
        proteinMin: 21.0,
        proteinMax: 24.5,
        lemakMin: 22.5,
        lemakMax: 26.3,
        karbohidratMin: 105.0,
        karbohidratMax: 122.5,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi Besar (PB) untuk guru / tenaga pendidik"
    },
    {
        no: 7,
        id: "tendik",
        kelompokSasaran: "Tenaga Kependidikan",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 713,
        energiMax: 831,
        proteinMin: 21.0,
        proteinMax: 24.5,
        lemakMin: 22.5,
        lemakMax: 26.3,
        karbohidratMin: 105.0,
        karbohidratMax: 122.5,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi Besar (PB) untuk staf kependidikan"
    },
    {
        no: 8,
        id: "balita_siang",
        kelompokSasaran: "Anak Balita",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 405,
        energiMax: 473,
        proteinMin: 6.0,
        proteinMax: 7.0,
        lemakMin: 13.5,
        lemakMax: 15.8,
        karbohidratMin: 64.5,
        karbohidratMax: 75.3,
        tipePorsi: "Porsi Kecil",
        keterangan: "Porsi Balita waktu siang"
    },
    {
        no: 9,
        id: "balita_13_59_pagi",
        kelompokSasaran: "Anak Balita usia 13-59 bulan",
        pendistribusian: "Pagi",
        rujukanAkgPct: "20-25%",
        energiMin: 270,
        energiMax: 338,
        proteinMin: 4.0,
        proteinMax: 5.0,
        lemakMin: 9.0,
        lemakMax: 11.3,
        karbohidratMin: 43.0,
        karbohidratMax: 53.8,
        tipePorsi: "Porsi Kecil",
        keterangan: "Porsi Balita 1-5 tahun waktu pagi"
    },
    {
        no: 10,
        id: "balita_6_11_siang",
        kelompokSasaran: "Balita usia 6-11 bulan",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 240,
        energiMax: 280,
        proteinMin: 4.5,
        proteinMax: 5.2,
        lemakMin: 10.5,
        lemakMax: 12.2,
        karbohidratMin: 31.5,
        karbohidratMax: 36.7,
        tipePorsi: "Porsi Kecil",
        keterangan: "MP-ASI Balita 6-11 bulan waktu siang"
    },
    {
        no: 11,
        id: "ibu_hamil",
        kelompokSasaran: "Ibu Hamil",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 753,
        energiMax: 879,
        proteinMin: 22.1,
        proteinMax: 25.8,
        lemakMin: 20.2,
        lemakMax: 23.6,
        karbohidratMin: 118.5,
        karbohidratMax: 138.3,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi gizi khusus Ibu Hamil"
    },
    {
        no: 12,
        id: "ibu_menyusui",
        kelompokSasaran: "Ibu Menyusui",
        pendistribusian: "Siang",
        rujukanAkgPct: "30-35%",
        energiMin: 782,
        energiMax: 912,
        proteinMin: 26.3,
        proteinMax: 30.6,
        lemakMin: 20.2,
        lemakMax: 23.5,
        karbohidratMin: 123.0,
        karbohidratMax: 143.5,
        tipePorsi: "Porsi Besar",
        keterangan: "Porsi gizi khusus Ibu Menyusui (Busui)"
    }
];

// Standar Acuan Agregat / Benchmark Operasional SPPG
// PK: Standar acuan SD Kelas 1-3 (Pagi 20-25% AKG)
export const STANDAR_AKG_PORSI_KECIL = {
    label: "Porsi Kecil (PK)",
    deskripsi: "TK/PAUD & SD Kelas 1-3 (Pagi 20-25% AKG)",
    energiMin: 330,
    energiMax: 413,
    proteinMin: 8.0,
    proteinMax: 10.0,
    lemakMin: 11.0,
    lemakMax: 13.8,
    karbohidratMin: 50.0,
    karbohidratMax: 62.5,
    seratMin: 4.0,
    seratMax: 7.0,
};

// PB: Standar acuan rentang gabungan SD Kelas 4-6 s.d. SMA/SMK & Tenaga Pendidik (Siang 30-35% AKG)
export const STANDAR_AKG_PORSI_BESAR = {
    label: "Porsi Besar (PB)",
    deskripsi: "SD Kelas 4-6, SMP, SMA/SMK, Tendik (Siang 30-35% AKG)",
    energiMin: 585,
    energiMax: 831,
    proteinMin: 15.8,
    proteinMax: 24.5,
    lemakMin: 19.5,
    lemakMax: 26.3,
    karbohidratMin: 87.0,
    karbohidratMax: 122.5,
    seratMin: 6.0,
    seratMax: 10.0,
};

// =======================================================================
// RUMUS DAN LOGIKA EVALUASI GIZI
// =======================================================================

export const RUMUS_GIZI_INFO = {
    dasarHukum: "Peraturan Kementerian Kesehatan RI No. 28 Tahun 2019",
    rumusKandunganBahan: "Zat Gizi Bahan = (Berat Bersih (g) / 100) × Nilai Gizi per 100g TKPI",
    rumusTotalPorsi: "Total Zat Gizi = ∑ (Zat Gizi Semua Bahan dalam 1 Porsi)",
    rumusPersentaseAkg: "% Pemenuhan = (Zat Gizi Porsi / Target AKG Rujukan) × 100%",
    keterangan: "Perhitungan kandungan gizi menggunakan basis data Tabel Komposisi Pangan Indonesia (TKPI) Kemenkes RI yang dikonversi dari berat bersih (edible portion) masing-masing bahan masakan."
};

/**
 * Evaluasi status badge AKG untuk Energi atau Total Nutrisi
 * @param {Object} nutritionObj - Objek nutrisi { energi, protein, lemak, karbohidrat, serat }
 * @param {boolean} isPB - True jika Porsi Besar, False jika Porsi Kecil
 */
export function getAkgStatusBadge(nutritionObj, isPB = false) {
    if (!nutritionObj) {
        return {
            status: "empty",
            label: "Belum Ada Formula",
            badgeClass: "bg-slate-100 text-slate-600 border-slate-200 font-extrabold text-[10px]",
        };
    }
    const energi = Number(nutritionObj.energi) || 0;
    if (energi === 0) {
        return {
            status: "empty",
            label: "Belum Ada Formula",
            badgeClass: "bg-slate-100 text-slate-600 border-slate-200 font-extrabold text-[10px]",
        };
    }

    const standard = isPB ? STANDAR_AKG_PORSI_BESAR : STANDAR_AKG_PORSI_KECIL;
    const minTarget = standard.energiMin;
    const maxTarget = standard.energiMax;

    if (energi >= minTarget && energi <= maxTarget) {
        return {
            status: "memenuhi",
            label: "✓ MEMENUHI RUJUKAN AKG MBG",
            badgeClass: "bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px]",
        };
    } else if (energi < minTarget) {
        return {
            status: "kurang",
            label: `⚠ DI BAWAH RUJUKAN (${energi} < ${minTarget} kkal)`,
            badgeClass: "bg-amber-50 text-amber-800 border-amber-300 font-extrabold text-[10px]",
        };
    } else {
        return {
            status: "lebih",
            label: `⚡ DI ATAS RUJUKAN (${energi} > ${maxTarget} kkal)`,
            badgeClass: "bg-blue-50 text-blue-800 border-blue-300 font-extrabold text-[10px]",
        };
    }
}

/**
 * Evaluasi status singkat tiap komponen zat gizi (Energi, Protein, Lemak, Karbohidrat, Serat)
 * Return: { status, label, shortLabel, badgeClass, borderClass, textClass }
 */
export function getNutrientStatus(val, minTarget, maxTarget) {
    const num = Number(val) || 0;
    if (num === 0) {
        return {
            status: "empty",
            label: "Belum Ada",
            shortLabel: "-",
            badgeClass: "bg-slate-100 text-slate-500 border-slate-200",
            borderClass: "border-slate-200/80",
            textClass: "text-slate-500",
        };
    }
    if (num >= minTarget && num <= maxTarget) {
        return {
            status: "sesuai",
            label: "✓ Sesuai",
            shortLabel: "✓ Sesuai",
            badgeClass: "bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold",
            borderClass: "border-emerald-200/90",
            textClass: "text-emerald-700",
        };
    } else if (num < minTarget) {
        return {
            status: "kurang",
            label: "⚠ Kurang",
            shortLabel: "↓ Kurang",
            badgeClass: "bg-amber-50 text-amber-800 border-amber-300 font-extrabold",
            borderClass: "border-amber-200/90",
            textClass: "text-amber-700",
        };
    } else {
        return {
            status: "lebih",
            label: "⚡ Lebih",
            shortLabel: "↑ Lebih",
            badgeClass: "bg-blue-50 text-blue-800 border-blue-300 font-extrabold",
            borderClass: "border-blue-200/90",
            textClass: "text-blue-700",
        };
    }
}

/**
 * Evaluasi status setiap zat gizi makro (Energi, Protein, Lemak, Karbohidrat)
 */
export function evaluateNutrientDetail(val, minTarget, maxTarget, unit = "g") {
    const num = Number(val) || 0;
    if (num === 0) {
        return {
            status: "empty",
            label: "0 " + unit,
            badgeClass: "bg-slate-100 text-slate-500",
            icon: "minus"
        };
    }
    if (num >= minTarget && num <= maxTarget) {
        return {
            status: "memenuhi",
            label: `${num} ${unit} (Ideal: ${minTarget}-${maxTarget})`,
            badgeClass: "bg-emerald-100 text-emerald-800 font-semibold",
            icon: "check"
        };
    } else if (num < minTarget) {
        return {
            status: "kurang",
            label: `${num} ${unit} (Kurang dari min ${minTarget})`,
            badgeClass: "bg-amber-100 text-amber-800 font-semibold",
            icon: "arrow-down"
        };
    } else {
        return {
            status: "lebih",
            label: `${num} ${unit} (Lebih dari max ${maxTarget})`,
            badgeClass: "bg-blue-100 text-blue-800 font-semibold",
            icon: "arrow-up"
        };
    }
}

/**
 * Cari rujukan AKG berdasarkan nama kelompok sasaran
 */
export function findRujukanAkgByKelompok(namaKelompok) {
    if (!namaKelompok) return null;
    const lower = namaKelompok.toLowerCase();
    
    if (lower.includes("tk") || lower.includes("paud") || lower.includes("ra")) {
        return RUJUKAN_AKG_MBG[0];
    }
    if (lower.includes("1-3") || lower.includes("1 - 3") || lower.includes("sd awal")) {
        return RUJUKAN_AKG_MBG[1];
    }
    if (lower.includes("4-6") || lower.includes("4 - 6") || lower.includes("sd atas")) {
        return RUJUKAN_AKG_MBG[2];
    }
    if (lower.includes("smp") || lower.includes("mts")) {
        return RUJUKAN_AKG_MBG[3];
    }
    if (lower.includes("sma") || lower.includes("smk") || lower.includes("ma")) {
        return RUJUKAN_AKG_MBG[4];
    }
    if (lower.includes("guru") || lower.includes("pendidik")) {
        return RUJUKAN_AKG_MBG[5];
    }
    if (lower.includes("tendik") || lower.includes("tenaga kependidikan")) {
        return RUJUKAN_AKG_MBG[6];
    }
    if (lower.includes("balita") || lower.includes("posyandu")) {
        return RUJUKAN_AKG_MBG[7];
    }
    if (lower.includes("hamil") || lower.includes("bumil")) {
        return RUJUKAN_AKG_MBG[10];
    }
    if (lower.includes("menyusui") || lower.includes("busui")) {
        return RUJUKAN_AKG_MBG[11];
    }
    return null;
}
