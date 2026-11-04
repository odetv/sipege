<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomVerifyEmailNotification;
use App\Notifications\CustomResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_GUEST = 'guest';
    public const ROLE_MEMBER = 'member';

    public const PAKET_FREE = 'free';
    public const PAKET_BASIC = 'basic';
    public const PAKET_STANDART = 'standart';
    public const PAKET_PRO = 'pro';

    public const SIKLUS_TRIAL_3_HARI = '3_hari';
    public const SIKLUS_BULANAN = 'bulanan';
    public const SIKLUS_TAHUNAN = 'tahunan';

    public const DAFTAR_PAKET = [
        self::PAKET_FREE => [
            'kode' => 'free',
            'nama' => 'Paket Free',
            'badge' => 'Trial 3 Hari',
            'is_free' => true,
            'keterangan' => 'Akses uji coba gratis 3 hari operasional (Bila disetujui admin).',
            'harga_bulanan' => 0,
            'harga_tahunan' => 0,
            'durasi_label' => 'Free 3 Hari (Bila Disetujui)',
        ],
        self::PAKET_BASIC => [
            'kode' => 'basic',
            'nama' => 'Paket Basic',
            'badge' => '100rb / bln',
            'is_free' => false,
            'keterangan' => 'Akses Standar Operasional SPPG, Perencanaan Menu & Distribusi.',
            'harga_bulanan' => 100000,
            'harga_tahunan' => 1000000, // Hemat Rp 200.000
            'durasi_label' => '100rb / bulan',
        ],
        self::PAKET_STANDART => [
            'kode' => 'standart',
            'nama' => 'Paket Standart',
            'badge' => '200rb / bln',
            'is_free' => false,
            'keterangan' => 'Akses Lengkap SPPG, Manajemen Shifting Petugas & Kelompok Penerima Manfaat.',
            'harga_bulanan' => 200000,
            'harga_tahunan' => 2000000, // Hemat Rp 400.000
            'durasi_label' => '200rb / bulan',
        ],
        self::PAKET_PRO => [
            'kode' => 'pro',
            'nama' => 'Paket Pro',
            'badge' => '350rb / bln',
            'is_free' => false,
            'keterangan' => 'Akses Prioritas Unlimited, Analisis AI Residu Makanan, dan Export Dokumen Lengkap.',
            'harga_bulanan' => 350000,
            'harga_tahunan' => 3500000, // Hemat Rp 700.000
            'durasi_label' => '350rb / bulan',
        ],
    ];

    /**
     * Kirim notifikasi verifikasi email dengan template kustom SIPEGE.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmailNotification());
    }

    /**
     * Kirim notifikasi reset password dengan template kustom SIPEGE.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'nama_lengkap',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tgl_pembayaran' => 'datetime',
            'nominal_langganan' => 'decimal:2',
            'tanggal_lahir' => 'date',
            'latitude_domisili' => 'float',
            'longitude_domisili' => 'float',
            'password' => 'hashed',
        ];
    }

    /**
     * Cek apakah pengguna merupakan administrator utama sistem.
     */
    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    /**
     * Cek apakah pengguna memiliki role member (sudah valid pembayaran).
     */
    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * Cek apakah pengguna masih berstatus guest (belum valid pembayaran).
     */
    public function isGuest(): bool
    {
        return $this->role === self::ROLE_GUEST;
    }

    /**
     * Cek apakah status pembayaran paket langganan pengguna sudah valid / lunas.
     */
    public function isPaymentVerified(): bool
    {
        return $this->isAdministrator() || $this->role === self::ROLE_MEMBER || $this->status_pembayaran === 'lunas';
    }

    /**
     * Cek apakah profil pengguna & data unit SPPG sudah dilengkapi (onboarding selesai).
     */
    public function isProfileCompleted(): bool
    {
        return $this->isAdministrator() || $this->status_onboarding === 'selesai';
    }

    /**
     * Accessor format nama lengkap dengan gelar depan dan pemisah koma pada gelar belakang.
     * Contoh: "Dr. Gede Bagler, S.Kom."
     */
    public function getNamaLengkapAttribute(): string
    {
        $nama = trim($this->nama ?? '');
        if (!empty($this->gelar_depan)) {
            $nama = trim($this->gelar_depan) . ' ' . $nama;
        }
        if (!empty($this->gelar_belakang)) {
            $cleanGelar = ltrim(trim($this->gelar_belakang), ', ');
            $nama = $nama . ', ' . $cleanGelar;
        }
        return $nama;
    }

    /**
     * Get the unit SPPG associated with the user (with cache support).
     */
    public function unitSppg(): HasOne
    {
        return $this->hasOne(UnitSppg::class);
    }

    /**
     * Get cached unit SPPG to avoid multiple remote DB round trips within request.
     */
    public function getCachedUnitSppg(): ?UnitSppg
    {
        if ($this->relationLoaded('unitSppg')) {
            return $this->getRelation('unitSppg');
        }
        $unit = $this->unitSppg()->first();
        if ($unit) {
            $this->setRelation('unitSppg', $unit);
        }
        return $unit;
    }
}
