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
            return [
                "Pelajar",
                "Pendukung (Guru)",
                "Pendukung (Tenaga Kependidikan)",
                "Pendukung (Satpam)",
                "Pendukung (Lainnya)",
            ];

        case "SD":
        case "MI":
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
