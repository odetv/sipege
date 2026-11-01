<?php

namespace App\Notifications;

use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Pastikan konfigurasi mail runtime sinkron dengan database
        MailConfigService::applyConfig();

        $verificationUrl = $this->verificationUrl($notifiable);
        $templates = MailConfigService::getTemplates();
        $template = $templates['verification'] ?? MailConfigService::getDefaultTemplates()['verification'];

        $variables = [
            'nama' => $notifiable->nama_lengkap ?? ($notifiable->nama ?? $notifiable->name ?? 'Pengguna SIPEGE'),
            'email' => $notifiable->email,
            'link_aksi' => $verificationUrl,
            'link_verifikasi' => $verificationUrl,
            'nama_aplikasi' => 'SIPEGE SPPG',
            'waktu_kedaluwarsa' => '60 menit',
        ];

        $subject = MailConfigService::interpolate($template['subject'] ?? 'Verifikasi Alamat Email Akun SIPEGE', $variables);
        $heading = MailConfigService::interpolate($template['heading'] ?? 'Verifikasi Alamat Email', $variables);
        $subheading = MailConfigService::interpolate($template['subheading'] ?? 'Sistem Pengelolaan SPPG - Badan Gizi Nasional', $variables);
        $greeting = MailConfigService::interpolate($template['greeting'] ?? 'Halo {nama},', $variables);
        $body = MailConfigService::interpolate($template['body'] ?? 'Silakan verifikasi email Anda dengan mengklik tombol di bawah ini:', $variables);
        $buttonText = MailConfigService::interpolate($template['button_text'] ?? 'Verifikasi Email Sekarang', $variables);
        $footerNote = MailConfigService::interpolate($template['footer_note'] ?? 'Tautan verifikasi berlaku selama 60 menit.', $variables);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.auth_notification', [
                'heading' => $heading,
                'subheading' => $subheading,
                'greeting' => $greeting,
                'body' => $body,
                'actionUrl' => $verificationUrl,
                'buttonText' => $buttonText,
                'footerNote' => $footerNote,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}

