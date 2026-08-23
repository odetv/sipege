// Service API Wilayah Indonesia (Sumber Data: cahyadsn/wilayah & Kepmendagri via wilayah.id API)
// Struktur: Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa/Kelurahan

const BASE_URL = "/api/wilayah";

const cache = {
    provinces: null,
    regencies: {},
    districts: {},
    villages: {},
};

// Fallback data dasar jika offline / network delay
const FALLBACK_PROVINCES = [
    { id: "51", code: "51", name: "Bali" },
    { id: "31", code: "31", name: "DKI Jakarta" },
    { id: "32", code: "32", name: "Jawa Barat" },
    { id: "33", code: "33", name: "Jawa Tengah" },
    { id: "34", code: "34", name: "DI Yogyakarta" },
    { id: "35", code: "35", name: "Jawa Timur" },
    { id: "11", code: "11", name: "Aceh" },
    { id: "12", code: "12", name: "Sumatera Utara" },
    { id: "13", code: "13", name: "Sumatera Barat" },
    { id: "14", code: "14", name: "Riau" },
    { id: "15", code: "15", name: "Jambi" },
    { id: "16", code: "16", name: "Sumatera Selatan" },
    { id: "17", code: "17", name: "Bengkulu" },
    { id: "18", code: "18", name: "Lampung" },
    { id: "19", code: "19", name: "Kepulauan Bangka Belitung" },
    { id: "21", code: "21", name: "Kepulauan Riau" },
    { id: "52", code: "52", name: "Nusa Tenggara Barat" },
    { id: "53", code: "53", name: "Nusa Tenggara Timur" },
    { id: "61", code: "61", name: "Kalimantan Barat" },
    { id: "62", code: "62", name: "Kalimantan Tengah" },
    { id: "63", code: "63", name: "Kalimantan Selatan" },
    { id: "64", code: "64", name: "Kalimantan Timur" },
    { id: "65", code: "65", name: "Kalimantan Utara" },
    { id: "71", code: "71", name: "Sulawesi Utara" },
    { id: "72", code: "72", name: "Sulawesi Tengah" },
    { id: "73", code: "73", name: "Sulawesi Selatan" },
    { id: "74", code: "74", name: "Sulawesi Tenggara" },
    { id: "75", code: "75", name: "Gorontalo" },
    { id: "76", code: "76", name: "Sulawesi Barat" },
    { id: "81", code: "81", name: "Maluku" },
    { id: "82", code: "82", name: "Maluku Utara" },
    { id: "91", code: "91", name: "Papua Barat" },
    { id: "92", code: "92", name: "Papua" },
];

export async function getProvinces() {
    if (cache.provinces) return cache.provinces;
    try {
        const res = await fetch(`${BASE_URL}/provinces`);
        if (!res.ok) throw new Error("Network error fetching provinces");
        const json = await res.json();
        const rawList = json.data || json;
        const formatted = rawList.map((item) => ({
            id: item.code || item.id,
            code: item.code || item.id,
            name: item.name,
        }));
        cache.provinces = formatted;
        return formatted;
    } catch (e) {
        console.warn("Using fallback provinces:", e);
        return FALLBACK_PROVINCES;
    }
}

export async function getRegencies(provinceCode) {
    if (!provinceCode) return [];
    const cleanCode = String(provinceCode).trim();
    if (cache.regencies[cleanCode]) return cache.regencies[cleanCode];
    try {
        const res = await fetch(`${BASE_URL}/regencies/${cleanCode}`);
        if (!res.ok) throw new Error("Network error fetching regencies");
        const json = await res.json();
        const rawList = json.data || json;
        const formatted = rawList.map((item) => ({
            id: item.code || item.id,
            code: item.code || item.id,
            name: item.name,
        }));
        cache.regencies[cleanCode] = formatted;
        return formatted;
    } catch (e) {
        console.warn(
            "Error fetching regencies, using fallback if available:",
            e,
        );
        if (cleanCode === "51") {
            return [
                { id: "51.01", code: "51.01", name: "Kabupaten Jembrana" },
                { id: "51.02", code: "51.02", name: "Kabupaten Tabanan" },
                { id: "51.03", code: "51.03", name: "Kabupaten Badung" },
                { id: "51.04", code: "51.04", name: "Kabupaten Gianyar" },
                { id: "51.05", code: "51.05", name: "Kabupaten Klungkung" },
                { id: "51.06", code: "51.06", name: "Kabupaten Bangli" },
                { id: "51.07", code: "51.07", name: "Kabupaten Karangasem" },
                { id: "51.08", code: "51.08", name: "Kabupaten Buleleng" },
                { id: "51.71", code: "51.71", name: "Kota Denpasar" },
            ];
        }
        return [];
    }
}

export async function getDistricts(regencyCode) {
    if (!regencyCode) return [];
    const cleanCode = String(regencyCode).trim();
    if (cache.districts[cleanCode]) return cache.districts[cleanCode];
    try {
        const res = await fetch(`${BASE_URL}/districts/${cleanCode}`);
        if (!res.ok) throw new Error("Network error fetching districts");
        const json = await res.json();
        const rawList = json.data || json;
        const formatted = rawList.map((item) => ({
            id: item.code || item.id,
            code: item.code || item.id,
            name: item.name,
        }));
        cache.districts[cleanCode] = formatted;
        return formatted;
    } catch (e) {
        console.warn(
            "Error fetching districts, using fallback if available:",
            e,
        );
        if (cleanCode === "51.08" || cleanCode === "5108") {
            return [
                { id: "51.08.01", code: "51.08.01", name: "Gerokgak" },
                { id: "51.08.02", code: "51.08.02", name: "Seririt" },
                { id: "51.08.03", code: "51.08.03", name: "Busungbiu" },
                { id: "51.08.04", code: "51.08.04", name: "Banjar" },
                { id: "51.08.05", code: "51.08.05", name: "Sukasada" },
                { id: "51.08.06", code: "51.08.06", name: "Buleleng" },
                { id: "51.08.07", code: "51.08.07", name: "Sawan" },
                { id: "51.08.08", code: "51.08.08", name: "Kubutambahan" },
                { id: "51.08.09", code: "51.08.09", name: "Tejakula" },
            ];
        }
        return [];
    }
}

export async function getVillages(districtCode) {
    if (!districtCode) return [];
    const cleanCode = String(districtCode).trim();
    if (cache.villages[cleanCode]) return cache.villages[cleanCode];
    try {
        const res = await fetch(`${BASE_URL}/villages/${cleanCode}`);
        if (!res.ok) throw new Error("Network error fetching villages");
        const json = await res.json();
        const rawList = json.data || json;
        const formatted = rawList.map((item) => ({
            id: item.code || item.id,
            code: item.code || item.id,
            name: item.name,
        }));
        cache.villages[cleanCode] = formatted;
        return formatted;
    } catch (e) {
        console.warn(
            "Error fetching villages, using fallback if available:",
            e,
        );
        if (cleanCode === "51.08.05" || cleanCode === "510805") {
            return [
                {
                    id: "51.08.05.2013",
                    code: "51.08.05.2013",
                    name: "Tegallinggah",
                },
                {
                    id: "51.08.05.1009",
                    code: "51.08.05.1009",
                    name: "Sukasada",
                },
                {
                    id: "51.08.05.2001",
                    code: "51.08.05.2001",
                    name: "Pancasari",
                },
                {
                    id: "51.08.05.2002",
                    code: "51.08.05.2002",
                    name: "Wanagiri",
                },
                {
                    id: "51.08.05.2003",
                    code: "51.08.05.2003",
                    name: "Ambengan",
                },
                { id: "51.08.05.2004", code: "51.08.05.2004", name: "Gitgit" },
                {
                    id: "51.08.05.2005",
                    code: "51.08.05.2005",
                    name: "Pegayaman",
                },
                {
                    id: "51.08.05.2006",
                    code: "51.08.05.2006",
                    name: "Silangjana",
                },
                {
                    id: "51.08.05.2007",
                    code: "51.08.05.2007",
                    name: "Pegadungan",
                },
                {
                    id: "51.08.05.2008",
                    code: "51.08.05.2008",
                    name: "Padangbulia",
                },
                {
                    id: "51.08.05.2010",
                    code: "51.08.05.2010",
                    name: "Sambangan",
                },
                { id: "51.08.05.2011", code: "51.08.05.2011", name: "Panji" },
                {
                    id: "51.08.05.2012",
                    code: "51.08.05.2012",
                    name: "Panji Anom",
                },
                { id: "51.08.05.2014", code: "51.08.05.2014", name: "Selat" },
                {
                    id: "51.08.05.2015",
                    code: "51.08.05.2015",
                    name: "Kayuputih",
                },
            ];
        }
        return [];
    }
}

/**
 * Format nama wilayah umum menjadi Title Case (contoh: "BALI" -> "Bali", "SUKASADA" -> "Sukasada")
 */
export function formatWilayahName(str) {
    if (!str) return "";
    return str
        .trim()
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

/**
 * Format nama Kabupaten/Kota: Menghilangkan kata 'KABUPATEN', 'KOTA', 'KAB.', 'KOTA.'
 * dan mengambil namanya saja (contoh: "KABUPATEN BULELENG" -> "Buleleng", "KOTA DENPASAR" -> "Denpasar")
 */
export function formatKabupatenName(str) {
    if (!str) return "";
    let cleaned = str.trim().replace(/^(kabupaten|kota|kab\.|kota\.)\s+/i, "");
    return cleaned
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

/**
 * Format nama lengkap dengan gelar depan dan pemisah tanda koma sebelum gelar belakang.
 * Contoh: formatNamaLengkap('Gede Bagler', 'Dr.', 'S.Kom.') -> "Dr. Gede Bagler, S.Kom."
 */
export function formatNamaLengkap(nama, gelarDepan, gelarBelakang) {
    let result = (nama || "").trim();
    if (gelarDepan && gelarDepan.trim()) {
        result = `${gelarDepan.trim()} ${result}`;
    }
    if (gelarBelakang && gelarBelakang.trim()) {
        const cleanGelar = gelarBelakang.trim().replace(/^,\s*/, "");
        result = `${result}, ${cleanGelar}`;
    }
    return result;
}
