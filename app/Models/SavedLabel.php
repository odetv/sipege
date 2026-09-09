<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedLabel extends Model
{
    use HasFactory;

    protected $table = 'saved_labels';

    protected $fillable = [
        'nomor_label',
        'unit_sppg_id',
        'work_order_id',
        'nama_menu',
        'tanggal_produksi',
        'jam_produksi',
        'batas_konsumsi',
        'petunjuk_menu',
        'template_id',
        'template_name',
        'aspect_ratio',
        'gizi_data',
        'selected_kelompok_ids',
        'kelompoks_snapshot',
        'total_sasaran',
        'total_porsi',
        'total_pk',
        'total_pb',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_produksi' => 'date:Y-m-d',
        'gizi_data' => 'array',
        'selected_kelompok_ids' => 'array',
        'kelompoks_snapshot' => 'array',
        'total_sasaran' => 'integer',
        'total_porsi' => 'integer',
        'total_pk' => 'integer',
        'total_pb' => 'integer',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
