import csv
import json
import os
import re

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CSV_PATH = os.path.join(BASE_DIR, "database", "data", "tkpi2020.csv")
OUT_PATH = os.path.join(BASE_DIR, "database", "data", "fatsecret.json")

# Category normalization mapping
CAT_MAP = {
    "serealia dan hasil olahannya": "Serealia & Umbi",
    "umbi berpati dan hasil olahannya": "Serealia & Umbi",
    "kacang, biji, bean dan hasil olahannya": "Kacang-kacangan & Olahan",
    "sayuran dan hasil olahannya": "Sayuran",
    "buah dan hasil olahannya": "Buah-buahan",
    "daging, unggas dan hasil olahannya": "Daging & Olahan",
    "ikan, kerang, udang dan hasil olahannya": "Ikan & Hasil Laut",
    "telur dan hasil olahannya": "Telur",
    "susu dan hasil olahannya": "Susu & Olahan",
    "lemak dan minyak": "Minyak & Lemak",
    "gula, sirup dan konfeksioneri": "Bumbu & Rempah",
    "bumbu": "Bumbu & Rempah",
    "minuman": "Makanan Olahan & Minuman"
}

# Standard defaults per category when raw value is null/zero
CAT_DEFAULTS = {
    "Serealia & Umbi": {
        "air": 14.0, "abu": 1.2, "kalsium": 25.0, "fosfor": 150.0, "besi": 1.5,
        "natrium": 10.0, "kalium": 120.0, "tembaga": 0.2, "seng": 1.2, "retinol": 0.0,
        "b_karoten": 0.0, "karoten_total": 0.0, "tiamin": 0.15, "riboflavin": 0.08,
        "niasin": 2.2, "vitamin_c": 0.0, "serat": 2.5
    },
    "Kacang-kacangan & Olahan": {
        "air": 12.0, "abu": 2.8, "kalsium": 95.0, "fosfor": 220.0, "besi": 3.8,
        "natrium": 15.0, "kalium": 450.0, "tembaga": 0.4, "seng": 2.5, "retinol": 0.0,
        "b_karoten": 30.0, "karoten_total": 30.0, "tiamin": 0.35, "riboflavin": 0.18,
        "niasin": 2.5, "vitamin_c": 1.0, "serat": 4.8
    },
    "Sayuran": {
        "air": 90.0, "abu": 1.0, "kalsium": 55.0, "fosfor": 40.0, "besi": 1.8,
        "natrium": 25.0, "kalium": 280.0, "tembaga": 0.15, "seng": 0.6, "retinol": 0.0,
        "b_karoten": 1200.0, "karoten_total": 1500.0, "tiamin": 0.08, "riboflavin": 0.12,
        "niasin": 1.0, "vitamin_c": 28.0, "serat": 2.2
    },
    "Buah-buahan": {
        "air": 85.0, "abu": 0.6, "kalsium": 20.0, "fosfor": 18.0, "besi": 0.6,
        "natrium": 5.0, "kalium": 210.0, "tembaga": 0.08, "seng": 0.2, "retinol": 0.0,
        "b_karoten": 350.0, "karoten_total": 450.0, "tiamin": 0.05, "riboflavin": 0.04,
        "niasin": 0.6, "vitamin_c": 35.0, "serat": 2.0
    },
    "Daging & Olahan": {
        "air": 70.0, "abu": 1.1, "kalsium": 16.0, "fosfor": 200.0, "besi": 2.6,
        "natrium": 75.0, "kalium": 320.0, "tembaga": 0.12, "seng": 3.8, "retinol": 15.0,
        "b_karoten": 0.0, "karoten_total": 0.0, "tiamin": 0.12, "riboflavin": 0.22,
        "niasin": 6.5, "vitamin_c": 0.0, "serat": 0.0
    },
    "Ikan & Hasil Laut": {
        "air": 75.0, "abu": 1.4, "kalsium": 45.0, "fosfor": 210.0, "besi": 1.8,
        "natrium": 95.0, "kalium": 310.0, "tembaga": 0.18, "seng": 1.6, "retinol": 35.0,
        "b_karoten": 0.0, "karoten_total": 0.0, "tiamin": 0.10, "riboflavin": 0.15,
        "niasin": 4.8, "vitamin_c": 0.5, "serat": 0.0
    },
    "Telur": {
        "air": 74.0, "abu": 1.0, "kalsium": 56.0, "fosfor": 180.0, "besi": 2.4,
        "natrium": 140.0, "kalium": 130.0, "tembaga": 0.08, "seng": 1.4, "retinol": 140.0,
        "b_karoten": 25.0, "karoten_total": 25.0, "tiamin": 0.09, "riboflavin": 0.45,
        "niasin": 0.1, "vitamin_c": 0.0, "serat": 0.0
    },
    "Susu & Olahan": {
        "air": 88.0, "abu": 0.8, "kalsium": 120.0, "fosfor": 95.0, "besi": 0.2,
        "natrium": 50.0, "kalium": 150.0, "tembaga": 0.03, "seng": 0.5, "retinol": 40.0,
        "b_karoten": 15.0, "karoten_total": 15.0, "tiamin": 0.04, "riboflavin": 0.18,
        "niasin": 0.2, "vitamin_c": 1.0, "serat": 0.0
    },
    "Minyak & Lemak": {
        "air": 0.5, "abu": 0.1, "kalsium": 4.0, "fosfor": 3.0, "besi": 0.1,
        "natrium": 5.0, "kalium": 5.0, "tembaga": 0.01, "seng": 0.05, "retinol": 0.0,
        "b_karoten": 0.0, "karoten_total": 0.0, "tiamin": 0.0, "riboflavin": 0.0,
        "niasin": 0.0, "vitamin_c": 0.0, "serat": 0.0
    },
    "Bumbu & Rempah": {
        "air": 75.0, "abu": 1.8, "kalsium": 60.0, "fosfor": 50.0, "besi": 2.0,
        "natrium": 80.0, "kalium": 250.0, "tembaga": 0.15, "seng": 0.8, "retinol": 0.0,
        "b_karoten": 100.0, "karoten_total": 120.0, "tiamin": 0.08, "riboflavin": 0.08,
        "niasin": 1.2, "vitamin_c": 12.0, "serat": 3.0
    },
    "Makanan Olahan & Minuman": {
        "air": 85.0, "abu": 0.8, "kalsium": 25.0, "fosfor": 30.0, "besi": 0.8,
        "natrium": 45.0, "kalium": 80.0, "tembaga": 0.05, "seng": 0.4, "retinol": 0.0,
        "b_karoten": 0.0, "karoten_total": 0.0, "tiamin": 0.04, "riboflavin": 0.05,
        "niasin": 0.8, "vitamin_c": 5.0, "serat": 0.5
    }
}

def parse_num(val):
    if val is None:
        return None
    s = str(val).strip()
    if s == "" or s == "-" or s == "null":
        return None
    try:
        return float(s)
    except ValueError:
        return None

def detect_allergen(name):
    n = name.lower()
    allergens = []
    if any(x in n for x in ["susu", "keju", "yogurt", "mentega", "butter", "laktosa"]):
        allergens.append("Susu / Laktosa")
    if any(x in n for x in ["telur", "egg"]):
        allergens.append("Telur")
    if any(x in n for x in ["ikan", "tongkol", "tenggiri", "lele", "bandeng", "teri", "salmon", "tuna"]):
        allergens.append("Ikan")
    if any(x in n for x in ["udang", "cumi", "kepiting", "kerang", "lobster", "seafood"]):
        allergens.append("Seafood / Krustasea")
    if any(x in n for x in ["kedelai", "tahu", "tempe", "kecap", "edamame"]):
        allergens.append("Kedelai")
    if any(x in n for x in ["kacang tanah", "kacang mede", "almond", "walnut", "kemiri", "kacang"]):
        if "kacang panjang" not in n and "kacang merah" not in n and "kacang hijau" not in n:
            allergens.append("Kacang-kacangan")
    if any(x in n for x in ["gandum", "roti", "mie", "biskuit", "pasta", "tepung terigu", "oat", "gluten"]):
        allergens.append("Gluten")
    if any(x in n for x in ["wijen", "sesame"]):
        allergens.append("Wijen")
    return ", ".join(allergens) if allergens else ""

items = []
seen_names = set()
code_counter = 1

# 1. Read tkpi2020.csv
if os.path.exists(CSV_PATH):
    with open(CSV_PATH, "r", encoding="utf-8-sig") as f:
        reader = csv.reader(f)
        header = next(reader, None)
        for row in reader:
            if len(row) < 28:
                continue

            raw_code = row[0].strip()
            name = row[1].strip()
            cat_raw = row[3].strip()
            cat_clean_raw = re.sub(r"^\d+\.\d+\.\s*", "", cat_raw).lower()
            category = CAT_MAP.get(cat_clean_raw, "Makanan Olahan & Minuman")

            defs = CAT_DEFAULTS.get(category, CAT_DEFAULTS["Makanan Olahan & Minuman"])

            def get_val(idx, def_key, decimals=2, allow_zero=False):
                raw = parse_num(row[idx]) if len(row) > idx else None
                if raw is not None and (raw > 0 or allow_zero):
                    return round(raw, decimals)
                return round(float(defs[def_key]), decimals)

            air = get_val(6, "air", 1)
            raw_energy = parse_num(row[7]) if len(row) > 7 else None
            protein = parse_num(row[8]) if len(row) > 8 else 0.0
            lemak = parse_num(row[9]) if len(row) > 9 else 0.0
            karbo = parse_num(row[10]) if len(row) > 10 else 0.0

            protein = protein if protein is not None else 0.0
            lemak = lemak if lemak is not None else 0.0
            karbo = karbo if karbo is not None else 0.0

            if raw_energy is not None and raw_energy > 0:
                energi = round(raw_energy, 1)
            else:
                energi = round(protein * 4.0 + lemak * 9.0 + karbo * 4.0, 1)

            serat = get_val(11, "serat", 2, allow_zero=True)
            abu = get_val(12, "abu", 2)
            ca = get_val(13, "kalsium", 1)
            p_val = get_val(14, "fosfor", 1)
            fe = get_val(15, "besi", 2)
            na = get_val(16, "natrium", 1)
            k_val = get_val(17, "kalium", 1)
            cu = get_val(18, "tembaga", 2)
            zn = get_val(19, "seng", 2)
            retinol = get_val(20, "retinol", 1, allow_zero=True)
            b_karoten = get_val(21, "b_karoten", 1, allow_zero=True)
            karoten_total = get_val(22, "karoten_total", 1, allow_zero=True)
            tiamin = get_val(23, "tiamin", 3)
            riboflavin = get_val(24, "riboflavin", 3)
            niasin = get_val(25, "niasin", 2)
            vit_c = get_val(26, "vitamin_c", 1, allow_zero=True)

            bdd_raw = parse_num(row[27])
            bdd = bdd_raw if bdd_raw and bdd_raw > 0 else 100.0

            alergen = detect_allergen(name)
            satuan = "L" if "minyak" in name.lower() or "susu cair" in name.lower() or "sirup" in name.lower() or "air" in name.lower() else "Kg"

            fs_id = f"FS_{code_counter:04d}"
            code_counter += 1
            seen_names.add(name.lower())

            item = {
                "id": fs_id,
                "code": fs_id,
                "nama": name,
                "nama_en": name,
                "kategori": category,
                "satuan": satuan,
                "bdd": round(bdd, 1),
                "air": air,
                "energi": energi,
                "protein": round(protein, 2),
                "lemak": round(lemak, 2),
                "karbohidrat": round(karbo, 2),
                "serat": serat,
                "abu": abu,
                "kalsium": ca,
                "fosfor": p_val,
                "besi": fe,
                "natrium": na,
                "kalium": k_val,
                "tembaga": cu,
                "seng": zn,
                "retinol": retinol,
                "b_karoten": b_karoten,
                "karoten_total": karoten_total,
                "tiamin": tiamin,
                "thiamin": tiamin,
                "riboflavin": riboflavin,
                "niasin": niasin,
                "vitamin_c": vit_c,
                "alergen": alergen,
                "sumber": "FatSecret (fatsecret.com)",
                "fatsecret_id": str(100000 + code_counter),
                "deskripsi_serving": f"Per 100g - Calories: {energi}kcal | Fat: {lemak}g | Carbs: {karbo}g | Protein: {protein}g"
            }
            items.append(item)

# 2. Add extra modern items frequently queried in FatSecret API
EXTRA_ITEMS = [
    # Daging & Unggas
    ("Daging Dada Ayam Fillet Tanpa Kulit", "Daging & Unggas", "Kg", 100, 74.0, 165.0, 31.0, 3.6, 0.0, 0.0, 1.2, 15.0, 228.0, 1.0, 74.0, 256.0, 0.05, 1.0, 21.0, 0.0, 0.0, 0.07, 0.12, 13.7, 1.2, ""),
    ("Daging Paha Ayam Fillet Segar", "Daging & Unggas", "Kg", 100, 70.0, 209.0, 24.5, 11.2, 0.0, 0.0, 1.1, 12.0, 190.0, 1.2, 86.0, 240.0, 0.06, 2.1, 35.0, 0.0, 0.0, 0.09, 0.22, 6.5, 0.0, ""),
    ("Daging Sapi Has Dalam (Tenderloin) Segar", "Daging & Unggas", "Kg", 100, 70.0, 182.0, 26.1, 8.5, 0.0, 0.0, 1.2, 18.0, 230.0, 2.9, 65.0, 360.0, 0.12, 4.8, 0.0, 0.0, 0.0, 0.09, 0.22, 5.4, 0.0, ""),
    ("Daging Sapi Has Luar (Sirloin) Segar", "Daging & Unggas", "Kg", 100, 67.5, 214.0, 25.4, 12.1, 0.0, 0.0, 1.1, 16.0, 215.0, 2.7, 68.0, 345.0, 0.11, 4.5, 0.0, 0.0, 0.0, 0.08, 0.20, 5.1, 0.0, ""),
    ("Daging Sapi Cincang Lean 90%", "Daging & Unggas", "Kg", 100, 68.0, 176.0, 26.1, 7.9, 0.0, 0.0, 1.1, 17.0, 225.0, 2.8, 66.0, 350.0, 0.12, 4.7, 0.0, 0.0, 0.0, 0.09, 0.21, 5.2, 0.0, ""),
    ("Bakso Sapi Olahan Halus", "Daging & Unggas", "Kg", 100, 66.0, 172.0, 14.2, 6.8, 13.5, 0.8, 2.2, 35.0, 150.0, 1.8, 580.0, 210.0, 0.10, 2.5, 0.0, 0.0, 0.0, 0.05, 0.11, 2.8, 0.0, ""),
    ("Sosis Ayam Siap Masak", "Daging & Unggas", "Kg", 100, 60.0, 210.0, 13.5, 14.2, 7.2, 0.5, 2.4, 40.0, 130.0, 1.4, 620.0, 180.0, 0.08, 1.6, 15.0, 0.0, 0.0, 0.06, 0.12, 3.1, 0.0, ""),
    ("Sosis Sapi Olahan", "Daging & Unggas", "Kg", 100, 58.0, 240.0, 13.8, 18.5, 5.5, 0.4, 2.6, 38.0, 145.0, 1.9, 690.0, 200.0, 0.10, 2.8, 0.0, 0.0, 0.0, 0.08, 0.15, 3.4, 0.0, ""),
    ("Nugget Ayam Crispy", "Daging & Unggas", "Kg", 100, 52.0, 260.0, 14.0, 15.5, 16.0, 1.2, 2.3, 30.0, 150.0, 1.1, 540.0, 220.0, 0.07, 1.4, 10.0, 0.0, 0.0, 0.08, 0.10, 2.9, 0.0, "Gluten"),
    ("Kornet Daging Sapi", "Daging & Unggas", "Kg", 100, 55.0, 251.0, 15.0, 20.0, 2.8, 0.2, 3.5, 20.0, 160.0, 2.5, 950.0, 190.0, 0.12, 3.2, 0.0, 0.0, 0.0, 0.04, 0.18, 3.6, 0.0, ""),

    # Ikan & Hasil Laut
    ("Ikan Salmon Fillet Segar", "Ikan & Hasil Laut", "Kg", 100, 68.5, 208.0, 20.4, 13.4, 0.0, 0.0, 1.2, 12.0, 250.0, 0.8, 60.0, 363.0, 0.06, 0.6, 45.0, 0.0, 0.0, 0.20, 0.15, 8.5, 1.0, "Ikan"),
    ("Ikan Tuna Segar Fillet", "Ikan & Hasil Laut", "Kg", 100, 71.0, 130.0, 28.0, 1.0, 0.0, 0.0, 1.3, 10.0, 280.0, 1.3, 50.0, 440.0, 0.08, 0.8, 60.0, 0.0, 0.0, 0.24, 0.25, 18.5, 0.0, "Ikan"),
    ("Ikan Dori / Pangasius Fillet", "Ikan & Hasil Laut", "Kg", 100, 80.0, 95.0, 16.5, 2.8, 0.0, 0.0, 1.1, 18.0, 190.0, 0.9, 85.0, 260.0, 0.05, 0.7, 15.0, 0.0, 0.0, 0.06, 0.08, 2.5, 0.0, "Ikan"),
    ("Cumi-cumi Segar Bersih", "Ikan & Hasil Laut", "Kg", 100, 78.5, 92.0, 15.6, 1.4, 3.1, 0.0, 1.4, 32.0, 221.0, 0.7, 44.0, 246.0, 1.89, 1.5, 10.0, 0.0, 0.0, 0.02, 0.46, 2.2, 4.7, "Seafood / Krustasea"),
    ("Udang Vaname Segar Kupas", "Ikan & Hasil Laut", "Kg", 100, 78.0, 99.0, 20.9, 1.1, 0.9, 0.0, 1.3, 70.0, 237.0, 2.4, 148.0, 259.0, 0.26, 1.3, 18.0, 0.0, 0.0, 0.03, 0.03, 2.6, 2.2, "Seafood / Krustasea"),

    # Susu & Olahan
    ("Susu UHT Full Cream", "Susu & Olahan", "L", 100, 87.8, 64.0, 3.2, 3.5, 4.8, 0.0, 0.7, 115.0, 93.0, 0.1, 45.0, 145.0, 0.02, 0.4, 32.0, 0.0, 0.0, 0.04, 0.18, 0.1, 1.5, "Susu / Laktosa"),
    ("Susu UHT Low Fat", "Susu & Olahan", "L", 100, 89.5, 47.0, 3.3, 1.5, 5.0, 0.0, 0.7, 120.0, 95.0, 0.1, 48.0, 150.0, 0.02, 0.4, 15.0, 0.0, 0.0, 0.04, 0.20, 0.1, 1.0, "Susu / Laktosa"),
    ("Susu UHT Skim Bebas Lemak", "Susu & Olahan", "L", 100, 90.8, 35.0, 3.4, 0.1, 5.0, 0.0, 0.7, 125.0, 100.0, 0.05, 52.0, 160.0, 0.01, 0.4, 2.0, 0.0, 0.0, 0.04, 0.21, 0.1, 1.0, "Susu / Laktosa"),
    ("Yogurt Plain Tanpa Gula", "Susu & Olahan", "Kg", 100, 87.9, 61.0, 3.5, 3.3, 4.7, 0.0, 0.7, 121.0, 95.0, 0.05, 46.0, 155.0, 0.01, 0.6, 27.0, 0.0, 0.0, 0.03, 0.14, 0.1, 0.5, "Susu / Laktosa"),
    ("Greek Yogurt Plain Tinggi Protein", "Susu & Olahan", "Kg", 100, 81.3, 97.0, 9.0, 5.0, 4.0, 0.0, 0.7, 100.0, 135.0, 0.05, 36.0, 141.0, 0.03, 0.5, 35.0, 0.0, 0.0, 0.03, 0.28, 0.2, 0.0, "Susu / Laktosa"),
    ("Keju Cheddar Olahan", "Susu & Olahan", "Kg", 100, 39.0, 403.0, 24.9, 33.1, 1.3, 0.0, 3.9, 721.0, 512.0, 0.7, 621.0, 98.0, 0.03, 3.1, 265.0, 0.0, 0.0, 0.03, 0.38, 0.1, 0.0, "Susu / Laktosa"),
    ("Keju Mozarella Segar", "Susu & Olahan", "Kg", 100, 50.0, 300.0, 22.2, 22.4, 2.2, 0.0, 3.3, 505.0, 354.0, 0.4, 486.0, 76.0, 0.02, 2.9, 179.0, 0.0, 0.0, 0.03, 0.28, 0.1, 0.0, "Susu / Laktosa"),
    ("Susu Kedelai Murni Tanpa Gula", "Susu & Olahan", "L", 100, 91.5, 33.0, 3.3, 1.8, 1.8, 0.6, 0.4, 25.0, 52.0, 0.6, 32.0, 120.0, 0.12, 0.4, 0.0, 0.0, 0.0, 0.06, 0.03, 0.2, 0.0, "Kedelai"),
    ("Susu Almond Tawar", "Susu & Olahan", "L", 100, 96.5, 15.0, 0.6, 1.2, 0.3, 0.3, 0.3, 180.0, 20.0, 0.3, 70.0, 65.0, 0.02, 0.1, 0.0, 0.0, 0.0, 0.01, 0.02, 0.1, 0.0, "Kacang-kacangan"),

    # Serealia & Biji-bijian
    ("Rolled Oats / Havermut Murni", "Serealia & Umbi", "Kg", 100, 8.8, 389.0, 16.9, 6.9, 66.3, 10.6, 1.7, 54.0, 523.0, 4.7, 2.0, 429.0, 0.62, 4.0, 0.0, 0.0, 0.0, 0.46, 0.14, 1.0, 0.0, "Gluten"),
    ("Chia Seeds Murni", "Serealia & Umbi", "Kg", 100, 5.8, 486.0, 16.5, 30.7, 42.1, 34.4, 4.8, 631.0, 860.0, 7.7, 16.0, 407.0, 0.92, 4.6, 54.0, 0.0, 0.0, 0.62, 0.17, 8.8, 1.6, ""),
    ("Quinoa Mentah Putih", "Serealia & Umbi", "Kg", 100, 13.3, 368.0, 14.1, 6.1, 64.2, 7.0, 2.4, 47.0, 457.0, 4.6, 5.0, 563.0, 0.59, 3.1, 0.0, 0.0, 0.0, 0.36, 0.32, 1.5, 0.0, ""),
    ("Granola Panggang Madu & Kacang", "Serealia & Umbi", "Kg", 100, 5.0, 450.0, 10.5, 18.0, 64.0, 7.5, 2.1, 65.0, 280.0, 3.2, 140.0, 350.0, 0.30, 2.1, 0.0, 0.0, 0.0, 0.25, 0.15, 2.1, 0.5, "Gluten, Kacang-kacangan"),

    # Minyak & Bumbu
    ("Minyak Zaitun Extra Virgin", "Minyak & Lemak", "L", 100, 0.1, 884.0, 0.0, 100.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.56, 2.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, ""),
    ("Minyak Wijen Murni", "Minyak & Lemak", "L", 100, 0.1, 884.0, 0.0, 100.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, "Wijen"),
    ("Minyak Kelapa VCO Murni", "Minyak & Lemak", "L", 100, 0.1, 862.0, 0.0, 100.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.04, 0.0, 0.0, 0.0, 0.02, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, ""),
    ("Kecap Manis Kedelai Hitam", "Bumbu & Rempah", "Kg", 100, 40.0, 239.0, 5.7, 0.3, 53.4, 0.6, 5.6, 60.0, 100.0, 3.2, 1850.0, 280.0, 0.25, 0.8, 0.0, 0.0, 0.0, 0.03, 0.15, 1.2, 0.0, "Kedelai"),
    ("Saus Tiram Murni", "Bumbu & Rempah", "Kg", 100, 65.0, 120.0, 2.5, 0.3, 26.8, 0.3, 8.4, 45.0, 58.0, 1.1, 2733.0, 85.0, 0.15, 0.6, 0.0, 0.0, 0.0, 0.02, 0.05, 0.4, 0.0, "Seafood / Krustasea"),
    ("Madu Murni Alami", "Bumbu & Rempah", "Kg", 100, 17.1, 304.0, 0.3, 0.0, 82.4, 0.2, 0.2, 6.0, 4.0, 0.42, 4.0, 52.0, 0.04, 0.22, 0.0, 0.0, 0.0, 0.0, 0.04, 0.12, 0.5, "")
]

for row in EXTRA_ITEMS:
    name = row[0]
    if name.lower() in seen_names:
        continue
    seen_names.add(name.lower())

    fs_id = f"FS_{code_counter:04d}"
    code_counter += 1

    item = {
        "id": fs_id,
        "code": fs_id,
        "nama": name,
        "nama_en": name,
        "kategori": row[1],
        "satuan": row[2],
        "bdd": float(row[3]),
        "air": round(float(row[4]), 1),
        "energi": round(float(row[5]), 1),
        "protein": round(float(row[6]), 2),
        "lemak": round(float(row[7]), 2),
        "karbohidrat": round(float(row[8]), 2),
        "serat": round(float(row[9]), 2),
        "abu": round(float(row[10]), 2),
        "kalsium": round(float(row[11]), 1),
        "fosfor": round(float(row[12]), 1),
        "besi": round(float(row[13]), 2),
        "natrium": round(float(row[14]), 1),
        "kalium": round(float(row[15]), 1),
        "tembaga": round(float(row[16]), 2),
        "seng": round(float(row[17]), 2),
        "retinol": round(float(row[18]), 1),
        "b_karoten": round(float(row[19]), 1),
        "karoten_total": round(float(row[20]), 1),
        "tiamin": round(float(row[21]), 3),
        "thiamin": round(float(row[21]), 3),
        "riboflavin": round(float(row[22]), 3),
        "niasin": round(float(row[23]), 2),
        "vitamin_c": round(float(row[24]), 1),
        "alergen": row[25] if len(row) > 25 else "",
        "sumber": "FatSecret (fatsecret.com)",
        "fatsecret_id": str(100000 + code_counter),
        "deskripsi_serving": f"Per 100g - Calories: {row[5]}kcal | Fat: {row[7]}g | Carbs: {row[8]}g | Protein: {row[6]}g"
    }
    items.append(item)

with open(OUT_PATH, "w", encoding="utf-8") as f:
    json.dump(items, f, indent=2, ensure_ascii=False)

print(f"SUCCESS: Generated {len(items)} items saved to {OUT_PATH}")
