<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KelompokPenerimaManfaat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kelompok_penerima_manfaat';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'jumlah_kader' => 'integer',
            'total_laki_laki' => 'integer',
            'total_perempuan' => 'integer',
            'total_penerima' => 'integer',
            'total_porsi_kecil' => 'integer',
            'total_porsi_besar' => 'integer',
            'alergi_porsi_kecil' => 'integer',
            'alergi_porsi_besar' => 'integer',
            'keterangan_alergi' => 'array',
        ];
    }

    /**
     * Get the unit SPPG that owns the kelompok.
     */
    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class);
    }

    /**
     * Get the rincian penerima manfaat for the kelompok.
     */
    public function rincian(): HasMany
    {
        return $this->hasMany(RincianPenerimaManfaat::class);
    }
}
