// Konfigurasi Kategori dan Subkategori Penerima Manfaat

export const KATEGORI_OPTIONS = [
    { value: "TK", label: "TK" },
    { value: "RA", label: "RA" },
    { value: "PAUD", label: "PAUD" },
    { value: "SD", label: "SD" },
    { value: "MI", label: "MI" },
    { value: "SMP", label: "SMP" },
    { value: "MTs", label: "MTs" },
    { value: "SMA", label: "SMA" },
    { value: "SMK", label: "SMK" },
    { value: "MA", label: "MA" },
    { value: "MAK", label: "MAK" },
    { value: "Posyandu", label: "Posyandu" },
];

export const JENIS_KEPEMILIKAN_OPTIONS = [
    { value: "Negeri", label: "Negeri" },
    { value: "Swasta", label: "Swasta" },
];

export const TIPE_IDENTITAS_OPTIONS = [
    { value: "NPSN", label: "NPSN" },
    { value: "NSPP", label: "NSPP" },
    { value: "NSM", label: "NSM" },
    { value: "NSNP", label: "NSNP" },
    { value: "TPK", label: "TPK" },
    { value: "Lainnya", label: "Lainnya" },
];

export const JENIS_PORSI_OPTIONS = [
    { value: "Porsi Kecil", label: "Porsi Kecil" },
    { value: "Porsi Besar", label: "Porsi Besar" },
];

export const ALERGI_OPTIONS = [
    { value: "Nasi/Beras", label: "Nasi/Beras" },
    { value: "Telur", label: "Telur" },
    { value: "Daging Ayam", label: "Daging Ayam" },
    { value: "Daging Sapi", label: "Daging Sapi" },
    { value: "Ikan", label: "Ikan" },
    { value: "Udang", label: "Udang" },
    { value: "Cumi-cumi", label: "Cumi-cumi" },
    { value: "Kerang", label: "Kerang" },
    { value: "Kepiting", label: "Kepiting" },
    { value: "Susu dan produk olahannya", label: "Susu dan produk olahannya" },
    { value: "Keju", label: "Keju" },
    { value: "Kedelai", label: "Kedelai" },
    { value: "Tahu", label: "Tahu" },
    { value: "Tempe", label: "Tempe" },
    { value: "Kacang Tanah", label: "Kacang Tanah" },
    { value: "Kacang Kedelai", label: "Kacang Kedelai" },
    { value: "Kacang Almond", label: "Kacang Almond" },
    { value: "Kacang Mete", label: "Kacang Mete" },
    { value: "Kacang Hazelnut", label: "Kacang Hazelnut" },
    { value: "Kacang Kenari", label: "Kacang Kenari" },
    { value: "Kacang-kacangan lainnya", label: "Kacang-kacangan lainnya" },
    { value: "Gandum/Tepung Terigu", label: "Gandum/Tepung Terigu" },
    { value: "Gluten", label: "Gluten" },
    { value: "Wijen", label: "Wijen" },
    { value: "Jagung", label: "Jagung" },
    { value: "Tomat", label: "Tomat" },
    { value: "Wortel", label: "Wortel" },
    { value: "Kentang", label: "Kentang" },
    { value: "Stroberi", label: "Stroberi" },
    { value: "Nanas", label: "Nanas" },
    { value: "Mangga", label: "Mangga" },
    { value: "Melon", label: "Melon" },
    { value: "Pisang", label: "Pisang" },
    { value: "Alpukat", label: "Alpukat" },
    { value: "Jeruk", label: "Jeruk" },
    { value: "Buah Naga", label: "Buah Naga" },
    { value: "Cokelat/Kakao", label: "Cokelat/Kakao" },
    { value: "Madu", label: "Madu" },
    { value: "Lainnya", label: "Lainnya" },
];

export const ALLERGEN_KEYWORDS = {
    "Nasi/Beras": ["nasi", "beras", "rice", "bubur beras", "lontong", "ketupat"],
    Telur: [
        "telur",
        "egg",
        "dadar",
        "ceplok",
        "omelet",
        "mayones",
        "mayonnaise",
        "telur puyuh",
        "telur bebek",
        "telur asin",
        "telor",
        "martabak telur",
    ],
    "Daging Ayam": [
        "ayam",
        "chicken",
        "unggas",
        "bebek",
        "kalkun",
        "daging ayam",
        "fillet ayam",
        "fillet dada ayam",
        "paha ayam",
        "dada ayam",
        "sayap ayam",
        "ati ampela ayam",
        "ceker ayam",
    ],
    "Daging Sapi": [
        "sapi",
        "beef",
        "daging sapi",
        "kornet",
        "rendang",
        "rawon",
        "empal",
        "bistik sapi",
        "iga sapi",
        "buntut sapi",
        "tetelan sapi",
        "dendeng sapi",
        "bakso sapi",
        "daging giling sapi",
        "daging cincang sapi",
    ],
    Ikan: [
        "ikan",
        "fish",
        "tongkol",
        "tuna",
        "lele",
        "bandeng",
        "gurame",
        "gurami",
        "nila",
        "dori",
        "salmon",
        "patin",
        "kakap",
        "tenggiri",
        "kembung",
        "teri",
        "belut",
        "pindang",
        "bawal",
        "cakalang",
        "mujair",
        "ikan mas",
        "gabus",
        "bader",
        "salem",
        "siakap",
    ],
    Udang: ["udang", "shrimp", "prawn", "ebi", "rebon", "udang windu", "udang vaname", "udang galah"],
    "Cumi-cumi": ["cumi", "cumi-cumi", "squid", "sotong", "gurita", "octopus"],
    Kerang: ["kerang", "clam", "mussel", "scallop", "tiram", "remis", "kupang", "oyster"],
    Kepiting: ["kepiting", "crab", "rajungan", "lobster", "king crab"],
    "Susu dan produk olahannya": [
        "susu",
        "milk",
        "dairy",
        "laktosa",
        "lactose",
        "yogurt",
        "yoghurt",
        "mentega",
        "butter",
        "krim",
        "cream",
        "keju",
        "cheese",
    ],
    Keju: ["keju", "cheese", "cheddar", "mozzarella", "parmesan", "edam", "gouda"],
    Kedelai: ["kedelai", "soy", "soya", "edamame", "kecap", "tauco", "tahu", "tempe"],
    Tahu: ["tahu", "tofu", "tahu putih", "tahu kuning", "tahu pong", "tahu sutra"],
    Tempe: ["tempe", "tempeh", "tempe kedelai", "tempe bacem", "tempe mendoan", "tempe orek"],
    "Kacang Tanah": [
        "kacang tanah",
        "peanut",
        "bumbu kacang",
        "pecel",
        "gado-gado",
        "saus kacang",
        "sambal kacang",
    ],
    "Kacang Kedelai": ["kacang kedelai", "soybean", "biji kedelai"],
    "Kacang Almond": ["almond", "badam", "kacang almond"],
    "Kacang Mete": ["kacang mete", "mete", "mede", "cashew", "kacang mede"],
    "Kacang Hazelnut": ["hazelnut", "kacang hazelnut"],
    "Kacang Kenari": ["kenari", "walnut", "kacang kenari", "kacang walnut"],
    "Kacang-kacangan lainnya": [
        "kacang merah",
        "kacang hijau",
        "kacang polong",
        "kacang tolo",
        "pistachio",
        "macadamia",
        "kacang kapri",
        "kacang panjang",
        "buncis",
    ],
    "Gandum/Tepung Terigu": [
        "gandum",
        "wheat",
        "terigu",
        "roti",
        "mie",
        "bakmi",
        "pasta",
        "spageti",
        "spaghetti",
        "makaroni",
        "biskuit",
        "tepung terigu",
        "tepung gandum",
        "kulit pangsit",
        "kulit lumpia",
        "mie telur",
        "mie kuning",
        "mie basah",
    ],
    Gluten: ["gluten", "seitan", "tepung terigu", "roti gandum", "mie", "spaghetti"],
    Wijen: ["wijen", "sesame", "tahini", "biji wijen", "minyak wijen"],
    Jagung: ["jagung", "corn", "maizena", "popcorn", "jagung manis", "tepung jagung", "baby corn", "putren"],
    Tomat: ["tomat", "tomato", "saos tomat", "saus tomat", "tomat ceri"],
    Wortel: ["wortel", "carrot"],
    Kentang: ["kentang", "potato", "kentang goreng", "perkedel kentang"],
    Stroberi: ["stroberi", "strawberry", "strawberi"],
    Nanas: ["nanas", "nenas", "pineapple"],
    Mangga: ["mangga", "mango"],
    Melon: ["melon", "cantaloupe", "honeydew"],
    Pisang: ["pisang", "banana"],
    Alpukat: ["alpukat", "avocado", "avokad"],
    Jeruk: ["jeruk", "orange", "citrus", "lemon", "mandarin", "jeruk nipis", "jeruk purut", "jeruk limau", "jeruk bali", "jeruk sunkist"],
    "Buah Naga": ["buah naga", "dragon fruit", "pitaya"],
    "Cokelat/Kakao": ["cokelat", "chocolate", "coklat", "kakao", "cocoa", "chocochip"],
    Madu: ["madu", "honey"],
};

export const REKOMENDASI_SUBSTITUSI = {
    "Nasi/Beras": "Jagung Pipil, Kentang Rebus, Ubi Manis, Singkong, atau Roti Bebas Gluten",
    Telur: "Tahu Sutra, Tempe Goreng/Bacem, Daging Ayam, Ikan Fillet, atau Daging Sapi",
    "Daging Ayam": "Ikan Fillet, Daging Sapi, Telur, Tahu, atau Tempe",
    "Daging Sapi": "Daging Ayam Fillet, Ikan Segar, Telur, Tahu, atau Tempe",
    Ikan: "Fillet Daging Ayam, Daging Sapi, Telur, Tahu, atau Tempe",
    Udang: "Daging Ayam Fillet, Daging Sapi, Ikan Fillet, atau Telur",
    "Cumi-cumi": "Daging Ayam Fillet, Ikan Fillet, atau Telur",
    Kerang: "Daging Ayam Fillet, Ikan Fillet, atau Daging Sapi",
    Kepiting: "Daging Ayam Fillet, Ikan Fillet, atau Telur",
    "Susu dan produk olahannya": "Susu Kedelai, Susu Almond, Santan Kelapa Segar, atau Jus Buah Segar",
    Keju: "Tahu Sutra, Saus Bumbu Rempah Nabati, atau Nutritional Yeast",
    Kedelai: "Telur, Daging Ayam, Ikan, Kacang Merah, atau Kacang Hijau",
    Tahu: "Telur, Daging Ayam Fillet, Ikan Fillet, atau Kentang",
    Tempe: "Telur, Daging Ayam, Ikan, atau Jamur Tiram",
    "Kacang Tanah": "Saus Wijen, Saus Tomat Segar, Saus Bumbu Kecap Manis, atau Bumbu Kuning",
    "Kacang Kedelai": "Kacang Hijau, Kacang Merah, atau Telur",
    "Kacang Almond": "Biji Bunga Matahari, Wijen, atau Biji Labu",
    "Kacang Mete": "Biji Labu, Wijen, atau Biskuit Non-Kacang",
    "Kacang Hazelnut": "Cokelat Murni Non-Kacang atau Selai Buah Alami",
    "Kacang Kenari": "Biji Wijen atau Biji Bunga Matahari",
    "Kacang-kacangan lainnya": "Saus Rempah Alami Non-Kacang",
    "Gandum/Tepung Terigu": "Nasi Putih, Bihun Beras, Kentang, Ubi Jalar, Jagung, atau Tepung Beras",
    Gluten: "Nasi Beras, Tepung Beras, Tepung Tapioka, atau Jagung Pipil",
    Wijen: "Bawang Goreng atau Minyak Sayur Nabati",
    Jagung: "Beras/Nasi, Kentang, Ubi Jalar, atau Singkong",
    Tomat: "Kecap Manis Rempah, Bumbu Kuning Gurih, atau Saus Asam Manis Alami",
    Wortel: "Labu Siam, Buncis Manis, Labu Kuning, atau Jagung Manis",
    Kentang: "Nasi Beras, Ubi Jalar Manis, Singkong, atau Jagung",
    Stroberi: "Pisang Ambon, Apel Manis, Jeruk Segar, atau Semangka",
    Nanas: "Pepaya Manis, Melon Segar, Semangka, Pisang, atau Apel",
    Mangga: "Pepaya Manis, Melon Segar, Jeruk Manis, atau Pisang",
    Melon: "Semangka Merah, Pepaya Manis, Pisang, atau Jeruk Manis",
    Pisang: "Pepaya Manis, Semangka, Melon, Apel, atau Jeruk",
    Alpukat: "Pisang Manis, Pepaya, Buah Naga, atau Melon",
    Jeruk: "Pisang Ambon, Pepaya Manis, Semangka, atau Melon",
    "Buah Naga": "Semangka Merah, Pepaya Manis, Melon, atau Pisang",
    "Cokelat/Kakao": "Perasa Vanila Alami, Gula Aren, atau Selai Buah",
    Madu: "Gula Aren Asli, Sirup Maple, atau Gula Tebu",
};

/**
 * Helper pencocokan kata dengan batas kata (word boundary) yang ketat dan presisi
 * Mencegah kesalahan false-positive (contoh: "bayam" tidak cocok dengan "ayam", "gudang" tidak cocok dengan "udang")
 */
export function matchWordBoundary(text, keyword) {
    if (!text || typeof text !== "string" || !keyword) return false;
    const cleanKw = keyword.trim();
    if (!cleanKw) return false;
    const escaped = cleanKw.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(^|[^a-zA-Z0-9\u00C0-\u024F])${escaped}(?![a-zA-Z0-9\u00C0-\u024F])`, "i");
    return regex.test(text);
}

/**
 * Cek apakah sebuah teks menu/bahan cocok dengan jenis alergi tertentu
 */
export function checkTextMatchesAllergen(text, allergenName) {
    if (!text || typeof text !== "string" || !allergenName) return false;
    const cleanText = text.trim();
    const cleanAllergen = allergenName.trim();
    if (!cleanText || !cleanAllergen) return false;

    // 1. Direct word-boundary match against the allergen name itself / parts
    const subNames = cleanAllergen.split(/[\/,]/).map(s => s.trim()).filter(Boolean);
    for (const sub of subNames) {
        if (matchWordBoundary(cleanText, sub)) {
            return true;
        }
    }

    // 2. Lookup in ALLERGEN_KEYWORDS
    const lowerAllergen = cleanAllergen.toLowerCase();
    for (const [key, keywords] of Object.entries(ALLERGEN_KEYWORDS)) {
        const lowerKey = key.toLowerCase();
        const isMatchingCategory =
            lowerKey === lowerAllergen ||
            lowerKey.includes(lowerAllergen) ||
            lowerAllergen.includes(lowerKey) ||
            subNames.some(sub => lowerKey.includes(sub.toLowerCase()) || sub.toLowerCase().includes(lowerKey));

        if (isMatchingCategory) {
            for (const kw of keywords) {
                if (matchWordBoundary(cleanText, kw)) {
                    return true;
                }
            }
        }
    }

    return false;
}

export const ANGGARAN_PORSI_MAP = {
    "Porsi Kecil": 8000,
    "Porsi Besar": 10000,
};

export const BUDGET_PAGU_PK = 8000; // Rp 8.000 / porsi
export const BUDGET_PAGU_PB = 10000; // Rp 10.000 / porsi
export const BUDGET_PLAFON_PK = BUDGET_PAGU_PK;
export const BUDGET_PLAFON_PB = BUDGET_PAGU_PB;

// Mapping default jenis porsi berdasarkan Sub Kategori & Jenjang
export const SUB_KATEGORI_PORSI_MAP = {
    // TK / RA / PAUD
    Pelajar: "Porsi Kecil",

    // SD / MI
    "Kelas 1": "Porsi Kecil",
    "Kelas 2": "Porsi Kecil",
    "Kelas 3": "Porsi Kecil",
    "Kelas 4": "Porsi Besar",
    "Kelas 5": "Porsi Besar",
    "Kelas 6": "Porsi Besar",

    // SMP / MTs
    "Kelas 7": "Porsi Besar",
    "Kelas 8": "Porsi Besar",
    "Kelas 9": "Porsi Besar",

    // SMA / SMK / MA / MAK
    "Kelas 10": "Porsi Besar",
    "Kelas 11": "Porsi Besar",
    "Kelas 12": "Porsi Besar",

    // Posyandu
    "Ibu Hamil": "Porsi Besar",
    "Ibu Menyusui": "Porsi Besar",
    Balita: "Porsi Kecil",

    // Pendukung (Semua jenjang = Porsi Besar)
    "Pendukung (Guru)": "Porsi Besar",
    "Pendukung (Tenaga Kependidikan)": "Porsi Besar",
    "Pendukung (Satpam)": "Porsi Besar",
    "Pendukung (Lainnya)": "Porsi Besar",
};

export function getJenisPorsiBySubKategori(subKategori, kategori = null) {
    if (!subKategori) return "Porsi Besar";

    if (SUB_KATEGORI_PORSI_MAP[subKategori]) {
        return SUB_KATEGORI_PORSI_MAP[subKategori];
    }

    // Default fallback
    if (
        subKategori === "Pelajar" ||
        subKategori === "Balita" ||
        subKategori.includes("Kelas 1") ||
        subKategori.includes("Kelas 2") ||
        subKategori.includes("Kelas 3")
    ) {
        return "Porsi Kecil";
    }

    return "Porsi Besar";
}

export function getSubKategoriByKategori(kategori) {
    if (!kategori) return [];

    switch (kategori) {
        case "TK":
        case "RA":
        case "PAUD":
        case "TK/RA":
        case "TK/RA/PAUD":
            return [
                "Pelajar",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];

        case "SD":
        case "MI":
        case "SD/MI":
            return [
                "Kelas 1",
                "Kelas 2",
                "Kelas 3",
                "Kelas 4",
                "Kelas 5",
                "Kelas 6",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];

        case "SMP":
        case "MTs":
        case "SMP/MTs":
            return [
                "Kelas 7",
                "Kelas 8",
                "Kelas 9",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];

        case "SMA":
        case "SMK":
        case "MA":
        case "MAK":
        case "SMA/MA":
        case "SMA/SMK":
        case "SMA/SMK/MA":
            return [
                "Kelas 10",
                "Kelas 11",
                "Kelas 12",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];

        case "Posyandu":
            return [
                "Ibu Hamil",
                "Ibu Menyusui",
                "Balita",
                "Pendukung (Lainnya)",
            ];

        default:
            return [
                "Penerima Utama",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];
    }
}

export function sortRincianByKategori(rincianList, kategori) {
    if (!Array.isArray(rincianList)) return [];
    const order = getSubKategoriByKategori(kategori);
    return [...rincianList].sort((a, b) => {
        const subA = a.sub_kategori || "";
        const subB = b.sub_kategori || "";
        let indexA = order.indexOf(subA);
        let indexB = order.indexOf(subB);
        if (indexA === -1) indexA = 999;
        if (indexB === -1) indexB = 999;
        return indexA - indexB;
    });
}
