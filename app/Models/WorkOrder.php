<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 'work_orders';

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'tanggal_distribusi' => 'date:Y-m-d',
        'siklus_ke' => 'integer',
        'total_pm' => 'integer',
        'total_pk' => 'integer',
        'total_pb' => 'integer',
        'total_alergi' => 'integer',
        'total_kelompok' => 'integer',
        'akg_pk' => 'array',
        'akg_pb' => 'array',
        'food_cost_pk' => 'float',
        'food_cost_pb' => 'float',
        'total_anggaran_master' => 'float',
        'total_anggaran_aktual' => 'float',
        'diajukan_pada' => 'datetime',
        'disetujui_pada' => 'datetime',
        'ditolak_pada' => 'datetime',
        'riwayat_verifikasi' => 'array',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class, 'work_order_id');
    }

    public function kelompoks(): HasMany
    {
        return $this->hasMany(WorkOrderKelompok::class, 'work_order_id');
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'work_order_id');
    }
}
