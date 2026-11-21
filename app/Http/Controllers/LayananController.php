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
        $unitSppg = $user?->getCachedUnitSppg();

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

        // Ambil konfigurasi tersimpan untuk Lokal & Cloud
        $dbConfigFile = storage_path('app/database_config.json');
        $savedDbConfig = [];
        if (file_exists($dbConfigFile)) {
            $savedDbConfig = json_decode(file_get_contents($dbConfigFile), true) ?: [];
        }

        $currentEnvHost = env('DB_HOST', 'aws-0-ap-southeast-1.pooler.supabase.com');
        $isCurrentLocal = in_array(strtolower($currentEnvHost), ['127.0.0.1', 'localhost', '::1', 'db']);

        $cloudConfig = $savedDbConfig['cloud'] ?? [
            'driver' => env('DB_CONNECTION', 'pgsql'),
            'host' => env('DB_HOST', 'aws-0-ap-southeast-1.pooler.supabase.com'),
            'port' => (int) env('DB_PORT', 6543),
            'database' => env('DB_DATABASE', 'postgres'),
            'username' => env('DB_USERNAME', 'postgres.xgmcjadwhlphedfkoehu'),
            'password' => env('DB_PASSWORD', 'Plenger@BGN!27'),
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ];

        $localConfig = $savedDbConfig['local'] ?? [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'sipege',
            'username' => 'postgres',
            'password' => '',
            'sslmode' => 'prefer',
        ];

        $activeTarget = $savedDbConfig['active_target'] ?? ($isCurrentLocal ? 'local' : 'cloud');

        if (!file_exists($dbConfigFile)) {
            @file_put_contents($dbConfigFile, json_encode([
                'active_target' => $activeTarget,
                'cloud' => $cloudConfig,
                'local' => $localConfig,
            ], JSON_PRETTY_PRINT));
        }

        $driverLabel = $activeTarget === 'cloud'
            ? 'PostgreSQL (Supabase Cloud)'
            : ($dbDefault === 'pgsql' ? 'PostgreSQL (Server Lokal)' : strtoupper($dbDefault) . ' (Server Lokal)');

        $databaseInfo = [
            'driver' => $dbDefault,
            'driver_label' => $driverLabel,
            'host' => $dbConfig['host'] ?? '127.0.0.1',
            'port' => $dbConfig['port'] ?? ($dbDefault === 'pgsql' ? '5432' : '3306'),
            'database' => $dbConfig['database'] ?? '',
            'username_masked' => $maskedUser,
            'status' => $dbStatus,
            'latency_ms' => $dbLatency,
            'version' => $dbVersion,
            'tables_count' => $dbTablesCount,
            'sslmode' => $dbConfig['sslmode'] ?? 'prefer',
            // Konfigurasi 2 target (Lokal & Cloud)
            'active_target' => $activeTarget,
            'cloud' => $cloudConfig,
            'local' => $localConfig,
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
            'templates' => \App\Services\MailConfigService::getTemplates(),
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
            'templates' => ['nullable', 'array'],
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
            'templates' => $request->input('templates', \App\Services\MailConfigService::getDefaultTemplates()),
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

        \App\Services\MailConfigService::applyConfig();

        $driverLabel = $driver === 'smtp' ? 'Google Mail (SMTP)' : 'Simulasi Local Log';
        return back()->with('success', "Konfigurasi Layanan Mail ({$driverLabel}) dan template pesan berhasil disimpan ke database.");
    }

    /**
     * Uji Kirim Email Pratinjau Template (Verifikasi Email atau Reset Password)
     */
    public function testMailTemplate(Request $request): JsonResponse
    {
        $type = $request->input('type', 'verification');
        $targetEmail = trim((string) $request->input('target_email', ''));
        $templateData = (array) $request->input('template', []);

        if (empty($targetEmail)) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email tujuan uji kirim template wajib diisi.',
            ], 422);
        }

        try {
            \App\Services\MailConfigService::applyConfig();

            $variables = [
                'nama' => auth()->user()?->nama_lengkap ?? (auth()->user()?->nama ?? 'Pengguna Uji SIPEGE'),
                'email' => $targetEmail,
                'link_aksi' => url('/'),
                'link_verifikasi' => url('/verify-email/demo-sample-link'),
                'link_reset' => url('/reset-password/demo-token?email=' . urlencode($targetEmail)),
                'nama_aplikasi' => 'SIPEGE SPPG',
                'waktu_kedaluwarsa' => '60 menit',
            ];

            $subject = \App\Services\MailConfigService::interpolate($templateData['subject'] ?? 'Pratinjau Template Email SIPEGE', $variables);
            $heading = \App\Services\MailConfigService::interpolate($templateData['heading'] ?? 'Uji Tampilan Email SIPEGE', $variables);
            $subheading = \App\Services\MailConfigService::interpolate($templateData['subheading'] ?? 'Sistem Pengelolaan SPPG - Badan Gizi Nasional', $variables);
            $greeting = \App\Services\MailConfigService::interpolate($templateData['greeting'] ?? 'Halo {nama},', $variables);
            $body = \App\Services\MailConfigService::interpolate($templateData['body'] ?? 'Ini adalah contoh isi pesan email yang telah dipoles.', $variables);
            $buttonText = \App\Services\MailConfigService::interpolate($templateData['button_text'] ?? 'Tombol Aksi Uji', $variables);
            $footerNote = \App\Services\MailConfigService::interpolate($templateData['footer_note'] ?? 'Catatan keamanan uji.', $variables);

            \Illuminate\Support\Facades\Mail::send('emails.auth_notification', [
                'heading' => $heading,
                'subheading' => $subheading,
                'greeting' => $greeting,
                'body' => $body,
                'actionUrl' => $variables['link_aksi'],
                'buttonText' => $buttonText,
                'footerNote' => $footerNote,
            ], function ($m) use ($targetEmail, $subject) {
                $m->to($targetEmail)->subject('[UJI TEMPLATE] ' . $subject);
            });

            return response()->json([
                'success' => true,
                'message' => "Email uji template ({$type}) berhasil dikirimkan ke {$targetEmail}. Silakan cek kotak masuk email Anda!",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal mengirim email uji template: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Gagal mengirim email: " . $e->getMessage(),
            ], 500);
        }
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
        $unitSppg = $user?->getCachedUnitSppg();
        $unitId = $unitSppg?->id;

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
        $unitSppg = $user?->getCachedUnitSppg();
        $unitId = $unitSppg?->id;

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
     * Simpan & Terapkan Konfigurasi Database (Lokal vs Cloud)
     */
    public function updateDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'active_target' => ['required', 'string', 'in:local,cloud'],
            'cloud' => ['required', 'array'],
            'cloud.driver' => ['required', 'string', 'in:pgsql,mysql,mariadb,sqlite'],
            'cloud.host' => ['required', 'string'],
            'cloud.port' => ['required', 'numeric'],
            'cloud.database' => ['required', 'string'],
            'cloud.username' => ['nullable', 'string'],
            'cloud.password' => ['nullable', 'string'],
            'cloud.sslmode' => ['nullable', 'string'],
            'local' => ['required', 'array'],
            'local.driver' => ['required', 'string', 'in:pgsql,mysql,mariadb,sqlite'],
            'local.host' => ['required', 'string'],
            'local.port' => ['required', 'numeric'],
            'local.database' => ['required', 'string'],
            'local.username' => ['nullable', 'string'],
            'local.password' => ['nullable', 'string'],
            'local.sslmode' => ['nullable', 'string'],
        ]);

        $activeTarget = $validated['active_target'];
        $cloudConfig = $validated['cloud'];
        $localConfig = $validated['local'];

        // 1. Simpan salinan konfigurasi lokal dan cloud ke storage file JSON
        $dbConfigFile = storage_path('app/database_config.json');
        @file_put_contents($dbConfigFile, json_encode([
            'active_target' => $activeTarget,
            'cloud' => $cloudConfig,
            'local' => $localConfig,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        // 2. Simpan juga ke PengaturanLayanan jika koneksi database aktif
        try {
            PengaturanLayanan::updateOrCreate(
                ['kategori' => 'database'],
                [
                    'driver' => $activeTarget,
                    'konfigurasi' => [
                        'active_target' => $activeTarget,
                        'cloud' => $cloudConfig,
                        'local' => $localConfig,
                    ],
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("Gagal menyimpan database config ke tabel pengaturan_layanans: " . $e->getMessage());
        }

        // 3. Terapkan konfigurasi target terpilih langsung ke berkas .env
        $targetConfig = $activeTarget === 'cloud' ? $cloudConfig : $localConfig;
        $this->setEnvValues([
            'DB_CONNECTION' => $targetConfig['driver'],
            'DB_HOST' => $targetConfig['host'],
            'DB_PORT' => (string) $targetConfig['port'],
            'DB_DATABASE' => $targetConfig['database'],
            'DB_USERNAME' => (string) ($targetConfig['username'] ?? ''),
            'DB_PASSWORD' => (string) ($targetConfig['password'] ?? ''),
        ]);

        // 4. Bersihkan cache bootstrap Laravel agar konfigurasi baru segera aktif
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Abaikan jika artisan gagal
        }

        $targetLabel = $activeTarget === 'cloud' ? 'Cloud (Supabase)' : 'Lokal (Server)';
        return back()->with('success', "Konfigurasi Database {$targetLabel} berhasil disimpan dan diterapkan ke sistem.");
    }

    /**
     * Uji Koneksi / Ping Database (Mendukung parameter form langsung atau koneksi default sistem)
     */
    public function testDatabase(Request $request): JsonResponse
    {
        $host = trim((string) $request->input('host', ''));

        // Jika user mengoper parameter form, lakukan live test via PDO
        if (!empty($host)) {
            $driver = $request->input('driver', 'pgsql');
            $port = (int) $request->input('port', $driver === 'pgsql' ? 5432 : 3306);
            $database = trim((string) $request->input('database', 'postgres'));
            $username = trim((string) $request->input('username', ''));
            $password = (string) $request->input('password', '');
            $sslmode = trim((string) $request->input('sslmode', 'prefer'));

            try {
                $start = microtime(true);
                if ($driver === 'pgsql') {
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";
                    $pdo = new \PDO($dsn, $username, $password, [
                        \PDO::ATTR_TIMEOUT => 6,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ]);
                    $latency = round((microtime(true) - $start) * 1000, 2);
                    $v = $pdo->query("SELECT version()")->fetchColumn();
                    $t = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                    $pdo = new \PDO($dsn, $username, $password, [
                        \PDO::ATTR_TIMEOUT => 6,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ]);
                    $latency = round((microtime(true) - $start) * 1000, 2);
                    $v = $pdo->query("SELECT VERSION()")->fetchColumn();
                    $t = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
                } else {
                    throw new Exception("Driver {$driver} belum didukung untuk live test.");
                }

                return response()->json([
                    'success' => true,
                    'status' => 'terhubung',
                    'latency_ms' => $latency,
                    'version' => $v,
                    'tables_count' => (int) $t,
                    'message' => "Koneksi database ({$driver}://{$host}:{$port}) berhasil dihubungi dengan latency {$latency} ms.",
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'gagal',
                    'latency_ms' => 0,
                    'version' => null,
                    'tables_count' => 0,
                    'message' => 'Gagal menghubungi database: ' . $e->getMessage(),
                    'tested_at' => now()->format('d/m/Y H:i:s'),
                ], 500);
            }
        }

        // Fallback: Uji koneksi aktif sistem saat ini
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            $dbDefault = config('database.default', 'pgsql');
            $version = 'Active';
            $tablesCount = 0;
            if ($dbDefault === 'pgsql') {
                $v = DB::select("SELECT version()");
                $version = $v[0]->version ?? 'PostgreSQL';
                $tables = DB::select("SELECT count(*) as total FROM information_schema.tables WHERE table_schema = 'public'");
                $tablesCount = (int) ($tables[0]->total ?? 0);
            } elseif ($dbDefault === 'mysql') {
                $v = DB::select("SELECT VERSION() as version");
                $version = $v[0]->version ?? 'MySQL';
                $tables = DB::select("SELECT count(*) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
                $tablesCount = (int) ($tables[0]->total ?? 0);
            }

            return response()->json([
                'success' => true,
                'status' => 'terhubung',
                'latency_ms' => $latency,
                'version' => $version,
                'tables_count' => $tablesCount,
                'message' => "Koneksi database aktif ({$dbDefault}) berhasil dihubungi dengan latency {$latency} ms.",
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'latency_ms' => 0,
                'version' => null,
                'tables_count' => 0,
                'message' => 'Gagal menghubungi database: ' . $e->getMessage(),
                'tested_at' => now()->format('d/m/Y H:i:s'),
            ], 500);
        }
    }

    /**
     * Tulis / Perbarui pasangan key=value pada berkas .env sistem
     */
    protected function setEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '$') || str_contains($value, '"'))
                ? '"' . addcslashes($value, '"\\$') . '"'
                : $value;

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escaped}";
            }
        }

        @file_put_contents($envPath, $envContent);
    }
}
