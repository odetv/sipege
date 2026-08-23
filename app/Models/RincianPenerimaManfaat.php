<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RincianPenerimaManfaat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rincian_penerima_manfaat';

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
            'jumlah_laki_laki' => 'integer',
            'jumlah_perempuan' => 'integer',
            'total' => 'integer',
        ];
    }

    /**
     * Get the kelompok penerima manfaat that owns the rincian.
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokPenerimaManfaat::class, 'kelompok_penerima_manfaat_id');
    }
}
