<?php

namespace App\Http\Controllers;

use App\Models\PengaturanApi;
use App\Services\AiResiduService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Tampilkan halaman manajemen API Key Multi-Platform
     */
    public function index(Request $request, AiResiduService $aiService): Response
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $supportedProviders = $aiService->getSupportedProviders();
        $savedConfigs = [];

        if ($unitSppg) {
            $configs = PengaturanApi::where('unit_sppg_id', $unitSppg->id)->get();
            foreach ($configs as $cfg) {
                $savedConfigs[$cfg->provider] = [
                    'id' => $cfg->id,
                    'provider' => $cfg->provider,
                    'api_key' => $cfg->api_key,
                    'model' => $cfg->model,
                    'is_active' => (bool) $cfg->is_active,
                    'status_koneksi' => $cfg->status_koneksi,
                    'last_tested_at' => $cfg->last_tested_at?->format('d/m/Y H:i'),
                    'pesan_koneksi' => $cfg->pesan_koneksi,
                ];
            }
        }

        // Cari konfigurasi yang aktif saat ini, jika belum ada default ke 'gemini'
        $activeProvider = 'gemini';
        foreach ($savedConfigs as $p => $c) {
            if (!empty($c['is_active']) && !empty($c['api_key'])) {
                $activeProvider = $p;
                break;
            }
        }

        return Inertia::render('Pengaturan/ApiKey/Index', [
            'supportedProviders' => $supportedProviders,
            'savedConfigs' => $savedConfigs,
            'defaultActiveProvider' => $activeProvider,
            'unitSppg' => $unitSppg,
        ]);
    }

    /**
     * Simpan / Perbarui Pengaturan API Key untuk platform tertentu
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        if (!$unitSppg) {
            return back()->with('error', 'Unit SPPG tidak ditemukan.');
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'model' => ['required', 'string', 'max:150'],
            'is_active' => ['required', 'boolean'],
            'status_koneksi' => ['nullable', 'string', 'max:30'],
            'pesan_koneksi' => ['nullable', 'string'],
        ]);

        $provider = strtolower(trim($validated['provider']));
        $isActive = (bool) $validated['is_active'];

        // Jika provider ini diaktifkan, nonaktifkan provider lain untuk unit_sppg ini
        if ($isActive) {
            PengaturanApi::where('unit_sppg_id', $unitSppg->id)
                ->where('provider', '!=', $provider)
                ->update(['is_active' => false]);
        }

        $updateData = [
            'api_key' => trim($validated['api_key'] ?? ''),
            'model' => trim($validated['model'] ?? 'auto'),
            'is_active' => $isActive,
        ];

        if (isset($validated['status_koneksi'])) {
            $updateData['status_koneksi'] = $validated['status_koneksi'];
            $updateData['last_tested_at'] = now();
        }
        if (isset($validated['pesan_koneksi'])) {
            $updateData['pesan_koneksi'] = $validated['pesan_koneksi'];
        }

        PengaturanApi::updateOrCreate(
            [
                'unit_sppg_id' => $unitSppg->id,
                'provider' => $provider,
            ],
            $updateData
        );

        $providerName = AiResiduService::PROVIDERS[$provider]['name'] ?? strtoupper($provider);
        return back()->with('success', "Pengaturan API Key untuk {$providerName} berhasil disimpan.");
    }

    /**
     * Uji Koneksi & Ambil Daftar Model secara REALTIME dari platform yang dipilih
     */
    public function testKoneksi(Request $request, AiResiduService $aiService): JsonResponse
    {
        $user = $request->user()->load('unitSppg');
        $unitSppg = $user->unitSppg;

        $provider = strtolower(trim((string) $request->input('provider', 'gemini')));
        $apiKey = trim((string) $request->input('api_key', ''));
        $selectedModel = trim((string) $request->input('model', ''));

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'provider' => $provider,
                'message' => 'API Key belum diisi. Silakan masukkan token platform terlebih dahulu.',
                'models' => [],
            ], 422);
        }

        // Jalankan tes koneksi dan fetch model secara realtime
        $result = $aiService->testConnection($provider, $apiKey, $selectedModel);

        // Jika user memiliki unit SPPG, simpan status tes koneksi ke database secara otomatis
        if ($unitSppg) {
            try {
                PengaturanApi::updateOrCreate(
                    [
                        'unit_sppg_id' => $unitSppg->id,
                        'provider' => $provider,
                    ],
                    [
                        'api_key' => $apiKey,
                        'model' => $selectedModel ?: ($result['models'][0]['id'] ?? 'auto'),
                        'status_koneksi' => $result['status'],
                        'last_tested_at' => now(),
                        'pesan_koneksi' => $result['message'],
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning("Auto-save test connection error: " . $e->getMessage());
            }
        }

        return response()->json($result);
    }
}
