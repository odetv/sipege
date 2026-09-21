<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitSppg extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unit_sppg';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_operasional' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Get the user that owns the unit SPPG.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the kelompok penerima manfaat for the unit SPPG.
     */
    public function kelompokPenerimaManfaat(): HasMany
    {
        return $this->hasMany(KelompokPenerimaManfaat::class);
    }
    /**
     * Get the setting kop dokumen for the unit SPPG.
     */
    public function settingKopDokumen(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SettingKopDokumen::class);
    }
}
