<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_STAF_GIZI = 'staf_gizi';
    public const ROLE_STAF_KEUANGAN = 'staf_keuangan';
    public const ROLE_PETUGAS_KEAMANAN = 'petugas_keamanan';
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
            'keterangan' => 'Akses Lengkap SPPG, Manajemen Petugas & Kelompok Penerima Manfaat.',
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
            'tgl_berakhir_langganan' => 'datetime',
            'nominal_langganan' => 'decimal:2',
            'tanggal_lahir' => 'date',
            'latitude_domisili' => 'float',
            'longitude_domisili' => 'float',
            'password' => 'hashed',
        ];
    }

    /**
     * Cek apakah pengguna merupakan Superadmin SaaS utama.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Cek apakah pengguna merupakan administrator sistem / SPPG.
     */
    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR || $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Cek apakah pengguna merupakan admin tenant SPPG.
     */
    public function isTenantAdmin(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR || $this->isSuperAdmin();
    }

    /**
     * Cek apakah langganan aktif / belum expired.
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->tgl_berakhir_langganan) {
            return now()->lte($this->tgl_berakhir_langganan);
        }

        if (!$this->tgl_pembayaran) {
            return $this->role === self::ROLE_MEMBER || $this->isAdministrator();
        }

        $graceDays = 3;
        if ($this->siklus_langganan === self::SIKLUS_TRIAL_3_HARI) {
            return now()->lte($this->tgl_pembayaran->copy()->addDays(3));
        }

        if ($this->siklus_langganan === self::SIKLUS_BULANAN) {
            return now()->lte($this->tgl_pembayaran->copy()->addMonth()->addDays($graceDays));
        }

        if ($this->siklus_langganan === self::SIKLUS_TAHUNAN) {
            return now()->lte($this->tgl_pembayaran->copy()->addYear()->addDays(7));
        }

        return true;
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
     * Get the unit SPPG associated with the user (sebagai staf atau anggota).
     */
    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    /**
     * Unit SPPG yang dimiliki / dibuat oleh pengguna ini.
     */
    public function ownedUnitSppg(): HasOne
    {
        return $this->hasOne(UnitSppg::class, 'user_id');
    }

    /**
     * Get cached unit SPPG to avoid multiple remote DB round trips within request.
     */
    public function getCachedUnitSppg(): ?UnitSppg
    {
        if ($this->relationLoaded('unitSppg') && $this->getRelation('unitSppg')) {
            return $this->getRelation('unitSppg');
        }

        $unit = null;
        if ($this->unit_sppg_id) {
            $unit = UnitSppg::find($this->unit_sppg_id);
        }
        if (!$unit) {
            $unit = $this->ownedUnitSppg()->first();
        }

        if ($unit) {
            $this->setRelation('unitSppg', $unit);
        }
        return $unit;
    }
}
