<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Petugas extends Model
{
    use HasFactory;

    protected $table = 'petugas';

    protected $guarded = ['id'];

    protected $appends = ['umur'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'gaji_harian_bgn' => 'integer',
            'bonus_harian_mitra' => 'integer',
            'iuran_bpjs_tk' => 'integer',
        ];
    }

    /**
     * Relasi ke Unit SPPG
     */
    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    /**
     * Accessor untuk menghitung umur petugas berdasarkan tanggal lahir
     */
    protected function umur(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->tanggal_lahir) {
                    return null;
                }

                return Carbon::parse($this->tanggal_lahir)->age;
            }
        );
    }
}
