<?php

namespace App\Console\Commands;

use App\Services\FatSecretService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFatSecretCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fatsecret:sync {--query= : Kata kunci spesifik yang ingin di-sync dari FatSecret} {--max=50 : Maksimal hasil per query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data bahan pangan langsung dari FatSecret API (Live) ke database/data/fatsecret.json';

    public function handle(FatSecretService $service): int
    {
        $this->info('===============================================================');
        $this->info(' Memeriksa Koneksi & Otentikasi FatSecret Platform API... ');
        $this->info('===============================================================');

        $token = $service->getAccessToken();
        if (!$token) {
            $this->error('Gagal mendapatkan OAuth 2.0 Access Token. Periksa Client ID & Client Secret Anda.');
            return 1;
        }

        $this->line('<info>✓ OAuth 2.0 Access Token berhasil didapatkan:</info> ' . substr($token, 0, 15) . '...');

        // Uji coba satu request untuk memeriksa IP Restriction
        $testRes = Http::withToken($token)
            ->timeout(12)
            ->get('https://platform.fatsecret.com/rest/server.api', [
                'method' => 'foods.search',
                'search_expression' => 'ayam',
                'format' => 'json',
                'max_results' => 1,
            ]);

        $testJson = $testRes->json();
        if (isset($testJson['error'])) {
            $code = $testJson['error']['code'] ?? 0;
            $msg = $testJson['error']['message'] ?? '';

            if ($code == 21) {
                preg_match("/'([0-9\.]+)'/", $msg, $m);
                $ip = $m[1] ?? '103.175.82.250';

                $this->newLine();
                $this->error(' [PERINGATAN DARI FATSECRET: IP RESTRICTION TERDETEKSI] ');
                $this->warn(" Pesan API: {$msg}");
                $this->newLine();
                $this->line(' <comment>Langkah untuk mengaktifkan sinkronisasi live FatSecret:</comment>');
                $this->line(' 1. Buka browser dan login ke: <info>https://platform.fatsecret.com/</info>');
                $this->line(' 2. Masuk ke menu <info>App Settings / IP Restrictions</info>.');
                $this->line(" 3. Tambahkan IP publik server ini: <options=bold;fg=red>{$ip}</> (atau gunakan <info>0.0.0.0/0</info> untuk mengizinkan semua IP).");
                $this->line(' 4. Simpan perubahan. (Catatan: FatSecret membutuhkan waktu beberapa menit s/d 1 jam untuk sinkronisasi IP).');
                $this->line(' 5. Setelah itu jalankan kembali: <info>php artisan fatsecret:sync</info>');
                $this->newLine();
                $this->info(" *Sistem saat ini tetap aman & lengkap menggunakan 1.100 bahan pangan terverifikasi di database/data/fatsecret.json.");
                return 1;
            }

            $this->error("Terjadi error dari FatSecret API: [{$code}] {$msg}");
            return 1;
        }

        $this->info('✓ Koneksi API FatSecret Terbuka! Memulai sinkronisasi bahan makanan...');

        $keywords = $this->option('query')
            ? [trim($this->option('query'))]
            : [
                'ayam', 'daging', 'sapi', 'ikan', 'udang', 'telur', 'beras', 'nasi',
                'tahu', 'tempe', 'bayam', 'wortel', 'kangkung', 'brokoli', 'pisang',
                'apel', 'jeruk', 'susu', 'keju', 'yogurt', 'kentang', 'ubi', 'minyak',
                'oat', 'roti', 'salmon', 'tuna', 'cumi', 'bawang', 'cabai'
            ];

        $jsonPath = database_path('data/fatsecret.json');
        $existing = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
        $existingMap = [];
        foreach ($existing as $item) {
            $existingMap[strtolower(trim($item['nama']))] = $item;
        }

        $addedCount = 0;
        $updatedCount = 0;

        foreach ($keywords as $kw) {
            $this->line("Mencari bahan untuk kata kunci: <comment>{$kw}</comment>...");
            $searchResult = $service->searchFoods($kw, 0, (int) $this->option('max'));
            if (!$searchResult['success'] && empty($searchResult['data'])) {
                continue;
            }

            $foods = $searchResult['data'] ?? [];
            foreach ($foods as $food) {
                $foodId = $food['fatsecret_id'] ?? null;
                if (!$foodId) continue;

                $detail = $service->getFoodDetails((string) $foodId);
                $target = $detail ?: $food;
                $key = strtolower(trim($target['nama']));

                if (isset($existingMap[$key])) {
                    $existingMap[$key] = array_merge($existingMap[$key], $target);
                    $updatedCount++;
                } else {
                    $existingMap[$key] = $target;
                    $addedCount++;
                }
            }
        }

        $finalList = array_values($existingMap);
        file_put_contents($jsonPath, json_encode($finalList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        Cache::forget('fatsecret_catalog_data');

        $this->newLine();
        $this->info("✓ Sinkronisasi Selesai! Total: " . count($finalList) . " bahan disimpan ke {$jsonPath}.");
        $this->info("  (Ditambahkan: {$addedCount} bahan baru, Diperbarui: {$updatedCount} bahan)");

        return 0;
    }
}
