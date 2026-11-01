<?php

namespace App\Services;

use App\Models\PengaturanLayanan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MailConfigService
{
    /**
     * Default template pesan email (Verifikasi & Reset Password)
     */
    public static function getDefaultTemplates(): array
    {
        return [
            'verification' => [
                'subject' => 'Verifikasi Alamat Email Akun SIPEGE Anda',
                'heading' => 'Verifikasi Alamat Email',
                'subheading' => 'Sistem Pengelolaan SPPG - Badan Gizi Nasional',
                'greeting' => 'Halo {nama},',
                'body' => 'Terima kasih telah mendaftar di SIPEGE. Untuk mengaktifkan akun dan mulai mengakses layanan operasional SPPG, silakan verifikasi alamat email Anda dengan menekan tombol di bawah ini:',
                'button_text' => 'Verifikasi Email Sekarang',
                'footer_note' => 'Tautan verifikasi ini berlaku selama {waktu_kedaluwarsa}. Jika Anda tidak merasa mendaftar di SIPEGE, abaikan email ini dengan aman.',
            ],
            'reset_password' => [
                'subject' => 'Permintaan Atur Ulang Kata Sandi SIPEGE',
                'heading' => 'Atur Ulang Kata Sandi',
                'subheading' => 'Sistem Pengelolaan SPPG - Badan Gizi Nasional',
                'greeting' => 'Halo {nama},',
                'body' => 'Kami menerima permintaan untuk mengatur ulang kata sandi akun SIPEGE Anda. Silakan klik tombol di bawah ini untuk membuat kata sandi baru akun Anda:',
                'button_text' => 'Atur Ulang Kata Sandi',
                'footer_note' => 'Tautan ini akan kedaluwarsa dalam waktu {waktu_kedaluwarsa}. Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini.',
            ],
        ];
    }

    /**
     * Terapkan konfigurasi mail dari database ke sistem Laravel runtime
     */
    public static function applyConfig(): void
    {
        try {
            $pengaturan = PengaturanLayanan::getKategori('mail');
            if (!$pengaturan) {
                return;
            }

            $driver = $pengaturan->driver === 'smtp' ? 'smtp' : 'log';
            $config = (array) ($pengaturan->konfigurasi ?? []);

            $host = trim((string) ($config['host'] ?? 'smtp.gmail.com')) ?: 'smtp.gmail.com';
            $port = (int) ($config['port'] ?? 587);
            $encryption = trim((string) ($config['encryption'] ?? 'tls')) ?: 'tls';
            $username = trim((string) ($config['username'] ?? ''));
            $password = trim((string) ($config['password'] ?? ''));
            $fromAddress = trim((string) ($config['from_address'] ?? '')) ?: $username;
            $fromName = trim((string) ($config['from_name'] ?? 'SIPEGE SPPG')) ?: 'SIPEGE SPPG';

            Config::set('mail.default', $driver);
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.encryption', $encryption);
            Config::set('mail.mailers.smtp.username', $username ?: null);
            Config::set('mail.mailers.smtp.password', $password ?: null);
            Config::set('mail.mailers.smtp.timeout', 30);

            if (!empty($fromAddress)) {
                Config::set('mail.from.address', $fromAddress);
            }
            if (!empty($fromName)) {
                Config::set('mail.from.name', $fromName);
            }

            // Bersihkan cache mail manager agar menggunakan konfigurasi terbaru
            if (app()->bound('mail.manager')) {
                app('mail.manager')->forgetMailers();
            }
        } catch (\Throwable $e) {
            // Hindari exception saat migrasi atau CLI bootstrap
            Log::warning("Gagal memuat konfigurasi mail dinamis: " . $e->getMessage());
        }
    }

    /**
     * Dapatkan konfigurasi template email tersimpan (digabung dengan default)
     */
    public static function getTemplates(): array
    {
        $defaults = self::getDefaultTemplates();
        try {
            $pengaturan = PengaturanLayanan::getKategori('mail');
            $custom = $pengaturan?->konfigurasi['templates'] ?? [];
            return array_replace_recursive($defaults, $custom);
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    /**
     * Render string template dengan menggantikan placeholder
     */
    public static function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}

