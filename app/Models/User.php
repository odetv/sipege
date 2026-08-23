<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'tanggal_lahir' => 'date',
            'latitude_domisili' => 'float',
            'longitude_domisili' => 'float',
            'password' => 'hashed',
        ];
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
     * Get the unit SPPG associated with the user.
     */
    public function unitSppg(): HasOne
    {
        return $this->hasOne(UnitSppg::class);
    }
}
