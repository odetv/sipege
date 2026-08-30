<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    use HasFactory;

    protected $table = 'work_order_items';

    protected $guarded = ['id'];

    protected $casts = [
        'gram_pk' => 'float',
        'gram_pb' => 'float',
        'bdd' => 'float',
        'buffer' => 'float',
        'gross_kg_pk' => 'float',
        'gross_kg_pb' => 'float',
        'total_gross_kg' => 'float',
        'harga_master' => 'float',
        'subtotal_master' => 'float',
        'nutrisi_pk' => 'array',
        'nutrisi_pb' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
}
