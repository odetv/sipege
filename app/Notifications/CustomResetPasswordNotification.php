<?php

namespace App\Notifications;

use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

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

        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $templates = MailConfigService::getTemplates();
        $template = $templates['reset_password'] ?? MailConfigService::getDefaultTemplates()['reset_password'];

        $variables = [
            'nama' => $notifiable->nama_lengkap ?? ($notifiable->nama ?? $notifiable->name ?? 'Pengguna SIPEGE'),
            'email' => $notifiable->email,
            'link_aksi' => $resetUrl,
            'link_reset' => $resetUrl,
            'nama_aplikasi' => 'SIPEGE SPPG',
            'waktu_kedaluwarsa' => '60 menit',
        ];

        $subject = MailConfigService::interpolate($template['subject'] ?? 'Permintaan Atur Ulang Kata Sandi SIPEGE', $variables);
        $heading = MailConfigService::interpolate($template['heading'] ?? 'Atur Ulang Kata Sandi', $variables);
        $subheading = MailConfigService::interpolate($template['subheading'] ?? 'Sistem Pengelolaan SPPG - Badan Gizi Nasional', $variables);
        $greeting = MailConfigService::interpolate($template['greeting'] ?? 'Halo {nama},', $variables);
        $body = MailConfigService::interpolate($template['body'] ?? 'Kami menerima permintaan untuk mengatur ulang kata sandi akun SIPEGE Anda. Silakan klik tombol di bawah ini:', $variables);
        $buttonText = MailConfigService::interpolate($template['button_text'] ?? 'Atur Ulang Kata Sandi', $variables);
        $footerNote = MailConfigService::interpolate($template['footer_note'] ?? 'Tautan atur ulang kata sandi ini berlaku selama 60 menit.', $variables);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.auth_notification', [
                'heading' => $heading,
                'subheading' => $subheading,
                'greeting' => $greeting,
                'body' => $body,
                'actionUrl' => $resetUrl,
                'buttonText' => $buttonText,
                'footerNote' => $footerNote,
            ]);
    }
}

