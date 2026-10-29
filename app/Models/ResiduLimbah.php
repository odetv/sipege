<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResiduLimbah extends Model
{
    use HasFactory;

    protected $table = 'residu_limbahs';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jumlah' => 'float',
    ];

    protected $appends = [
        'tanggal_formatted',
    ];

    /**
     * Format tanggal DD-MM-YYYY sesuai instruksi
     */
    public function getTanggalFormattedAttribute(): string
    {
        return $this->tanggal ? Carbon::parse($this->tanggal)->format('d-m-Y') : '-';
    }

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }
}
