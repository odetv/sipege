<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$workOrders = App\Models\WorkOrder::with(['items', 'purchaseOrder.items'])->get();

foreach ($workOrders as $wo) {
    echo "Updating WO: " . $wo->nomor_wo . "\n";
    $totalAnggaranMaster = 0;

    foreach ($wo->items as $item) {
        $pk = (float) $item->gram_pk;
        $pb = (float) $item->gram_pb;
        $bdd = ((float) ($item->bdd ?: 100)) / 100;
        $buffer = 1 + (((float) ($item->buffer ?: 0)) / 100);
        $harga = (float) $item->harga_master;

        $targetPK = $item->tipe_porsi === 'alergi' ? (int) ($wo->total_alergi_pk ?: 1) : (int) $wo->total_pk;
        $targetPB = $item->tipe_porsi === 'alergi' ? (int) ($wo->total_alergi_pb ?: 1) : (int) $wo->total_pb;

        $grossPK = (($pk / $bdd) * $buffer * $targetPK) / 1000;
        $grossPB = (($pb / $bdd) * $buffer * $targetPB) / 1000;
        $totalGross = $grossPK + $grossPB;

        $subtotal = round($totalGross * $harga);
        if ($totalGross > 0 && $harga > 0 && $subtotal == 0) {
            $subtotal = ceil($totalGross * $harga);
        }

        $item->update([
            'gross_kg_pk' => $grossPK,
            'gross_kg_pb' => $grossPB,
            'total_gross_kg' => $totalGross,
            'subtotal_master' => $subtotal,
        ]);

        $totalAnggaranMaster += $subtotal;
        echo " - Item: " . $item->nama . " -> Gross: " . $totalGross . " kg, Subtotal: Rp " . $subtotal . "\n";
    }

    $wo->update([
        'total_anggaran_master' => $totalAnggaranMaster,
    ]);

    if ($wo->purchaseOrder) {
        $po = $wo->purchaseOrder;
        $totalPoMaster = 0;
        $totalPoAktual = 0;

        foreach ($po->items as $poItem) {
            $woItem = $wo->items->where('id', $poItem->work_order_item_id)->first()
                ?: $wo->items->where('nama', $poItem->nama)->first();

            $grossKg = $woItem ? (float) $woItem->total_gross_kg : (float) $poItem->gross_kg;
            $hargaMaster = $woItem ? (float) $woItem->harga_master : (float) $poItem->harga_master;
            $hargaAktual = (float) ($poItem->harga_aktual ?: $hargaMaster);

            $subtotalAktual = round($grossKg * $hargaAktual);
            if ($grossKg > 0 && $hargaAktual > 0 && $subtotalAktual == 0) {
                $subtotalAktual = ceil($grossKg * $hargaAktual);
            }

            $poItem->update([
                'gross_kg' => $grossKg,
                'harga_master' => $hargaMaster,
                'harga_aktual' => $hargaAktual,
                'subtotal_aktual' => $subtotalAktual,
            ]);

            $totalPoMaster += ($grossKg * $hargaMaster);
            $totalPoAktual += $subtotalAktual;
            echo "   * PO Item: " . $poItem->nama . " -> Gross: " . $grossKg . " kg, Subtotal Aktual: Rp " . $subtotalAktual . "\n";
        }

        $po->update([
            'total_nominal_master' => $totalPoMaster,
            'total_nominal_aktual' => $totalPoAktual,
        ]);
    }
}

echo "Database backfill complete!\n";
