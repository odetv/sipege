<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pos = App\Models\PurchaseOrder::with('workOrder')->get();
foreach ($pos as $p) {
    echo "====================================\n";
    echo "PO ID: " . $p->id . " | No: " . $p->nomor_po . " | Status: " . $p->status_po . "\n";
    echo "PO catatan: " . $p->catatan . "\n";
    echo "PO riwayat_verifikasi (" . gettype($p->riwayat_verifikasi) . "):\n";
    print_r($p->riwayat_verifikasi);
    if ($p->workOrder) {
        echo "WO ID: " . $p->workOrder->id . " | No: " . $p->workOrder->nomor_wo . " | Status: " . $p->workOrder->status . "\n";
        echo "WO catatan_keuangan: " . $p->workOrder->catatan_keuangan . "\n";
        echo "WO riwayat_verifikasi (" . gettype($p->workOrder->riwayat_verifikasi) . "):\n";
        print_r($p->workOrder->riwayat_verifikasi);
    }
}
