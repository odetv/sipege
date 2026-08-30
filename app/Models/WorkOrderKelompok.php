<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderKelompok extends Model
{
    use HasFactory;

    protected $table = 'work_order_kelompoks';

    protected $guarded = ['id'];

    protected $casts = [
        'is_menerima' => 'boolean',
        'porsi_kecil' => 'integer',
        'porsi_besar' => 'integer',
        'total_penerima' => 'integer',
        'rincian' => 'array',
        'detail_alergi' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KelompokPenerimaManfaat::class, 'kelompok_id');
    }
}
