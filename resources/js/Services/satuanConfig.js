/**
 * Konfigurasi Standar Satuan Bahan Baku & Operasional MBG
 * Satuan standar yang didukung: Kg, g, L, ml, pcs, bks, buah
 */

export const SATUAN_LIST = [
    { value: "Kg", label: "Kg (Kilogram)", short: "Kg", type: "weight" },
    { value: "g", label: "g (Gram)", short: "g", type: "weight" },
    { value: "L", label: "L (Liter)", short: "L", type: "volume" },
    { value: "ml", label: "ml (Mililiter)", short: "ml", type: "volume" },
    { value: "pcs", label: "pcs (Pieces)", short: "pcs", type: "count" },
    { value: "bks", label: "bks (Bungkus)", short: "bks", type: "count" },
    { value: "buah", label: "buah (Buah)", short: "buah", type: "count" },
];

export const SATUAN_VALUES = SATUAN_LIST.map((s) => s.value);

/**
 * Format kuantitas kotor sesuai satuan
 */
export function formatGrossQty(qty, satuan = "Kg") {
    if (qty === null || qty === undefined || qty === "" || isNaN(Number(qty))) {
        return `0 ${satuan || "Kg"}`;
    }
    const num = Number(qty);
    if (num <= 0) return `0 ${satuan || "Kg"}`;
    const s = (satuan || "Kg").trim();

    if (s === "Kg" || s === "Liter" || s === "L") {
        if (num < 0.001) {
            return `${parseFloat(num.toFixed(4))} ${s}`;
        } else if (num < 0.01) {
            return `${parseFloat(num.toFixed(3))} ${s}`;
        } else {
            return `${parseFloat(num.toFixed(2))} ${s}`;
        }
    }
    if (s === "Gram" || s === "g" || s === "ml" || s === "mL") {
        return `${Number(num.toFixed(1)).toLocaleString("id-ID")} ${s}`;
    }
    if (Number.isInteger(num)) {
        return `${num.toLocaleString("id-ID")} ${s}`;
    }
    return `${Number(num.toFixed(2)).toLocaleString("id-ID")} ${s}`;
}

/**
 * Menghasilkan array ringkasan kuantitas per satuan (grouped without bullet strings)
 * Returns: [{ unit: 'Kg', qty: 5.2, label: '5.2 Kg' }, { unit: 'L', qty: 2.0, label: '2 L' }]
 */
export function getGroupedUnitList(items, qtyKey = "totalGrossKg") {
    if (!items || !items.length) {
        return [{ unit: "Kg", qty: 0, label: "0 Kg" }];
    }

    const unitGroups = {};

    items.forEach((it) => {
        const rawSatuan = (it.satuan || "Kg").trim();
        const sLower = rawSatuan.toLowerCase();
        let val = 0;
        if (typeof qtyKey === "function") {
            val = qtyKey(it);
        } else if (qtyKey && it[qtyKey] !== undefined && it[qtyKey] !== null) {
            val = it[qtyKey];
        } else {
            val = it.totalGrossKg ?? it.gross_kg ?? it.qty ?? 0;
        }
        const qty = Number(val || 0);
        if (qty <= 0) return;

        let canonicalUnit = rawSatuan;
        if (sLower === "kg" || sLower === "kilogram") canonicalUnit = "Kg";
        else if (sLower === "g" || sLower === "gram") canonicalUnit = "g";
        else if (sLower === "l" || sLower === "liter") canonicalUnit = "L";
        else if (sLower === "ml") canonicalUnit = "ml";
        else if (sLower === "pcs") canonicalUnit = "pcs";
        else if (sLower === "bks" || sLower === "bungkus") canonicalUnit = "bks";
        else if (sLower === "buah") canonicalUnit = "buah";

        unitGroups[canonicalUnit] = (unitGroups[canonicalUnit] || 0) + qty;
    });

    const result = [];
    const knownOrder = ["Kg", "g", "L", "ml", "pcs", "bks", "buah"];

    knownOrder.forEach((unit) => {
        if (unitGroups[unit] !== undefined && unitGroups[unit] > 0) {
            result.push({
                unit,
                qty: unitGroups[unit],
                label: formatGrossQty(unitGroups[unit], unit),
            });
            delete unitGroups[unit];
        }
    });

    // Satuan custom lainnya jika ada
    for (const [unit, qty] of Object.entries(unitGroups)) {
        if (qty > 0) {
            result.push({
                unit,
                qty,
                label: formatGrossQty(qty, unit),
            });
        }
    }

    if (result.length === 0) {
        return [{ unit: "Kg", qty: 0, label: "0 Kg" }];
    }

    return result;
}

export function formatGroupedUnitSummary(items, delimiter = " • ", qtyKey = "totalGrossKg") {
    const list = getGroupedUnitList(items, qtyKey);
    return list.map((item) => item.label).join(delimiter);
}
