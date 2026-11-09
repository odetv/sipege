<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiKeuangan extends Model
{
    use HasFactory;

    protected $table = 'transaksi_keuangans';

    protected $fillable = [
        'unit_sppg_id',
        'user_id',
        'nomor_transaksi',
        'tanggal',
        'tanggal_pesanan',
        'kategori',
        'arus',
        'nama_transaksi',
        'volume',
        'satuan',
        'nominal',
        'status',
        'perlu_npo',
        'supplier_id',
        'work_order_id',
        'vendor',
        'keterangan',
        'metode_bayar',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_pesanan' => 'date',
        'volume' => 'float',
        'nominal' => 'float',
        'perlu_npo' => 'boolean',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
