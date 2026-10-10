/**
 * Helper Reusable untuk Parsing dan Menempel (Paste) Nilai Kandungan Gizi (AKG).
 * Mendukung berbagai format input salinan:
 * 1. Baris atau kolom dari Excel / Google Sheets (vertikal atau horizontal dengan pemisah tab/newline)
 * 2. Teks dengan label kata kunci: e.g. "Energi: 550 kkal, Protein: 18.5 g, Lemak: 16 g..."
 * 3. Nilai angka mentah berurutan: 550, 18.5, 16, 75, 6.5
 * 4. Otomatis mengenali koma desimal khas Indonesia (18,5 -> 18.5)
 */

export const NUTRISI_KEYS = ['energi', 'protein', 'lemak', 'karbohidrat', 'serat'];

export const NUTRISI_META = [
    { key: 'energi', label: 'Energi', unit: 'kkal', icon: '🔥', defaultStep: '0.1' },
    { key: 'protein', label: 'Protein', unit: 'g', icon: '🥩', defaultStep: '0.1' },
    { key: 'lemak', label: 'Lemak', unit: 'g', icon: '🥑', defaultStep: '0.1' },
    { key: 'karbohidrat', label: 'Karbohidrat', unit: 'g', icon: '🍚', defaultStep: '0.1' },
    { key: 'serat', label: 'Serat', unit: 'g', icon: '🥦', defaultStep: '0.1' },
];

/**
 * Parsing teks dari clipboard atau textarea menjadi objek nilai gizi
 *
 * @param {string} text - Teks mentah yang ditempel
 * @param {string} startKey - Key awal jika berupa list angka terurut (default: 'energi')
 * @returns {{ values: Object, count: number, isMulti: boolean, hasKeywords: boolean }}
 */
export function parsePastedNutritionText(text, startKey = 'energi') {
    if (!text || typeof text !== 'string') {
        return { values: {}, count: 0, isMulti: false, hasKeywords: false };
    }

    const cleanedText = text.trim();
    if (!cleanedText) {
        return { values: {}, count: 0, isMulti: false, hasKeywords: false };
    }

    // 1. Cek pencocokan berbasis kata kunci / label gizi
    const keywordRegexes = {
        energi: /(?:energi|energy|kalori|calories?|kkal|kcal)\s*[:=\-–]?\s*([0-9]+(?:[.,][0-9]+)?)/i,
        protein: /(?:protein|prot)\s*[:=\-–]?\s*([0-9]+(?:[.,][0-9]+)?)/i,
        lemak: /(?:lemak|fat|lipids?)\s*[:=\-–]?\s*([0-9]+(?:[.,][0-9]+)?)/i,
        karbohidrat: /(?:karbohidrat|karbo|carbohydrates?|carbs?|kh)\s*[:=\-–]?\s*([0-9]+(?:[.,][0-9]+)?)/i,
        serat: /(?:serat|fiber|dietary\s*fiber)\s*[:=\-–]?\s*([0-9]+(?:[.,][0-9]+)?)/i,
    };

    const parsedValues = {};
    let matchedKeywordsCount = 0;

    for (const [key, regex] of Object.entries(keywordRegexes)) {
        const match = cleanedText.match(regex);
        if (match && match[1]) {
            const num = parseFloat(match[1].replace(',', '.'));
            if (!isNaN(num)) {
                parsedValues[key] = Math.round(num * 100) / 100;
                matchedKeywordsCount++;
            }
        }
    }

    if (matchedKeywordsCount >= 1) {
        return {
            values: parsedValues,
            count: matchedKeywordsCount,
            isMulti: matchedKeywordsCount > 1,
            hasKeywords: true,
        };
    }

    // 2. Jika bukan teks berlabel, parse sebagai data list / tabel angka (Excel, CSV, spasi/tab)
    const lines = cleanedText.split(/[\r\n]+/);
    const cleanedLines = lines
        .map((line) => {
            // Hapus penomoran atau bullet hanya jika diikuti spasi (misal: "1. 412.1" atau "1) 412.1" atau "- 412.1")
            // Penting: Jangan gunakan \s* karena akan menghapus "412." dari "412.1"
            return line
                .replace(/^([0-9]+[\.\)]\s+|[\-\*\•\–\—]\s+)/, '')
                .trim();
        })
        .filter(Boolean);

    let combinedText = cleanedLines.join(' ');

    // Standarisasi koma desimal Indonesia: e.g. "412,1" -> "412.1"
    let normalized = combinedText.replace(/(\d+),(\d+)/g, '$1.$2');

    // Ekstrak semua angka desimal / integer dari teks
    const matches = normalized.match(/-?\d+(?:\.\d+)?/g);

    if (!matches || matches.length === 0) {
        return { values: {}, count: 0, isMulti: false, hasKeywords: false };
    }

    const extractedNumbers = matches
        .map((m) => parseFloat(m))
        .filter((n) => !isNaN(n))
        .map((n) => Math.round(n * 100) / 100);

    if (extractedNumbers.length === 0) {
        return { values: {}, count: 0, isMulti: false, hasKeywords: false };
    }

    const startIdx = Math.max(0, NUTRISI_KEYS.indexOf(startKey));
    let assignedCount = 0;

    extractedNumbers.slice(0, NUTRISI_KEYS.length - startIdx).forEach((num, i) => {
        const targetKey = NUTRISI_KEYS[startIdx + i];
        if (targetKey) {
            parsedValues[targetKey] = num;
            assignedCount++;
        }
    });

    return {
        values: parsedValues,
        count: assignedCount,
        isMulti: extractedNumbers.length > 1,
        hasKeywords: false,
    };
}

/**
 * Terapkan nilai gizi hasil parse ke objek reaktif target
 *
 * @param {Object} targetObj - Objek reaktif target (e.g. akgPk, akgPb, akgAlergi[...].pk)
 * @param {Object} parsedValues - Nilai { energi?: number, protein?: number, ... }
 * @returns {number} Jumlah atribut gizi yang berhasil diisi
 */
export function applyNutritionValues(targetObj, parsedValues) {
    if (!targetObj || !parsedValues || typeof parsedValues !== 'object') return 0;

    let applied = 0;
    for (const key of NUTRISI_KEYS) {
        if (parsedValues[key] !== undefined && parsedValues[key] !== null) {
            const val = Number(parsedValues[key]);
            if (!isNaN(val)) {
                targetObj[key] = Math.round(val * 100) / 100;
                applied++;
            }
        }
    }
    return applied;
}

/**
 * Handler event @paste pada input elemen nilai gizi
 *
 * @param {ClipboardEvent} event
 * @param {Object} targetObj
 * @param {string} currentKey
 * @param {Function} [onApplied] - Callback saat berhasil diterapkan: (appliedCount, parsedValues) => void
 */
export function handleNutritionPasteEvent(event, targetObj, currentKey = 'energi', onApplied = null) {
    const clipboardData = event.clipboardData || window.clipboardData;
    if (!clipboardData) return;

    const pastedText = clipboardData.getData('text');
    if (!pastedText) return;

    const parsed = parsePastedNutritionText(pastedText, currentKey);

    // Selalu intersep jika ada nilai angka gizi (termasuk 1 angka dengan koma '412,1' agar input type="number" tidak menolaknya)
    if (parsed.count >= 1) {
        event.preventDefault();
        const appliedCount = applyNutritionValues(targetObj, parsed.values);
        if (appliedCount > 0 && typeof onApplied === 'function') {
            onApplied(appliedCount, parsed.values);
        }
    }
}

/**
 * Membaca clipboard pengguna via Navigator Clipboard API atau fallback ke callback modal
 *
 * @param {Object} targetObj
 * @param {Function} onApplied - Callback sukses paste langsung: (appliedCount, parsedValues) => void
 * @param {Function} onFallbackModal - Callback jika clipboard diblokir/butuh konfirmasi: (initialText) => void
 */
export async function quickPasteNutritionFromClipboard(targetObj, onApplied, onFallbackModal) {
    try {
        if (navigator.clipboard && navigator.clipboard.readText) {
            const clipText = await navigator.clipboard.readText();
            if (clipText && clipText.trim()) {
                const parsed = parsePastedNutritionText(clipText, 'energi');
                if (parsed.count >= 2 || parsed.hasKeywords) {
                    const appliedCount = applyNutritionValues(targetObj, parsed.values);
                    if (appliedCount > 0 && typeof onApplied === 'function') {
                        onApplied(appliedCount, parsed.values);
                        return;
                    }
                } else if (parsed.count === 1) {
                    if (typeof onFallbackModal === 'function') {
                        onFallbackModal(clipText);
                        return;
                    }
                }
            }
        }
    } catch (err) {
        console.warn('Clipboard read error or not permitted:', err);
    }

    if (typeof onFallbackModal === 'function') {
        onFallbackModal('');
    }
}
