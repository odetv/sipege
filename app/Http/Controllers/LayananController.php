<?php

namespace App\Http\Controllers;

use App\Models\PengaturanApi;
use App\Models\PengaturanLayanan;
use App\Models\UnitSppg;
use App\Services\AiResiduService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LayananController extends Controller
{
    /**
     * Tampilkan halaman manajemen Layanan Terpadu (Database, Storage, Mail, AI)
     * Pengaturan bersifat global untuk seluruh pengguna sistem.
     */
    public function index(Request $request, AiResiduService $aiService): Response
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : null;
        if (!$unitSppg) {
            $unitSppg = UnitSppg::first();
        }

        // ── 1. Data Layanan AI (Global) ─────────────────────────────
        $supportedProviders = $aiService->getSupportedProviders();
        $savedConfigs = [];

        $allApiConfigs = PengaturanApi::all();
        foreach ($allApiConfigs as $cfg) {
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

        $activeProvider = 'gemini';
        foreach ($savedConfigs as $p => $c) {
            if (!empty($c['is_active']) && !empty($c['api_key'])) {
                $activeProvider = $p;
                break;
            }
        }

        // ── 2. Data Layanan Database ────────────────────────────────
        $dbDefault = config('database.default', 'pgsql');
        $dbConfig = config("database.connections.{$dbDefault}", []);
        
        $dbStatus = 'terhubung';
        $dbLatency = 0;
        $dbVersion = 'Unknown';
        $dbTablesCount = 0;

        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $start) * 1000, 2);

            if ($dbDefault === 'pgsql') {
                $v = DB::select("SELECT version()");
                $dbVersion = $v[0]->version ?? 'PostgreSQL';
                $tables = DB::select("SELECT count(*) as total FROM information_schema.tables WHERE table_schema = 'public'");
                $dbTablesCount = (int) ($tables[0]->total ?? 0);
            } elseif ($dbDefault === 'mysql') {
                $v = DB::select("SELECT VERSION() as version");
                $dbVersion = $v[0]->version ?? 'MySQL';
                $tables = DB::select("SELECT count(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
                $dbTablesCount = (int) ($tables[0]->total ?? 0);
            } else {
                $dbVersion = 'SQLite / Lainnya';
            }
        } catch (\Throwable $e) {
            $dbStatus = 'gagal';
            $dbVersion = 'Error: ' . $e->getMessage();
        }

        // Masking username database
        $rawUser = (string) ($dbConfig['username'] ?? '');
        $maskedUser = strlen($rawUser) > 10 ? substr($rawUser, 0, 8) . '...' . substr($rawUser, -4) : $rawUser;

        $databaseInfo = [
            'driver' => $dbDefault,
            'driver_label' => $dbDefault === 'pgsql' ? 'PostgreSQL (Supabase Cloud)' : strtoupper($dbDefault),
            'host' => $dbConfig['host'] ?? '127.0.0.1',
            'port' => $dbConfig['port'] ?? ($dbDefault === 'pgsql' ? '5432' : '3306'),
            'database' => $dbConfig['database'] ?? '',
            'username_masked' => $maskedUser,
            'status' => $dbStatus,
            'latency_ms' => $dbLatency,
            'version' => $dbVersion,
            'tables_count' => $dbTablesCount,
            'sslmode' => $dbConfig['sslmode'] ?? 'prefer',
        ];

        // ── 3. Data Layanan Storage (Global Database Setting) ───────
        $dbStorage = PengaturanLayanan::getKategori('storage');
        $storageDriver = $dbStorage?->driver ?? 'local';
        $storageConfig = $dbStorage?->konfigurasi ?? [
            'account_id' => '',
            'access_key_id' => '',
            'secret_access_key' => '',
            'bucket' => '',
            'region' => 'auto',
            'endpoint' => '',
            'public_domain' => '',
        ];

        $storageAppPath = storage_path('app');
        $publicStoragePath = storage_path('app/public');
        $symlinkTarget = public_path('storage');
        $isSymlinkLinked = file_exists($symlinkTarget);

        $freeBytes = @disk_free_space(storage_path());
        $totalBytes = @disk_total_space(storage_path());
        $freeGb = $freeBytes ? round($freeBytes / (1024 * 1024 * 1024), 2) : null;
        $totalGb = $totalBytes ? round($totalBytes / (1024 * 1024 * 1024), 2) : null;

        $storageInfo = [
            'active_driver' => $storageDriver, // 'local' atau 'cloud' (Cloudflare R2)
            'r2_config' => [
                'account_id' => $storageConfig['account_id'] ?? '',
                'access_key_id' => $storageConfig['access_key_id'] ?? '',
                'secret_access_key' => $storageConfig['secret_access_key'] ?? '',
                'bucket' => $storageConfig['bucket'] ?? '',
                'region' => $storageConfig['region'] ?? 'auto',
                'endpoint' => $storageConfig['endpoint'] ?? '',
                'public_domain' => $storageConfig['public_domain'] ?? '',
            ],
            'status_koneksi' => $dbStorage?->status_koneksi ?? 'belum_dites',
            'pesan_koneksi' => $dbStorage?->pesan_koneksi ?? null,
            'last_tested_at' => $dbStorage?->last_tested_at?->format('d/m/Y H:i'),
            // Info lokal
            'default_disk' => config('filesystems.default', 'local'),
            'is_app_writable' => is_writable($storageAppPath),
            'is_logs_writable' => is_writable(storage_path('logs')),
            'is_framework_writable' => is_writable(storage_path('framework')),
            'is_symlink_linked' => $isSymlinkLinked,
            'storage_path' => $storageAppPath,
            'public_path' => $publicStoragePath,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'free_space_gb' => $freeGb,
            'total_space_gb' => $totalGb,
        ];

        // ── 4. Data Layanan Mail (Global Database Setting) ──────────
        $dbMail = PengaturanLayanan::getKategori('mail');
        $mailDriver = $dbMail?->driver ?? 'log';
        $mailConfig = $dbMail?->konfigurasi ?? [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_address' => '',
            'from_name' => 'SIPEGE SPPG',
        ];

        $mailInfo = [
            'active_driver' => $mailDriver, // 'smtp' (Google Mail) atau 'log'
            'smtp_config' => [
                'host' => $mailConfig['host'] ?? 'smtp.gmail.com',
                'port' => (int) ($mailConfig['port'] ?? 587),
                'encryption' => $mailConfig['encryption'] ?? 'tls',
                'username' => $mailConfig['username'] ?? '',
                'password' => $mailConfig['password'] ?? '',
                'from_address' => $mailConfig['from_address'] ?? '',
                'from_name' => $mailConfig['from_name'] ?? 'SIPEGE SPPG',
            ],
            'status_koneksi' => $dbMail?->status_koneksi ?? 'belum_dites',
            'pesan_koneksi' => $dbMail?->pesan_koneksi ?? null,
            'last_tested_at' => $dbMail?->last_tested_at?->format('d/m/Y H:i'),
            // Fallback config env
            'queue_connection' => config('queue.default', 'sync'),
        ];

        return Inertia::render('Pengaturan/Layanan/Index', [
            'databaseInfo' => $databaseInfo,
            'storageInfo' => $storageInfo,
            'mailInfo' => $mailInfo,
            'supportedProviders' => $supportedProviders,
            'savedConfigs' => $savedConfigs,
            'defaultActiveProvider' => $activeProvider,
            'unitSppg' => $unitSppg,
            'initialTab' => $request->query('tab', 'database'),
        ]);
    }

    /**
     * Simpan / Perbarui Konfigurasi Penyimpanan (Storage: Lokal vs Cloudflare R2)
     */
    public function updateStorage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', 'string', 'in:local,cloud'],
            'account_id' => ['nullable', 'string', 'max:150'],
            'access_key_id' => ['nullable', 'string', 'max:150'],
            'secret_access_key' => ['nullable', 'string', 'max:250'],
            'bucket' => ['nullable', 'string', 'max:150'],
            'region' => ['nullable', 'string', 'max:50'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'public_domain' => ['nullable', 'string', 'max:255'],
            'status_koneksi' => ['nullable', 'string', 'max:30'],
            'pesan_koneksi' => ['nullable', 'string'],
        ]);

        $driver = $validated['driver'];
        $accountId = trim((string) ($validated['account_id'] ?? ''));
        $endpoint = trim((string) ($validated['endpoint'] ?? ''));
        if (empty($endpoint) && !empty($accountId)) {
            $endpoint = "https://{$accountId}.r2.cloudflarestorage.com";
        }

        $configData = [
            'account_id' => $accountId,
            'access_key_id' => trim((string) ($validated['access_key_id'] ?? '')),
            'secret_access_key' => trim((string) ($validated['secret_access_key'] ?? '')),
            'bucket' => trim((string) ($validated['bucket'] ?? '')),
            'region' => trim((string) ($validated['region'] ?? 'auto')) ?: 'auto',
            'endpoint' => $endpoint,
            'public_domain' => trim((string) ($validated['public_domain'] ?? '')),
        ];

        $updateData = [
            'driver' => $driver,
            'konfigurasi' => $configData,
            'is_active' => true,
        ];

        if (isset($validated['status_koneksi'])) {
            $updateData['status_koneksi'] = $validated['status_koneksi'];
            $updateData['last_tested_at'] = now();
        }
        if (isset($validated['pesan_koneksi'])) {
            $updateData['pesan_koneksi'] = $validated['pesan_koneksi'];
        }

        PengaturanLayanan::updateOrCreate(
            ['kategori' => 'storage'],
            $updateData
        );

        $driverLabel = $driver === 'cloud' ? 'Cloudflare R2 (Cloud)' : 'Penyimpanan Lokal (Server)';
        return back()->with('success', "Konfigurasi Layanan Storage ({$driverLabel}) berhasil disimpan ke database secara global.");
    }

    /**
     * Uji Baca & Tulis Berkas ke Disk Storage (Lokal atau Cloudflare R2)
     */
    public function testStorage(Request $request): JsonResponse
    {
        $driver = $request->input('driver', 'local');

        if ($driver === 'cloud') {
            $accountId = trim((string) $request->input('account_id', ''));
            $accessKeyId = trim((string) $request->input('access_key_id', ''));
            $secretAccessKey = trim((string) $request->input('secret_access_key', ''));
            $bucket = trim((string) $request->input('bucket', ''));
            $region = trim((string) $request->input('region', 'auto')) ?: 'auto';
            $endpoint = trim((string) $request->input('endpoint', ''));
            $publicDomain = trim((string) $request->input('public_domain', ''));

            if (empty($endpoint) && !empty($accountId)) {
                $endpoint = "https://{$accountId}.r2.cloudflarestorage.com";
            }

            if (empty($accessKeyId) || empty($secretAccessKey) || empty($bucket)) {
                return response()->json([
                    'success' => false,
                    'status' => 'gagal',
                    'message' => 'Variabel Access Key ID, Secret Access Key, dan Nama Bucket Cloudflare R2 wajib diisi.',
                ], 422);
            }

            try {
                // Konfigurasi dinamis disk R2
                config([
                    'filesystems.disks.r2_probe' => [
                        'driver' => 's3',
                        'key' => $accessKeyId,
                        'secret' => $secretAccessKey,
                        'region' => $region,
                        'bucket' => $bucket,
                        'url' => $publicDomain ?: null,
                        'endpoint' => $endpoint,
                        'use_path_style_endpoint' => false,
                        'throw' => true,
                    ],
                ]);

                $start = microtime(true);
                $probeFile = 'sipege_r2_probe_' . time() . '.txt';
                $probeContent = 'SIPEGE R2 storage probe test executed at ' . now()->toIso8601String();

                // Put file
                Storage::disk('r2_probe')->put($probeFile, $probeContent);

                // Read file
                $readContent = Storage::disk('r2_probe')->get($probeFile);

                // Delete file
                Storage::disk('r2_probe')->delete($probeFile);

                $duration = round((microtime(true) - $start) * 1000, 2);

                if ($readContent !== $probeContent) {
                    throw new Exception('Data verifikasi berkas R2 tidak cocok.');
                }

                // Simpan status live ke DB
                PengaturanLayanan::updateOrCreate(
                    ['kategori' => 'storage'],
                    [
                        'driver' => 'cloud',
                        'status_koneksi' => 'terhubung',
                        'last_tested_at' => now(),
                        'pesan_koneksi' => "Koneksi Cloudflare R2 berhasil! Operasi tulis, baca, dan hapus berkas selesai dalam {$duration} ms.",
                    ]
                );

                return response()->json([
                    'success' => true,
                    'status' => 'terhubung',
                    'duration_ms' => $duration,
                    'message' => "Koneksi Cloudflare R2 berhasil! Operasi tulis, baca, dan hapus berkas selesai dalam {$duration} ms.",
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ]);
            } catch (\Throwable $e) {
                $errMsg = $e->getMessage();
                Log::warning("Test Cloudflare R2 Error: " . $errMsg);

                PengaturanLayanan::updateOrCreate(
                    ['kategori' => 'storage'],
                    [
                        'driver' => 'cloud',
                        'status_koneksi' => 'gagal',
                        'last_tested_at' => now(),
                        'pesan_koneksi' => "Gagal terhubung ke Cloudflare R2: " . $errMsg,
                    ]
                );

                return response()->json([
                    'success' => false,
                    'status' => 'gagal',
                    'duration_ms' => 0,
                    'message' => "Gagal terhubung ke Cloudflare R2: {$errMsg}",
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ], 500);
            }
        }

        // Default: Pengujian Disk Lokal
        try {
            $start = microtime(true);
            $testFileName = 'healthcheck_local_' . time() . '.tmp';
            $testContent = 'SIPEGE local storage test at ' . now()->toIso8601String();

            Storage::disk('local')->put($testFileName, $testContent);
            $readContent = Storage::disk('local')->get($testFileName);
            Storage::disk('local')->delete($testFileName);

            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($readContent !== $testContent) {
                throw new Exception('Data disk lokal tidak cocok.');
            }

            PengaturanLayanan::updateOrCreate(
                ['kategori' => 'storage'],
                [
                    'driver' => 'local',
                    'status_koneksi' => 'terhubung',
                    'last_tested_at' => now(),
                    'pesan_koneksi' => "Uji operasi baca/tulis disk lokal berhasil dalam {$duration} ms.",
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 'terhubung',
                'duration_ms' => $duration,
                'message' => "Uji operasi baca/tulis disk lokal berhasil dalam {$duration} ms.",
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'duration_ms' => 0,
                'message' => 'Gagal melakukan uji disk lokal: ' . $e->getMessage(),
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ], 500);
        }
    }

    /**
     * Simpan / Perbarui Konfigurasi Mail (Google SMTP vs Simulasi Log)
     */
    public function updateMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', 'string', 'in:smtp,log'],
            'host' => ['nullable', 'string', 'max:150'],
            'port' => ['nullable', 'numeric'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'max:200'],
            'from_address' => ['nullable', 'string', 'max:150'],
            'from_name' => ['nullable', 'string', 'max:150'],
            'status_koneksi' => ['nullable', 'string', 'max:30'],
            'pesan_koneksi' => ['nullable', 'string'],
        ]);

        $driver = $validated['driver'];
        $configData = [
            'host' => trim((string) ($validated['host'] ?? 'smtp.gmail.com')) ?: 'smtp.gmail.com',
            'port' => (int) ($validated['port'] ?? 587),
            'encryption' => trim((string) ($validated['encryption'] ?? 'tls')) ?: 'tls',
            'username' => trim((string) ($validated['username'] ?? '')),
            'password' => trim((string) ($validated['password'] ?? '')),
            'from_address' => trim((string) ($validated['from_address'] ?? '')) ?: trim((string) ($validated['username'] ?? '')),
            'from_name' => trim((string) ($validated['from_name'] ?? 'SIPEGE SPPG')) ?: 'SIPEGE SPPG',
        ];

        $updateData = [
            'driver' => $driver,
            'konfigurasi' => $configData,
            'is_active' => true,
        ];

        if (isset($validated['status_koneksi'])) {
            $updateData['status_koneksi'] = $validated['status_koneksi'];
            $updateData['last_tested_at'] = now();
        }
        if (isset($validated['pesan_koneksi'])) {
            $updateData['pesan_koneksi'] = $validated['pesan_koneksi'];
        }

        PengaturanLayanan::updateOrCreate(
            ['kategori' => 'mail'],
            $updateData
        );

        $driverLabel = $driver === 'smtp' ? 'Google Mail (SMTP)' : 'Simulasi Local Log';
        return back()->with('success', "Konfigurasi Layanan Mail ({$driverLabel}) berhasil disimpan ke database secara global.");
    }

    /**
     * Uji Koneksi Mailer (Autentikasi Google SMTP atau Uji Kirim Email)
     */
    public function testMail(Request $request): JsonResponse
    {
        $driver = $request->input('driver', 'smtp');

        if ($driver === 'smtp') {
            $host = trim((string) $request->input('host', 'smtp.gmail.com')) ?: 'smtp.gmail.com';
            $port = (int) $request->input('port', 587);
            $encryption = trim((string) $request->input('encryption', 'tls')) ?: 'tls';
            $username = trim((string) $request->input('username', ''));
            $password = trim((string) $request->input('password', ''));
            $fromAddress = trim((string) $request->input('from_address', '')) ?: $username;
            $fromName = trim((string) $request->input('from_name', 'SIPEGE SPPG')) ?: 'SIPEGE SPPG';
            $sendRealEmail = (bool) $request->input('send_real_email', false);

            if (empty($username) || empty($password)) {
                return response()->json([
                    'success' => false,
                    'status' => 'gagal',
                    'message' => 'Alamat email Google dan Sandi Aplikasi (Google App Password) wajib diisi.',
                ], 422);
            }

            try {
                $isSsl = ($encryption === 'ssl' || $port === 465);
                $transport = new EsmtpTransport($host, $port, $isSsl);
                $transport->setUsername($username);
                $transport->setPassword($password);

                $start = microtime(true);
                // Handshake & Autentikasi dengan server Google SMTP
                $transport->start();
                $latency = round((microtime(true) - $start) * 1000, 2);

                if ($sendRealEmail && !empty($fromAddress)) {
                    $mailer = new Mailer($transport);
                    $email = (new Email())
                        ->from(new Address($fromAddress, $fromName))
                        ->to(new Address($username))
                        ->subject('Uji Koneksi Layanan Email SIPEGE')
                        ->text("Halo,\n\nEmail ini dikirim secara otomatis untuk menguji konfigurasi Google SMTP pada sistem SIPEGE.\n\nWaktu verifikasi: " . now()->format('d/m/Y H:i:s') . "\nStatus: Autentikasi SMTP Berhasil Terhubung.\n\nSalam,\nSIPEGE SPPG");

                    $mailer->send($email);
                    $transport->stop();

                    $msg = "Autentikasi Google SMTP ({$username}) sukses dan email uji berhasil terkirim ke {$username} (Latency: {$latency} ms)!";
                } else {
                    $transport->stop();
                    $msg = "Autentikasi Google SMTP ({$username}) sukses terhubung ke {$host}:{$port} (Latency: {$latency} ms)!";
                }

                PengaturanLayanan::updateOrCreate(
                    ['kategori' => 'mail'],
                    [
                        'driver' => 'smtp',
                        'status_koneksi' => 'terhubung',
                        'last_tested_at' => now(),
                        'pesan_koneksi' => $msg,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'status' => 'terhubung',
                    'latency_ms' => $latency,
                    'message' => $msg,
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ]);
            } catch (\Throwable $e) {
                $errMsg = $e->getMessage();
                Log::warning("Test Google SMTP Error: " . $errMsg);

                PengaturanLayanan::updateOrCreate(
                    ['kategori' => 'mail'],
                    [
                        'driver' => 'smtp',
                        'status_koneksi' => 'gagal',
                        'last_tested_at' => now(),
                        'pesan_koneksi' => "Gagal terhubung ke Google SMTP: " . $errMsg,
                    ]
                );

                return response()->json([
                    'success' => false,
                    'status' => 'gagal',
                    'latency_ms' => 0,
                    'message' => "Gagal terhubung ke Google SMTP: {$errMsg}",
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ], 500);
            }
        }

        // Mode Log Simulasi
        try {
            Log::info("Uji simulasi mailer log SIPEGE pada " . now());

            PengaturanLayanan::updateOrCreate(
                ['kategori' => 'mail'],
                [
                    'driver' => 'log',
                    'status_koneksi' => 'terhubung',
                    'last_tested_at' => now(),
                    'pesan_koneksi' => "Driver simulasi log aktif. Email dicatat ke storage/logs/laravel.log tanpa pengiriman eksternal.",
                ]
            );

            return response()->json([
                'success' => true,
                'status' => 'terhubung',
                'latency_ms' => 0.5,
                'message' => "Driver email lokal (Log Simulasi) aktif dan siap digunakan untuk pencatatan email.",
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'latency_ms' => 0,
                'message' => 'Gagal menguji mailer log: ' . $e->getMessage(),
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ], 500);
        }
    }

    /**
     * Simpan / Perbarui Pengaturan Kunci API untuk platform tertentu (Tab AI)
     * Bersifat global untuk seluruh sistem.
     */
    public function updateAi(Request $request): RedirectResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : null;
        if (!$unitSppg) {
            $unitSppg = UnitSppg::first();
        }

        $unitId = $unitSppg ? $unitSppg->id : 1;

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

        if ($isActive) {
            PengaturanApi::where('provider', '!=', $provider)
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
                'unit_sppg_id' => $unitId,
                'provider' => $provider,
            ],
            $updateData
        );

        $providerName = AiResiduService::PROVIDERS[$provider]['name'] ?? strtoupper($provider);
        return back()->with('success', "Pengaturan Layanan AI ({$providerName}) berhasil disimpan secara global.");
    }

    /**
     * Uji Koneksi & Ambil Daftar Model secara Realtime dari platform AI
     */
    public function testKoneksiAi(Request $request, AiResiduService $aiService): JsonResponse
    {
        $user = $request->user();
        $unitSppg = $user ? $user->load('unitSppg')->unitSppg : null;
        if (!$unitSppg) {
            $unitSppg = UnitSppg::first();
        }

        $unitId = $unitSppg ? $unitSppg->id : 1;

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

        $result = $aiService->testConnection($provider, $apiKey, $selectedModel);

        try {
            PengaturanApi::updateOrCreate(
                [
                    'unit_sppg_id' => $unitId,
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
            Log::warning("Auto-save AI test connection error: " . $e->getMessage());
        }

        return response()->json($result);
    }

    /**
     * Uji Ping Koneksi Database
     */
    public function testDatabase(): JsonResponse
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            $dbDefault = config('database.default', 'pgsql');
            $version = 'Active';
            if ($dbDefault === 'pgsql') {
                $v = DB::select("SELECT version()");
                $version = $v[0]->version ?? 'PostgreSQL';
            } elseif ($dbDefault === 'mysql') {
                $v = DB::select("SELECT VERSION() as version");
                $version = $v[0]->version ?? 'MySQL';
            }

            return response()->json([
                'success' => true,
                'status' => 'terhubung',
                'latency_ms' => $latency,
                'version' => $version,
                'message' => "Koneksi database {$dbDefault} berhasil dihubungi dengan waktu respon {$latency} ms.",
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'latency_ms' => 0,
                'version' => null,
                'message' => 'Gagal menghubungi database: ' . $e->getMessage(),
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ], 500);
        }
    }
}
