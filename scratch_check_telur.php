<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$wo = App\Models\WorkOrder::where('nomor_wo', 'WO-MBG-20261011')->first();
echo "WO: {$wo->nomor_wo}\n";
echo "Total Alergi WO: {$wo->total_alergi}\n";
echo "Sub Menu Alergi:\n" . json_encode($wo->sub_menu_alergi, JSON_PRETTY_PRINT) . "\n";

echo "Kelompoks with Telur:\n";
foreach ($wo->kelompoks as $k) {
    if (!empty($k->detail_alergi)) {
        foreach ($k->detail_alergi as $d) {
            if (stripos($d['jenis_alergi'] ?? '', 'telur') !== false) {
                echo "  {$k->nama_kelompok}: " . json_encode($d) . "\n";
            }
        }
    }
}
