/**
 * Dataset & Formula Komposisi Pangan Indonesia (TKPI 2020)
 * Standar Perhitungan Kebutuhan dan Produksi MBG (Badan Gizi Nasional - BGN)
 */

export const TKPI_2020_DATABASE = [
    // 1. KARBOHIDRAT / MAKANAN POKOK
    {
        id: "KHB-01",
        kategori: "Karbohidrat",
        nama: "Beras Giling Putih Lokal",
        energi: 360, // per 100g
        protein: 6.8,
        lemak: 0.7,
        karbohidrat: 78.9,
        serat: 0.8,
        bdd: 100, // 100%
        fmm: 220, // Faktor Masak Matang (Nasi mengembang 2.2x)
        buffer: 3, // 3%
        harga_master: 15500, // Rp/kg
        alergen: null,
    },
    {
        id: "KHB-02",
        kategori: "Karbohidrat",
        nama: "Beras Merah Pulen",
        energi: 352,
        protein: 7.5,
        lemak: 1.8,
        karbohidrat: 76.2,
        serat: 3.4,
        bdd: 100,
        fmm: 210,
        buffer: 3,
        harga_master: 19000,
        alergen: null,
    },
    {
        id: "KHB-03",
        kategori: "Karbohidrat",
        nama: "Kentang Segar",
        energi: 87,
        protein: 2.0,
        lemak: 0.1,
        karbohidrat: 20.1,
        serat: 1.8,
        bdd: 85, // Kulit dikupas
        fmm: 90,
        buffer: 5,
        harga_master: 18000,
        alergen: null,
    },

    // 2. LAUK HEWANI (PROTEIN HEWANI)
    {
        id: "LKH-01",
        kategori: "Lauk Hewani",
        nama: "Daging Ayam Broiler Fillet (Dada/Paha)",
        energi: 298,
        protein: 18.2,
        lemak: 25.0,
        karbohidrat: 0.0,
        serat: 0.0,
        bdd: 90, // Tulang/lemak dibuang
        fmm: 75, // Menyusut setelah dimasak
        buffer: 5,
        harga_master: 38000,
        alergen: null,
    },
    {
        id: "LKH-02",
        kategori: "Lauk Hewani",
        nama: "Telur Ayam Ras Segar",
        energi: 154,
        protein: 12.4,
        lemak: 10.8,
        karbohidrat: 0.7,
        serat: 0.0,
        bdd: 89, // Kulit telur 11%
        fmm: 95,
        buffer: 4,
        harga_master: 29000,
        alergen: "Telur",
    },
    {
        id: "LKH-03",
        kategori: "Lauk Hewani",
        nama: "Ikan Dori / Kakap Fillet Segar",
        energi: 96,
        protein: 19.5,
        lemak: 1.2,
        karbohidrat: 0.0,
        serat: 0.0,
        bdd: 95,
        fmm: 70,
        buffer: 5,
        harga_master: 42000,
        alergen: "Seafood/Ikan",
    },
    {
        id: "LKH-04",
        kategori: "Lauk Hewani",
        nama: "Daging Sapi Segar Cincang",
        energi: 217,
        protein: 19.6,
        lemak: 15.0,
        karbohidrat: 0.0,
        serat: 0.0,
        bdd: 95,
        fmm: 68,
        buffer: 5,
        harga_master: 120000,
        alergen: null,
    },

    // 3. LAUK NABATI (PROTEIN NABATI)
    {
        id: "LKN-01",
        kategori: "Lauk Nabati",
        nama: "Tempe Kedelai Murni",
        energi: 201,
        protein: 20.8,
        lemak: 8.8,
        karbohidrat: 13.5,
        serat: 1.4,
        bdd: 100,
        fmm: 90,
        buffer: 3,
        harga_master: 16000,
        alergen: "Kedelai",
    },
    {
        id: "LKN-02",
        kategori: "Lauk Nabati",
        nama: "Tahu Putih Segar",
        energi: 80,
        protein: 10.9,
        lemak: 4.7,
        karbohidrat: 0.8,
        serat: 0.1,
        bdd: 100,
        fmm: 85,
        buffer: 3,
        harga_master: 14000,
        alergen: "Kedelai",
    },
    {
        id: "LKN-03",
        kategori: "Lauk Nabati",
        nama: "Kacang Merah / Hijau Rebus",
        energi: 127,
        protein: 8.7,
        lemak: 0.5,
        karbohidrat: 22.8,
        serat: 6.4,
        bdd: 100,
        fmm: 180,
        buffer: 4,
        harga_master: 28000,
        alergen: "Kacang",
    },

    // 4. SAYURAN SEGAR
    {
        id: "SYR-01",
        kategori: "Sayuran",
        nama: "Wortel Segar Lokal",
        energi: 36,
        protein: 1.0,
        lemak: 0.2,
        karbohidrat: 7.9,
        serat: 2.8,
        bdd: 88, // Kupas kulit & pangkal
        fmm: 85,
        buffer: 5,
        harga_master: 14000,
        alergen: null,
    },
    {
        id: "SYR-02",
        kategori: "Sayuran",
        nama: "Buncis Muda Segar",
        energi: 35,
        protein: 2.4,
        lemak: 0.3,
        karbohidrat: 7.2,
        serat: 3.2,
        bdd: 90, // Ujung batang dibuang
        fmm: 80,
        buffer: 5,
        harga_master: 16000,
        alergen: null,
    },
    {
        id: "SYR-03",
        kategori: "Sayuran",
        nama: "Bayam Hijau Segar",
        energi: 37,
        protein: 3.5,
        lemak: 0.5,
        karbohidrat: 6.5,
        serat: 2.2,
        bdd: 71, // Batang tua dibuang
        fmm: 70,
        buffer: 7,
        harga_master: 12000,
        alergen: null,
    },
    {
        id: "SYR-04",
        kategori: "Sayuran",
        nama: "Brokoli Hijau Segar",
        energi: 34,
        protein: 2.8,
        lemak: 0.4,
        karbohidrat: 6.6,
        serat: 2.6,
        bdd: 80,
        fmm: 85,
        buffer: 5,
        harga_master: 25000,
        alergen: null,
    },

    // 5. BUAH SEGAR
    {
        id: "BUH-01",
        kategori: "Buah Segar",
        nama: "Pisang Cavendish / Barangan",
        energi: 92,
        protein: 1.0,
        lemak: 0.5,
        karbohidrat: 23.4,
        serat: 2.6,
        bdd: 68, // Kulit pisang 32%
        fmm: 100,
        buffer: 3,
        harga_master: 18000,
        alergen: null,
    },
    {
        id: "BUH-02",
        kategori: "Buah Segar",
        nama: "Semangka Merah Segar",
        energi: 28,
        protein: 0.5,
        lemak: 0.2,
        karbohidrat: 6.9,
        serat: 0.4,
        bdd: 46, // Kulit semangka tebal 54%
        fmm: 100,
        buffer: 4,
        harga_master: 9000,
        alergen: null,
    },
    {
        id: "BUH-03",
        kategori: "Buah Segar",
        nama: "Jeruk Manis Nusantara",
        energi: 45,
        protein: 0.9,
        lemak: 0.2,
        karbohidrat: 11.2,
        serat: 1.4,
        bdd: 72, // Kulit jeruk 28%
        fmm: 100,
        buffer: 4,
        harga_master: 20000,
        alergen: null,
    },
    {
        id: "BUH-04",
        kategori: "Buah Segar",
        nama: "Pepaya California Manis",
        energi: 46,
        protein: 0.5,
        lemak: 0.1,
        karbohidrat: 12.2,
        serat: 1.6,
        bdd: 75, // Kulit & biji 25%
        fmm: 100,
        buffer: 4,
        harga_master: 10000,
        alergen: null,
    },

    // 6. MINYAK & BUMBU / PELENGKAP
    {
        id: "BMB-01",
        kategori: "Bumbu & Pelengkap",
        nama: "Minyak Goreng Sawit",
        energi: 884,
        protein: 0.0,
        lemak: 100.0,
        karbohidrat: 0.0,
        serat: 0.0,
        bdd: 100,
        fmm: 100,
        buffer: 2,
        harga_master: 17500,
        alergen: null,
    },
    {
        id: "BMB-02",
        kategori: "Bumbu & Pelengkap",
        nama: "Bumbu Rempah Masak (Bawang, Jahe, Kunyit, Garam)",
        energi: 50,
        protein: 1.5,
        lemak: 0.8,
        karbohidrat: 9.0,
        serat: 1.5,
        bdd: 85,
        fmm: 90,
        buffer: 5,
        harga_master: 30000,
        alergen: null,
    },
    {
        id: "BMB-03",
        kategori: "Bumbu & Pelengkap",
        nama: "Susu Pasteurisasi UHT (125 ml)",
        energi: 80,
        protein: 4.0,
        lemak: 4.0,
        karbohidrat: 6.0,
        serat: 0.0,
        bdd: 100,
        fmm: 100,
        buffer: 1,
        harga_master: 28000, // per liter (~3500 per kotak)
        alergen: "Laktosa / Susu Sapi",
    },
];

/**
 * Standar Batas AKG Resmi BGN (Badan Gizi Nasional)
 */
export const STANDAR_AKG_BGN = {
    PK: {
        nama: "Porsi Kecil (PK)",
        energi_min: 450,
        energi_max: 550,
        protein_min: 15,
        protein_max: 22,
        lemak_min: 12,
        lemak_max: 18,
        karbohidrat_min: 65,
        karbohidrat_max: 85,
        serat_min: 4.0,
        plafon_food_cost: 8000, // Rp 8.000 / porsi
    },
    PB: {
        nama: "Porsi Besar (PB)",
        energi_min: 650,
        energi_max: 800,
        protein_min: 24,
        protein_max: 35,
        lemak_min: 18,
        lemak_max: 26,
        karbohidrat_min: 85,
        karbohidrat_max: 110,
        serat_min: 6.0,
        plafon_food_cost: 10000, // Rp 10.000 / porsi
    },
};

/**
 * Kalkulasi Kebutuhan Kotor Bahan (Gramasi Bersih -> Kebutuhan Kotor Total Kg)
 * Rumus BGN: Gross = (Net / (BDD/100)) * (1 + Buffer/100) * Total Porsi / 1000 (Kg)
 */
export function calculateGrossWeightKg(netGramPerPorsi, totalPorsi, bddPercent = 100, bufferPercent = 0) {
    if (!netGramPerPorsi || !totalPorsi) return 0;
    const bddFactor = Math.max(0.1, (bddPercent || 100) / 100);
    const bufferFactor = 1 + ((bufferPercent || 0) / 100);
    const grossGramPerPorsi = (netGramPerPorsi / bddFactor) * bufferFactor;
    const totalGrossKg = (grossGramPerPorsi * totalPorsi) / 1000;
    return Number(totalGrossKg.toFixed(2));
}

/**
 * Hitung Nilai Nutrisi berdasarkan Berat Bersih (Gram) dari TKPI 2020 (per 100g)
 */
export function calculateNutritionFromNetGram(itemTkpi, netGram) {
    if (!itemTkpi || !netGram) {
        return { energi: 0, protein: 0, lemak: 0, karbohidrat: 0, serat: 0 };
    }
    const factor = netGram / 100;
    return {
        energi: Number((itemTkpi.energi * factor).toFixed(1)),
        protein: Number((itemTkpi.protein * factor).toFixed(1)),
        lemak: Number((itemTkpi.lemak * factor).toFixed(1)),
        karbohidrat: Number((itemTkpi.karbohidrat * factor).toFixed(1)),
        serat: Number((itemTkpi.serat * factor).toFixed(1)),
    };
}

/**
 * Hitung Food Cost per Porsi dari Berat Kotor & Harga Satuan
 */
export function calculateItemFoodCostPerPortion(netGram, bddPercent, bufferPercent, hargaPerKg) {
    if (!netGram || !hargaPerKg) return 0;
    const bddFactor = Math.max(0.1, (bddPercent || 100) / 100);
    const bufferFactor = 1 + ((bufferPercent || 0) / 100);
    const grossGram = (netGram / bddFactor) * bufferFactor;
    const cost = (grossGram / 1000) * hargaPerKg;
    return Math.round(cost);
}
