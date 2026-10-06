<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$kpms = App\Models\KelompokPenerimaManfaat::where('unit_sppg_id', 1)->get();

$summary = [];
foreach ($kpms as $k) {
    if (is_array($k->keterangan_alergi)) {
        foreach ($k->keterangan_alergi as $item) {
            $jenis = is_string($item) ? $item : ($item['jenis_alergi'] ?? '');
            if (!$jenis) continue;
            $cleanJenis = trim($jenis);
            if (!isset($summary[$cleanJenis])) {
                $summary[$cleanJenis] = ['jenis_alergi' => $cleanJenis, 'porsi_kecil' => 0, 'porsi_besar' => 0, 'total' => 0];
            }
            $pk = (int)($item['porsi_kecil'] ?? 0);
            $pb = (int)($item['porsi_besar'] ?? 0);
            $summary[$cleanJenis]['porsi_kecil'] += $pk;
            $summary[$cleanJenis]['porsi_besar'] += $pb;
            $summary[$cleanJenis]['total'] += ($pk + $pb);
        }
    }
}

echo "Summary Alergi in DB KPM:\n";
foreach ($summary as $j => $s) {
    echo "  $j: Total {$s['total']} (PK: {$s['porsi_kecil']}, PB: {$s['porsi_besar']})\n";
}
