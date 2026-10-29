<?php

namespace App\Services;

use App\Models\PengaturanApi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AiResiduService
{
    /**
     * Definisi Platform AI yang didukung secara universal
     */
    public const PROVIDERS = [
        'gemini' => [
            'id' => 'gemini',
            'name' => 'Google Gemini AI',
            'company' => 'Google',
            'badge' => 'Gratis & Cepat',
            'default_model' => 'gemini-3.1-flash-lite',
            'placeholder' => 'AIzaSy... atau AQ.Ab8...',
            'help_url' => 'https://aistudio.google.com/app/apikey',
            'help_text' => 'Dapatkan Google Gemini API Key gratis di Google AI Studio.',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_type' => 'gemini',
        ],
        'openai' => [
            'id' => 'openai',
            'name' => 'OpenAI (ChatGPT)',
            'company' => 'OpenAI',
            'badge' => 'Standar Industri',
            'default_model' => 'gpt-4o-mini',
            'placeholder' => 'sk-proj-... atau sk-...',
            'help_url' => 'https://platform.openai.com/api-keys',
            'help_text' => 'Dapatkan OpenAI API Key di dashboard OpenAI Platform.',
            'base_url' => 'https://api.openai.com/v1',
            'api_type' => 'openai',
        ],
        'groq' => [
            'id' => 'groq',
            'name' => 'Groq (Ultra-Fast LPU)',
            'company' => 'Groq',
            'badge' => 'Kecepatan Instan',
            'default_model' => 'llama-3.3-70b-versatile',
            'placeholder' => 'gsk_...',
            'help_url' => 'https://console.groq.com/keys',
            'help_text' => 'Dapatkan Groq API Key gratis dengan latensi super cepat di Groq Console.',
            'base_url' => 'https://api.groq.com/openai/v1',
            'api_type' => 'openai',
        ],
        'deepseek' => [
            'id' => 'deepseek',
            'name' => 'DeepSeek AI',
            'company' => 'DeepSeek',
            'badge' => 'Hemat & Cerdas',
            'default_model' => 'deepseek-chat',
            'placeholder' => 'sk-...',
            'help_url' => 'https://platform.deepseek.com/api_keys',
            'help_text' => 'Dapatkan DeepSeek API Key di platform DeepSeek.',
            'base_url' => 'https://api.deepseek.com',
            'api_type' => 'openai',
        ],
        'anthropic' => [
            'id' => 'anthropic',
            'name' => 'Anthropic Claude',
            'company' => 'Anthropic',
            'badge' => 'Penalaran Presisi',
            'default_model' => 'claude-3-5-haiku-latest',
            'placeholder' => 'sk-ant-...',
            'help_url' => 'https://console.anthropic.com/settings/keys',
            'help_text' => 'Dapatkan Claude API Key di Anthropic Console.',
            'base_url' => 'https://api.anthropic.com/v1',
            'api_type' => 'anthropic',
        ],
        'openrouter' => [
            'id' => 'openrouter',
            'name' => 'OpenRouter',
            'company' => 'OpenRouter',
            'badge' => 'Agregator Semua Model',
            'default_model' => 'meta-llama/llama-3.3-70b-instruct',
            'placeholder' => 'sk-or-...',
            'help_url' => 'https://openrouter.ai/keys',
            'help_text' => 'Dapatkan OpenRouter API Key untuk akses ke puluhan model open source & komersial.',
            'base_url' => 'https://openrouter.ai/api/v1',
            'api_type' => 'openai',
        ],
        'mistral' => [
            'id' => 'mistral',
            'name' => 'Mistral AI',
            'company' => 'Mistral',
            'badge' => 'Model Eropa',
            'default_model' => 'mistral-small-latest',
            'placeholder' => '...',
            'help_url' => 'https://console.mistral.ai/api-keys/',
            'help_text' => 'Dapatkan Mistral API Key di konsol Mistral La Plateforme.',
            'base_url' => 'https://api.mistral.ai/v1',
            'api_type' => 'openai',
        ],
    ];

    /**
     * Dapatkan daftar platform yang didukung untuk frontend
     */
    public function getSupportedProviders(): array
    {
        return array_values(self::PROVIDERS);
    }

    /**
     * Ambil daftar model secara REALTIME langsung dari API server platform yang dipilih
     */
    public function fetchLiveModels(string $provider, string $apiKey): array
    {
        $provider = strtolower(trim($provider));
        $apiKey = trim($apiKey);

        if (empty($apiKey)) {
            return [];
        }

        switch ($provider) {
            case 'gemini':
                return $this->fetchGeminiLiveModels($apiKey);

            case 'openai':
                return $this->fetchOpenAiLiveModels($apiKey, 'https://api.openai.com/v1');

            case 'groq':
                return $this->fetchOpenAiLiveModels($apiKey, 'https://api.groq.com/openai/v1', 'groq');

            case 'deepseek':
                return $this->fetchOpenAiLiveModels($apiKey, 'https://api.deepseek.com', 'deepseek');

            case 'openrouter':
                return $this->fetchOpenRouterLiveModels($apiKey);

            case 'anthropic':
                return $this->fetchAnthropicLiveModels($apiKey);

            case 'mistral':
                return $this->fetchOpenAiLiveModels($apiKey, 'https://api.mistral.ai/v1', 'mistral');

            default:
                return [];
        }
    }

    /**
     * Fetch Live Models: Google Gemini
     */
    protected function fetchGeminiLiveModels(string $apiKey): array
    {
        $response = Http::timeout(8)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("Google Gemini API Error: {$errorMsg}");
        }

        $modelsData = $response->json('models') ?? [];
        $result = [];

        foreach ($modelsData as $m) {
            $mName = $m['name'] ?? ''; // e.g. "models/gemini-2.0-flash"
            $cleanId = str_replace('models/', '', $mName);
            $methods = $m['supportedGenerationMethods'] ?? [];

            // Hanya model teks / content generation
            if (!in_array('generateContent', $methods)) {
                continue;
            }
            if (str_contains($cleanId, 'tts') || str_contains($cleanId, 'audio') || str_contains($cleanId, 'embed')) {
                continue;
            }

            $displayName = $m['displayName'] ?? $cleanId;
            $description = $m['description'] ?? 'Google Gemini Multimodal AI Model';

            $result[] = [
                'id' => $cleanId,
                'name' => $displayName,
                'description' => substr($description, 0, 100) . (strlen($description) > 100 ? '...' : ''),
                'badge' => str_contains($cleanId, 'flash') ? 'Cepat' : (str_contains($cleanId, 'pro') ? 'Penalaran' : ''),
            ];
        }

        // Urutkan model: flash di paling atas
        usort($result, function ($a, $b) {
            $aFlash = str_contains($a['id'], 'flash') ? 1 : 0;
            $bFlash = str_contains($b['id'], 'flash') ? 1 : 0;
            return $bFlash <=> $aFlash;
        });

        return $result;
    }

    /**
     * Fetch Live Models: OpenAI / Groq / DeepSeek / Mistral (Standar OpenAI GET /models)
     */
    protected function fetchOpenAiLiveModels(string $apiKey, string $baseUrl, string $platform = 'openai'): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->timeout(8)->get("{$baseUrl}/models");

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("API Error ({$platform}): {$errorMsg}");
        }

        $modelsData = $response->json('data') ?? [];
        $result = [];

        foreach ($modelsData as $m) {
            $id = $m['id'] ?? '';
            if (empty($id)) continue;

            // Filter model yang tidak relevan untuk chat / text parsing
            if ($platform === 'openai') {
                if (!preg_match('/^(gpt-|o1-|o3-|chatgpt)/i', $id)) {
                    continue;
                }
                if (preg_match('/(audio|realtime|transcription|embedding|tts|dall-e|moderation|search)/i', $id)) {
                    continue;
                }
            } elseif ($platform === 'groq') {
                if (preg_match('/(whisper|embedding|tts)/i', $id)) {
                    continue;
                }
            }

            $result[] = [
                'id' => $id,
                'name' => $id,
                'description' => $m['owned_by'] ?? ($platform . ' Model'),
                'badge' => (str_contains($id, 'mini') || str_contains($id, 'instant') || str_contains($id, 'small')) ? 'Hemat & Cepat' : 'Unggulan',
            ];
        }

        // Urutkan alfabetis terbalik atau model rekomendasi di atas
        usort($result, fn($a, $b) => strcmp($a['id'], $b['id']));

        return $result;
    }

    /**
     * Fetch Live Models: OpenRouter
     */
    protected function fetchOpenRouterLiveModels(string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->timeout(8)->get("https://openrouter.ai/api/v1/models");

        if (!$response->successful()) {
            $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("OpenRouter API Error: {$errorMsg}");
        }

        $modelsData = $response->json('data') ?? [];
        $result = [];

        // Ambil maksimal 50 model terpopuler / relevan
        $count = 0;
        foreach ($modelsData as $m) {
            if ($count >= 60) break;
            $id = $m['id'] ?? '';
            $name = $m['name'] ?? $id;
            $desc = $m['description'] ?? '';

            $result[] = [
                'id' => $id,
                'name' => $name,
                'description' => substr($desc, 0, 90) . (strlen($desc) > 90 ? '...' : ''),
                'badge' => str_contains($id, 'free') ? 'Gratis' : '',
            ];
            $count++;
        }

        return $result;
    }

    /**
     * Fetch Live Models: Anthropic Claude
     */
    protected function fetchAnthropicLiveModels(string $apiKey): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(8)->get("https://api.anthropic.com/v1/models");

        if (!$response->successful()) {
            // Jika endpoint /v1/models belum aktif untuk akun tertentu, sediakan model resmi Claude
            if ($response->status() === 404 || $response->status() === 405) {
                return [
                    ['id' => 'claude-3-5-haiku-latest', 'name' => 'Claude 3.5 Haiku', 'description' => 'Model tercepat dan paling efisien dari Anthropic', 'badge' => 'Cepat'],
                    ['id' => 'claude-3-5-sonnet-latest', 'name' => 'Claude 3.5 Sonnet', 'description' => 'Model unggulan untuk penalaran dan kecerdasan tinggi', 'badge' => 'Unggulan'],
                    ['id' => 'claude-3-opus-latest', 'name' => 'Claude 3 Opus', 'description' => 'Model penalaran kompleks kelas tertinggi', 'badge' => 'Power'],
                ];
            }
            $errorMsg = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("Anthropic API Error: {$errorMsg}");
        }

        $modelsData = $response->json('data') ?? [];
        $result = [];

        foreach ($modelsData as $m) {
            $id = $m['id'] ?? '';
            $name = $m['display_name'] ?? $id;
            $result[] = [
                'id' => $id,
                'name' => $name,
                'description' => 'Anthropic Claude AI Model',
                'badge' => str_contains($id, 'haiku') ? 'Cepat' : 'Penalaran',
            ];
        }

        return $result;
    }

    /**
     * Test Koneksi & Ambil Model Realtime
     */
    public function testConnection(string $provider, string $apiKey, ?string $model = null): array
    {
        $provider = strtolower(trim($provider));
        $apiKey = trim($apiKey);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'status' => 'gagal',
                'message' => 'Token / Kunci API belum diisi.',
                'models' => [],
            ];
        }

        try {
            $liveModels = $this->fetchLiveModels($provider, $apiKey);
            $count = count($liveModels);

            // Validasi model yang dipilih (jika ada)
            $modelFound = false;
            if ($model && $model !== 'auto') {
                foreach ($liveModels as $lm) {
                    if ($lm['id'] === $model) {
                        $modelFound = true;
                        break;
                    }
                }
            }

            return [
                'success' => true,
                'status' => 'terhubung',
                'provider' => $provider,
                'message' => "Koneksi berhasil terhubung! Ditemukan {$count} model aktif yang siap digunakan.",
                'models' => $liveModels,
                'selected_model_valid' => $modelFound,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status' => 'gagal',
                'provider' => $provider,
                'message' => $e->getMessage(),
                'models' => [],
            ];
        }
    }

    /**
     * Resolusi Konfigurasi AI Aktif untuk Unit SPPG yang sedang login
     */
    public function resolveActiveConfig(): ?PengaturanApi
    {
        try {
            $user = Auth::user();
            $unitId = null;

            if ($user) {
                $unit = method_exists($user, 'getCachedUnitSppg') ? $user->getCachedUnitSppg() : null;
                if (!$unit && method_exists($user, 'unitSppg')) {
                    $unit = $user->unitSppg;
                }
                $unitId = $unit?->id;
            }

            if (!$unitId) {
                $firstUnit = UnitSppg::first();
                $unitId = $firstUnit?->id;
            }

            if ($unitId) {
                // 1. Cari konfigurasi yang aktif dan memiliki api_key untuk unit ini
                $activeConfig = PengaturanApi::where('unit_sppg_id', $unitId)
                    ->where('is_active', true)
                    ->whereNotNull('api_key')
                    ->where('api_key', '!=', '')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($activeConfig) {
                    return $activeConfig;
                }

                // 2. Jika belum ada yang aktif, ambil konfigurasi apapun yang punya api_key
                $anyConfig = PengaturanApi::where('unit_sppg_id', $unitId)
                    ->whereNotNull('api_key')
                    ->where('api_key', '!=', '')
                    ->orderByDesc('updated_at')
                    ->first();

                if ($anyConfig) {
                    return $anyConfig;
                }
            }

            // 3. Fallback konfigurasi global dari database jika ada
            $globalConfig = PengaturanApi::whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->orderByDesc('is_active')
                ->orderByDesc('updated_at')
                ->first();

            if ($globalConfig) {
                return $globalConfig;
            }
        } catch (\Throwable $e) {
            Log::warning("Error resolving AI config: " . $e->getMessage());
        }

        // Cek fallback env jika ada GEMINI_API_KEY atau OPENAI_API_KEY
        $envGemini = env('GEMINI_API_KEY');
        if (!empty($envGemini)) {
            $dummy = new PengaturanApi();
            $dummy->provider = 'gemini';
            $dummy->api_key = $envGemini;
            $dummy->model = 'auto';
            $dummy->is_active = true;
            return $dummy;
        }

        $envOpenAi = env('OPENAI_API_KEY');
        if (!empty($envOpenAi)) {
            $dummy = new PengaturanApi();
            $dummy->provider = 'openai';
            $dummy->api_key = $envOpenAi;
            $dummy->model = 'gpt-4o-mini';
            $dummy->is_active = true;
            return $dummy;
        }

        return null;
    }

    /**
     * Parse input pesan chat / teks bebas menjadi array catatan residu beserta metadata eksekusi AI & token.
     */
    public function parseFromTextWithMeta(string $text): array
    {
        $startTime = microtime(true);
        $config = $this->resolveActiveConfig();
        $fallbackReason = null;

        if ($config && !empty($config->api_key)) {
            try {
                $aiResult = $this->callUniversalAiApiWithMeta($config, $text);
                if (!empty($aiResult['items'])) {
                    $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                    return [
                        'items' => $this->normalizeItems($aiResult['items'], $config->provider),
                        'execution' => [
                            'is_ai' => true,
                            'provider' => $config->provider,
                            'provider_name' => self::PROVIDERS[$config->provider]['name'] ?? ucfirst($config->provider),
                            'model' => $aiResult['model'] ?? $config->model,
                            'tokens' => $aiResult['tokens'] ?? [
                                'prompt_tokens' => 0,
                                'completion_tokens' => 0,
                                'total_tokens' => 0,
                            ],
                            'duration_ms' => $durationMs,
                            'fallback_reason' => null,
                        ],
                    ];
                }
            } catch (Exception $e) {
                $fallbackReason = $e->getMessage();
                Log::warning("Universal AI call ({$config->provider}) failed, falling back to smart heuristic: " . $fallbackReason);
            }
        } else {
            $fallbackReason = "Belum ada API Key aktif yang tersimpan untuk unit SPPG ini di menu Pengaturan > API Key.";
        }

        // Fallback: Smart Heuristic Local Parser (Tetap bekerja handal tanpa API Key!)
        $localItems = $this->smartLocalParse($text);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        return [
            'items' => $localItems,
            'execution' => [
                'is_ai' => false,
                'provider' => 'local_heuristic',
                'provider_name' => 'Smart Heuristic (Lokal / Tanpa AI)',
                'model' => 'Algoritma Regex & NLP Lokal SPPG',
                'tokens' => [
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0,
                ],
                'duration_ms' => $durationMs,
                'fallback_reason' => $fallbackReason,
            ],
        ];
    }

    /**
     * Parse input pesan chat / teks bebas (backward-compatible)
     */
    public function parseFromText(string $text): array
    {
        $res = $this->parseFromTextWithMeta($text);
        return $res['items'];
    }

    /**
     * Parse isi file dokumen universal (Word, Excel, PowerPoint, PDF, CSV, TXT, JSON, dll) beserta metadata eksekusi.
     */
    public function parseFromFileWithMeta($uploadedFile): array
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $realPath = $uploadedFile->getRealPath();

        // 1. Format Spreadsheet (Excel & CSV) dengan struktur kolom khusus
        if (in_array($extension, ['xlsx', 'xls', 'csv', 'ods'])) {
            $startTime = microtime(true);
            $extractedRows = [];
            try {
                $extractedRows = $this->readSpreadsheetRows($realPath, $extension);
            } catch (Exception $e) {
                Log::warning("Spreadsheet parse fallback: " . $e->getMessage());
            }

            if (!empty($extractedRows)) {
                $items = $this->mapSpreadsheetRowsToResidu($extractedRows);
                if (!empty($items)) {
                    $normalized = [];
                    foreach ($items as $item) {
                        $normalized[] = $this->enrichItemCorrelation($item);
                    }
                    $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                    return [
                        'items' => $this->normalizeItems($normalized, 'excel_doc'),
                        'execution' => [
                            'is_ai' => false,
                            'provider' => 'excel_parser',
                            'provider_name' => 'Spreadsheet Reader (Excel / CSV)',
                            'model' => 'Ekstraksi Kolom Otomatis',
                            'tokens' => [
                                'prompt_tokens' => 0,
                                'completion_tokens' => 0,
                                'total_tokens' => 0,
                            ],
                            'duration_ms' => $durationMs,
                            'fallback_reason' => null,
                        ],
                    ];
                }

                // Jika pemetaan tabel kolom tidak cocok, jadikan teks lalu kirim ke AI parser
                $textSummary = $this->convertRowsToTextSummary($extractedRows);
                if (!empty(trim($textSummary))) {
                    return $this->parseFromTextWithMeta($textSummary);
                }
            }
        }

        // 2. Ekstraksi Universal Teks Dokumen (Word docx/doc, PowerPoint pptx/ppt, PDF, TXT, RTF, JSON, ODF, dll)
        $docText = $this->extractTextFromAnyDocument($realPath, $extension);

        if (empty(trim($docText))) {
            throw new Exception("Dokumen '{$uploadedFile->getClientOriginalName()}' tidak memuat teks yang dapat dibaca. Pastikan dokumen memuat catatan teks (bukan scan gambar murni tanpa teks OCR).");
        }

        return $this->parseFromTextWithMeta($docText);
    }

    /**
     * Parse isi file dokumen (backward-compatible)
     */
    public function parseFromFile($uploadedFile): array
    {
        $res = $this->parseFromFileWithMeta($uploadedFile);
        return $res['items'];
    }

    /**
     * Eksekusi pemanggilan AI secara universal dengan mengembalikan metadata token & model
     */
    protected function callUniversalAiApiWithMeta(PengaturanApi $config, string $prompt): array
    {
        $provider = strtolower($config->provider);
        $apiKey = $config->api_key;
        $model = $config->model ?: 'auto';
        $today = Carbon::today()->format('Y-m-d');

        $systemInstruction = <<<EOT
Anda adalah asisten AI operasional SPPG (Sentra Pelayanan Pangan Gizi) untuk pencatatan residu & limbah dapur harian.
Tugas Anda adalah mengekstrak seluruh data limbah dari teks/dokumen pengguna menjadi array JSON objek residu yang lengkap dan valid.

Aturan Pemilihan Nilai (Strict Constraint):
1. tanggal: Wajib format YYYY-MM-DD. Perhatikan jika ada pengelompokan tanggal seperti "26/10/2026:". Jika pengguna menyebutkan "hari ini", gunakan "{$today}". Jika tanggal tidak disebutkan, gunakan "{$today}".
2. nama_limbah: Nama limbah/sisa yang jelas, bersih dari tanggal, nomor urut, atau kuantitas. Contoh: "Nasi", "Tulang Ayam", "Kulit Jagung", "Bonggol Jagung", "Sayur", "Lauk".
3. jumlah: Angka float desimal positif (contoh: 2.35, 18.2, 0.25).
4. satuan: HANYA boleh "Kg" atau "L". (Default: "Kg").
5. jenis_limbah: HANYA boleh "Organik" atau "Anorganik".
6. sumber_limbah: HANYA boleh salah satu dari: "Persiapan", "Pengolahan", "Ompreng", "Lainnya".
7. metode_penanganan: HANYA boleh salah satu dari: "Donasi", "Kompos", "Daur Ulang", "Pembuangan".
8. status_berbahaya: HANYA boleh "Tidak Berbahaya" atau "Berbahaya".
9. keterangan: Catatan penjelas ringkas.

Format Output WAJIB berupa JSON array murni tanpa markdown, contoh:
[
  {
    "tanggal": "2026-10-26",
    "nama_limbah": "Nasi",
    "jumlah": 3.4,
    "satuan": "Kg",
    "jenis_limbah": "Organik",
    "sumber_limbah": "Ompreng",
    "metode_penanganan": "Donasi",
    "status_berbahaya": "Tidak Berbahaya",
    "keterangan": "Sisa nasi ompreng"
  }
]
EOT;

        if ($provider === 'gemini') {
            return $this->callGeminiApiWithMeta($prompt, $apiKey, $model, $systemInstruction);
        } elseif ($provider === 'anthropic') {
            return $this->callAnthropicApiWithMeta($prompt, $apiKey, $model, $systemInstruction);
        } else {
            return $this->callOpenAiCompatibleApiWithMeta($provider, $prompt, $apiKey, $model, $systemInstruction);
        }
    }

    /**
     * Call Google Gemini API dengan pencatatan model dan token
     */
    protected function callGeminiApiWithMeta(string $prompt, string $apiKey, string $model, string $systemInstruction): array
    {
        $chosenModel = ($model === 'auto' || empty($model)) ? 'gemini-3.1-flash-lite' : $model;

        // Siapkan kandidat model tanpa menyertakan model audio/tts/image
        $candidates = [];
        if (!empty($model) && $model !== 'auto') {
            $cleanM = strtolower($model);
            if (!str_contains($cleanM, 'tts') && !str_contains($cleanM, 'audio') && !str_contains($cleanM, 'image')) {
                $candidates[] = $model;
            }
        }

        $candidates = array_merge($candidates, [
            'gemini-3.1-flash-lite',
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
            'gemini-3-flash-preview',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
        ]);
        $candidates = array_unique($candidates);
        $lastException = null;

        foreach ($candidates as $tryModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$tryModel}:generateContent?key={$apiKey}";
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => "{$systemInstruction}\n\nInput Pengguna:\n{$prompt}"],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

                if ($response->successful()) {
                    $resJson = $response->json();
                    $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
                    $items = $this->cleanAndDecodeJson($rawText);

                    $promptTokens = (int) ($resJson['usageMetadata']['promptTokenCount'] ?? 0);
                    $candTokens = (int) ($resJson['usageMetadata']['candidatesTokenCount'] ?? 0);
                    $totalTokens = (int) ($resJson['usageMetadata']['totalTokenCount'] ?? ($promptTokens + $candTokens));

                    return [
                        'items' => $items,
                        'model' => $tryModel,
                        'tokens' => [
                            'prompt_tokens' => $promptTokens,
                            'completion_tokens' => $candTokens,
                            'total_tokens' => $totalTokens,
                        ],
                    ];
                } elseif (in_array($response->status(), [404, 429, 500, 503])) {
                    $errText = $response->json('error.message') ?? "HTTP {$response->status()}";
                    $lastException = new Exception("Gemini API Model '{$tryModel}' ({$response->status()}): {$errText}");
                    continue;
                } else {
                    $errText = $response->json('error.message') ?? "HTTP {$response->status()}";
                    throw new Exception("Gemini API Error: {$errText}");
                }
            } catch (Exception $e) {
                $lastException = $e;
            }
        }

        if ($lastException) throw $lastException;
        throw new Exception("Seluruh kandidat model Gemini API tidak dapat dihubungi.");
    }

    /**
     * Call OpenAI-Compatible API dengan pencatatan model dan token
     */
    protected function callOpenAiCompatibleApiWithMeta(string $provider, string $prompt, string $apiKey, string $model, string $systemInstruction): array
    {
        $providerConfig = self::PROVIDERS[$provider] ?? self::PROVIDERS['openai'];
        $baseUrl = $providerConfig['base_url'];
        $chosenModel = ($model === 'auto' || empty($model)) ? $providerConfig['default_model'] : $model;
        $endpoint = "{$baseUrl}/chat/completions";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(12)->post($endpoint, [
            'model' => $chosenModel,
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
        ]);

        if (!$response->successful()) {
            $errText = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("{$providerConfig['name']} Error: {$errText}");
        }

        $rawText = $response->json('choices.0.message.content') ?? '[]';
        $items = $this->cleanAndDecodeJson($rawText);

        $promptTokens = (int) $response->json('usage.prompt_tokens', 0);
        $completionTokens = (int) $response->json('usage.completion_tokens', 0);
        $totalTokens = (int) $response->json('usage.total_tokens', $promptTokens + $completionTokens);

        return [
            'items' => $items,
            'model' => $chosenModel,
            'tokens' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
            ],
        ];
    }

    /**
     * Call Anthropic Claude API dengan pencatatan model dan token
     */
    protected function callAnthropicApiWithMeta(string $prompt, string $apiKey, string $model, string $systemInstruction): array
    {
        $chosenModel = ($model === 'auto' || empty($model)) ? 'claude-3-5-haiku-latest' : $model;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(12)->post('https://api.anthropic.com/v1/messages', [
            'model' => $chosenModel,
            'max_tokens' => 4096,
            'system' => $systemInstruction,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
        ]);

        if (!$response->successful()) {
            $errText = $response->json('error.message') ?? "HTTP {$response->status()}: " . $response->body();
            throw new Exception("Anthropic Claude Error: {$errText}");
        }

        $rawText = $response->json('content.0.text') ?? '[]';
        $items = $this->cleanAndDecodeJson($rawText);

        $inputTokens = (int) $response->json('usage.input_tokens', 0);
        $outputTokens = (int) $response->json('usage.output_tokens', 0);

        return [
            'items' => $items,
            'model' => $chosenModel,
            'tokens' => [
                'prompt_tokens' => $inputTokens,
                'completion_tokens' => $outputTokens,
                'total_tokens' => $inputTokens + $outputTokens,
            ],
        ];
    }

    /**
     * Helper: Bersihkan markdown pembungkus dan decode JSON
     */
    protected function cleanAndDecodeJson(string $rawText): array
    {
        $cleanJson = trim($rawText);
        $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanJson);
        $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

        $decoded = json_decode($cleanJson, true);
        if (is_array($decoded)) {
            if (isset($decoded['items']) && is_array($decoded['items'])) {
                return $decoded['items'];
            }
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                return $decoded['data'];
            }
            return $decoded;
        }

        return [];
    }

    /**
     * Smart Local Heuristic Parser (Sistem Fallback Cerdas Tanpa API Key)
     */
    public function smartLocalParse(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $items = [];
        $today = Carbon::today()->format('Y-m-d');
        $currentContextDate = $today;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Cek apakah baris merupakan header tanggal
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})[\s:]*$/i', $line, $matches) ||
                preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})[\s:]*$/i', $line, $matches)) {
                $currentContextDate = $this->parseDateSafely($line) ?: $currentContextDate;
                continue;
            }

            // Jika baris memuat tanggal di awal, gunakan tanggal tersebut
            $itemDate = $currentContextDate;
            if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $line, $dm)) {
                $parsed = $this->parseDateSafely($dm[1]);
                if ($parsed) $itemDate = $parsed;
            } elseif (preg_match('/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/', $line, $dm)) {
                $parsed = $this->parseDateSafely($dm[1]);
                if ($parsed) $itemDate = $parsed;
            }

            // Deteksi angka jumlah dan satuan
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilogram|liter|ltr|l)\b/i', $line, $qm)) {
                $qty = (float) str_replace(',', '.', $qm[1]);
                $satuan = in_array(strtolower($qm[2]), ['l', 'liter', 'ltr']) ? 'L' : 'Kg';

                // PEMBERSIHAN NAMA LIMBAH SECARA PRESISI:
                // 1. Buang tanggal lengkap 3-bagian terlebih dahulu (misal 26-10-2026 atau 2026-10-26)
                $cleanLine = preg_replace('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/', '', $line);
                $cleanLine = preg_replace('/\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b/', '', $cleanLine);

                // 2. Jika ada sisa fragmen tanggal di awal (misal 10-2026 atau 10/2026), buang
                $cleanLine = preg_replace('/^\s*\d{1,2}[\/\-]\d{2,4}\s*/', '', $cleanLine);

                // 3. Buang nomor urut di awal baris (hanya jika diikuti titik, kurung tutup, atau strip berjarak spasi)
                $cleanLine = preg_replace('/^\s*\d+[\.\)]\s*/', '', $cleanLine);
                $cleanLine = preg_replace('/^\s*\d+\s*[-–—]\s+/', '', $cleanLine);
                $cleanLine = preg_replace('/^\s*[•\-\*\+]\s*/', '', $cleanLine);

                // 4. Buang bagian jumlah dan satuan serta keterangan setelahnya
                $cleanLine = preg_replace('/(\d+(?:[.,]\d+)?)\s*(kg|kilogram|liter|ltr|l)\b.*/i', '', $cleanLine);

                // 5. Bersihkan tanda baca di awal dan akhir teks
                $cleanLine = preg_replace('/^[:,\-\s]+|[:,\-\s]+$/', '', $cleanLine);
                // Bersihkan sekali lagi jika ada sisa tanggal di depan
                $cleanLine = preg_replace('/^\d{1,4}[-\/]\d{1,4}\s*/', '', $cleanLine);

                $nama = trim($cleanLine);
                if (empty($nama)) $nama = 'Limbah Sisa Dapur';

                $item = [
                    'tanggal' => $itemDate,
                    'nama_limbah' => $nama,
                    'jumlah' => $qty,
                    'satuan' => $satuan,
                ];

                $items[] = $this->enrichItemCorrelation($item);
            }
        }

        return $this->normalizeItems($items, 'local_heuristic');
    }

    /**
     * Enrich Item dengan korelasi cerdas SPPG
     */
    protected function enrichItemCorrelation(array $item): array
    {
        $nama = strtolower($item['nama_limbah'] ?? '');

        // 1. Jenis Limbah
        $isAnorganik = preg_match('/(plastik|botol|kaleng|kardus|kresek|kemasan|kaca|styrofoam|sak|jerigen)/i', $nama);
        $jenis = $isAnorganik ? 'Anorganik' : 'Organik';

        // 2. Sumber Limbah
        $sumber = 'Persiapan';
        if (preg_match('/(ompreng|piring|wadah|makan siang|siswa|sisa nasi|sisa lauk)/i', $nama)) {
            $sumber = 'Ompreng';
        } elseif (preg_match('/(jelantah|goreng|kerak|wajan|dapur masak|rebus|bumbu|pengolahan)/i', $nama)) {
            $sumber = 'Pengolahan';
        } elseif ($isAnorganik || preg_match('/(kantor|supplier|pembongkaran|gudang)/i', $nama)) {
            $sumber = 'Lainnya';
        }

        // 3. Metode Penanganan
        $metode = 'Kompos';
        if ($isAnorganik) {
            $metode = 'Daur Ulang';
        } elseif (preg_match('/(nasi|lauk|ayam|daging|tulang|ternak|maggot)/i', $nama)) {
            $metode = 'Donasi';
        } elseif (preg_match('/(jelantah|minyak)/i', $nama)) {
            $metode = 'Daur Ulang';
        }

        // 4. Status Berbahaya
        $bahaya = 'Tidak Berbahaya';
        if (preg_match('/(kaca|pecahan|racun|kimia|pembersih|karbol|baterai|jarum)/i', $nama)) {
            $bahaya = 'Berbahaya';
        }

        return array_merge($item, [
            'jenis_limbah' => $item['jenis_limbah'] ?? $jenis,
            'sumber_limbah' => $item['sumber_limbah'] ?? $sumber,
            'metode_penanganan' => $item['metode_penanganan'] ?? $metode,
            'status_berbahaya' => $item['status_berbahaya'] ?? $bahaya,
            'keterangan' => $item['keterangan'] ?? "Catatan residu {$item['nama_limbah']}",
        ]);
    }

    /**
     * Normalisasi data ke format baku
     */
    protected function normalizeItems(array $rawItems, string $source = 'universal_ai'): array
    {
        $normalized = [];
        $today = Carbon::today()->format('Y-m-d');

        foreach ($rawItems as $item) {
            if (!is_array($item)) continue;

            $nama = trim($item['nama_limbah'] ?? $item['nama'] ?? '');
            if (empty($nama)) continue;

            $jumlah = (float) ($item['jumlah'] ?? $item['qty'] ?? 1.0);
            if ($jumlah <= 0) $jumlah = 1.0;

            $satuan = strtoupper(trim($item['satuan'] ?? 'Kg'));
            $satuan = in_array($satuan, ['L', 'LITER', 'LTR']) ? 'L' : 'Kg';

            $tgl = $this->parseDateSafely($item['tanggal'] ?? null) ?: $today;
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $tgl)) {
                $tgl = $today;
            }

            $normalized[] = [
                'tanggal' => $tgl,
                'nama_limbah' => ucwords(strtolower($nama)),
                'jumlah' => round($jumlah, 2),
                'satuan' => $satuan,
                'jenis_limbah' => in_array($item['jenis_limbah'] ?? '', ['Organik', 'Anorganik']) ? $item['jenis_limbah'] : 'Organik',
                'sumber_limbah' => in_array($item['sumber_limbah'] ?? '', ['Persiapan', 'Pengolahan', 'Ompreng', 'Lainnya']) ? $item['sumber_limbah'] : 'Persiapan',
                'metode_penanganan' => in_array($item['metode_penanganan'] ?? '', ['Donasi', 'Kompos', 'Daur Ulang', 'Pembuangan']) ? $item['metode_penanganan'] : 'Kompos',
                'status_berbahaya' => in_array($item['status_berbahaya'] ?? '', ['Tidak Berbahaya', 'Berbahaya']) ? $item['status_berbahaya'] : 'Tidak Berbahaya',
                'keterangan' => trim($item['keterangan'] ?? ''),
                'ai_source' => $source,
            ];
        }

        return $normalized;
    }

    /**
     * Parse tanggal secara aman dan presisi (Mendukung format Y-m-d, d-m-Y, Excel Serial Date, dan Tanggal Indonesia).
     * Mencegah dan memperbaiki otomatis cacat tanggal seperti '2026-00-24' menjadi tanggal valid.
     */
    protected function parseDateSafely($dateStr, ?string $contextDate = null): ?string
    {
        $today = Carbon::today()->format('Y-m-d');
        $currentMonth = (int) date('m');

        if ($contextDate && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $contextDate, $cm)) {
            $currentMonth = (int)$cm[2];
        }

        if (empty($dateStr)) {
            return $contextDate ?: $today;
        }

        $str = trim((string)$dateStr);

        // 1. Dukungan Excel Serial Date (contoh: 46291 -> 2026-09-26)
        if (is_numeric($str) && (float)$str > 20000 && (float)$str < 80000) {
            $unix = ((float)$str - 25569) * 86400;
            return gmdate('Y-m-d', (int)$unix);
        }

        try {
            $bulanIndo = [
                'januari' => 1, 'jan' => 1, 'january' => 1,
                'februari' => 2, 'feb' => 2, 'pebruari' => 2, 'february' => 2,
                'maret' => 3, 'mar' => 3, 'march' => 3,
                'april' => 4, 'apr' => 4,
                'mei' => 5, 'may' => 5,
                'juni' => 6, 'jun' => 6, 'june' => 6,
                'juli' => 7, 'jul' => 7, 'july' => 7,
                'agustus' => 8, 'agu' => 8, 'agt' => 8, 'aug' => 8, 'august' => 8,
                'september' => 9, 'sep' => 9, 'sept' => 9,
                'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
                'november' => 11, 'nov' => 11, 'nop' => 11,
                'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
            ];

            // 2. Format dengan nama bulan (contoh: 24-Okt-2026, 24/Oktober/2026, 24 Sept 2026)
            if (preg_match('/^(\d{1,2})[\s\/\-]+([a-zA-Z]+)[\s\/\-]+(\d{2,4})$/i', $str, $bm)) {
                $bNama = strtolower($bm[2]);
                if (isset($bulanIndo[$bNama])) {
                    $day = (int)$bm[1];
                    $month = $bulanIndo[$bNama];
                    $year = strlen($bm[3]) === 2 ? (int)('20' . $bm[3]) : (int)$bm[3];
                    if (checkdate($month, $day, $year)) {
                        return sprintf('%04d-%02d-%02d', $year, $month, $day);
                    }
                }
            }

            // 3. Format YYYY-MM-DD atau YYYY/MM/DD (Koreksi cacat bulan 00 seperti 2026-00-24)
            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $str, $m)) {
                $year = (int)$m[1];
                $month = (int)$m[2];
                $day = (int)$m[3];

                if ($month < 1 || $month > 12) {
                    $month = $currentMonth;
                }

                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }

            // 4. Format DD-MM-YYYY atau DD/MM/YYYY (Koreksi cacat bulan 00 seperti 24-00-2026)
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $str, $m)) {
                $day = (int)$m[1];
                $month = (int)$m[2];
                $year = strlen($m[3]) === 2 ? (int)('20' . $m[3]) : (int)$m[3];

                if ($month < 1 || $month > 12) {
                    $month = $currentMonth;
                }

                if (checkdate($month, $day, $year)) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }

            $carbon = Carbon::parse($str);
            if ($carbon && checkdate($carbon->month, $carbon->day, $carbon->year)) {
                return $carbon->format('Y-m-d');
            }

            return $contextDate ?: $today;
        } catch (\Throwable $e) {
            return $contextDate ?: $today;
        }
    }

    /**
     * Baca baris dari Spreadsheet (Excel / CSV)
     */
    protected function readSpreadsheetRows(string $filePath, string $ext): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            return $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            Log::warning("Spreadsheet load error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Map baris tabel spreadsheet ke array residu secara presisi dan instan
     */
    protected function mapSpreadsheetRowsToResidu(array $rows): array
    {
        if (count($rows) < 2) return [];

        $headerIndex = -1;
        $colMap = [];

        // Deteksi baris header tabel
        foreach ($rows as $idx => $row) {
            if (!is_array($row)) continue;

            $tempMap = [];
            $hasNama = false;
            $hasJumlah = false;

            foreach ($row as $cIdx => $rawVal) {
                $val = strtolower(trim((string)$rawVal));
                if (empty($val)) continue;

                // 1. Jenis Limbah (Cek terlebih dahulu sebelum Nama agar kata "limbah" tidak salah cocok)
                if (preg_match('/jenis\s*(?:limbah)?/i', $val) || $val === 'jenis') {
                    $tempMap['jenis'] = $cIdx;
                }
                // 2. Sumber Limbah
                elseif (preg_match('/sumber\s*(?:limbah)?/i', $val) || $val === 'sumber') {
                    $tempMap['sumber'] = $cIdx;
                }
                // 3. Metode Penanganan
                elseif (preg_match('/(?:metode|cara)\s*(?:penanganan)?|penanganan/i', $val)) {
                    $tempMap['metode'] = $cIdx;
                }
                // 4. Status Bahaya
                elseif (preg_match('/status\s*(?:bahaya|berbahaya)?|bahaya/i', $val)) {
                    $tempMap['status_bahaya'] = $cIdx;
                }
                // 5. Keterangan
                elseif (preg_match('/keterangan|catatan|notes?/i', $val)) {
                    $tempMap['keterangan'] = $cIdx;
                }
                // 6. Tanggal
                elseif (preg_match('/tanggal|tgl|date/i', $val)) {
                    $tempMap['tanggal'] = $cIdx;
                }
                // 7. Satuan
                elseif (preg_match('/satuan|unit/i', $val)) {
                    $tempMap['satuan'] = $cIdx;
                }
                // 8. Jumlah / Qty
                elseif (preg_match('/jumlah|qty|kuantitas|berat|volume|bobot/i', $val)) {
                    $tempMap['jumlah'] = $cIdx;
                    $hasJumlah = true;
                }
                // 9. Nama Limbah (Hanya jika bukan nomor, jenis, atau sumber)
                elseif (!preg_match('/^(?:no|nomor)\b/i', $val) && preg_match('/(?:nama\s*(?:limbah|residu|barang)?|residu|limbah|uraian|bahan|deskripsi|komoditas)/i', $val)) {
                    $tempMap['nama'] = $cIdx;
                    $hasNama = true;
                }
            }

            // Jika baris memuat Nama dan Jumlah (atau Qty), ini adalah Header Tabel!
            if ($hasNama && $hasJumlah) {
                $headerIndex = $idx;
                $colMap = $tempMap;
                break;
            }
        }

        // Jika tidak ditemukan header eksplisit, coba cari baris pertama yang memuat kata "Nama" atau "Limbah"
        if ($headerIndex === -1) {
            foreach ($rows as $idx => $row) {
                if (!is_array($row)) continue;
                foreach ($row as $cIdx => $rawVal) {
                    $val = strtolower(trim((string)$rawVal));
                    if (str_contains($val, 'nama') || str_contains($val, 'residu')) {
                        // Periksa apakah di baris yang sama ada kolom yang berbau jumlah/angka
                        foreach ($row as $c2Idx => $val2) {
                            $v2 = strtolower(trim((string)$val2));
                            if (str_contains($v2, 'jumlah') || str_contains($v2, 'berat') || str_contains($v2, 'qty') || str_contains($v2, 'kg')) {
                                $headerIndex = $idx;
                                $colMap['nama'] = $cIdx;
                                $colMap['jumlah'] = $c2Idx;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        if ($headerIndex === -1 || !isset($colMap['nama']) || !isset($colMap['jumlah'])) {
            return [];
        }

        $items = [];
        $currentDateContext = Carbon::today()->format('Y-m-d');

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!is_array($row)) continue;

            $nama = trim((string)($row[$colMap['nama']] ?? ''));
            $qtyRaw = trim((string)($row[$colMap['jumlah']] ?? ''));

            if (empty($nama) || empty($qtyRaw)) continue;

            // Abaikan baris ringkasan/total atau tanda tangan pejabat di bawah tabel
            $namaLower = strtolower($nama);
            if (preg_match('/^(?:total|jumlah keseluruhan|mengetahui|kepala|petugas|ttd|pj pengadaan|akuntan)\b/i', $namaLower)) {
                continue;
            }

            // Bersihkan angka jumlah
            $qtyClean = (float) str_replace(',', '.', preg_replace('/[^\d.,]/', '', $qtyRaw));
            if ($qtyClean <= 0) continue;

            // Ekstrak satuan
            $satuan = 'Kg';
            if (isset($colMap['satuan']) && !empty($row[$colMap['satuan']])) {
                $rawSat = strtoupper(trim((string)$row[$colMap['satuan']]));
                $satuan = in_array($rawSat, ['L', 'LITER', 'LTR']) ? 'L' : 'Kg';
            }

            // Ekstrak tanggal
            $tanggal = null;
            if (isset($colMap['tanggal']) && !empty($row[$colMap['tanggal']])) {
                $tanggal = $this->parseDateSafely($row[$colMap['tanggal']]);
            }
            if (!$tanggal) {
                $tanggal = $currentDateContext;
            } else {
                $currentDateContext = $tanggal;
            }

            // Ekstrak kolom lainnya jika tersedia di Excel
            $jenis = null;
            if (isset($colMap['jenis']) && !empty($row[$colMap['jenis']])) {
                $rawJenis = trim((string)$row[$colMap['jenis']]);
                if (in_array(ucwords(strtolower($rawJenis)), ['Organik', 'Anorganik'])) {
                    $jenis = ucwords(strtolower($rawJenis));
                }
            }

            $sumber = null;
            if (isset($colMap['sumber']) && !empty($row[$colMap['sumber']])) {
                $rawSumber = trim((string)$row[$colMap['sumber']]);
                if (in_array(ucwords(strtolower($rawSumber)), ['Persiapan', 'Pengolahan', 'Ompreng', 'Lainnya'])) {
                    $sumber = ucwords(strtolower($rawSumber));
                }
            }

            $metode = null;
            if (isset($colMap['metode']) && !empty($row[$colMap['metode']])) {
                $rawMetode = trim((string)$row[$colMap['metode']]);
                if (in_array(ucwords(strtolower($rawMetode)), ['Donasi', 'Kompos', 'Daur Ulang', 'Pembuangan'])) {
                    $metode = ucwords(strtolower($rawMetode));
                }
            }

            $statusBahaya = null;
            if (isset($colMap['status_bahaya']) && !empty($row[$colMap['status_bahaya']])) {
                $rawStatus = trim((string)$row[$colMap['status_bahaya']]);
                if (in_array(ucwords(strtolower($rawStatus)), ['Tidak Berbahaya', 'Berbahaya'])) {
                    $statusBahaya = ucwords(strtolower($rawStatus));
                }
            }

            $keterangan = isset($colMap['keterangan']) ? trim((string)$row[$colMap['keterangan']]) : '';

            $item = [
                'tanggal' => $tanggal,
                'nama_limbah' => $nama,
                'jumlah' => $qtyClean,
                'satuan' => $satuan,
                'jenis_limbah' => $jenis,
                'sumber_limbah' => $sumber,
                'metode_penanganan' => $metode,
                'status_berbahaya' => $statusBahaya,
                'keterangan' => $keterangan,
            ];

            // Lengkapi dengan korelasi cerdas SPPG jika ada kolom yang kosong di Excel
            $items[] = $this->enrichItemCorrelation($item);
        }

        return $items;
    }

    /**
     * Konversi baris spreadsheet ke ringkasan teks untuk diproses parser
     */
    protected function convertRowsToTextSummary(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $filtered = array_filter(array_map('trim', $row));
            if (!empty($filtered)) {
                $lines[] = implode(' ', $filtered);
            }
        }
        return implode("\n", array_slice($lines, 0, 100));
    }

    /**
     * Ekstrak teks dari berbagai jenis format dokumen (Word, PDF, PowerPoint, TXT, RTF, ODF, dll)
     */
    public function extractTextFromAnyDocument(string $filePath, string $extension): string
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['txt', 'text', 'json', 'log', 'csv', 'tsv', 'md'])) {
            return (string) @file_get_contents($filePath);
        }

        if (in_array($extension, ['docx', 'dotx'])) {
            return $this->extractTextFromDocx($filePath);
        }

        if (in_array($extension, ['pptx', 'ppsx', 'potx'])) {
            return $this->extractTextFromPptx($filePath);
        }

        if (in_array($extension, ['odt', 'odp', 'ods'])) {
            return $this->extractTextFromOdf($filePath);
        }

        if ($extension === 'pdf') {
            return $this->extractTextFromPdf($filePath);
        }

        if ($extension === 'rtf') {
            return $this->extractTextFromRtf($filePath);
        }

        if (in_array($extension, ['doc', 'ppt'])) {
            return $this->extractTextFromLegacyBinary($filePath);
        }

        // Fallback: ekstrak teks printable UTF-8 / ASCII
        $raw = @file_get_contents($filePath);
        if ($raw) {
            preg_match_all('/[\x20-\x7E\xA0-\xFF]{4,}/', $raw, $matches);
            return implode("\n", $matches[0] ?? []);
        }

        return '';
    }

    /**
     * Ekstrak teks dari file DOCX menggunakan ZipArchive bawaan PHP
     */
    protected function extractTextFromDocx(string $filePath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            return '';
        }

        $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:tr>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:tc>/i', " ", $xml);

        $text = strip_tags($xml);
        return html_entity_decode(trim($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Ekstrak teks dari file PPTX menggunakan ZipArchive bawaan PHP
     */
    protected function extractTextFromPptx(string $filePath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $text = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (preg_match('/^ppt\/slides\/slide[0-9]+\.xml$/i', $entry)) {
                $slideXml = $zip->getFromName($entry);
                if ($slideXml) {
                    $slideXml = preg_replace('/<\/a:p>/i', "\n", $slideXml);
                    $text .= "\n" . strip_tags($slideXml);
                }
            }
        }
        $zip->close();

        return html_entity_decode(trim($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Ekstrak teks dari file OpenDocument (ODT / ODP / ODS)
     */
    protected function extractTextFromOdf(string $filePath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();

        if (!$xml) {
            return '';
        }

        $xml = preg_replace('/<\/text:p>/i', "\n", $xml);
        $xml = preg_replace('/<\/table:table-row>/i', "\n", $xml);
        return html_entity_decode(trim(strip_tags($xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Ekstrak teks dari file RTF
     */
    protected function extractTextFromRtf(string $filePath): string
    {
        $rtf = @file_get_contents($filePath);
        if (!$rtf) return '';

        $rtf = preg_replace('/{\\\\colortbl;.*?}/s', '', $rtf);
        $rtf = preg_replace('/{\\\\fonttbl;.*?}/s', '', $rtf);
        $rtf = preg_replace('/\\\\[a-z]+(\d+)? ?/i', ' ', $rtf);
        $rtf = str_replace(['{', '}'], '', $rtf);
        return trim($rtf);
    }

    /**
     * Ekstrak teks dari file biner DOC / PPT format lama (OLE compound binary)
     */
    protected function extractTextFromLegacyBinary(string $filePath): string
    {
        $raw = @file_get_contents($filePath);
        if (!$raw) return '';

        preg_match_all('/[\x20-\x7E\xA0-\xFF]{4,}/', $raw, $matches);
        return implode("\n", $matches[0] ?? []);
    }

    /**
     * Ekstrak teks dari file PDF secara mandiri (Native stream & text extraction)
     */
    protected function extractTextFromPdf(string $filePath): string
    {
        $content = @file_get_contents($filePath);
        if (!$content) return '';

        $text = '';

        // 1. Ekstrak stream dalam PDF
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches)) {
            foreach ($streamMatches[1] as $stream) {
                // Coba dekompresi FlateDecode
                $decompressed = @gzuncompress($stream);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($stream);
                }
                if ($decompressed === false) {
                    $decompressed = $stream;
                }

                // Periksa apakah terdapat blok teks PDF (BT ... ET)
                if (preg_match_all('/BT[\s\S]*?ET/m', $decompressed, $btMatches)) {
                    foreach ($btMatches[0] as $btBlock) {
                        // Pola (Teks) Tj atau ' atau "
                        if (preg_match_all('/\((.*?)\)\s*(?:Tj|\'|\")/s', $btBlock, $tjMatches)) {
                            foreach ($tjMatches[1] as $match) {
                                $text .= ' ' . stripcslashes($match);
                            }
                            $text .= "\n";
                        }
                        // Pola array [(Teks) 120 (Lainnya)] TJ
                        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $btBlock, $tjArrayMatches)) {
                            foreach ($tjArrayMatches[1] as $arrMatch) {
                                if (preg_match_all('/\((.*?)\)/s', $arrMatch, $innerMatches)) {
                                    foreach ($innerMatches[1] as $in) {
                                        $text .= stripcslashes($in);
                                    }
                                    $text .= ' ';
                                }
                            }
                            $text .= "\n";
                        }
                    }
                }
            }
        }

        // 2. Fallback jika PDF menggunakan encoding stream berbeda atau raw printable text
        if (mb_strlen(trim($text)) < 20) {
            preg_match_all('/[\x20-\x7E\xA0-\xFF]{4,}/', $content, $printable);
            $cleanLines = [];
            foreach ($printable[0] ?? [] as $chunk) {
                $trimmed = trim($chunk);
                if (preg_match('/^(obj|endobj|stream|endstream|xref|trailer|startxref|\/[A-Z0-9]+|<<|>>)/i', $trimmed)) {
                    continue;
                }
                if (strlen($trimmed) >= 3) {
                    $cleanLines[] = $trimmed;
                }
            }
            $text = implode("\n", $cleanLines);
        }

        return trim($text);
    }
}

