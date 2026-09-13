<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $guarded = ['id'];

    protected $casts = [
        'komoditas' => 'array',
    ];

    public function unitSppg(): BelongsTo
    {
        return $this->belongsTo(UnitSppg::class, 'unit_sppg_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }
}
