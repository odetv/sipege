<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FatSecretService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl = 'https://oauth.fatsecret.com/connect/token';
    protected string $apiUrl = 'https://platform.fatsecret.com/rest/server.api';

    public function __construct()
    {
        $this->clientId = config('services.fatsecret.client_id', env('FATSECRET_CLIENT_ID', '23aaa6c30de54f859ba35e602cb11561'));
        $this->clientSecret = config('services.fatsecret.client_secret', env('FATSECRET_CLIENT_SECRET', 'd70a526703bc4e4f8bcb46e771a932b6'));
    }

    /**
     * Mendapatkan OAuth 2.0 Access Token
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('fatsecret_access_token', now()->addHours(23), function () {
            try {
                $response = Http::asForm()
                    ->withBasicAuth($this->clientId, $this->clientSecret)
                    ->timeout(10)
                    ->post($this->tokenUrl, [
                        'grant_type' => 'client_credentials',
                        'scope' => 'basic',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'] ?? null;
                }

                Log::error('FatSecret OAuth Token Error: ' . $response->body());
                return null;
            } catch (\Throwable $e) {
                Log::error('FatSecret OAuth Exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Cari bahan pangan di FatSecret (Live API dengan fallback cerdas ke Katalog Lengkap)
     *
     * @param string $query
     * @param int $page
     * @param int $maxResults
     * @return array{success: bool, data: array, error_code?: int, error_message?: string, ip_detected?: string, total?: int}
     */
    public function searchFoods(string $query, int $page = 0, int $maxResults = 50): array
    {
        $cleanQuery = trim($query);
        if (empty($cleanQuery)) {
            $all = $this->getPopularFoods();
            return [
                'success' => true,
                'data' => array_slice($all, 0, $maxResults),
                'total' => count($all),
            ];
        }

        $cacheKey = 'fatsecret_search_' . md5(strtolower($cleanQuery) . '_' . $page . '_' . $maxResults);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($cleanQuery, $page, $maxResults) {
            $token = $this->getAccessToken();
            if (!$token) {
                $localMatches = $this->searchLocalCatalog($cleanQuery);
                return [
                    'success' => false,
                    'error_code' => 500,
                    'error_message' => 'Gagal mengotentikasi ke FatSecret API (Invalid OAuth Credentials). Menampilkan hasil katalog lokal.',
                    'data' => $localMatches,
                    'total' => count($localMatches),
                ];
            }

            try {
                $response = Http::withToken($token)
                    ->timeout(12)
                    ->get($this->apiUrl, [
                        'method' => 'foods.search',
                        'search_expression' => $cleanQuery,
                        'format' => 'json',
                        'page_number' => $page,
                        'max_results' => $maxResults,
                    ]);

                $json = $response->json();

                // Cek error dari FatSecret (misal IP Restriction error code 21)
                if (isset($json['error'])) {
                    $errorCode = (int) ($json['error']['code'] ?? 0);
                    $errorMsg = (string) ($json['error']['message'] ?? 'Terjadi kesalahan dari FatSecret API.');
                    $detectedIp = null;

                    if ($errorCode === 21 && preg_match("/'([0-9\.]+)'/", $errorMsg, $matches)) {
                        $detectedIp = $matches[1];
                    }

                    // Fallback cerdas: cari dari 1.100+ database pangan lokal FatSecret
                    $localMatches = $this->searchLocalCatalog($cleanQuery);

                    return [
                        'success' => false,
                        'error_code' => $errorCode,
                        'error_message' => $errorMsg,
                        'ip_detected' => $detectedIp,
                        'data' => $localMatches,
                        'total' => count($localMatches),
                    ];
                }

                $foodsData = $json['foods']['food'] ?? [];
                if (!empty($foodsData) && !isset($foodsData[0])) {
                    $foodsData = [$foodsData];
                }

                $total = (int) ($json['foods']['total_results'] ?? count($foodsData));

                $formatted = array_map(function ($item) {
                    return $this->formatFoodItem($item);
                }, $foodsData);

                return [
                    'success' => true,
                    'data' => $formatted,
                    'total' => $total,
                ];
            } catch (\Throwable $e) {
                Log::error('FatSecret Search Exception: ' . $e->getMessage());
                $localMatches = $this->searchLocalCatalog($cleanQuery);
                return [
                    'success' => false,
                    'error_code' => 500,
                    'error_message' => 'Koneksi ke FatSecret API gagal: ' . $e->getMessage() . '. Menampilkan hasil katalog lokal.',
                    'data' => $localMatches,
                    'total' => count($localMatches),
                ];
            }
        });
    }

    /**
     * Mengambil detail gizi bahan pangan berdasarkan food_id (sesuai spesifikasi food.get.v5 / food.get.v2)
     */
    public function getFoodDetails(string $foodId): ?array
    {
        $cleanId = str_replace('FS_', '', $foodId);
        $cacheKey = 'fatsecret_food_' . $cleanId;

        return Cache::rememberForever($cacheKey, function () use ($cleanId, $foodId) {
            $token = $this->getAccessToken();
            if ($token) {
                try {
                    // Coba food.get.v5 terlebih dahulu sesuai docs terbaru FatSecret
                    $response = Http::withToken($token)
                        ->timeout(12)
                        ->get($this->apiUrl, [
                            'method' => 'food.get.v5',
                            'food_id' => $cleanId,
                            'format' => 'json',
                        ]);

                    $json = $response->json();
                    if (!isset($json['error']) && isset($json['food'])) {
                        return $this->formatDetailedFood($json['food']);
                    }

                    // Fallback ke food.get.v2 jika v5 tidak didukung versi tier
                    $responseV2 = Http::withToken($token)
                        ->timeout(12)
                        ->get($this->apiUrl, [
                            'method' => 'food.get.v2',
                            'food_id' => $cleanId,
                            'format' => 'json',
                        ]);

                    $jsonV2 = $responseV2->json();
                    if (!isset($jsonV2['error']) && isset($jsonV2['food'])) {
                        return $this->formatDetailedFood($jsonV2['food']);
                    }
                } catch (\Throwable $e) {
                    Log::error("FatSecret Detail Exception for {$cleanId}: " . $e->getMessage());
                }
            }

            // Fallback: cari di katalog database/data/fatsecret.json
            return $this->findInLocalCatalog($foodId);
        });
    }

    /**
     * Format item dari foods.search menjadi format standar SIPEGE
     */
    protected function formatFoodItem(array $raw): array
    {
        $foodId = (string) ($raw['food_id'] ?? uniqid());
        $name = (string) ($raw['food_name'] ?? 'Bahan Makanan FatSecret');
        $desc = (string) ($raw['food_description'] ?? '');
        $type = (string) ($raw['food_type'] ?? 'Generic');

        $parsed = $this->parseFoodDescription($desc);
        $category = $this->mapCategory($name, $type);

        // Ambil perkiraan mikronutrien standar agar tidak kosong jika belum di-load detail
        $estimated = $this->estimateNutrients($category, $parsed['protein'], $parsed['lemak'], $parsed['karbohidrat']);

        return [
            'id' => 'FS_' . $foodId,
            'code' => 'FS_' . $foodId,
            'nama' => $name,
            'nama_en' => $name,
            'kategori' => $category,
            'satuan' => 'Kg',
            'bdd' => 100.0,
            'air' => $estimated['air'],
            'energi' => $parsed['energi'],
            'protein' => $parsed['protein'],
            'lemak' => $parsed['lemak'],
            'karbohidrat' => $parsed['karbohidrat'],
            'serat' => $parsed['serat'] > 0 ? $parsed['serat'] : $estimated['serat'],
            'abu' => $estimated['abu'],
            'kalsium' => $estimated['kalsium'],
            'fosfor' => $estimated['fosfor'],
            'besi' => $estimated['besi'],
            'natrium' => $estimated['natrium'],
            'kalium' => $estimated['kalium'],
            'tembaga' => $estimated['tembaga'],
            'seng' => $estimated['seng'],
            'retinol' => $estimated['retinol'],
            'b_karoten' => $estimated['b_karoten'],
            'karoten_total' => $estimated['karoten_total'],
            'tiamin' => $estimated['tiamin'],
            'thiamin' => $estimated['tiamin'],
            'riboflavin' => $estimated['riboflavin'],
            'niasin' => $estimated['niasin'],
            'vitamin_c' => $estimated['vitamin_c'],
            'alergen' => $this->detectAllergenFromName($name),
            'sumber' => 'FatSecret (fatsecret.com)',
            'fatsecret_id' => $foodId,
            'deskripsi_serving' => $desc,
        ];
    }

    /**
     * Parse string deskripsi bawaan FatSecret
     */
    protected function parseFoodDescription(string $desc): array
    {
        $energi = 0.0;
        $lemak = 0.0;
        $karbo = 0.0;
        $protein = 0.0;
        $serat = 0.0;

        if (preg_match('/Calories:\s*([0-9\.]+)\s*kcal/i', $desc, $m)) {
            $energi = (float) $m[1];
        }
        if (preg_match('/Fat:\s*([0-9\.]+)\s*g/i', $desc, $m)) {
            $lemak = (float) $m[1];
        }
        if (preg_match('/Carbs:\s*([0-9\.]+)\s*g/i', $desc, $m)) {
            $karbo = (float) $m[1];
        }
        if (preg_match('/Protein:\s*([0-9\.]+)\s*g/i', $desc, $m)) {
            $protein = (float) $m[1];
        }

        // Jika porsi bukan 100g, normalisasikan ke 100g
        if (preg_match('/Per\s+([0-9\.]+)\s*g/i', $desc, $m)) {
            $gramServing = (float) $m[1];
            if ($gramServing > 0 && abs($gramServing - 100.0) > 0.01) {
                $factor = 100.0 / $gramServing;
                $energi = round($energi * $factor, 1);
                $lemak = round($lemak * $factor, 2);
                $karbo = round($karbo * $factor, 2);
                $protein = round($protein * $factor, 2);
            }
        }

        return [
            'energi' => $energi,
            'lemak' => $lemak,
            'karbohidrat' => $karbo,
            'protein' => $protein,
            'serat' => $serat,
        ];
    }

    /**
     * Format item detail dari food.get.v5 / food.get.v2
     */
    protected function formatDetailedFood(array $food): array
    {
        $foodId = (string) ($food['food_id'] ?? uniqid());
        $name = (string) ($food['food_name'] ?? 'Bahan Makanan FatSecret');
        $type = (string) ($food['food_type'] ?? 'Generic');

        $servings = $food['servings']['serving'] ?? [];
        if (!empty($servings) && !isset($servings[0])) {
            $servings = [$servings];
        }

        // Cari serving 100g atau serving terstandar (serving_id = 0)
        $bestServing = null;
        foreach ($servings as $s) {
            $amount = (float) ($s['metric_serving_amount'] ?? 0);
            $unit = strtolower((string) ($s['metric_serving_unit'] ?? ''));
            if ($amount == 100 && ($unit === 'g' || $unit === 'ml')) {
                $bestServing = $s;
                break;
            }
        }

        if (!$bestServing) {
            foreach ($servings as $s) {
                if (($s['serving_id'] ?? '') === '0') {
                    $bestServing = $s;
                    break;
                }
            }
        }

        if (!$bestServing && !empty($servings)) {
            $bestServing = $servings[0];
        }

        $multiplier = 1.0;
        if ($bestServing) {
            $amount = (float) ($bestServing['metric_serving_amount'] ?? 100);
            if ($amount > 0 && abs($amount - 100) > 0.01) {
                $multiplier = 100.0 / $amount;
            }
        }

        $s = $bestServing ?? [];
        $category = $this->mapCategory($name, $type);

        $energi = round(((float) ($s['calories'] ?? 0)) * $multiplier, 1);
        $protein = round(((float) ($s['protein'] ?? 0)) * $multiplier, 2);
        $lemak = round(((float) ($s['fat'] ?? 0)) * $multiplier, 2);
        $karbo = round(((float) ($s['carbohydrate'] ?? 0)) * $multiplier, 2);
        $serat = round(((float) ($s['fiber'] ?? 0)) * $multiplier, 2);
        $kalsium = round(((float) ($s['calcium'] ?? 0)) * $multiplier, 1);
        $besi = round(((float) ($s['iron'] ?? 0)) * $multiplier, 2);
        $natrium = round(((float) ($s['sodium'] ?? 0)) * $multiplier, 1);
        $kalium = round(((float) ($s['potassium'] ?? 0)) * $multiplier, 1);
        $vitA = round(((float) ($s['vitamin_a'] ?? 0)) * $multiplier, 1);
        $vitC = round(((float) ($s['vitamin_c'] ?? 0)) * $multiplier, 1);

        // Ekstraksi alergen resmi FatSecret dari food_attributes
        $allergensList = [];
        if (isset($food['food_attributes']['allergens']['allergen'])) {
            $rawAllergens = $food['food_attributes']['allergens']['allergen'];
            if (!isset($rawAllergens[0])) {
                $rawAllergens = [$rawAllergens];
            }
            foreach ($rawAllergens as $a) {
                if (($a['value'] ?? 0) == 1) {
                    $aName = (string) ($a['name'] ?? '');
                    $allergensList[] = match (strtolower($aName)) {
                        'milk' => 'Susu / Laktosa',
                        'lactose' => 'Susu / Laktosa',
                        'egg' => 'Telur',
                        'fish' => 'Ikan',
                        'gluten' => 'Gluten',
                        'nuts', 'peanuts' => 'Kacang-kacangan',
                        'shellfish' => 'Seafood / Krustasea',
                        'soy' => 'Kedelai',
                        'sesame' => 'Wijen',
                        default => $aName,
                    };
                }
            }
        }

        $allergenStr = !empty($allergensList)
            ? implode(', ', array_unique($allergensList))
            : $this->detectAllergenFromName($name);

        $estimated = $this->estimateNutrients($category, $protein, $lemak, $karbo);

        return [
            'id' => 'FS_' . $foodId,
            'code' => 'FS_' . $foodId,
            'nama' => $name,
            'nama_en' => $name,
            'kategori' => $category,
            'satuan' => 'Kg',
            'bdd' => 100.0,
            'air' => $estimated['air'],
            'energi' => $energi,
            'protein' => $protein,
            'lemak' => $lemak,
            'karbohidrat' => $karbo,
            'serat' => $serat > 0 ? $serat : $estimated['serat'],
            'abu' => $estimated['abu'],
            'kalsium' => $kalsium > 0 ? $kalsium : $estimated['kalsium'],
            'fosfor' => $estimated['fosfor'],
            'besi' => $besi > 0 ? $besi : $estimated['besi'],
            'natrium' => $natrium > 0 ? $natrium : $estimated['natrium'],
            'kalium' => $kalium > 0 ? $kalium : $estimated['kalium'],
            'tembaga' => $estimated['tembaga'],
            'seng' => $estimated['seng'],
            'retinol' => $vitA > 0 ? $vitA : $estimated['retinol'],
            'b_karoten' => $estimated['b_karoten'],
            'karoten_total' => $estimated['karoten_total'],
            'tiamin' => $estimated['tiamin'],
            'thiamin' => $estimated['tiamin'],
            'riboflavin' => $estimated['riboflavin'],
            'niasin' => $estimated['niasin'],
            'vitamin_c' => $vitC > 0 ? $vitC : $estimated['vitamin_c'],
            'alergen' => $allergenStr,
            'sumber' => 'FatSecret (fatsecret.com)',
            'fatsecret_id' => $foodId,
            'deskripsi_serving' => $s['serving_description'] ?? 'Per 100g',
        ];
    }

    /**
     * Memetakan kategori FatSecret ke kategori pangan standar Indonesia
     */
    protected function mapCategory(string $name, string $defaultType): string
    {
        $n = strtolower($name);

        if (str_contains($n, 'ayam') || str_contains($n, 'daging') || str_contains($n, 'sapi') || str_contains($n, 'kambing') || str_contains($n, 'bebek') || str_contains($n, 'bakso') || str_contains($n, 'sosis') || str_contains($n, 'kornet') || str_contains($n, 'nugget')) {
            return 'Daging & Olahan';
        }
        if (str_contains($n, 'ikan') || str_contains($n, 'udang') || str_contains($n, 'cumi') || str_contains($n, 'lele') || str_contains($n, 'tongkol') || str_contains($n, 'nila') || str_contains($n, 'salmon') || str_contains($n, 'tuna') || str_contains($n, 'kepiting') || str_contains($n, 'kerang')) {
            return 'Ikan & Hasil Laut';
        }
        if (str_contains($n, 'telur')) {
            return 'Telur';
        }
        if (str_contains($n, 'tahu') || str_contains($n, 'tempe') || str_contains($n, 'kacang') || str_contains($n, 'kedelai') || str_contains($n, 'edamame')) {
            return 'Kacang-kacangan & Olahan';
        }
        if (str_contains($n, 'bayam') || str_contains($n, 'kangkung') || str_contains($n, 'wortel') || str_contains($n, 'kubis') || str_contains($n, 'sayur') || str_contains($n, 'buncis') || str_contains($n, 'brokoli') || str_contains($n, 'sawi') || str_contains($n, 'labu')) {
            return 'Sayuran';
        }
        if (str_contains($n, 'pisang') || str_contains($n, 'apel') || str_contains($n, 'jeruk') || str_contains($n, 'pepaya') || str_contains($n, 'semangka') || str_contains($n, 'melon') || str_contains($n, 'buah') || str_contains($n, 'mangga') || str_contains($n, 'nanas')) {
            return 'Buah-buahan';
        }
        if (str_contains($n, 'beras') || str_contains($n, 'nasi') || str_contains($n, 'jagung') || str_contains($n, 'kentang') || str_contains($n, 'ubi') || str_contains($n, 'singkong') || str_contains($n, 'gandum') || str_contains($n, 'roti') || str_contains($n, 'mie') || str_contains($n, 'oat') || str_contains($n, 'quinoa')) {
            return 'Serealia & Umbi';
        }
        if (str_contains($n, 'susu') || str_contains($n, 'keju') || str_contains($n, 'yogurt')) {
            return 'Susu & Olahan';
        }
        if (str_contains($n, 'minyak') || str_contains($n, 'mentega') || str_contains($n, 'margarin')) {
            return 'Minyak & Lemak';
        }
        if (str_contains($n, 'bawang') || str_contains($n, 'garam') || str_contains($n, 'gula') || str_contains($n, 'cabai') || str_contains($n, 'merica') || str_contains($n, 'ketumbar') || str_contains($n, 'kunyit') || str_contains($n, 'jahe') || str_contains($n, 'kecap') || str_contains($n, 'saus')) {
            return 'Bumbu & Rempah';
        }

        return 'Makanan Olahan & Minuman';
    }

    /**
     * Estimasi nilai gizi mikronutrien ilmiah untuk melengkapi kolom yang tidak dikembalikan API
     */
    protected function estimateNutrients(string $category, float $protein, float $lemak, float $karbo): array
    {
        $water = round(max(5.0, 100.0 - ($protein + $lemak + $karbo + 3.0)), 1);

        $defaults = [
            'Daging & Olahan' => [
                'abu' => 1.2, 'kalsium' => 16.0, 'fosfor' => 200.0, 'besi' => 2.5, 'natrium' => 75.0,
                'kalium' => 320.0, 'tembaga' => 0.12, 'seng' => 3.8, 'retinol' => 15.0, 'b_karoten' => 0.0,
                'karoten_total' => 0.0, 'tiamin' => 0.12, 'riboflavin' => 0.22, 'niasin' => 6.5, 'vitamin_c' => 0.0, 'serat' => 0.0
            ],
            'Ikan & Hasil Laut' => [
                'abu' => 1.4, 'kalsium' => 45.0, 'fosfor' => 210.0, 'besi' => 1.8, 'natrium' => 95.0,
                'kalium' => 310.0, 'tembaga' => 0.18, 'seng' => 1.6, 'retinol' => 35.0, 'b_karoten' => 0.0,
                'karoten_total' => 0.0, 'tiamin' => 0.10, 'riboflavin' => 0.15, 'niasin' => 4.8, 'vitamin_c' => 0.5, 'serat' => 0.0
            ],
            'Telur' => [
                'abu' => 1.0, 'kalsium' => 56.0, 'fosfor' => 180.0, 'besi' => 2.4, 'natrium' => 140.0,
                'kalium' => 130.0, 'tembaga' => 0.08, 'seng' => 1.4, 'retinol' => 140.0, 'b_karoten' => 25.0,
                'karoten_total' => 25.0, 'tiamin' => 0.09, 'riboflavin' => 0.45, 'niasin' => 0.1, 'vitamin_c' => 0.0, 'serat' => 0.0
            ],
            'Sayuran' => [
                'abu' => 1.0, 'kalsium' => 55.0, 'fosfor' => 40.0, 'besi' => 1.8, 'natrium' => 25.0,
                'kalium' => 280.0, 'tembaga' => 0.15, 'seng' => 0.6, 'retinol' => 0.0, 'b_karoten' => 1200.0,
                'karoten_total' => 1500.0, 'tiamin' => 0.08, 'riboflavin' => 0.12, 'niasin' => 1.0, 'vitamin_c' => 28.0, 'serat' => 2.2
            ],
            'Buah-buahan' => [
                'abu' => 0.6, 'kalsium' => 20.0, 'fosfor' => 18.0, 'besi' => 0.6, 'natrium' => 5.0,
                'kalium' => 210.0, 'tembaga' => 0.08, 'seng' => 0.2, 'retinol' => 0.0, 'b_karoten' => 350.0,
                'karoten_total' => 450.0, 'tiamin' => 0.05, 'riboflavin' => 0.04, 'niasin' => 0.6, 'vitamin_c' => 35.0, 'serat' => 2.0
            ],
            'Serealia & Umbi' => [
                'abu' => 1.2, 'kalsium' => 25.0, 'fosfor' => 150.0, 'besi' => 1.5, 'natrium' => 10.0,
                'kalium' => 120.0, 'tembaga' => 0.20, 'seng' => 1.2, 'retinol' => 0.0, 'b_karoten' => 0.0,
                'karoten_total' => 0.0, 'tiamin' => 0.15, 'riboflavin' => 0.08, 'niasin' => 2.2, 'vitamin_c' => 0.0, 'serat' => 2.5
            ],
            'Kacang-kacangan & Olahan' => [
                'abu' => 2.8, 'kalsium' => 95.0, 'fosfor' => 220.0, 'besi' => 3.8, 'natrium' => 15.0,
                'kalium' => 450.0, 'tembaga' => 0.40, 'seng' => 2.5, 'retinol' => 0.0, 'b_karoten' => 30.0,
                'karoten_total' => 30.0, 'tiamin' => 0.35, 'riboflavin' => 0.18, 'niasin' => 2.5, 'vitamin_c' => 1.0, 'serat' => 4.8
            ],
            'Susu & Olahan' => [
                'abu' => 0.8, 'kalsium' => 120.0, 'fosfor' => 95.0, 'besi' => 0.2, 'natrium' => 50.0,
                'kalium' => 150.0, 'tembaga' => 0.03, 'seng' => 0.5, 'retinol' => 40.0, 'b_karoten' => 15.0,
                'karoten_total' => 15.0, 'tiamin' => 0.04, 'riboflavin' => 0.18, 'niasin' => 0.2, 'vitamin_c' => 1.0, 'serat' => 0.0
            ],
            'Minyak & Lemak' => [
                'abu' => 0.1, 'kalsium' => 2.0, 'fosfor' => 1.0, 'besi' => 0.1, 'natrium' => 5.0,
                'kalium' => 5.0, 'tembaga' => 0.01, 'seng' => 0.05, 'retinol' => 0.0, 'b_karoten' => 0.0,
                'karoten_total' => 0.0, 'tiamin' => 0.0, 'riboflavin' => 0.0, 'niasin' => 0.0, 'vitamin_c' => 0.0, 'serat' => 0.0
            ],
        ];

        $def = $defaults[$category] ?? [
            'abu' => 1.0, 'kalsium' => 30.0, 'fosfor' => 50.0, 'besi' => 1.0, 'natrium' => 50.0,
            'kalium' => 150.0, 'tembaga' => 0.10, 'seng' => 0.8, 'retinol' => 0.0, 'b_karoten' => 50.0,
            'karoten_total' => 50.0, 'tiamin' => 0.08, 'riboflavin' => 0.08, 'niasin' => 1.0, 'vitamin_c' => 5.0, 'serat' => 1.0
        ];

        $def['air'] = $water;
        return $def;
    }

    /**
     * Deteksi alergen dari nama bahan pangan
     */
    protected function detectAllergenFromName(string $name): string
    {
        $n = strtolower($name);
        $allergens = [];

        if (str_contains($n, 'susu') || str_contains($n, 'keju') || str_contains($n, 'yogurt') || str_contains($n, 'mentega') || str_contains($n, 'butter')) {
            $allergens[] = 'Susu / Laktosa';
        }
        if (str_contains($n, 'telur')) {
            $allergens[] = 'Telur';
        }
        if (str_contains($n, 'ikan') || str_contains($n, 'tongkol') || str_contains($n, 'lele') || str_contains($n, 'salmon') || str_contains($n, 'tuna') || str_contains($n, 'bandeng')) {
            $allergens[] = 'Ikan';
        }
        if (str_contains($n, 'udang') || str_contains($n, 'cumi') || str_contains($n, 'kepiting') || str_contains($n, 'kerang') || str_contains($n, 'seafood')) {
            $allergens[] = 'Seafood / Krustasea';
        }
        if (str_contains($n, 'kedelai') || str_contains($n, 'tahu') || str_contains($n, 'tempe') || str_contains($n, 'kecap') || str_contains($n, 'edamame')) {
            $allergens[] = 'Kedelai';
        }
        if (str_contains($n, 'kacang tanah') || str_contains($n, 'kacang mede') || str_contains($n, 'almond') || str_contains($n, 'walnut')) {
            $allergens[] = 'Kacang-kacangan';
        }
        if (str_contains($n, 'gandum') || str_contains($n, 'terigu') || str_contains($n, 'roti') || str_contains($n, 'mie') || str_contains($n, 'pasta') || str_contains($n, 'biskuit') || str_contains($n, 'oat')) {
            $allergens[] = 'Gluten';
        }
        if (str_contains($n, 'wijen')) {
            $allergens[] = 'Wijen';
        }

        return implode(', ', $allergens);
    }

    /**
     * Mengambil katalog lengkap 1.100+ bahan pangan FatSecret terverifikasi (Per 100g)
     */
    public function getPopularFoods(): array
    {
        return Cache::rememberForever('fatsecret_catalog_data', function () {
            $path = database_path('data/fatsecret.json');
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $decoded = json_decode($content, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return $decoded;
                }
            }
            return [];
        });
    }

    /**
     * Cari di katalog lokal
     */
    protected function searchLocalCatalog(string $query): array
    {
        $all = $this->getPopularFoods();
        $q = strtolower(trim($query));

        if (empty($q)) {
            return array_slice($all, 0, 50);
        }

        $results = [];
        foreach ($all as $item) {
            $nama = strtolower($item['nama'] ?? '');
            $namaEn = strtolower($item['nama_en'] ?? '');
            $kat = strtolower($item['kategori'] ?? '');
            $code = strtolower($item['code'] ?? '');

            if (str_contains($nama, $q) || str_contains($namaEn, $q) || str_contains($kat, $q) || str_contains($code, $q)) {
                $results[] = $item;
            }
        }

        return array_slice($results, 0, 100);
    }

    /**
     * Cari satu item di katalog lokal
     */
    protected function findInLocalCatalog(string $foodId): ?array
    {
        $all = $this->getPopularFoods();
        $cleanId = str_replace('FS_', '', $foodId);

        foreach ($all as $item) {
            if ($item['id'] === $foodId || $item['code'] === $foodId || ($item['fatsecret_id'] ?? '') === $cleanId) {
                return $item;
            }
        }

        return null;
    }
}
